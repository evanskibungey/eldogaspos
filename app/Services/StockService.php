<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class StockService
{
    /**
     * Deduct stock for a product with pessimistic locking
     *
     * @param int $productId
     * @param int $quantity
     * @param string $referenceType
     * @param int $referenceId
     * @param float|null $unitPrice
     * @param string|null $notes
     * @param string|null $serialNumber
     * @return Product
     * @throws \Exception
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
        return DB::transaction(function () use ($productId, $quantity, $referenceType, $referenceId, $unitPrice, $notes, $serialNumber) {
            // Lock the product row for update
            $product = Product::where('id', $productId)
                ->lockForUpdate()
                ->first();

            if (!$product) {
                throw new \Exception("Product with ID {$productId} not found");
            }

            // Check stock availability
            if ($product->stock < $quantity) {
                throw new \Exception(
                    "Insufficient stock for {$product->name}. Available: {$product->stock}, Requested: {$quantity}"
                );
            }

            // Deduct stock
            $product->decrement('stock', $quantity);

            // Create stock movement record
            StockMovement::create([
                'product_id' => $product->id,
                'type' => 'out',
                'quantity' => $quantity,
                'unit_price' => $unitPrice ?? $product->price,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'notes' => $notes,
                'serial_number' => $serialNumber,
                'created_by' => Auth::id(),
            ]);

            Log::info('Stock deducted successfully', [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => $quantity,
                'new_stock' => $product->stock,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
            ]);

            // Refresh to get updated stock
            return $product->fresh();
        });
    }

    /**
     * Restore stock for a product with pessimistic locking
     *
     * @param int $productId
     * @param int $quantity
     * @param string $referenceType
     * @param int $referenceId
     * @param float|null $unitPrice
     * @param string|null $notes
     * @param string|null $serialNumber
     * @return Product
     * @throws \Exception
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
        return DB::transaction(function () use ($productId, $quantity, $referenceType, $referenceId, $unitPrice, $notes, $serialNumber) {
            // Lock the product row for update
            $product = Product::where('id', $productId)
                ->lockForUpdate()
                ->first();

            if (!$product) {
                throw new \Exception("Product with ID {$productId} not found");
            }

            // Restore stock
            $product->increment('stock', $quantity);

            // Create stock movement record
            StockMovement::create([
                'product_id' => $product->id,
                'type' => 'in',
                'quantity' => $quantity,
                'unit_price' => $unitPrice ?? $product->price,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'notes' => $notes,
                'serial_number' => $serialNumber,
                'created_by' => Auth::id(),
            ]);

            Log::info('Stock restored successfully', [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => $quantity,
                'new_stock' => $product->stock,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
            ]);

            // Refresh to get updated stock
            return $product->fresh();
        });
    }

    /**
     * Deduct stock for multiple items with pessimistic locking
     * All items are locked before any deduction to prevent deadlocks
     *
     * @param array $items Array of ['product_id' => int, 'quantity' => int, 'unit_price' => float, 'serial_number' => string|null]
     * @param string $referenceType
     * @param int $referenceId
     * @param string|null $notesTemplate
     * @return array Array of updated products
     * @throws \Exception
     */
    public function deductMultipleStock(
        array $items,
        string $referenceType,
        int $referenceId,
        ?string $notesTemplate = null
    ): array {
        return DB::transaction(function () use ($items, $referenceType, $referenceId, $notesTemplate) {
            $products = [];
            $productIds = array_column($items, 'product_id');

            // Lock all products at once in a consistent order (by ID) to prevent deadlocks
            $lockedProducts = Product::whereIn('id', $productIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // Verify all products exist
            foreach ($items as $item) {
                if (!isset($lockedProducts[$item['product_id']])) {
                    throw new \Exception("Product with ID {$item['product_id']} not found");
                }
            }

            // Check stock availability for all items first
            foreach ($items as $item) {
                $product = $lockedProducts[$item['product_id']];
                if ($product->stock < $item['quantity']) {
                    throw new \Exception(
                        "Insufficient stock for {$product->name}. Available: {$product->stock}, Requested: {$item['quantity']}"
                    );
                }
            }

            // Now deduct stock for all items
            foreach ($items as $item) {
                $product = $lockedProducts[$item['product_id']];
                
                // Deduct stock
                $product->decrement('stock', $item['quantity']);

                // Create notes
                $notes = $notesTemplate 
                    ? str_replace(
                        ['{product_name}', '{quantity}'],
                        [$product->name, $item['quantity']],
                        $notesTemplate
                    )
                    : "Stock deducted - {$referenceType} #{$referenceId}";

                // Create stock movement record
                StockMovement::create([
                    'product_id' => $product->id,
                    'type' => 'out',
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'] ?? $product->price,
                    'reference_type' => $referenceType,
                    'reference_id' => $referenceId,
                    'notes' => $notes,
                    'serial_number' => $item['serial_number'] ?? null,
                    'created_by' => Auth::id(),
                ]);

                $products[] = $product->fresh();

                Log::info('Stock deducted in batch', [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $item['quantity'],
                    'new_stock' => $product->stock,
                    'reference_type' => $referenceType,
                    'reference_id' => $referenceId,
                ]);
            }

            return $products;
        });
    }

    /**
     * Restore stock for multiple items with pessimistic locking
     *
     * @param array $items Array of ['product_id' => int, 'quantity' => int, 'unit_price' => float, 'serial_number' => string|null]
     * @param string $referenceType
     * @param int $referenceId
     * @param string|null $notesTemplate
     * @return array Array of updated products
     * @throws \Exception
     */
    public function restoreMultipleStock(
        array $items,
        string $referenceType,
        int $referenceId,
        ?string $notesTemplate = null
    ): array {
        return DB::transaction(function () use ($items, $referenceType, $referenceId, $notesTemplate) {
            $products = [];
            $productIds = array_column($items, 'product_id');

            // Lock all products at once in a consistent order (by ID) to prevent deadlocks
            $lockedProducts = Product::whereIn('id', $productIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // Verify all products exist
            foreach ($items as $item) {
                if (!isset($lockedProducts[$item['product_id']])) {
                    throw new \Exception("Product with ID {$item['product_id']} not found");
                }
            }

            // Restore stock for all items
            foreach ($items as $item) {
                $product = $lockedProducts[$item['product_id']];
                
                // Restore stock
                $product->increment('stock', $item['quantity']);

                // Create notes
                $notes = $notesTemplate 
                    ? str_replace(
                        ['{product_name}', '{quantity}'],
                        [$product->name, $item['quantity']],
                        $notesTemplate
                    )
                    : "Stock restored - {$referenceType} #{$referenceId}";

                // Create stock movement record
                StockMovement::create([
                    'product_id' => $product->id,
                    'type' => 'in',
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'] ?? $product->price,
                    'reference_type' => $referenceType,
                    'reference_id' => $referenceId,
                    'notes' => $notes,
                    'serial_number' => $item['serial_number'] ?? null,
                    'created_by' => Auth::id(),
                ]);

                $products[] = $product->fresh();

                Log::info('Stock restored in batch', [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $item['quantity'],
                    'new_stock' => $product->stock,
                    'reference_type' => $referenceType,
                    'reference_id' => $referenceId,
                ]);
            }

            return $products;
        });
    }

    /**
     * Check if sufficient stock is available without locking
     * Use this for UI validation, not for actual transactions
     *
     * @param int $productId
     * @param int $quantity
     * @return bool
     */
    public function hasStock(int $productId, int $quantity): bool
    {
        $product = Product::find($productId);
        return $product && $product->stock >= $quantity;
    }

    /**
     * Get current stock for a product
     *
     * @param int $productId
     * @return int
     */
    public function getCurrentStock(int $productId): int
    {
        $product = Product::find($productId);
        return $product ? $product->stock : 0;
    }
}
