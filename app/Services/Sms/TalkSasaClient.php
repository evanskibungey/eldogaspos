<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin HTTP client for the TalkSasa (Ultimate SMS) v3 API.
 *
 * Deliberately dumb: it speaks HTTP and nothing else. Deciding whether to send,
 * what to say, and what to record is SmsService's job. Guzzle already ships with
 * the framework, so no gateway SDK is needed.
 */
class TalkSasaClient
{
    /**
     * Send one message to one or many recipients.
     *
     * @param string|string[] $recipients Gateway-formatted msisdn(s).
     */
    public function send($recipients, string $message, ?string $senderId = null, ?string $scheduleAt = null): SmsResponse
    {
        $payload = [
            'recipient' => $recipients,
            'sender_id' => $senderId ?: config('services.talksasa.sender_id'),
            'type' => 'plain',
            'message' => $message,
        ];

        if ($scheduleAt !== null) {
            $payload['schedule_time'] = $scheduleAt;
        }

        return $this->post(config('services.talksasa.endpoints.send'), $payload);
    }

    /**
     * Current credit balance, or null when the endpoint path is wrong for this
     * account. Balance is informational, so a failure here is never fatal.
     */
    public function balance(): ?array
    {
        $response = $this->get(config('services.talksasa.endpoints.balance'));

        return $response->successful ? $response->raw : null;
    }

    public function messageStatus(string $uid): SmsResponse
    {
        $path = str_replace('{uid}', $uid, config('services.talksasa.endpoints.show'));

        return $this->get($path);
    }

    private function post(string $path, array $payload): SmsResponse
    {
        try {
            $response = $this->request()->post($this->url($path), $payload);
        } catch (\Throwable $e) {
            // Timeouts and DNS failures must not surface as a 500 during a sale.
            Log::error('TalkSasa request failed', ['path' => $path, 'error' => $e->getMessage()]);

            return SmsResponse::failure('Gateway unreachable: ' . $e->getMessage());
        }

        return $this->interpret($response->status(), $response->json() ?? [], $response->body());
    }

    private function get(string $path): SmsResponse
    {
        try {
            $response = $this->request()->get($this->url($path));
        } catch (\Throwable $e) {
            Log::error('TalkSasa request failed', ['path' => $path, 'error' => $e->getMessage()]);

            return SmsResponse::failure('Gateway unreachable: ' . $e->getMessage());
        }

        return $this->interpret($response->status(), $response->json() ?? [], $response->body());
    }

    private function request()
    {
        return Http::withToken(config('services.talksasa.token'))
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('services.talksasa.timeout', 15));
    }

    private function url(string $path): string
    {
        return rtrim(config('services.talksasa.base_url'), '/') . '/' . ltrim($path, '/');
    }

    /**
     * Map the gateway envelope onto SmsResponse.
     *
     * The platform reports application errors inside a 200 body as
     * {"status":"error","message":"..."}, so the HTTP code alone is not enough
     * to decide success.
     */
    private function interpret(int $status, array $body, string $rawBody): SmsResponse
    {
        if ($status < 200 || $status >= 300) {
            $message = $body['message'] ?? ($rawBody !== '' ? $rawBody : 'HTTP ' . $status);

            return SmsResponse::failure((string) $message, $body, $status);
        }

        $envelope = strtolower((string) ($body['status'] ?? 'success'));

        if (in_array($envelope, ['error', 'fail', 'failed'], true)) {
            return SmsResponse::failure((string) ($body['message'] ?? 'Gateway reported an error'), $body, $status);
        }

        return SmsResponse::success($this->extractUid($body), $body, $status);
    }

    /**
     * The message identifier is nested inconsistently across this platform's
     * responses, so check the shapes it is known to use before giving up.
     */
    private function extractUid(array $body): ?string
    {
        $candidates = [
            // What TalkSasa actually returns, confirmed against a live send on
            // 2026-09-12: {"data":{"queue_uid":"f7ac...","status":"accepted"}}.
            // It is the id in the check_status_url, so it is what a delivery
            // lookup needs. The rest are kept as fallbacks for other installs
            // of this platform.
            $body['data']['queue_uid'] ?? null,
            $body['data']['uid'] ?? null,
            $body['data']['message_id'] ?? null,
            $body['data']['id'] ?? null,
            $body['uid'] ?? null,
            $body['message_id'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (is_scalar($candidate) && (string) $candidate !== '') {
                return (string) $candidate;
            }
        }

        return null;
    }
}
