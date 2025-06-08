<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class CylinderTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_number',
        'customer_id',
        'customer_name',
        'customer_phone',
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
        'amount' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
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

    public function getTotalAmount()
    {
        return $this->amount + $this->deposit_amount;
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

    // Generate unique reference number
    public static function generateReferenceNumber()
    {
        $prefix = 'CYL';
        $date = now()->format('Ymd');
        $count = self::whereDate('created_at', today())->count() + 1;
        
        return $prefix . $date . str_pad($count, 3, '0', STR_PAD_LEFT);
    }

    // Boot method to auto-generate reference number
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            if (!$transaction->reference_number) {
                $transaction->reference_number = self::generateReferenceNumber();
            }
        });
    }
}