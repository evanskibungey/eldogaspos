<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Insert default settings
        $this->seedDefaultSettings();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }

    /**
     * Seed default settings.
     */
    private function seedDefaultSettings()
    {
        $settings = [
            'company_name' => 'EldoGas POS',
            'company_email' => 'info@eldogas.com',
            'company_phone' => '0700123456',
            'company_address' => 'Eldoret, Kenya',
            'currency_symbol' => 'KSh',
            'tax_percentage' => '16',
            'low_stock_threshold' => '10',
            'receipt_footer' => 'Thank you for your business!',
            'enable_stock_alerts' => '1',
            'enable_credit_sales' => '1',
            'enable_receipt_printing' => '1',
            'require_serial_number' => '1',
        ];

        $table = DB::table('settings');
        
        foreach ($settings as $key => $value) {
            $table->insert([
                'key' => $key,
                'value' => $value,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};