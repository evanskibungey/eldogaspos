<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lets the till ask for the same sale twice and get one sale.
     *
     * Quick-sale completes a transaction on a single click, so the usual
     * protections are gone: there is no cart to review and no confirm step. A
     * double-click, an impatient second tap, a retried request on a flaky
     * connection - each would otherwise deduct stock and take money twice.
     *
     * The till sends a key it generates per sale attempt. If a sale already
     * exists for that key, the server returns it instead of creating another.
     * Unique rather than merely indexed on purpose: the database is what makes
     * it impossible, not the application logic.
     *
     * Nullable because every sale made before this, and any made through the
     * older paths, has no key - MySQL permits many NULLs in a unique index.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('idempotency_key', 64)->nullable()->unique()->after('receipt_number');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique(['idempotency_key']);
            $table->dropColumn('idempotency_key');
        });
    }
};
