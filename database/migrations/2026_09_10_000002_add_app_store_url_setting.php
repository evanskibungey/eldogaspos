<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Destination for the short /app redirect.
     *
     * The Play Store URL is 90 characters, which is most of a billed SMS. A
     * redirect on our own domain carries the same customer to the same place
     * in about 25, and unlike a third-party shortener it cannot be taken away:
     * Google's own goo.gl service was shut down in 2025, taking every link
     * printed against it. It also means the destination can change - to an iOS
     * listing, or a chooser page - without editing message copy.
     */
    private array $settings = [
        'app_store_url' => 'https://play.google.com/store/apps/details?id=co.ke.eldogas.customer&pcampaignid=web_share',
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
