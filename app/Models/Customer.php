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