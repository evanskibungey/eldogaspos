<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->boolean('is_offline_sync')->default(false)->after('notes');
            $table->string('offline_receipt_number')->nullable()->after('is_offline_sync');
            $table->timestamp('offline_created_at')->nullable()->after('offline_receipt_number');
            
            $table->index(['is_offline_sync', 'created_at']);
            $table->index('offline_receipt_number');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['is_offline_sync', 'created_at']);
            $table->dropIndex(['offline_receipt_number']);
            $table->dropColumn(['is_offline_sync', 'offline_receipt_number', 'offline_created_at']);
        });
    }
};