<?php

namespace App\Models;

use App\Services\Sms\SmsService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsLog extends Model
{
    use HasFactory;

    public const STATUS_QUEUED = 'queued';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';

    public const PURPOSE_SALE_RECEIPT = 'sale_receipt';
    public const PURPOSE_CYLINDER_RECEIPT = 'cylinder_receipt';
    public const PURPOSE_CYLINDER_THANK_YOU = 'cylinder_thank_you';
    public const PURPOSE_PAYMENT_RECEIVED = 'payment_received';
    public const PURPOSE_CAMPAIGN = 'campaign';

    protected $fillable = [
        'recipient',
        'message',
        'purpose',
        'reference_type',
        'reference_id',
        'customer_id',
        'user_id',
        'status',
        'gateway_uid',
        'error',
        'response',
        'sent_at',
    ];

    protected $casts = [
        'response' => 'array',
        'sent_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeSent($query)
    {
        return $query->where('status', self::STATUS_SENT);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeCampaigns($query)
    {
        return $query->where('purpose', self::PURPOSE_CAMPAIGN);
    }

    /**
     * Messages are billed per 160-character segment, so the segment count is
     * what actually predicts spend on a campaign.
     */
    public function getSegmentsAttribute(): int
    {
        return SmsService::segments($this->message);
    }
}
