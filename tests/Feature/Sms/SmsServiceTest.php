<?php

namespace Tests\Feature\Sms;

use App\Jobs\SendSmsJob;
use App\Models\Customer;
use App\Models\SmsLog;
use App\Services\Sms\PhoneNumber;
use App\Services\Sms\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SmsServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): SmsService
    {
        return app(SmsService::class);
    }

    public function test_it_logs_and_queues_a_sendable_message(): void
    {
        Queue::fake();

        $log = $this->service()->queue('0712345678', 'Hello', SmsLog::PURPOSE_SALE_RECEIPT);

        $this->assertNotNull($log);
        $this->assertSame('254712345678', $log->recipient);
        $this->assertSame(SmsLog::STATUS_QUEUED, $log->status);

        Queue::assertPushed(SendSmsJob::class);
    }

    /**
     * Most cash sales are walk-ins. Suppressing them must be silent and must
     * not create a log row, or the history fills with messages never sent.
     */
    public function test_it_suppresses_the_walk_in_placeholder_without_logging(): void
    {
        Queue::fake();

        $log = $this->service()->queue(PhoneNumber::WALK_IN, 'Hello', SmsLog::PURPOSE_SALE_RECEIPT);

        $this->assertNull($log);
        $this->assertSame(0, SmsLog::count());
        Queue::assertNothingPushed();
    }

    public function test_it_suppresses_unparseable_numbers(): void
    {
        Queue::fake();

        $this->assertNull($this->service()->queue('not-a-number', 'Hello', SmsLog::PURPOSE_CAMPAIGN));
        $this->assertNull($this->service()->queue(null, 'Hello', SmsLog::PURPOSE_CAMPAIGN));

        $this->assertSame(0, SmsLog::count());
        Queue::assertNothingPushed();
    }

    public function test_a_campaign_creates_one_log_row_per_recipient(): void
    {
        Queue::fake();

        $customers = collect([
            Customer::create(['name' => 'A', 'phone' => '0712345678', 'status' => 'active']),
            Customer::create(['name' => 'B', 'phone' => '0722345678', 'status' => 'active']),
            // Not sendable - must be skipped rather than counted.
            Customer::create(['name' => 'C', 'phone' => '0000000000', 'status' => 'active']),
        ]);

        $queued = $this->service()->queueCampaign($customers, 'Promo!');

        $this->assertSame(2, $queued);
        $this->assertSame(2, SmsLog::campaigns()->count());
        Queue::assertPushed(SendSmsJob::class, 2);
    }

    /**
     * The log driver must mark messages sent without touching the network, so
     * the whole pipeline can be exercised locally without spending credits.
     */
    public function test_the_log_driver_marks_messages_sent_without_calling_the_gateway(): void
    {
        config(['services.talksasa.driver' => 'log']);

        $log = SmsLog::create([
            'recipient' => '254712345678',
            'message' => 'Hello',
            'purpose' => SmsLog::PURPOSE_CAMPAIGN,
            'status' => SmsLog::STATUS_QUEUED,
        ]);

        $response = $this->service()->deliver($log);

        $this->assertTrue($response->successful);
        $this->assertSame(SmsLog::STATUS_SENT, $log->fresh()->status);
        $this->assertNotNull($log->fresh()->sent_at);
    }

    /**
     * A retried job must not re-send an already-delivered message, which the
     * gateway would bill a second time.
     */
    public function test_the_job_skips_a_message_already_marked_sent(): void
    {
        $log = SmsLog::create([
            'recipient' => '254712345678',
            'message' => 'Hello',
            'purpose' => SmsLog::PURPOSE_CAMPAIGN,
            'status' => SmsLog::STATUS_SENT,
            'sent_at' => now(),
            'gateway_uid' => 'already-sent',
        ]);

        (new SendSmsJob($log->id))->handle($this->service());

        $this->assertSame('already-sent', $log->fresh()->gateway_uid);
    }

    public function test_sending_is_suppressed_when_the_token_is_missing(): void
    {
        Queue::fake();

        config([
            'services.talksasa.driver' => 'talksasa',
            'services.talksasa.token' => null,
        ]);

        $this->assertFalse($this->service()->enabled());
        $this->assertNull($this->service()->queue('0712345678', 'Hello', SmsLog::PURPOSE_CAMPAIGN));

        Queue::assertNothingPushed();
    }
}
