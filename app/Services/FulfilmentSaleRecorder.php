<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\Sms\PhoneNumber;

/**
 * Writes the `sales` row for work that was fulfilled somewhere other than the
 * till - a cylinder refill collected, an order delivered by a rider.
 *
 * WHY THIS DOES NOT TOUCH StockService
 * ------------------------------------
 * By the time this runs the stock has already moved, under its own reference
 * type (`cylinder_collection`, `rider_delivery`). Putting the sale through the
 * stock service as well would deduct the same cylinders a second time. This
 * class records revenue only; the caller owns the stock movement.
 *
 * Every figure comes from the caller's own line items rather than being
 * recalculated, so the sale reconciles exactly with the transaction it came
 * from.
 */
class FulfilmentSaleRecorder
{
    protected ReferenceNumberService $referenceNumbers;

    public function __construct(ReferenceNumberService $referenceNumbers)
    {
        $this->referenceNumbers = $referenceNumbers;
    }

    /**
     * @param  array  $lines  each ['product_id', 'quantity', 'unit_price', 'subtotal']
     * @param  array  $attributes  customer_id, user_id, order_number, payment_status,
     *                             payment_method, notes - all optional
     */
    public function record(array $lines, array $attributes = []): Sale
    {
        $total = round(array_sum(array_column($lines, 'subtotal')), 2);

        $sale = Sale::create([
            'user_id' => $attributes['user_id'] ?? auth()->id(),
            'customer_id' => $attributes['customer_id'] ?? $this->walkInCustomer()->id,
            'receipt_number' => $this->referenceNumbers->generateReceiptNumber(),
            'order_number' => $attributes['order_number'] ?? null,
            'total_amount' => $total,
            'payment_method' => $attributes['payment_method'] ?? 'cash',
            'payment_status' => $attributes['payment_status'] ?? 'paid',
            'status' => Sale::STATUS_COMPLETED,
            'notes' => $attributes['notes'] ?? null,
        ]);

        foreach ($lines as $line) {
            SaleItem::create([
                'sale_id' => $sale->id,
                'product_id' => $line['product_id'],
                'quantity' => $line['quantity'],
                'unit_price' => $line['unit_price'],
                'subtotal' => $line['subtotal'],
                'order_number' => $attributes['order_number'] ?? null,
                'serial_number' => $line['serial_number'] ?? null,
            ]);
        }

        // Back-date both the sale and its lines when the caller is recording
        // something that happened earlier - a backfill of historical
        // collections must land on the day the money was taken, not today.
        if (!empty($attributes['created_at'])) {
            $sale->forceFill([
                'created_at' => $attributes['created_at'],
                'updated_at' => $attributes['created_at'],
            ])->save();

            SaleItem::where('sale_id', $sale->id)->update([
                'created_at' => $attributes['created_at'],
                'updated_at' => $attributes['created_at'],
            ]);
        }

        return $sale;
    }

    /**
     * The shared placeholder the POS uses for cash sales with no named
     * customer. Matched on the same literal so one row is reused rather than
     * a new "Walk-in Customer" being created per call.
     */
    public function walkInCustomer(): Customer
    {
        return Customer::firstOrCreate(
            ['phone' => PhoneNumber::WALK_IN],
            ['name' => 'Walk-in Customer', 'status' => 'active']
        );
    }
}
