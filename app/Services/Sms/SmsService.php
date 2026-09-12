<?php

namespace App\Services\Sms;

use App\Jobs\SendSmsJob;
use App\Models\SmsLog;
use Illuminate\Support\Facades\Log;

/**
 * Entry point for everything that sends SMS.
 *
 * Responsibilities are split deliberately: this class decides whether a message
 * may be sent and records the attempt, TalkSasaClient performs the HTTP call,
 * and SendSmsJob keeps that call off the request thread. Callers only touch
 * this class.
 */
class SmsService
{
    private TalkSasaClient $client;

    public function __construct(TalkSasaClient $client)
    {
        $this->client = $client;
    }

    /**
     * Record a message and hand it to the queue.
     *
     * Returns the log row, or null when the message was suppressed (SMS
     * disabled, or the number is not sendable). Suppression is normal — most
     * POS sales are walk-ins with no real phone number — so it is not an error.
     */
    public function queue(
        ?string $rawPhone,
        string $message,
        string $purpose,
        array $context = []
    ): ?SmsLog {
        if (!$this->enabled()) {
            return null;
        }

        $recipient = PhoneNumber::normalise($rawPhone);

        if ($recipient === null) {
            // Walk-in placeholder numbers land here on nearly every cash sale,
            // so this is debug rather than warning to keep the log readable.
            Log::debug('SMS skipped - unsendable number', ['purpose' => $purpose, 'phone' => $rawPhone]);

            return null;
        }

        $smsLog = SmsLog::create([
            'recipient' => $recipient,
            'message' => $message,
            'purpose' => $purpose,
            'reference_type' => $context['reference_type'] ?? null,
            'reference_id' => $context['reference_id'] ?? null,
            'customer_id' => $context['customer_id'] ?? null,
            'user_id' => $context['user_id'] ?? auth()->id(),
            'status' => SmsLog::STATUS_QUEUED,
        ]);

        SendSmsJob::dispatch($smsLog->id);

        return $smsLog;
    }

    /**
     * Perform the send for an already-logged message and record the outcome.
     * Called from the queue worker, never from a web request.
     */
    public function deliver(SmsLog $smsLog): SmsResponse
    {
        if (config('services.talksasa.driver') === 'log') {
            Log::info('SMS (log driver, not sent)', [
                'to' => $smsLog->recipient,
                'message' => $smsLog->message,
            ]);

            $smsLog->update([
                'status' => SmsLog::STATUS_SENT,
                'gateway_uid' => 'log-' . $smsLog->id,
                'sent_at' => now(),
            ]);

            return SmsResponse::success('log-' . $smsLog->id);
        }

        $recipient = config('services.talksasa.plus_prefix')
            ? '+' . $smsLog->recipient
            : $smsLog->recipient;

        $response = $this->client->send(
            $recipient,
            $smsLog->message,
            setting('sms_sender_id', config('services.talksasa.sender_id'))
        );

        $smsLog->update([
            'status' => $response->successful ? SmsLog::STATUS_SENT : SmsLog::STATUS_FAILED,
            'gateway_uid' => $response->uid,
            'error' => $response->message,
            'response' => $response->raw,
            'sent_at' => $response->successful ? now() : null,
        ]);

        return $response;
    }

    /**
     * Send one message to many recipients, for campaigns.
     *
     * Each recipient gets its own log row so failures and costs stay
     * attributable per customer, rather than collapsing into one bulk record.
     *
     * @param iterable $customers Models exposing `phone` and `id`.
     * @return int Number of messages queued.
     */
    public function queueCampaign(iterable $customers, string $message, array $context = []): int
    {
        $queued = 0;

        foreach ($customers as $customer) {
            $log = $this->queue(
                $customer->phone,
                $message,
                SmsLog::PURPOSE_CAMPAIGN,
                array_merge($context, ['customer_id' => $customer->id])
            );

            if ($log !== null) {
                $queued++;
            }
        }

        return $queued;
    }

    /**
     * Whether sending is switched on. The env driver is the hard gate; the
     * setting is the switch an admin can reach.
     */
    public function enabled(): bool
    {
        if (config('services.talksasa.driver') === 'log') {
            // The log driver is always "enabled" — it is how the pipeline is
            // exercised locally without spending credits.
            return true;
        }

        if (empty(config('services.talksasa.token'))) {
            Log::warning('SMS enabled but TALKSASA_TOKEN is not set; messages suppressed');

            return false;
        }

        return (bool) setting('sms_enabled', false);
    }

    /**
     * How many messages the gateway will bill for this text.
     *
     * A single SMS holds 160 GSM-7 characters, or 70 when the text contains
     * anything outside GSM-7. Once it no longer fits, the message is split and
     * every part carries a concatenation header that eats into the payload,
     * dropping the per-part capacity to 153 (67 for unicode) - so a 161
     * character message is billed as two parts of 153, not two of 160.
     */
    public static function segments(string $message): int
    {
        $isGsm = (bool) preg_match('/^[\x20-\x7E\n\r]*$/', $message);

        $single = $isGsm ? 160 : 70;
        $concatenated = $isGsm ? 153 : 67;

        $length = mb_strlen($message);

        if ($length <= $single) {
            return 1;
        }

        return (int) ceil($length / $concatenated);
    }
}
