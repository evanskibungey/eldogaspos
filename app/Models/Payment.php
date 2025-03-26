<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'user_id',
        'amount',
        'payment_method',
        'reference_number',
        'notes',
    ];

    /**
     * Get the customer associated with the payment.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the user who recorded the payment.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}