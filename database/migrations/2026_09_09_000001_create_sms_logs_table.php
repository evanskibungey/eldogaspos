<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Every outbound message is recorded here.
     *
     * SMS costs money per message and the gateway is the only other record of
     * what was sent, so a local log is what makes billing disputes, delivery
     * chasing, and "did the customer actually get their receipt?" answerable.
     */
    public function up(): void
    {
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();

            $table->string('recipient', 20);
            $table->text('message');

            // What prompted the send: 'sale_receipt', 'cylinder_receipt',
            // 'campaign', etc. Kept as a string so new triggers do not need a
            // migration to widen an enum.
            $table->string('purpose', 40)->index();

            // Nullable polymorphic-ish link back to the sale or transaction,
            // without an enforced relation so deleting a sale never blocks on
            // its message history.
            $table->string('reference_type', 40)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();

            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->enum('status', ['queued', 'sent', 'failed'])->default('queued');
            $table->string('gateway_uid')->nullable();
            $table->text('error')->nullable();
            $table->json('response')->nullable();
            $table->timestamp('sent_at')->nullable();

            $table->timestamps();

            $table->index(['reference_type', 'reference_id']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
    }
};
