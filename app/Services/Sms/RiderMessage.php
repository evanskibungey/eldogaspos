<?php

namespace App\Services\Sms;

use App\Models\RiderAllocation;

/**
 * Messages sent to delivery riders.
 *
 * Kept apart from ReceiptMessage because the audience is different: these go to
 * staff, not customers, so they carry no marketing, no app link and no footer -
 * a rider on a bike needs to know what to take and which order it is, nothing
 * else. That also keeps both messages inside one billed segment.
 */
class RiderMessage
{
    /** Longest an item list may run before it is summarised to a count. */
    private const ITEM_BUDGET = 70;

    /**
     * Sent the moment cylinders are booked out: what to collect, and the
     * reference the order will be completed against.
     */
    public static function allocated(RiderAllocation $allocation): string
    {
        $items = self::itemsSummary($allocation);

        $message = "Pick-up #{$allocation->reference_number}\n{$items}";

        // Only when the till knew it. Dialable form, not the stored 254...,
        // because the rider is meant to ring this number, not read it.
        $customer = PhoneNumber::local($allocation->customer_phone);

        if ($customer !== null) {
            $message .= "\nCustomer: {$customer}";
        }

        return $message;
    }

    /**
     * Sent once the rider has returned and the order is closed off.
     */
    public static function completed(RiderAllocation $allocation): string
    {
        return "Order #{$allocation->reference_number} has been completed successfully. Thank you.";
    }

    /**
     * What the rider is carrying, named, within a budget.
     *
     * Prices are left out deliberately - the rider needs the goods and the
     * count to do the job, and every character is billed.
     */
    private static function itemsSummary(RiderAllocation $allocation): string
    {
        $parts = [];
        $totalQuantity = 0;

        foreach ($allocation->items as $item) {
            $quantity = (int) $item->quantity;
            $totalQuantity += $quantity;

            $name = trim((string) (optional($item->product)->name ?? ''));
            if ($name === '') {
                continue;
            }

            $parts[] = $quantity > 1 ? "{$quantity}x {$name}" : $name;
        }

        if ($parts === []) {
            return $totalQuantity . ' ' . ($totalQuantity === 1 ? 'cylinder' : 'cylinders');
        }

        $full = implode(', ', $parts);

        if (mb_strlen($full) <= self::ITEM_BUDGET) {
            return $full;
        }

        for ($keep = count($parts) - 1; $keep >= 1; $keep--) {
            $remaining = count($parts) - $keep;
            $candidate = implode(', ', array_slice($parts, 0, $keep)) . " +{$remaining} more";

            if (mb_strlen($candidate) <= self::ITEM_BUDGET) {
                return $candidate;
            }
        }

        return $totalQuantity . ' ' . ($totalQuantity === 1 ? 'cylinder' : 'cylinders');
    }
}
