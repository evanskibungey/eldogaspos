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
            // Add product_id field to link cylinder transactions with products
            $table->foreignId('product_id')->nullable()->after('customer_phone')->constrained('products')->onDelete('restrict');
            
            // Add index for better query performance
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cylinder_transactions', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropIndex(['product_id']);
            $table->dropColumn('product_id');
        });
    }
};
