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
        'sku',
        'serial_number',
        'price',
        'cost_price',
        'stock',
        'min_stock',
        'image',
        'status'
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

    /**
     * Check if product is low on stock.
     */
    public function isLowStock()
    {
        return $this->stock <= $this->min_stock;
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
}