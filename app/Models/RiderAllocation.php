<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Cylinders booked out to a rider for delivery.
 *
 * The same movement as a cylinder advance collection - stock leaves now, the
 * empties come back later - with a rider holding it in between instead of a
 * customer. It reuses the same stock vocabulary on purpose: reserved while the
 * rider has them, committed once delivered, released if cancelled.
 *
 * No money is recorded until completion. Cylinders on a bike are not revenue;
 * they may come back unsold.
 */
class RiderAllocation extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const STOCK_RESERVED = 'reserved';
    public const STOCK_COMMITTED = 'committed';
    public const STOCK_RELEASED = 'released';

    protected $fillable = [
        'reference_number',
        'idempotency_key',
        'rider_id',
        'customer_phone',
        'user_id',
        'sale_id',
        'status',
        'stock_status',
        'total_amount',
        'allocated_at',
        'completed_at',
        'completion_notified_at',
        'notes',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'allocated_at' => 'datetime',
        'completed_at' => 'datetime',
        'completion_notified_at' => 'datetime',
    ];

    public function rider()
    {
        return $this->belongsTo(Rider::class);
    }

    /** The admin who booked the cylinders out. */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** The sale written when this allocation was completed. */
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function items()
    {
        return $this->hasMany(RiderAllocationItem::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function hasReservedStock(): bool
    {
        return $this->stock_status === self::STOCK_RESERVED;
    }

    /**
     * Whether the rider has already been told this order is done. Checked
     * rather than assumed, so repeating the action cannot send a second
     * message - each one is billed.
     */
    public function completionAlreadyNotified(): bool
    {
        return $this->completion_notified_at !== null;
    }

    /**
     * Line items shaped for StockService, matching CylinderTransaction.
     */
    public function stockLines(): array
    {
        return $this->items->map(fn ($item) => [
            'product_id' => $item->product_id,
            'quantity' => $item->quantity,
            'unit_price' => $item->unit_price,
            'serial_number' => null,
        ])->all();
    }

    public function productIdsInOrder(): array
    {
        return $this->items->pluck('product_id')->map(fn ($id) => (int) $id)->all();
    }

    public function getTotalQuantityAttribute(): int
    {
        return (int) $this->items->sum('quantity');
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'bg-yellow-100 text-yellow-800',
            self::STATUS_COMPLETED => 'bg-green-100 text-green-800',
            self::STATUS_CANCELLED => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}
