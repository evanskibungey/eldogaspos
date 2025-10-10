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
        Schema::table('sales', function (Blueprint $table) {
            // Add indexes for sales table
            $table->index('receipt_number', 'idx_sales_receipt_number');
            $table->index(['status', 'created_at'], 'idx_sales_status_created');
            $table->index(['user_id', 'created_at'], 'idx_sales_user_created');
            $table->index(['customer_id', 'created_at'], 'idx_sales_customer_created');
            $table->index(['payment_method', 'payment_status'], 'idx_sales_payment');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            // Add composite indexes for sale_items
            $table->index(['sale_id', 'product_id'], 'idx_sale_items_sale_product');
            $table->index(['product_id', 'created_at'], 'idx_sale_items_product_created');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            // Add indexes for stock_movements
            $table->index(['product_id', 'created_at'], 'idx_stock_movements_product_created');
            $table->index(['product_id', 'reference_type', 'reference_id'], 'idx_stock_movements_reference');
            $table->index(['reference_type', 'reference_id'], 'idx_stock_movements_ref_type_id');
            $table->index(['created_at', 'type'], 'idx_stock_movements_created_type');
            $table->index(['created_by', 'created_at'], 'idx_stock_movements_creator');
        });

        Schema::table('products', function (Blueprint $table) {
            // Add indexes for products
            $table->index(['status', 'stock'], 'idx_products_status_stock');
            $table->index(['category_id', 'status'], 'idx_products_category_status');
            $table->index('sku', 'idx_products_sku');
        });

        Schema::table('cylinder_transactions', function (Blueprint $table) {
            // Add additional indexes for cylinder_transactions
            $table->index(['customer_id', 'created_at'], 'idx_cylinder_customer_created');
            $table->index(['payment_status', 'status'], 'idx_cylinder_payment_status');
            $table->index(['status', 'drop_off_date'], 'idx_cylinder_status_dropoff');
        });

        Schema::table('cylinder_transaction_items', function (Blueprint $table) {
            // Add indexes for cylinder_transaction_items
            $table->index(['product_id', 'created_at'], 'idx_cylinder_items_product_created');
        });

        Schema::table('customers', function (Blueprint $table) {
            // Add indexes for customers
            $table->index(['phone', 'status'], 'idx_customers_phone_status');
            $table->index(['status', 'created_at'], 'idx_customers_status_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('idx_sales_receipt_number');
            $table->dropIndex('idx_sales_status_created');
            $table->dropIndex('idx_sales_user_created');
            $table->dropIndex('idx_sales_customer_created');
            $table->dropIndex('idx_sales_payment');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropIndex('idx_sale_items_sale_product');
            $table->dropIndex('idx_sale_items_product_created');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex('idx_stock_movements_product_created');
            $table->dropIndex('idx_stock_movements_reference');
            $table->dropIndex('idx_stock_movements_ref_type_id');
            $table->dropIndex('idx_stock_movements_created_type');
            $table->dropIndex('idx_stock_movements_creator');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_products_status_stock');
            $table->dropIndex('idx_products_category_status');
            $table->dropIndex('idx_products_sku');
        });

        Schema::table('cylinder_transactions', function (Blueprint $table) {
            $table->dropIndex('idx_cylinder_customer_created');
            $table->dropIndex('idx_cylinder_payment_status');
            $table->dropIndex('idx_cylinder_status_dropoff');
        });

        Schema::table('cylinder_transaction_items', function (Blueprint $table) {
            $table->dropIndex('idx_cylinder_items_product_created');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('idx_customers_phone_status');
            $table->dropIndex('idx_customers_status_created');
        });
    }
};
