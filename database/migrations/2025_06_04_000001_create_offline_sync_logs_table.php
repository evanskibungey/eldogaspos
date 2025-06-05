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
        Schema::create('offline_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('offline_receipt_number')->index();
            $table->string('server_receipt_number')->nullable();
            $table->foreignId('sale_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('sync_status', ['pending', 'synced', 'failed'])->default('pending');
            $table->json('original_data'); // Store the original offline data
            $table->text('error_message')->nullable();
            $table->integer('sync_attempts')->default(0);
            $table->timestamp('offline_created_at');
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            
            $table->index(['sync_status', 'created_at']);
            $table->index(['offline_created_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('offline_sync_logs');
    }
};