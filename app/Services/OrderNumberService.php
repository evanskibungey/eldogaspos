<?php

namespace App\Services;

use App\Models\Product;

/**
 * Derives the customer-facing order number from stock levels.
 *
 * The rule: an order number is the product's sellable stock immediately before
 * that line was taken out of the pool.
 *
 *   6kg stock 30  ->  sale of one  ->  order number 30, stock becomes 29
 *   6kg stock 29  ->  sale of one  ->  order number 29, stock becomes 28
 *
 * The number is never computed by re-reading the product. It is always taken
 * from the result of the StockService call that performed the deduction, which
 * captured it while holding the row lock. That is what makes the number and the
 * deduction consistent under concurrency - two cashiers selling the last two
 * units get 2 and 1, never 2 and 2.
 *
 * Order numbers repeat across restocks by design, so they are not unique and
 * never used as an identifier. Sales keep receipt_number for that.
 */
class OrderNumberService
{
    /**
     * The order number for a single deducted line.
     *
     * @param  array  $stockResult  one entry from a StockService result set
     */
    public function forLine(array $stockResult): int
    {
        return (int) $stockResult['available_before'];
    }

    /**
     * The headline number printed on a receipt.
     *
     * A cart can hold several products, so one line has to represent the sale.
     * Cylinders are what the numbering scheme is about, so the first cylinder
     * line in the cart wins; a cart with no cylinder falls back to its first
     * line. Every individual line still carries its own number on sale_items.
     *
     * @param  array  $stockResults      StockService results keyed by product id
     * @param  array  $productIdsInOrder product ids in the order the cart listed them
     */
    public function headline(array $stockResults, array $productIdsInOrder): ?int
    {
        if (empty($stockResults)) {
            return null;
        }

        $preferred = $this->firstCylinderId($stockResults, $productIdsInOrder);

        if ($preferred !== null) {
            return $this->forLine($stockResults[$preferred]);
        }

        foreach ($productIdsInOrder as $productId) {
            if (isset($stockResults[$productId])) {
                return $this->forLine($stockResults[$productId]);
            }
        }

        return $this->forLine(reset($stockResults));
    }

    /**
     * Order number for a cylinder collection.
     *
     * Cylinder units are reserved at drop-off, so availability already fell then
     * and stays flat across the collection. The figure that actually decrements
     * at collection is the physical count, so that is what the number tracks.
     */
    public function forCollection(array $stockResults, array $productIdsInOrder): ?int
    {
        if (empty($stockResults)) {
            return null;
        }

        $preferred = $this->firstCylinderId($stockResults, $productIdsInOrder)
            ?? $this->firstPresentId($stockResults, $productIdsInOrder);

        $result = $preferred !== null ? $stockResults[$preferred] : reset($stockResults);

        return (int) $result['stock_before'];
    }

    /**
     * First product in cart order that is a gas cylinder.
     */
    private function firstCylinderId(array $stockResults, array $productIdsInOrder): ?int
    {
        foreach ($productIdsInOrder as $productId) {
            if (!isset($stockResults[$productId])) {
                continue;
            }

            $product = $stockResults[$productId]['product'] ?? null;

            if ($product instanceof Product && $product->isCylinder()) {
                return (int) $productId;
            }
        }

        return null;
    }

    private function firstPresentId(array $stockResults, array $productIdsInOrder): ?int
    {
        foreach ($productIdsInOrder as $productId) {
            if (isset($stockResults[$productId])) {
                return (int) $productId;
            }
        }

        return null;
    }
}
