<?php

namespace Tests\Feature\Sms;

use App\Services\Sms\TalkSasaClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The gateway's exact response envelope is not published, so these tests pin
 * down how the client interprets the shapes it is known to return. If a live
 * send ever behaves differently, correct the interpretation here first.
 */
class TalkSasaClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.talksasa.base_url' => 'https://bulksms.talksasa.com/api/v3',
            'services.talksasa.token' => 'test-token',
            'services.talksasa.sender_id' => 'ELDOGAS',
            'services.talksasa.endpoints.send' => 'sms/send',
        ]);
    }

    public function test_it_posts_to_the_send_endpoint_with_a_bearer_token(): void
    {
        Http::fake([
            '*' => Http::response(['status' => 'success', 'data' => ['uid' => 'abc123']], 200),
        ]);

        $response = (new TalkSasaClient())->send('254712345678', 'Hello');

        $this->assertTrue($response->successful);
        $this->assertSame('abc123', $response->uid);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://bulksms.talksasa.com/api/v3/sms/send'
                && $request->method() === 'POST'
                && $request->hasHeader('Authorization', 'Bearer test-token')
                && $request['recipient'] === '254712345678'
                && $request['sender_id'] === 'ELDOGAS'
                && $request['type'] === 'plain'
                && $request['message'] === 'Hello';
        });
    }

    /**
     * The exact body TalkSasa returned on a live send, 2026-09-12. It answers
     * 202 rather than 200, and names the reference `queue_uid` - the id that
     * appears in its own check_status_url. Nothing else in the response is a
     * message id, so without this the reference is lost.
     */
    public function test_it_reads_the_reference_from_a_real_talksasa_response(): void
    {
        Http::fake([
            '*' => Http::response([
                'status' => 'success',
                'message' => 'Your SMS is being processed and will be delivered',
                'data' => [
                    'queue_uid' => 'f7ac7f97-4276-43b8-b5cc-2a80d95cf61d',
                    'status' => 'accepted',
                    'recipients_count' => 1,
                    'sms_count' => 1,
                    'estimated_cost' => 1,
                    'check_status_url' => 'https://bulksms.talksasa.com/api/v3/sms/queue/f7ac7f97-4276-43b8-b5cc-2a80d95cf61d',
                ],
            ], 202),
        ]);

        $response = (new TalkSasaClient())->send('254796486683', 'Hello');

        $this->assertTrue($response->successful, 'A 202 with status=success is a successful send.');
        $this->assertSame('f7ac7f97-4276-43b8-b5cc-2a80d95cf61d', $response->uid);
        $this->assertSame(202, $response->statusCode);
    }

    public function test_it_sends_an_array_of_recipients_for_bulk(): void
    {
        Http::fake(['*' => Http::response(['status' => 'success'], 200)]);

        (new TalkSasaClient())->send(['254712345678', '254722345678'], 'Hello');

        Http::assertSent(function ($request) {
            return $request['recipient'] === ['254712345678', '254722345678'];
        });
    }

    /**
     * This platform reports application errors inside a 200 response, so the
     * HTTP status alone must not be treated as success.
     */
    public function test_it_treats_an_error_envelope_in_a_200_as_a_failure(): void
    {
        Http::fake([
            '*' => Http::response(['status' => 'error', 'message' => 'Insufficient balance'], 200),
        ]);

        $response = (new TalkSasaClient())->send('254712345678', 'Hello');

        $this->assertTrue($response->failed());
        $this->assertSame('Insufficient balance', $response->message);
    }

    /**
     * Verbatim from the live gateway: it answers HTTP 200 even when the token
     * is rejected, so treating 2xx as success would silently swallow every
     * auth failure and report messages as sent.
     */
    public function test_an_unauthenticated_error_inside_a_200_is_a_failure(): void
    {
        Http::fake([
            '*' => Http::response(['status' => 'error', 'message' => 'Unauthenticated.'], 200),
        ]);

        $response = (new TalkSasaClient())->send('254712345678', 'Hello');

        $this->assertTrue($response->failed());
        $this->assertSame('Unauthenticated.', $response->message);
    }

    /**
     * Also observed live: an unknown path returns 200 with this body rather
     * than a 404, which is how a mistyped endpoint would otherwise look fine.
     */
    public function test_an_unknown_route_inside_a_200_is_a_failure(): void
    {
        Http::fake([
            '*' => Http::response([
                'status' => 'error',
                'message' => 'The route api/v3/wrong could not be found.',
            ], 200),
        ]);

        $response = (new TalkSasaClient())->send('254712345678', 'Hello');

        $this->assertTrue($response->failed());
        $this->assertStringContainsString('could not be found', (string) $response->message);
    }

    public function test_it_reports_http_errors(): void
    {
        Http::fake([
            '*' => Http::response(['message' => 'Unauthenticated.'], 401),
        ]);

        $response = (new TalkSasaClient())->send('254712345678', 'Hello');

        $this->assertTrue($response->failed());
        $this->assertSame('Unauthenticated.', $response->message);
        $this->assertSame(401, $response->statusCode);
    }

    /**
     * A gateway timeout must come back as a failed response, never as an
     * exception that could bubble up into a checkout request.
     */
    public function test_a_connection_failure_returns_a_failed_response(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('Connection timed out');
        });

        $response = (new TalkSasaClient())->send('254712345678', 'Hello');

        $this->assertTrue($response->failed());
        $this->assertStringContainsString('Gateway unreachable', (string) $response->message);
    }

    public function test_it_includes_schedule_time_only_when_given(): void
    {
        Http::fake(['*' => Http::response(['status' => 'success'], 200)]);

        $client = new TalkSasaClient();
        $client->send('254712345678', 'Later', null, '2026-09-10 14:30:00');

        Http::assertSent(function ($request) {
            return $request['schedule_time'] === '2026-09-10 14:30:00';
        });

        $client->send('254712345678', 'Now');

        Http::assertSent(function ($request) {
            return $request['message'] === 'Now' && !isset($request['schedule_time']);
        });
    }
}
