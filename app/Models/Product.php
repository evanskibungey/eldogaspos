<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'category_id',
        'brand',
        'cylinder_size_kg',
        'sku',
        'serial_number',
        'price',
        'cost_price',
        'stock',
        'reserved_stock',
        'min_stock',
        'image',
        'status'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'cylinder_size_kg' => 'decimal:2',
        'stock' => 'integer',
        'reserved_stock' => 'integer',
        'min_stock' => 'integer',
    ];

    /**
     * Get the category that owns the product.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get all stock movements for the product.
     */
    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Stock availability
    |--------------------------------------------------------------------------
    |
    | `stock` is the physical count in the yard. `reserved_stock` is the part of
    | it already promised to an open cylinder transaction but not yet handed to
    | the customer. Only the difference may be sold, so every availability check
    | in the system goes through available_stock - never through `stock` directly.
    |
    */

    /**
     * Units that may still be sold or reserved.
     */
    public function getAvailableStockAttribute(): int
    {
        return max(0, (int) $this->stock - (int) $this->reserved_stock);
    }

    /**
     * Whether the requested quantity can be satisfied right now.
     */
    public function hasAvailableStock(int $quantity = 1): bool
    {
        return $this->available_stock >= $quantity;
    }

    /**
     * Nothing left to sell. Drives the out-of-stock messaging in the POS.
     */
    public function isOutOfStock(): bool
    {
        return $this->available_stock <= 0;
    }

    /**
     * Check if product is low on stock.
     *
     * Compares against sellable stock rather than the raw physical count, so a
     * product whose entire shelf is reserved reads as low even before collection.
     */
    public function isLowStock()
    {
        return $this->available_stock <= $this->min_stock;
    }

    /*
    |--------------------------------------------------------------------------
    | Cylinder identity
    |--------------------------------------------------------------------------
    */

    /**
     * A product is a gas cylinder when it carries a structured size.
     */
    public function isCylinder(): bool
    {
        return $this->cylinder_size_kg !== null;
    }

    /**
     * Human label for the size, e.g. "13kg". Used in receipts and stock reports.
     */
    public function getCylinderSizeLabelAttribute(): ?string
    {
        if (!$this->isCylinder()) {
            return null;
        }

        $size = (float) $this->cylinder_size_kg;

        // Drop the decimal part for whole sizes: 13.00 -> "13kg", 12.50 -> "12.5kg"
        return rtrim(rtrim(number_format($size, 2, '.', ''), '0'), '.') . 'kg';
    }

    /**
     * Get stock in movements.
     */
    public function stockIn()
    {
        return $this->stockMovements()->ofType('in');
    }

    /**
     * Get stock out movements.
     */
    public function stockOut()
    {
        return $this->stockMovements()->ofType('out');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Only gas cylinders (products carrying a structured size).
     */
    public function scopeCylinders($query)
    {
        return $query->whereNotNull('cylinder_size_kg');
    }

    /**
     * Cylinders of one particular size, e.g. ->ofCylinderSize(13).
     */
    public function scopeOfCylinderSize($query, $sizeKg)
    {
        return $query->where('cylinder_size_kg', $sizeKg);
    }

    /**
     * Products with something left to sell.
     */
    public function scopeInStock($query)
    {
        return $query->whereRaw('(stock - reserved_stock) > 0');
    }

    /**
     * Products with nothing left to sell.
     */
    public function scopeOutOfStock($query)
    {
        return $query->whereRaw('(stock - reserved_stock) <= 0');
    }

    public function scopeLowStock($query)
    {
        return $query->whereRaw('(stock - reserved_stock) <= min_stock');
    }
}
