<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'customer_id',
        'receipt_number',
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
        'offline_created_at' => 'datetime'
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

    public function scopeOfflineSync($query)
    {
        return $query->where('is_offline_sync', true);
    }

    public function scopeOnline($query)
    {
        return $query->where('is_offline_sync', false);
    }
}