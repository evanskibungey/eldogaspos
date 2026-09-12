<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The customer app link promoted in the thank-you and payment messages.
     *
     * Kept as a setting rather than hard-coded: it is marketing copy that will
     * outlive this release, and store URLs change.
     *
     * Length is billable. The `pcampaignid=web_share` parameter costs 22
     * characters, which is free on the payment message (already two segments)
     * but takes the thank-you from 148 to 170 characters - one segment to two,
     * doubling the cost of the highest-volume message the business sends.
     * Trimming it back to
     *   https://play.google.com/store/apps/details?id=co.ke.eldogas.customer
     * restores the single segment without changing where the link points.
     */
    private array $settings = [
        'sms_app_link' => 'https://play.google.com/store/apps/details?id=co.ke.eldogas.customer&pcampaignid=web_share',
        'sms_send_thank_you' => '1',
    ];

    public function up(): void
    {
        foreach ($this->settings as $key => $value) {
            if (DB::table('settings')->where('key', $key)->exists()) {
                continue;
            }

            DB::table('settings')->insert([
                'key' => $key,
                'value' => $value,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', array_keys($this->settings))->delete();
    }
};
