<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

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

    /**
     * Scope for pending sync logs
     */
    public function scopePending($query)
    {
        return $query->where('sync_status', 'pending');
    }

    /**
     * Scope for synced logs
     */
    public function scopeSynced($query)
    {
        return $query->where('sync_status', 'synced');
    }

    /**
     * Scope for failed sync logs
     */
    public function scopeFailed($query)
    {
        return $query->where('sync_status', 'failed');
    }

    /**
     * Increment sync attempts
     */
    public function incrementSyncAttempts()
    {
        $this->increment('sync_attempts');
        $this->save();
    }

    /**
     * Mark as synced
     */
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

    /**
     * Mark as failed
     */
    public function markAsFailed($errorMessage)
    {
        $this->update([
            'sync_status' => 'failed',
            'error_message' => $errorMessage
        ]);
    }

    /**
     * Relationship with sale
     */
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}
