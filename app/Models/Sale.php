<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_VOIDED = 'voided';

    protected $fillable = [
        'user_id',
        'customer_id',
        'receipt_number',
        'order_number',
        'total_amount',
        'payment_method',
        'payment_status',
        'status',
        'notes',
        'is_offline_sync',
        'offline_receipt_number',
        'offline_created_at'
    ];

    protected $casts = [
        'is_offline_sync' => 'boolean',
        'offline_created_at' => 'datetime',
        'order_number' => 'integer',
        'total_amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function offlineSyncLog()
    {
        return $this->hasOne(OfflineSyncLog::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class, 'reference_id')
                    ->where('reference_type', 'sale');
    }

    public function voidStockMovements()
    {
        return $this->hasMany(StockMovement::class, 'reference_id')
                    ->where('reference_type', 'sale_void');
    }

    public function scopeOfflineSync($query)
    {
        return $query->where('is_offline_sync', true);
    }

    public function scopeOnline($query)
    {
        return $query->where('is_offline_sync', false);
    }

    /**
     * Sales that still count towards revenue.
     *
     * Voiding was impossible before the status enum was widened, so every
     * `where('status','!=','voided')` filter in the reports was a no-op. Now
     * that voids are real, use this scope rather than repeating the literal.
     */
    public function scopeNotVoided($query)
    {
        return $query->where('status', '!=', self::STATUS_VOIDED);
    }

    public function scopeVoided($query)
    {
        return $query->where('status', self::STATUS_VOIDED);
    }

    public function isVoided(): bool
    {
        return $this->status === self::STATUS_VOIDED;
    }

    /**
     * The number shown to the customer. Falls back to the receipt reference for
     * sales taken before stock-derived numbering was introduced.
     */
    public function getDisplayNumberAttribute(): string
    {
        return $this->order_number !== null
            ? (string) $this->order_number
            : (string) $this->receipt_number;
    }
}