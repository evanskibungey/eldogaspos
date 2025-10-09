<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CylinderTransactionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cylinder_transaction_id',
        'product_id',
        'brand',
        'quantity',
        'unit_price',
        'subtotal',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    // Relationships
    public function cylinderTransaction()
    {
        return $this->belongsTo(CylinderTransaction::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->withDefault([
            'name' => 'Unknown Product',
            'price' => 0,
        ]);
    }

    // Calculate subtotal
    public function calculateSubtotal()
    {
        $this->subtotal = $this->quantity * $this->unit_price;
        return $this->subtotal;
    }
}
