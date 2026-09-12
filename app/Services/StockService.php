<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

/**
 * The single gateway for every change to product stock.
 *
 * Two quantities are tracked per product:
 *
 *   stock           physical units in the yard
 *   reserved_stock  units already promised to an open cylinder transaction
 *
 * Sellable quantity is (stock - reserved_stock). Every availability check in
 * the system resolves through that difference, never through `stock` directly.
 *
 * Lifecycle of a cylinder unit:
 *
 *   reserve()  creation      available drops, physical unchanged, no ledger row
 *   commit()   collection    physical drops, reservation released, ledger row
 *   release()  cancellation  reservation dropped, nothing ever left the yard
 *
 * A straight POS sale skips reservation and calls deductMultipleStock() -
 * the customer walks out with the goods immediately.
 *
 * Every method that touches stock does so inside a transaction, holding a
 * row-level lock taken in a consistent order (by product id) to avoid
 * deadlocks. Quantities are aggregated per product before validation so that
 * a cart listing the same product on two lines cannot overdraw.
 */
class StockService
{
    /*
    |--------------------------------------------------------------------------
    | Physical deduction (POS sales, advance collections)
    |--------------------------------------------------------------------------
    */

    /**
     * Deduct stock for a single product.
     *
     * @return Product
     * @throws \Exception when stock is insufficient
     */
    public function deductStock(
        int $productId,
        int $quantity,
        string $referenceType,
        int $referenceId,
        ?float $unitPrice = null,
        ?string $notes = null,
        ?string $serialNumber = null
    ): Product {
        $results = $this->deductMultipleStock(
            [[
                'product_id' => $productId,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'serial_number' => $serialNumber,
            ]],
            $referenceType,
            $referenceId,
            $notes
        );

        return $results[$productId]['product'];
    }

    /**
     * Deduct stock for several products at once.
     *
     * Validates the whole basket against sellable stock before mutating
     * anything, so a partially-applied deduction is impossible.
     *
     * @param  array  $items  [['product_id'=>int,'quantity'=>int,'unit_price'=>?float,'serial_number'=>?string], ...]
     * @return array  keyed by product_id, see buildResult() for the shape
     * @throws \Exception
     */
    public function deductMultipleStock(
        array $items,
        string $referenceType,
        int $referenceId,
        ?string $notesTemplate = null
    ): array {
        return DB::transaction(function () use ($items, $referenceType, $referenceId, $notesTemplate) {
            $lines = $this->aggregate($items);
            $products = $this->lockProducts(array_keys($lines));

            // Validate everything first - all or nothing.
            foreach ($lines as $productId => $line) {
                $product = $products[$productId];
                $available = $this->availableFor($product);

                if ($available < $line['quantity']) {
                    throw new InsufficientStockException(
                        $this->insufficientMessage($product, $available, $line['quantity']),
                        [[
                            'product_id' => (int) $product->id,
                            'product_name' => $product->name,
                            'available' => $available,
                            'requested' => $line['quantity'],
                        ]]
                    );
                }
            }

            $results = [];

            foreach ($lines as $productId => $line) {
                $product = $products[$productId];

                $stockBefore = (int) $product->stock;
                $availableBefore = $this->availableFor($product);

                $product->decrement('stock', $line['quantity']);

                $this->recordMovement(
                    $product,
                    'out',
                    $line,
                    $referenceType,
                    $referenceId,
                    $this->renderNotes($notesTemplate, $product, $line['quantity'])
                        ?? "Stock deducted - {$referenceType} #{$referenceId}"
                );

                $results[$productId] = $this->buildResult(
                    $product,
                    $line['quantity'],
                    $stockBefore,
                    $stockBefore - $line['quantity'],
                    $availableBefore,
                    $availableBefore - $line['quantity']
                );

                Log::info('Stock deducted', [
                    'product_id' => $productId,
                    'quantity' => $line['quantity'],
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockBefore - $line['quantity'],
                    'reference' => "{$referenceType}#{$referenceId}",
                ]);
            }

            return $results;
        });
    }

    /**
     * Restore stock for a single product.
     */
    public function restoreStock(
        int $productId,
        int $quantity,
        string $referenceType,
        int $referenceId,
        ?float $unitPrice = null,
        ?string $notes = null,
        ?string $serialNumber = null
    ): Product {
        $results = $this->restoreMultipleStock(
            [[
                'product_id' => $productId,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'serial_number' => $serialNumber,
            ]],
            $referenceType,
            $referenceId,
            $notes
        );

        return $results[$productId]['product'];
    }

    /**
     * Put stock back - voided sales, cancelled transactions, returns.
     */
    public function restoreMultipleStock(
        array $items,
        string $referenceType,
        int $referenceId,
        ?string $notesTemplate = null
    ): array {
        return DB::transaction(function () use ($items, $referenceType, $referenceId, $notesTemplate) {
            $lines = $this->aggregate($items);
            $products = $this->lockProducts(array_keys($lines));

            $results = [];

            foreach ($lines as $productId => $line) {
                $product = $products[$productId];

                $stockBefore = (int) $product->stock;
                $availableBefore = $this->availableFor($product);

                $product->increment('stock', $line['quantity']);

                $this->recordMovement(
                    $product,
                    'in',
                    $line,
                    $referenceType,
                    $referenceId,
                    $this->renderNotes($notesTemplate, $product, $line['quantity'])
                        ?? "Stock restored - {$referenceType} #{$referenceId}"
                );

                $results[$productId] = $this->buildResult(
                    $product,
                    $line['quantity'],
                    $stockBefore,
                    $stockBefore + $line['quantity'],
                    $availableBefore,
                    $availableBefore + $line['quantity']
                );

                Log::info('Stock restored', [
                    'product_id' => $productId,
                    'quantity' => $line['quantity'],
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockBefore + $line['quantity'],
                    'reference' => "{$referenceType}#{$referenceId}",
                ]);
            }

            return $results;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Reservation (cylinder drop-offs awaiting collection)
    |--------------------------------------------------------------------------
    */

    /**
     * Promise units to an open transaction without moving them.
     *
     * Availability drops immediately so the same cylinder cannot be sold twice,
     * but no stock movement is written - nothing has physically left the yard,
     * and the ledger must keep reconciling to the physical count.
     *
     * @throws \Exception when stock is insufficient
     */
    public function reserveMultipleStock(
        array $items,
        string $referenceType,
        int $referenceId
    ): array {
        return DB::transaction(function () use ($items, $referenceType, $referenceId) {
            $lines = $this->aggregate($items);
            $products = $this->lockProducts(array_keys($lines));

            foreach ($lines as $productId => $line) {
                $product = $products[$productId];
                $available = $this->availableFor($product);

                if ($available < $line['quantity']) {
                    throw new InsufficientStockException(
                        $this->insufficientMessage($product, $available, $line['quantity']),
                        [[
                            'product_id' => (int) $product->id,
                            'product_name' => $product->name,
                            'available' => $available,
                            'requested' => $line['quantity'],
                        ]]
                    );
                }
            }

            $results = [];

            foreach ($lines as $productId => $line) {
                $product = $products[$productId];

                $stockBefore = (int) $product->stock;
                $availableBefore = $this->availableFor($product);

                $product->increment('reserved_stock', $line['quantity']);

                $results[$productId] = $this->buildResult(
                    $product,
                    $line['quantity'],
                    $stockBefore,
                    $stockBefore,
                    $availableBefore,
                    $availableBefore - $line['quantity']
                );

                Log::info('Stock reserved', [
                    'product_id' => $productId,
                    'quantity' => $line['quantity'],
                    'available_before' => $availableBefore,
                    'available_after' => $availableBefore - $line['quantity'],
                    'reference' => "{$referenceType}#{$referenceId}",
                ]);
            }

            return $results;
        });
    }

    /**
     * Drop a reservation without deducting - the transaction was cancelled or
     * deleted before the customer ever collected.
     */
    public function releaseMultipleReservations(
        array $items,
        string $referenceType,
        int $referenceId
    ): array {
        return DB::transaction(function () use ($items, $referenceType, $referenceId) {
            $lines = $this->aggregate($items);
            $products = $this->lockProducts(array_keys($lines));

            $results = [];

            foreach ($lines as $productId => $line) {
                $product = $products[$productId];

                $stockBefore = (int) $product->stock;
                $availableBefore = $this->availableFor($product);

                // Clamp: never let a double-release drive the counter negative.
                $release = min($line['quantity'], (int) $product->reserved_stock);

                if ($release > 0) {
                    $product->decrement('reserved_stock', $release);
                }

                $results[$productId] = $this->buildResult(
                    $product,
                    $release,
                    $stockBefore,
                    $stockBefore,
                    $availableBefore,
                    $availableBefore + $release
                );

                Log::info('Stock reservation released', [
                    'product_id' => $productId,
                    'quantity' => $release,
                    'requested' => $line['quantity'],
                    'reference' => "{$referenceType}#{$referenceId}",
                ]);
            }

            return $results;
        });
    }

    /**
     * The customer has collected: convert a reservation into a real deduction.
     *
     * Physical stock drops and the reservation is released in the same locked
     * transaction, so availability is unchanged by this step - it already fell
     * when the units were reserved.
     *
     * @throws \Exception when physical stock cannot cover the collection
     */
    public function commitReservedStock(
        array $items,
        string $referenceType,
        int $referenceId,
        ?string $notesTemplate = null
    ): array {
        return DB::transaction(function () use ($items, $referenceType, $referenceId, $notesTemplate) {
            $lines = $this->aggregate($items);
            $products = $this->lockProducts(array_keys($lines));

            foreach ($lines as $productId => $line) {
                $product = $products[$productId];

                if ((int) $product->stock < $line['quantity']) {
                    throw new \Exception(
                        "Cannot complete collection for {$product->name}. "
                        . "Physical stock is {$product->stock} but {$line['quantity']} is being collected."
                    );
                }
            }

            $results = [];

            foreach ($lines as $productId => $line) {
                $product = $products[$productId];

                $stockBefore = (int) $product->stock;
                $availableBefore = $this->availableFor($product);

                // Release first so availability stays flat across the commit.
                $release = min($line['quantity'], (int) $product->reserved_stock);
                if ($release > 0) {
                    $product->decrement('reserved_stock', $release);
                }

                $product->decrement('stock', $line['quantity']);

                $this->recordMovement(
                    $product,
                    'out',
                    $line,
                    $referenceType,
                    $referenceId,
                    $this->renderNotes($notesTemplate, $product, $line['quantity'])
                        ?? "Stock collected - {$referenceType} #{$referenceId}"
                );

                $results[$productId] = $this->buildResult(
                    $product,
                    $line['quantity'],
                    $stockBefore,
                    $stockBefore - $line['quantity'],
                    $availableBefore,
                    $availableBefore - $line['quantity'] + $release
                );

                Log::info('Reserved stock committed on collection', [
                    'product_id' => $productId,
                    'quantity' => $line['quantity'],
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockBefore - $line['quantity'],
                    'released' => $release,
                    'reference' => "{$referenceType}#{$referenceId}",
                ]);
            }

            return $results;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Manual adjustment
    |--------------------------------------------------------------------------
    */

    /**
     * Set a product's physical stock to an absolute figure, under lock, writing
     * the difference to the ledger. Used by the admin stock screens, which
     * previously wrote products.stock directly with no lock and no transaction.
     *
     * @throws \Exception
     */
    public function setStockLevel(
        int $productId,
        int $newStock,
        ?string $notes = null,
        string $referenceType = 'manual_adjustment',
        ?int $referenceId = null,
        ?string $serialNumber = null
    ): array {
        if ($newStock < 0) {
            throw new \Exception('Stock cannot be negative.');
        }

        return DB::transaction(function () use ($productId, $newStock, $notes, $referenceType, $referenceId, $serialNumber) {
            $products = $this->lockProducts([$productId]);
            $product = $products[$productId];

            $stockBefore = (int) $product->stock;
            $availableBefore = $this->availableFor($product);
            $delta = $newStock - $stockBefore;

            if ($newStock < (int) $product->reserved_stock) {
                throw new \Exception(
                    "Cannot set {$product->name} stock to {$newStock}: "
                    . "{$product->reserved_stock} unit(s) are reserved for cylinder collections awaiting pickup."
                );
            }

            if ($delta !== 0) {
                $product->update(['stock' => $newStock]);

                $this->recordMovement(
                    $product,
                    $delta > 0 ? 'in' : 'out',
                    ['quantity' => abs($delta), 'unit_price' => null, 'serial_number' => $serialNumber],
                    $referenceType,
                    $referenceId ?? $product->id,
                    $notes ?? 'Manual stock adjustment'
                );
            }

            return $this->buildResult(
                $product,
                abs($delta),
                $stockBefore,
                $newStock,
                $availableBefore,
                $availableBefore + $delta
            );
        });
    }

    /**
     * Move a product's stock by a relative amount, under lock.
     *
     * @param  int  $delta  positive to add, negative to remove
     * @throws \Exception
     */
    public function adjustStockBy(
        int $productId,
        int $delta,
        ?string $notes = null,
        string $referenceType = 'manual_adjustment',
        ?string $serialNumber = null,
        ?float $unitPrice = null
    ): array {
        return DB::transaction(function () use ($productId, $delta, $notes, $referenceType, $serialNumber, $unitPrice) {
            $products = $this->lockProducts([$productId]);
            $product = $products[$productId];

            $stockBefore = (int) $product->stock;
            $availableBefore = $this->availableFor($product);
            $newStock = $stockBefore + $delta;

            if ($newStock < 0) {
                throw new \Exception(
                    "Stock cannot be negative. {$product->name} has {$stockBefore} unit(s); "
                    . 'cannot remove ' . abs($delta) . '.'
                );
            }

            if ($newStock < (int) $product->reserved_stock) {
                throw new \Exception(
                    "Cannot reduce {$product->name} to {$newStock}: "
                    . "{$product->reserved_stock} unit(s) are reserved for cylinder collections awaiting pickup."
                );
            }

            if ($delta !== 0) {
                $product->update(['stock' => $newStock]);

                $this->recordMovement(
                    $product,
                    $delta > 0 ? 'in' : 'out',
                    [
                        'quantity' => abs($delta),
                        'unit_price' => $unitPrice,
                        'serial_number' => $serialNumber,
                    ],
                    $referenceType,
                    $product->id,
                    $notes ?? 'Manual stock adjustment'
                );
            }

            return $this->buildResult(
                $product,
                abs($delta),
                $stockBefore,
                $newStock,
                $availableBefore,
                $availableBefore + $delta
            );
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Read helpers (no locking - UI validation only)
    |--------------------------------------------------------------------------
    */

    /**
     * Sellable quantity check. For display and pre-flight validation only;
     * the authoritative check happens under lock inside the deduct/reserve calls.
     */
    public function hasStock(int $productId, int $quantity): bool
    {
        $product = Product::find($productId);

        return $product && $product->available_stock >= $quantity;
    }

    /**
     * Physical stock on hand.
     */
    public function getCurrentStock(int $productId): int
    {
        $product = Product::find($productId);

        return $product ? (int) $product->stock : 0;
    }

    /**
     * Sellable stock - what the POS should show and validate against.
     */
    public function getAvailableStock(int $productId): int
    {
        $product = Product::find($productId);

        return $product ? $product->available_stock : 0;
    }

    /*
    |--------------------------------------------------------------------------
    | Internals
    |--------------------------------------------------------------------------
    */

    /**
     * Collapse duplicate product lines into one entry per product.
     *
     * Without this a cart holding the same product twice would be validated
     * line-by-line against the full stock figure and could overdraw.
     */
    private function aggregate(array $items): array
    {
        $lines = [];

        foreach ($items as $item) {
            $productId = (int) $item['product_id'];
            $quantity = (int) $item['quantity'];

            if ($quantity < 1) {
                throw new \Exception('Quantity must be at least 1.');
            }

            if (!isset($lines[$productId])) {
                $lines[$productId] = [
                    'quantity' => 0,
                    'unit_price' => $item['unit_price'] ?? null,
                    'serial_number' => $item['serial_number'] ?? null,
                ];
            }

            $lines[$productId]['quantity'] += $quantity;
        }

        // Lock in ascending product id order to keep the deadlock guarantee.
        ksort($lines);

        return $lines;
    }

    /**
     * Take row locks on every product, ordered by id.
     *
     * @throws \Exception when a product no longer exists
     */
    private function lockProducts(array $productIds): array
    {
        $products = Product::whereIn('id', $productIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($productIds as $productId) {
            if (!isset($products[$productId])) {
                throw new \Exception("Product with ID {$productId} not found");
            }
        }

        return $products->all();
    }

    private function availableFor(Product $product): int
    {
        return max(0, (int) $product->stock - (int) $product->reserved_stock);
    }

    private function insufficientMessage(Product $product, int $available, int $requested): string
    {
        if ($available <= 0) {
            $reserved = (int) $product->reserved_stock;

            return $reserved > 0
                ? "{$product->name} is out of stock. All {$reserved} remaining unit(s) are reserved for cylinder collections awaiting pickup."
                : "{$product->name} is out of stock.";
        }

        return "Insufficient stock for {$product->name}. Available: {$available}, Requested: {$requested}";
    }

    private function recordMovement(
        Product $product,
        string $type,
        array $line,
        string $referenceType,
        int $referenceId,
        string $notes
    ): void {
        StockMovement::create([
            'product_id' => $product->id,
            'type' => $type,
            'quantity' => $line['quantity'],
            'unit_price' => $line['unit_price'] ?? $product->price,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'notes' => $notes,
            'serial_number' => $line['serial_number'] ?? null,
            'created_by' => Auth::id(),
        ]);
    }

    private function renderNotes(?string $template, Product $product, int $quantity): ?string
    {
        if ($template === null) {
            return null;
        }

        return str_replace(
            ['{product_name}', '{quantity}'],
            [$product->name, $quantity],
            $template
        );
    }

    /**
     * Uniform result shape returned by every mutating method.
     *
     * `available_before` is what the POS was showing when the operator hit sell -
     * it is the figure the stock-derived order number is built from.
     */
    private function buildResult(
        Product $product,
        int $quantity,
        int $stockBefore,
        int $stockAfter,
        int $availableBefore,
        int $availableAfter
    ): array {
        return [
            'product_id' => (int) $product->id,
            'product' => $product,
            'quantity' => $quantity,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'available_before' => $availableBefore,
            'available_after' => max(0, $availableAfter),
        ];
    }
}
