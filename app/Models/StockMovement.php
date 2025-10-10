<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'type',
        'quantity',
        'unit_price',
        'reference_type',
        'reference_id',
        'notes',
        'serial_number',
        'created_by', // Standardized field name
    ];

    /**
     * Get the product associated with the stock movement.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the user who created the stock movement.
     * Using 'created_by' as the standard field name
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Alias for creator() for backward compatibility
     */
    public function user()
    {
        return $this->creator();
    }

    /**
     * Scope a query to only include stock movements of a given type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to only include stock movements of a given reference type.
     */
    public function scopeOfReferenceType($query, $referenceType)
    {
        return $query->where('reference_type', $referenceType);
    }

    /**
     * Scope a query to only include sale-related stock movements.
     */
    public function scopeSales($query)
    {
        return $query->where('reference_type', 'sale');
    }

    /**
     * Scope a query to only include void-related stock movements.
     */
    public function scopeVoids($query)
    {
        return $query->where('reference_type', 'sale_void');
    }

    /**
     * Scope a query to only include cylinder transaction stock movements.
     */
    public function scopeCylinderTransactions($query)
    {
        return $query->where('reference_type', 'cylinder_transaction');
    }

    /**
     * Scope a query to only include cylinder cancellation stock movements.
     */
    public function scopeCylinderCancellations($query)
    {
        return $query->where('reference_type', 'cylinder_cancellation');
    }
}
