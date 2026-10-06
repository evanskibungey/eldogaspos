<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Who the cylinders are going to, when the till happens to know.
     *
     * Optional on purpose. A pick-up is often booked out before anyone has the
     * customer on the phone, and requiring a number would put a form in front
     * of a flow whose whole point is one tap. When it IS known, the rider gets
     * it in the same text as the order and can call ahead instead of ringing
     * the shop back.
     *
     * Stored normalised (254...) like every other number in the system, so it
     * matches `customers.phone` spellings when looked up. It is deliberately
     * NOT a foreign key to `customers`: this is a delivery contact, and
     * creating a customer record for it would put a row in the ledger that
     * nobody ever sells to.
     */
    public function up(): void
    {
        Schema::table('rider_allocations', function (Blueprint $table) {
            $table->string('customer_phone', 20)->nullable()->after('rider_id');
        });
    }

    public function down(): void
    {
        Schema::table('rider_allocations', function (Blueprint $table) {
            $table->dropColumn('customer_phone');
        });
    }
};
