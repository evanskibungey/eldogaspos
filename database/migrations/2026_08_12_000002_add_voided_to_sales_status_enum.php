<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The void workflow has always written status = 'voided', but the enum only
     * allowed pending/completed/cancelled. Under STRICT_TRANS_TABLES that write
     * throws, so voiding a sale rolled back every time and stock was never
     * restored. Widening the enum makes the ~30 `where('status','!=','voided')`
     * report guards meaningful for the first time.
     *
     * Raw SQL is used deliberately: doctrine/dbal cannot round-trip enum columns.
     */
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE sales MODIFY COLUMN status "
            . "ENUM('pending','completed','cancelled','voided') NOT NULL DEFAULT 'pending'"
        );
    }

    public function down(): void
    {
        // Any rows sitting on the value being removed must be parked somewhere
        // valid first, otherwise the ALTER silently truncates them to ''.
        DB::table('sales')->where('status', 'voided')->update(['status' => 'cancelled']);

        DB::statement(
            "ALTER TABLE sales MODIFY COLUMN status "
            . "ENUM('pending','completed','cancelled') NOT NULL DEFAULT 'pending'"
        );
    }
};
