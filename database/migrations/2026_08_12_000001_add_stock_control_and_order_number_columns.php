<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds the columns required for:
     *  - tracking stock by cylinder size (products.cylinder_size_kg)
     *  - reserving stock for open cylinder transactions (products.reserved_stock)
     *  - stock-derived order numbers (sales / sale_items / cylinder_transactions)
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Structured cylinder size. NULL means "not a gas cylinder".
            // Previously the size only existed as free text inside products.name.
            $table->decimal('cylinder_size_kg', 6, 2)->nullable()->after('brand');

            // Units physically present but already committed to an open cylinder
            // transaction. Sellable quantity is (stock - reserved_stock).
            $table->unsignedInteger('reserved_stock')->default(0)->after('stock');

            $table->index('cylinder_size_kg', 'idx_products_cylinder_size');
        });

        Schema::table('sales', function (Blueprint $table) {
            // Headline order number shown to the customer. Derived from the stock
            // level of the first cylinder line at the moment stock was deducted.
            // receipt_number remains the unique audit identifier.
            $table->unsignedInteger('order_number')->nullable()->after('receipt_number');
            $table->index(['order_number', 'created_at'], 'idx_sales_order_number');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            // Per-line stock number: the product's sellable stock immediately
            // before this line was deducted.
            $table->unsignedInteger('order_number')->nullable()->after('subtotal');
            $table->index(['product_id', 'order_number'], 'idx_sale_items_order_number');
        });

        Schema::table('cylinder_transactions', function (Blueprint $table) {
            // Set at the moment stock is committed (collection), not at creation.
            $table->unsignedInteger('order_number')->nullable()->after('reference_number');
            $table->index(['order_number', 'created_at'], 'idx_cylinder_order_number');
        });
    }

    public function down(): void
    {
        Schema::table('cylinder_transactions', function (Blueprint $table) {
            $table->dropIndex('idx_cylinder_order_number');
            $table->dropColumn('order_number');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropIndex('idx_sale_items_order_number');
            $table->dropColumn('order_number');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('idx_sales_order_number');
            $table->dropColumn('order_number');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_products_cylinder_size');
            $table->dropColumn(['cylinder_size_kg', 'reserved_stock']);
        });
    }
};
