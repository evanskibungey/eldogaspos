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
        'status'
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