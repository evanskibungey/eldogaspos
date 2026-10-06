<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class CylinderTransaction extends Model
{
    use HasFactory;

    // Effect this transaction currently has on product stock.
    public const STOCK_RESERVED = 'reserved';
    public const STOCK_COMMITTED = 'committed';
    public const STOCK_RELEASED = 'released';

    protected $fillable = [
        'reference_number',
        'order_number',
        'sale_id',
        'stock_status',
        'stock_committed_at',
        'customer_id',
        'customer_name',
        'customer_phone',
        'product_id',
        'cylinder_size',
        'cylinder_type',
        'transaction_type',
        'payment_status',
        'amount',
        'deposit_amount',
        'status',
        'drop_off_date',
        'collection_date',
        'return_date',
        'notes',
        'created_by',
        'completed_by'
    ];

    protected $casts = [
        'drop_off_date' => 'datetime',
        'collection_date' => 'datetime',
        'return_date' => 'datetime',
        'stock_committed_at' => 'datetime',
        'amount' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'order_number' => 'integer',
    ];

    // Relationships
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function items()
    {
        return $this->hasMany(CylinderTransactionItem::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'cylinder_transaction_items')
                    ->withPivot('quantity', 'unit_price', 'subtotal')
                    ->withTimestamps();
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class, 'reference_id')
                    ->where('reference_type', 'cylinder_transaction');
    }

    public function cancellationStockMovements()
    {
        return $this->hasMany(StockMovement::class, 'reference_id')
                    ->where('reference_type', 'cylinder_cancellation');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeDropOffs($query)
    {
        return $query->where('transaction_type', 'drop_off');
    }

    public function scopeAdvanceCollections($query)
    {
        return $query->where('transaction_type', 'advance_collection');
    }

    public function scopePending($query)
    {
        return $query->where('payment_status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    // Helper methods
    public function isDropOff()
    {
        return $this->transaction_type === 'drop_off';
    }

    public function isAdvanceCollection()
    {
        return $this->transaction_type === 'advance_collection';
    }

    public function isActive()
    {
        return $this->status === 'active';
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function isPending()
    {
        return $this->payment_status === 'pending';
    }

    public function isPaid()
    {
        return $this->payment_status === 'paid';
    }

    /*
    |--------------------------------------------------------------------------
    | Stock state
    |--------------------------------------------------------------------------
    |
    | Units are reserved when a drop-off is created and committed when the
    | customer collects. Advance collections skip straight to committed, since
    | the gas physically leaves the yard at creation. These guards are what
    | prevent a transaction affecting stock twice.
    |
    */

    public function hasReservedStock(): bool
    {
        return $this->stock_status === self::STOCK_RESERVED;
    }

    public function hasCommittedStock(): bool
    {
        return $this->stock_status === self::STOCK_COMMITTED;
    }

    public function stockAlreadyReleased(): bool
    {
        return $this->stock_status === self::STOCK_RELEASED;
    }

    /**
     * Line items shaped for StockService.
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

    /**
     * Product ids in the order they were added, for order-number selection.
     */
    public function productIdsInOrder(): array
    {
        return $this->items->pluck('product_id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * The number shown to the customer, once stock has been committed.
     */
    public function getDisplayNumberAttribute(): string
    {
        return $this->order_number !== null
            ? (string) $this->order_number
            : (string) $this->reference_number;
    }

    public function getTotalAmount()
    {
        return $this->amount + $this->deposit_amount;
    }

    /** The sale written when this transaction was completed, if any. */
    public function sale()
    {
        return $this->belongsTo(\App\Models\Sale::class);
    }

    /**
     * Whether the revenue from this transaction has already been recorded.
     *
     * Checked rather than assumed: completing is guarded elsewhere, but a
     * retried request or a backfill run twice must never book the same money
     * into `sales` a second time.
     */
    public function saleAlreadyRecorded(): bool
    {
        return $this->sale_id !== null;
    }

    /**
     * Line items shaped for FulfilmentSaleRecorder.
     *
     * The deposit is deliberately absent. It is a refundable obligation that
     * completion hands back, not money earned, so it must not inflate revenue.
     */
    public function saleLines(): array
    {
        return $this->items->map(fn ($item) => [
            'product_id' => $item->product_id,
            'quantity' => (int) $item->quantity,
            'unit_price' => (float) $item->unit_price,
            'subtotal' => (float) $item->subtotal,
            'serial_number' => null,
        ])->all();
    }

    public function calculateTotalFromItems()
    {
        return $this->items()->sum('subtotal');
    }

    public function getTotalQuantity()
    {
        return $this->items()->sum('quantity');
    }

    public function getStatusBadgeColor()
    {
        return match($this->status) {
            'active' => 'bg-yellow-100 text-yellow-800',
            'completed' => 'bg-green-100 text-green-800',
            'cancelled' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    public function getPaymentStatusBadgeColor()
    {
        return match($this->payment_status) {
            'paid' => 'bg-green-100 text-green-800',
            'pending' => 'bg-orange-100 text-orange-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    public function getTransactionTypeBadgeColor()
    {
        return match($this->transaction_type) {
            'drop_off' => 'bg-blue-100 text-blue-800',
            'advance_collection' => 'bg-purple-100 text-purple-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    public function getDaysWaiting()
    {
        if ($this->isCompleted()) {
            return 0;
        }

        return Carbon::now()->diffInDays($this->drop_off_date);
    }

    public function getAgeStatus()
    {
        $daysWaiting = $this->getDaysWaiting();

        if ($daysWaiting < 7) {
            return ['days' => $daysWaiting, 'status' => 'recent', 'color' => 'bg-green-100 text-green-800'];
        } elseif ($daysWaiting < 14) {
            return ['days' => $daysWaiting, 'status' => 'aging', 'color' => 'bg-yellow-100 text-yellow-800'];
        } elseif ($daysWaiting < 30) {
            return ['days' => $daysWaiting, 'status' => 'overdue', 'color' => 'bg-orange-100 text-orange-800'];
        } else {
            return ['days' => $daysWaiting, 'status' => 'critical', 'color' => 'bg-red-100 text-red-800'];
        }
    }
}
