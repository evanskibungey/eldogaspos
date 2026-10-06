<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Whether this customer has asked not to receive marketing SMS.
     *
     * Promotional messages are a different thing from the receipts and
     * collection notices the shop already sends: those are asked for by making
     * a purchase, a campaign is not. A customer who says stop has to be able
     * to be honoured immediately and permanently, and the record of that has to
     * outlive whoever took the call.
     *
     * The timestamp is kept alongside the flag because "when did they ask" is
     * the question that gets asked if a complaint is ever made.
     *
     * Transactional messages deliberately ignore this flag - somebody who
     * bought a cylinder still gets their receipt.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->boolean('sms_opt_out')->default(false)->after('status');
            $table->timestamp('sms_opt_out_at')->nullable()->after('sms_opt_out');
            $table->string('sms_opt_out_source', 20)->nullable()->after('sms_opt_out_at');

            // Campaigns filter on this on every send.
            $table->index('sms_opt_out');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['sms_opt_out']);
            $table->dropColumn(['sms_opt_out', 'sms_opt_out_at', 'sms_opt_out_source']);
        });
    }
};
