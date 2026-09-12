<?php

namespace App\Jobs;

use App\Models\SmsLog;
use App\Services\Sms\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Delivers one logged message via the gateway.
 *
 * Takes the log id rather than the model so a queued job cannot resurrect a
 * stale copy of the row, and so the payload stays small.
 */
class SendSmsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /** Back off between attempts so a brief gateway outage is ridden out. */
    public array $backoff = [10, 60, 300];

    private int $smsLogId;

    public function __construct(int $smsLogId)
    {
        $this->smsLogId = $smsLogId;
    }

    public function handle(SmsService $sms): void
    {
        $smsLog = SmsLog::find($this->smsLogId);

        if ($smsLog === null) {
            return;
        }

        // A retry of an already-delivered message would be billed again.
        if ($smsLog->status === SmsLog::STATUS_SENT) {
            return;
        }

        $response = $sms->deliver($smsLog);

        if ($response->failed()) {
            // Throwing hands the job back to the queue for the next attempt;
            // the log row already records why this one failed.
            throw new \RuntimeException('SMS delivery failed: ' . $response->message);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SMS job exhausted retries', [
            'sms_log_id' => $this->smsLogId,
            'error' => $e->getMessage(),
        ]);

        SmsLog::where('id', $this->smsLogId)->update([
            'status' => SmsLog::STATUS_FAILED,
            'error' => $e->getMessage(),
        ]);
    }
}
