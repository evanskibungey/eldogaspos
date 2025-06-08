<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('cylinder_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number')->unique(); // Auto-generated reference
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->string('customer_name'); // Store customer name for quick reference
            $table->string('customer_phone'); // Store customer phone for quick reference
            $table->string('cylinder_size'); // e.g., "6kg", "13kg", "50kg"
            $table->string('cylinder_type')->default('LPG'); // Type of gas cylinder
            $table->enum('transaction_type', ['drop_off', 'advance_collection']); 
            // drop_off: Customer leaves empty cylinder first
            // advance_collection: Customer takes gas before returning empty cylinder
            
            $table->enum('payment_status', ['paid', 'pending'])->default('pending');
            $table->decimal('amount', 10, 2); // Amount for the gas refill
            $table->decimal('deposit_amount', 10, 2)->default(0); // Extra deposit for advance collection
            
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->datetime('drop_off_date'); // When cylinder was dropped off or gas was collected
            $table->datetime('collection_date')->nullable(); // When customer collected refilled cylinder
            $table->datetime('return_date')->nullable(); // When empty cylinder was returned (for advance_collection)
            
            $table->text('notes')->nullable(); // Any additional notes
            $table->foreignId('created_by')->constrained('users'); // Staff who created the record
            $table->foreignId('completed_by')->nullable()->constrained('users'); // Staff who completed the transaction
            
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['customer_id', 'status']);
            $table->index(['transaction_type', 'status']);
            $table->index(['reference_number']);
            $table->index(['created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('cylinder_transactions');
    }
};