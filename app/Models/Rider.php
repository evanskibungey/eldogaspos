<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A delivery rider.
 *
 * Availability is derived, never stored: a rider is "out" exactly while they
 * are holding cylinders that have not been delivered. A manual flag would go
 * stale the first time somebody forgot to clear it, and the POS decides who to
 * offer based on this.
 */
class Rider extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'name',
        'phone',
        'status',
        'national_id',
        'notes',
    ];

    public function allocations()
    {
        return $this->hasMany(RiderAllocation::class);
    }

    /**
     * Cylinders this rider is holding right now.
     */
    public function activeAllocations()
    {
        return $this->hasMany(RiderAllocation::class)
            ->where('status', RiderAllocation::STATUS_PENDING);
    }

    /** Riders who still work here. Not the same as free right now. */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Riders the POS may assign work to, cheapest-first for the picker: the
     * count drives both the availability label and the ordering.
     */
    public function scopeAssignable($query)
    {
        return $query->active()->withCount('activeAllocations');
    }

    public function isOut(): bool
    {
        // Uses the loaded count when the caller asked for it, so rendering a
        // list of riders does not run one query per row.
        $held = $this->active_allocations_count ?? $this->activeAllocations()->count();

        return $held > 0;
    }

    public function getAvailabilityAttribute(): string
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return 'Inactive';
        }

        return $this->isOut() ? 'Out on delivery' : 'Available';
    }

    /**
     * How many cylinders the rider is carrying, across every open allocation.
     */
    public function getCylindersHeldAttribute(): int
    {
        return (int) RiderAllocationItem::whereIn(
            'rider_allocation_id',
            $this->activeAllocations()->select('id')
        )->sum('quantity');
    }
}
