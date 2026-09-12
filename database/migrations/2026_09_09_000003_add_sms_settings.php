<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * SMS toggles live in the existing settings table so an admin can turn
     * receipts off without a redeploy — credentials stay in .env, only
     * behaviour is configurable here.
     */
    private array $settings = [
        'sms_enabled' => '0',
        'sms_send_sale_receipts' => '1',
        'sms_send_cylinder_receipts' => '1',
        'sms_sender_id' => 'ELDOGAS',
    ];

    public function up(): void
    {
        foreach ($this->settings as $key => $value) {
            // Guard against re-runs and against production having been edited
            // directly, which would otherwise collide with the unique key.
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
