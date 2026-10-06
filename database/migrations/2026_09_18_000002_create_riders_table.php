<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Delivery riders.
     *
     * Deliberately not `users`: riders never sign in, so giving them an email
     * and a password to satisfy that table would be inventing credentials
     * nobody uses, and would put them in the staff login list.
     *
     * The phone is the important column - it is how the rider is told what to
     * collect and when an order is done, so it is unique: two rows for one
     * person would mean two different allocation histories for the same human.
     *
     * Availability is NOT stored. A rider is "out" exactly when they are
     * holding undelivered cylinders, which is derivable from their allocations
     * and therefore cannot go stale the way a manual flag does.
     */
    public function up(): void
    {
        Schema::create('riders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone', 20)->unique();

            // Whether the rider works here at all - not whether they are free
            // right now. Inactive riders stay for their allocation history but
            // are never offered for a new one.
            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->string('national_id', 50)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riders');
    }
};
