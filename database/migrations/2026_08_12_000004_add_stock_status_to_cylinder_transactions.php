<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Makes a cylinder transaction's effect on stock explicit rather than
     * inferred from status + type.
     *
     *   reserved   units held for an open drop-off, not yet handed over
     *   committed  units physically deducted (collected, or taken in advance)
     *   released   reservation dropped or deduction reversed
     *
     * Every stock transition guards on this column, which is what stops a
     * transaction being deducted twice - once at creation and again at
     * collection - regardless of how it was created or how often complete()
     * is called.
     */
    public function up(): void
    {
        Schema::table('cylinder_transactions', function (Blueprint $table) {
            $table->enum('stock_status', ['reserved', 'committed', 'released'])
                ->nullable()
                ->after('status');
            $table->timestamp('stock_committed_at')->nullable()->after('stock_status');
            $table->index('stock_status', 'idx_cylinder_stock_status');
        });

        // Transactions created before this change deducted stock at creation.
        // Mark them committed so completing one does not deduct a second time.
        DB::table('cylinder_transactions')
            ->whereNull('stock_status')
            ->whereIn('status', ['active', 'completed'])
            ->update([
                'stock_status' => 'committed',
                'stock_committed_at' => DB::raw('created_at'),
            ]);

        DB::table('cylinder_transactions')
            ->whereNull('stock_status')
            ->where('status', 'cancelled')
            ->update(['stock_status' => 'released']);
    }

    public function down(): void
    {
        Schema::table('cylinder_transactions', function (Blueprint $table) {
            $table->dropIndex('idx_cylinder_stock_status');
            $table->dropColumn(['stock_status', 'stock_committed_at']);
        });
    }
};
