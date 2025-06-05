<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfflineSyncLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'offline_receipt_number',
        'server_receipt_number', 
        'sale_id',
        'sync_status',
        'original_data',
        'error_message',
        'sync_attempts',
        'offline_created_at',
        'synced_at'
    ];

    protected $casts = [
        'original_data' => 'array',
        'offline_created_at' => 'datetime',
        'synced_at' => 'datetime'
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function incrementSyncAttempts()
    {
        $this->increment('sync_attempts');
    }

    public function markAsSynced($saleId, $serverReceiptNumber)
    {
        $this->update([
            'sync_status' => 'synced',
            'sale_id' => $saleId,
            'server_receipt_number' => $serverReceiptNumber,
            'synced_at' => now(),
            'error_message' => null
        ]);
    }

    public function markAsFailed($errorMessage)
    {
        $this->update([
            'sync_status' => 'failed',
            'error_message' => $errorMessage
        ]);
    }

    public function scopePending($query)
    {
        return $query->where('sync_status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('sync_status', 'failed');
    }

    public function scopeSynced($query)
    {
        return $query->where('sync_status', 'synced');
    }
}