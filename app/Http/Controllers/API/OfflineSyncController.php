<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Customer;
use App\Models\Setting;
use App\Models\OfflineSyncLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class OfflineSyncController extends Controller
{
    /**
     * Get products for offline use
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProductsForOffline()
    {
        try {
            $products = Product::with('category')
                ->where('status', 'active')
                ->select([
                    'id', 'name', 'sku', 'serial_number', 'price', 
                    'stock', 'min_stock', 'category_id', 'image', 'status'
                ])
                ->get()
                ->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'category_id' => $product->category_id,
                        'sku' => $product->sku,
                        'serial_number' => $product->serial_number,
                        'price' => (float)$product->price,
                        'stock' => $product->stock,
                        'min_stock' => $product->min_stock,
                        'image' => $product->image ? asset('storage/' . $product->image) : asset('images/placeholder.jpg'),
                        'status' => $product->status,
                        'category_name' => $product->category ? $product->category->name : 'Uncategorized',
                        'last_updated' => $product->updated_at->toISOString()
                    ];
                });

            return response()->json($products);
        } catch (\Exception $e) {
            Log::error('Error fetching products for offline use: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to fetch products',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sync offline sale to server
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function syncOfflineSale(Request $request)
    {
        Log::info('Syncing offline sale:', $request->all());

        try {
            // Validate the request
            $validated = $request->validate([
                'cart_items' => 'required|array',
                'cart_items.*.id' => 'required|exists:products,id',
                'cart_items.*.quantity' => 'required|integer|min:1',
                'cart_items.*.price' => 'required|numeric|min:0',
                'payment_method' => 'required|in:cash,credit',
                'customer_details' => 'nullable|array',
                'customer_details.name' => 'required_if:payment_method,credit|string',
                'customer_details.phone' => 'required_if:payment_method,credit|string',
                'offline_receipt_number' => 'required|string',
                'offline_created_at' => 'required|date',
                'user_id' => 'nullable|exists:users,id'
            ]);

            DB::beginTransaction();

            // Check if this offline sale has already been synced
            $existingSyncLog = OfflineSyncLog::where('offline_receipt_number', $request->offline_receipt_number)
                ->where('sync_status', 'synced')
                ->first();
                
            if ($existingSyncLog) {
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'Sale already synced',
                    'sale_id' => $existingSyncLog->sale_id,
                    'server_receipt_number' => $existingSyncLog->server_receipt_number
                ]);
            }

            // Create or update sync log
            $syncLog = OfflineSyncLog::firstOrCreate(
                ['offline_receipt_number' => $request->offline_receipt_number],
                [
                    'sync_status' => 'pending',
                    'original_data' => $request->all(),
                    'offline_created_at' => Carbon::parse($request->offline_created_at),
                    'sync_attempts' => 0
                ]
            );

            $syncLog->incrementSyncAttempts();

            try {
                // Handle customer
                $customer = null;
                if ($request->payment_method === 'credit') {
                    $customer = $this->handleCustomerCreation($request->customer_details);
                } else {
                    $customer = $this->getOrCreateWalkInCustomer();
                }

                // Generate new receipt number for server
                $serverReceiptNumber = $this->generateReceiptNumber();

                // Validate stock availability
                $this->validateStockAvailability($request->cart_items);

                // Calculate total amount
                $totalAmount = $this->calculateTotalAmount($request->cart_items);

                // Get user ID from the request or use the authenticated user
                $userId = $request->input('user_id', auth()->id());
                
                if (!$userId) {
                    throw new \Exception('User ID is required for offline sync');
                }
                
                // Create sale record with offline sync flags
                $sale = Sale::create([
                    'user_id' => $userId,
                    'customer_id' => $customer->id,
                    'receipt_number' => $serverReceiptNumber,
                    'total_amount' => $totalAmount,
                    'payment_method' => $request->payment_method,
                    'payment_status' => $request->payment_method === 'cash' ? 'paid' : 'pending',
                    'status' => 'completed',
                    'notes' => "Synced from offline. Original receipt: {$request->offline_receipt_number}. Created: {$request->offline_created_at}",
                    'is_offline_sync' => true,
                    'offline_receipt_number' => $request->offline_receipt_number,
                    'offline_created_at' => Carbon::parse($request->offline_created_at),
                    'created_at' => Carbon::parse($request->offline_created_at),
                    'updated_at' => now()
                ]);

                // Process cart items
                $this->processCartItems($request->cart_items, $sale->id);

                // Update customer balance for credit sales
                if ($request->payment_method === 'credit') {
                    $customer->increment('balance', $totalAmount);
                }

                // Mark sync log as successful
                $syncLog->markAsSynced($sale->id, $serverReceiptNumber);

                // Log the sync
                Log::info('Offline sale synced successfully', [
                    'offline_receipt' => $request->offline_receipt_number,
                    'server_receipt' => $serverReceiptNumber,
                    'sale_id' => $sale->id,
                    'sync_log_id' => $syncLog->id
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Offline sale synced successfully',
                    'sale_id' => $sale->id,
                    'server_receipt_number' => $serverReceiptNumber,
                    'original_receipt_number' => $request->offline_receipt_number,
                    'sync_log_id' => $syncLog->id
                ]);

            } catch (\Exception $e) {
                // Mark sync log as failed
                $syncLog->markAsFailed($e->getMessage());
                throw $e;
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollback();
            Log::error('Validation error syncing offline sale:', $e->errors());
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error syncing offline sale: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync offline sale: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Batch sync multiple offline sales
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function batchSyncOfflineSales(Request $request)
    {
        try {
            $validated = $request->validate([
                'sales' => 'required|array|max:50', // Limit to 50 sales per batch
                'sales.*.cart_items' => 'required|array',
                'sales.*.payment_method' => 'required|in:cash,credit',
                'sales.*.offline_receipt_number' => 'required|string',
                'sales.*.offline_created_at' => 'required|date'
            ]);

            $results = [];
            $successCount = 0;
            $failureCount = 0;

            foreach ($validated['sales'] as $saleData) {
                try {
                    // Create a new request object for each sale
                    $saleRequest = new Request($saleData);
                    $response = $this->syncOfflineSale($saleRequest);
                    $responseData = json_decode($response->getContent(), true);
                    
                    if ($responseData['success']) {
                        $successCount++;
                    } else {
                        $failureCount++;
                    }
                    
                    $results[] = [
                        'offline_receipt_number' => $saleData['offline_receipt_number'],
                        'success' => $responseData['success'],
                        'message' => $responseData['message']
                    ];
                    
                } catch (\Exception $e) {
                    $failureCount++;
                    $results[] = [
                        'offline_receipt_number' => $saleData['offline_receipt_number'],
                        'success' => false,
                        'message' => $e->getMessage()
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Batch sync completed. Success: {$successCount}, Failed: {$failureCount}",
                'summary' => [
                    'total' => count($validated['sales']),
                    'success' => $successCount,
                    'failed' => $failureCount
                ],
                'results' => $results
            ]);

        } catch (\Exception $e) {
            Log::error('Error in batch sync: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Batch sync failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get sync status and statistics
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSyncStatus()
    {
        try {
            $todayStart = Carbon::today();
            $todayEnd = Carbon::today()->endOfDay();

            // Get today's sales (including offline synced ones)
            $todaySales = Sale::whereBetween('created_at', [$todayStart, $todayEnd])->count();
            $todayRevenue = Sale::whereBetween('created_at', [$todayStart, $todayEnd])->sum('total_amount');

            // Get offline synced sales
            $offlineSyncedSales = Sale::offlineSync()
                ->whereBetween('created_at', [$todayStart, $todayEnd])
                ->count();

            // Get sync log statistics
            $pendingSyncCount = OfflineSyncLog::pending()->count();
            $failedSyncCount = OfflineSyncLog::failed()->count();
            $totalSyncedCount = OfflineSyncLog::synced()->count();

            return response()->json([
                'success' => true,
                'sync_status' => [
                    'server_time' => now()->toISOString(),
                    'today_sales' => $todaySales,
                    'today_revenue' => (float)$todayRevenue,
                    'offline_synced_sales' => $offlineSyncedSales,
                    'pending_sync_count' => $pendingSyncCount,
                    'failed_sync_count' => $failedSyncCount,
                    'total_synced_count' => $totalSyncedCount,
                    'last_sync_check' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting sync status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get sync status'
            ], 500);
        }
    }

    /**
     * Get failed sync logs that need attention
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFailedSyncs()
    {
        try {
            $failedSyncs = OfflineSyncLog::failed()
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get([
                    'id',
                    'offline_receipt_number',
                    'error_message',
                    'sync_attempts',
                    'offline_created_at',
                    'created_at'
                ]);

            return response()->json([
                'success' => true,
                'failed_syncs' => $failedSyncs
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting failed syncs: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get failed syncs'
            ], 500);
        }
    }

    /**
     * Retry a failed sync
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function retrySyncLog(Request $request)
    {
        try {
            $validated = $request->validate([
                'sync_log_id' => 'required|exists:offline_sync_logs,id'
            ]);

            $syncLog = OfflineSyncLog::find($validated['sync_log_id']);
            
            if ($syncLog->sync_status === 'synced') {
                return response()->json([
                    'success' => false,
                    'message' => 'This sync log is already synced'
                ]);
            }

            // Reset sync log status and retry
            $syncLog->update([
                'sync_status' => 'pending',
                'error_message' => null
            ]);

            // Create a new request from the original data
            $originalData = $syncLog->original_data;
            $retryRequest = new Request($originalData);
            
            return $this->syncOfflineSale($retryRequest);

        } catch (\Exception $e) {
            Log::error('Error retrying sync: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retry sync: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle customer creation or retrieval
     */
    private function handleCustomerCreation(array $customerDetails)
    {
        try {
            return Customer::firstOrCreate(
                ['phone' => $customerDetails['phone']],
                [
                    'name' => $customerDetails['name'],
                    'status' => 'active'
                ]
            );
        } catch (\Exception $e) {
            Log::error('Error creating customer: ' . $e->getMessage());
            throw new \Exception('Failed to create customer record');
        }
    }

    /**
     * Get or create walk-in customer
     */
    private function getOrCreateWalkInCustomer()
    {
        try {
            return Customer::firstOrCreate(
                ['phone' => '0000000000'], // Default phone for walk-in customer
                [
                    'name' => 'Walk-in Customer',
                    'status' => 'active'
                ]
            );
        } catch (\Exception $e) {
            Log::error('Error creating walk-in customer: ' . $e->getMessage());
            throw new \Exception('Failed to create walk-in customer record');
        }
    }

    /**
     * Generate receipt number
     */
    private function generateReceiptNumber()
    {
        $prefix = 'RCP-' . date('Ymd');
        $random = strtoupper(Str::random(5));
        return $prefix . '-' . $random;
    }

    /**
     * Validate stock availability
     */
    private function validateStockAvailability(array $cartItems)
    {
        foreach ($cartItems as $item) {
            $product = Product::findOrFail($item['id']);
            
            if ($product->stock < $item['quantity']) {
                throw new \Exception("Insufficient stock for {$product->name}. Available: {$product->stock}, Requested: {$item['quantity']}");
            }
        }
    }

    /**
     * Calculate total amount
     */
    private function calculateTotalAmount(array $cartItems)
    {
        return collect($cartItems)->sum(function ($item) {
            return $item['price'] * $item['quantity'];
        });
    }

    /**
     * Process cart items and create sale items
     */
    private function processCartItems(array $cartItems, int $saleId)
    {
        foreach ($cartItems as $item) {
            $product = Product::findOrFail($item['id']);

            try {
                // Use the serial number from the item if available, otherwise use it from the product
                $serialNumber = isset($item['serial_number']) ? $item['serial_number'] : (isset($product->serial_number) ? $product->serial_number : null);

                SaleItem::create([
                    'sale_id' => $saleId,
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                    'subtotal' => $item['price'] * $item['quantity'],
                    'serial_number' => $serialNumber
                ]);

                // Update product stock
                $product->decrement('stock', $item['quantity']);
            } catch (\Exception $e) {
                Log::error('Error processing cart item: ' . $e->getMessage());
                Log::error('Item data: ' . json_encode($item));
                Log::error('Product data: ' . json_encode($product->toArray()));
                throw new \Exception('Failed to process cart item: ' . $e->getMessage());
            }
        }
    }
}