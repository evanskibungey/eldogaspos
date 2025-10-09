<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cylinder_transactions', function (Blueprint $table) {
            // Remove product_id as we now use cylinder_transaction_items
            $table->dropForeign(['product_id']);
            $table->dropIndex(['product_id']);
            $table->dropColumn('product_id');
            
            // cylinder_size and cylinder_type are now just for reference/notes
            // The actual products come from cylinder_transaction_items
            $table->string('cylinder_size')->nullable()->change();
            $table->string('cylinder_type')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cylinder_transactions', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->after('customer_phone')->constrained('products')->onDelete('restrict');
            $table->index('product_id');
            $table->string('cylinder_size')->nullable(false)->change();
            $table->string('cylinder_type')->nullable(false)->change();
        });
    }
};
