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
        Schema::create('cylinder_transaction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cylinder_transaction_id')->constrained('cylinder_transactions')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2); // Price per unit at time of transaction
            $table->decimal('subtotal', 10, 2); // quantity * unit_price
            $table->timestamps();
            
            // Indexes
            $table->index('cylinder_transaction_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cylinder_transaction_items');
    }
};
