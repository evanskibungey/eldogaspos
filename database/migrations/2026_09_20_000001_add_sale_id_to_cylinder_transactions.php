<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Links a completed cylinder transaction to the sale it produced.
     *
     * Completing a refill takes money and moves stock, but wrote no `sales`
     * row - so roughly half the shop's revenue was invisible to the POS sales
     * badge, the admin Sales Overview and every cashier report, all of which
     * count `sales`. The two halves of the business were counted in two places
     * that never met.
     *
     * Nullable because every transaction taken before this has no sale, and
     * because an active or cancelled transaction never gets one. The column is
     * also what makes recording idempotent: a transaction that already points
     * at a sale must never be given a second one.
     */
    public function up(): void
    {
        Schema::table('cylinder_transactions', function (Blueprint $table) {
            $table->foreignId('sale_id')
                ->nullable()
                ->after('order_number')
                ->constrained('sales')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cylinder_transactions', function (Blueprint $table) {
            $table->dropForeign(['sale_id']);
            $table->dropColumn('sale_id');
        });
    }
};
