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
            // Make product_id required (NOT NULL)
            $table->foreignId('product_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cylinder_transactions', function (Blueprint $table) {
            // Make product_id nullable again
            $table->foreignId('product_id')->nullable()->change();
        });
    }
};
