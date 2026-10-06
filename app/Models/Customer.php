<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'credit_limit',
        'balance',
        'status',
        'sms_opt_out',
        'sms_opt_out_at',
        'sms_opt_out_source',
    ];

    protected $casts = [
        'sms_opt_out' => 'boolean',
        'sms_opt_out_at' => 'datetime',
    ];

    /**
     * Customers a user may pick from a list.
     *
     * Excludes the POS walk-in placeholder: cash sales all attach to one shared
     * row, so it is an active customer like any other and was being offered for
     * selection. Anything assigned to it - a cylinder deposit, a collection
     * reference - belongs to nobody and can never be chased.
     */
    public function scopeSelectable($query)
    {
        return $query->where('status', 'active')
            ->where('phone', '!=', \App\Services\Sms\PhoneNumber::WALK_IN);
    }

    /**
     * Match a phone number in any of the spellings it may be stored in.
     */
    public function scopeWithPhone($query, ?string $phone)
    {
        return $query->whereIn('phone', \App\Services\Sms\PhoneNumber::variants($phone));
    }

    /**
     * Customers who may be sent marketing.
     *
     * Only campaigns use this. Receipts and collection notices are service
     * messages about a purchase the customer made, so they are sent whatever
     * the marketing preference says.
     */
    public function scopeMarketable($query)
    {
        return $query->selectable()->where('sms_opt_out', false);
    }

    /**
     * Record that this person has asked to stop receiving marketing.
     *
     * Idempotent, and it keeps the FIRST request's timestamp: if someone opts
     * out twice, when they first asked is the answer that matters.
     */
    public function optOutOfSms(string $source = 'admin'): bool
    {
        if ($this->sms_opt_out) {
            return false;
        }

        $this->forceFill([
            'sms_opt_out' => true,
            'sms_opt_out_at' => now(),
            'sms_opt_out_source' => $source,
        ])->save();

        return true;
    }

    public function optInToSms(): void
    {
        $this->forceFill([
            'sms_opt_out' => false,
            'sms_opt_out_at' => null,
            'sms_opt_out_source' => null,
        ])->save();
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function cylinderTransactions()
    {
        return $this->hasMany(CylinderTransaction::class);
    }

    public function activeCylinderTransactions()
    {
        return $this->hasMany(CylinderTransaction::class)->where('status', 'active');
    }
}