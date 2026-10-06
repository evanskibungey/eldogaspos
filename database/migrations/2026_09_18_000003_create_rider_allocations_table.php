<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cylinders booked out to a rider for delivery.
     *
     * Shaped after cylinder_transactions, because the movement is the same one:
     * gas leaves the yard now and the empties come back later. The difference
     * is only who holds it in between - a rider rather than a customer.
     *
     * Money is recorded on completion, not on allocation. Cylinders on a bike
     * are not revenue: they may come back unsold. `sale_id` is the link to the
     * Sale created at that point, so the allocation and the money it produced
     * can always be tied together.
     */
    public function up(): void
    {
        Schema::create('rider_allocations', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number')->unique();

            // Sent with the pick-up request so a double-click, a retry, or an
            // impatient second tap on a rider's name cannot book the same
            // cylinders out twice. Unique: the database enforces it, not us.
            $table->string('idempotency_key', 64)->nullable()->unique();

            $table->foreignId('rider_id')->constrained('riders')->onDelete('restrict');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');

            // Set when the allocation is completed and the sale is written.
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();

            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');

            // Mirrors cylinder_transactions: reserved while the rider holds the
            // cylinders, committed once delivered, released if it is cancelled.
            // This is what stops stock being deducted or returned twice.
            $table->enum('stock_status', ['reserved', 'committed', 'released'])->default('reserved');

            $table->decimal('total_amount', 10, 2)->default(0);

            $table->timestamp('allocated_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // When the "order completed" SMS was sent. Its presence is what
            // prevents a second completion message if the action is repeated.
            $table->timestamp('completion_notified_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['rider_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rider_allocations');
    }
};
