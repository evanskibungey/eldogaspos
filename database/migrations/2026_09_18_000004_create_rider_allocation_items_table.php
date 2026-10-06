<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The cylinders on one allocation.
     *
     * Prices are copied in at allocation time rather than read from the product
     * later, for the same reason sale_items does it: the price list moves, and
     * what this allocation was worth must not move with it.
     *
     * A separate table even though the first version of the Pick-up button
     * sends one product at a time - a rider taking a mixed load is the obvious
     * next step, and reshaping a live table later is far more expensive than
     * carrying a join now.
     */
    public function up(): void
    {
        Schema::create('rider_allocation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rider_allocation_id')->constrained('rider_allocations')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();

            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rider_allocation_items');
    }
};
