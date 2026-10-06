<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiderAllocationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'rider_allocation_id',
        'product_id',
        'quantity',
        'unit_price',
        'subtotal',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function allocation()
    {
        return $this->belongsTo(RiderAllocation::class, 'rider_allocation_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
