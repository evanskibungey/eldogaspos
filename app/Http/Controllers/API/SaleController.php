<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SaleController extends Controller
{
    /**
     * Get settings helper function
     * 
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    protected function getSetting($key = null, $default = null)
    {
        if ($key) {
            return Setting::where('key', $key)->value('value') ?? $default;
        }
        
        return Setting::pluck('value', 'key')->toArray();
    }

    /**
     * Display a listing of sales with optional filters.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $query = Sale::with(['customer', 'user', 'items.product']);
            
            // Apply date range filter
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
            }
            
            // Apply status filter
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            
            // Apply payment method filter
            if ($request->filled('payment_method')) {
                $query->where('payment_method', $request->payment_method);
            }
            
            // Apply payment status filter
            if ($request->filled('payment_status')) {
                $query->where('payment_status', $request->payment_status);
            }
            
            // Apply search filter
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('receipt_number', 'like', "%{$search}%")
                      ->orWhereHas('customer', function($q) use ($search) {
                          $q->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                      });
                });
            }
            
            // Apply limit
            if ($request->filled('limit')) {
                $limit = $request->limit;
                $sales = $query->latest()->take($limit)->get();
                
                return response()->json([
                    'data' => $sales
                ]);
            }
            
            // Paginate results
            $sales = $query->latest()->paginate(10);
            
            return response()->json($sales);
        } catch (\Exception $e) {
            Log::error('Error in sale index: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error retrieving sales: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created sale in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        Log::info('API sale request:', $request->all());

        // Check if this is an offline sync request
        $isOfflineSync = $request->hasHeader('X-Offline-Sync');
        
        if ($isOfflineSync) {
            Log::info('Detected offline sync request - redirecting to sync controller');
            // For offline sync requests, use the dedicated sync controller
            $syncController = new \App\Http\Controllers\Api\OfflineSyncController();
            return $syncController->syncOfflineSale($request);
        }

        try {
            // Validate the request
            Log::info('Validating request');
            // Validate basic request structure first
            $basicValidation = $request->validate([
                'cart_items' => 'required|array|min:1',
                'cart_items.*.id' => 'required|exists:products,id',
                'cart_items.*.quantity' => 'required|integer|min:1',
                'cart_items.*.price' => 'required|numeric|min:0',
                'cart_items.*.serial_number' => 'nullable|string|max:255',
                'payment_method' => 'required|in:cash,credit',
            ]);
            
            // Additional validation for credit payments
            if ($request->payment_method === 'credit') {
                $request->validate([
                    'customer_details' => 'required|array',
                    'customer_details.customer_id' => 'nullable|exists:customers,id',
                    'customer_details.name' => 'required_without:customer_details.customer_id|string|max:255',
                    'customer_details.phone' => 'required_without:customer_details.customer_id|string|max:20'
                ]);
            }
            Log::info('Request validated successfully');

            // Pre-validate stock availability before starting transaction
            foreach ($request->cart_items as $item) {
                $product = Product::find($item['id']);
                if (!$product) {
                    throw new \Exception("Product with ID {$item['id']} not found");
                }
                if ($product->stock < $item['quantity']) {
                    throw new \Exception("Insufficient stock for {$product->name}. Available: {$product->stock}, Requested: {$item['quantity']}");
                }
            }
            Log::info('Stock availability pre-check passed');

            DB::beginTransaction();

            // Handle customer based on payment method
            $customer = null;
            if ($request->payment_method === 'credit') {
                // For credit payment, create or get customer from provided details
                Log::info('Processing credit payment, handling customer creation');
                $customer = $this->handleCustomerCreation($request->customer_details);
                Log::info('Customer created/found', ['customer_id' => $customer->id]);
            } else {
                // For cash payment, use default walk-in customer or create if doesn't exist
                Log::info('Processing cash payment, using default walk-in customer');
                $customer = $this->getOrCreateWalkInCustomer();
                Log::info('Walk-in customer used', ['customer_id' => $customer->id]);
            }

            // Generate unique receipt number
            $receiptNumber = $this->generateReceiptNumber();
            Log::info('Receipt number generated', ['receipt_number' => $receiptNumber]);

            // Calculate total amount
            $totalAmount = $this->calculateTotalAmount($request->cart_items);
            Log::info('Total amount calculated', ['total' => $totalAmount]);

            // Create sale record
            Log::info('Creating sale record');
            $sale = $this->createSaleRecord([
                'user_id' => auth()->id(),
                'customer_id' => $customer->id, // Always associate a customer
                'receipt_number' => $receiptNumber,
                'total_amount' => $totalAmount,
                'payment_method' => $request->payment_method,
                'payment_status' => $request->payment_method === 'cash' ? 'paid' : 'pending',
                'status' => 'completed'
            ]);
            Log::info('Sale record created', ['sale_id' => $sale->id]);

            // Process cart items
            Log::info('Processing cart items');
            $this->processCartItems($request->cart_items, $sale->id);
            Log::info('Cart items processed successfully');

            // Update customer balance for credit sales
            if ($request->payment_method === 'credit') {
                Log::info('Updating customer balance');
                $this->updateCustomerBalance($customer, $totalAmount);
                Log::info('Customer balance updated');
            }
            
            // Fetch the sale items with their serial numbers for the receipt
            $saleItems = SaleItem::where('sale_id', $sale->id)
                ->with('product')
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->product_id,
                        'name' => $item->product->name,
                        'quantity' => $item->quantity,
                        'price' => $item->unit_price,
                        'subtotal' => $item->subtotal,
                        'serial_number' => $item->serial_number // Include the saved serial number
                    ];
                });

            DB::commit();
            Log::info('DB transaction committed');

            Log::info('Sale completed successfully', ['receipt_number' => $receiptNumber]);

            return response()->json([
                'success' => true,
                'receipt_number' => $receiptNumber,
                'message' => 'Sale completed successfully',
                'sale_id' => $sale->id,
                'customer' => $customer ? [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                    'balance' => $customer->balance
                ] : null,
                'receipt_data' => [
                    'date' => now()->format('Y-m-d H:i:s'),
                    'items' => $saleItems, // Use the processed items with serial numbers
                    'total' => $totalAmount,
                    'payment_method' => $request->payment_method,
                    'customer' => [
                        'name' => $customer->name,
                        'phone' => $customer->phone
                    ]
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            Log::error('Validation error in API sale:', $e->errors());
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors(),
                'error_type' => 'validation'
            ], 422);
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            Log::error('Database error in API sale: ' . $e->getMessage());
            Log::error('SQL Error Code: ' . $e->getCode());
            return response()->json([
                'success' => false,
                'message' => 'Database error occurred while processing the sale. Please try again.',
                'error_type' => 'database',
                'error_code' => $e->getCode()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in API sale: ' . $e->getMessage());
            Log::error('Error in API sale stack trace: ' . $e->getTraceAsString());
            
            // Provide more user-friendly error messages
            $userMessage = $e->getMessage();
            if (str_contains($e->getMessage(), 'Insufficient stock')) {
                $userMessage = $e->getMessage();
            } elseif (str_contains($e->getMessage(), 'not found')) {
                $userMessage = 'One or more products in your cart are no longer available.';
            } else {
                $userMessage = 'An unexpected error occurred while processing the sale. Please try again.';
            }
            
            return response()->json([
                'success' => false,
                'message' => $userMessage,
                'error_type' => 'general'
            ], 500);
        }
    }

    /**
     * Display the specified sale.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $sale = Sale::with(['customer', 'user', 'items.product'])->findOrFail($id);
            
            return response()->json($sale);
        } catch (\Exception $e) {
            Log::error('Error showing sale: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error retrieving sale: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark a sale as voided.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function void(Request $request, $id)
    {
        try {
            $request->validate([
                'reason' => 'required|string|max:255'
            ]);
            
            DB::beginTransaction();
            
            $sale = Sale::with('items.product')->findOrFail($id);
            
            // Check if sale is already voided
            if ($sale->status === 'voided') {
                return response()->json([
                    'success' => false,
                    'message' => 'Sale is already voided'
                ], 422);
            }
            
            // Update sale status
            $sale->status = 'voided';
            $sale->void_reason = $request->reason;
            $sale->voided_by = auth()->id();
            $sale->voided_at = now();
            $sale->save();
            
            // Restore stock for each item
            foreach ($sale->items as $item) {
                $item->product->increment('stock', $item->quantity);
            }
            
            // If it was a credit sale, update customer balance
            if ($sale->payment_method === 'credit' && $sale->payment_status !== 'paid') {
                $customer = Customer::find($sale->customer_id);
                if ($customer) {
                    $customer->decrement('balance', $sale->total_amount);
                }
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Sale voided successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error voiding sale: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error voiding sale: ' . $e->getMessage()
            ], 500);
        }
    }
    public function recentSales(Request $request)
{
    try {
        $limit = $request->input('limit', 5);
        
        $sales = Sale::with(['customer', 'user', 'items.product'])
            ->where('status', '!=', 'voided')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(function ($sale) {
                return [
                    'id' => $sale->id,
                    'receipt_number' => $sale->receipt_number,
                    'customer_name' => $sale->customer->name,
                    'total_amount' => $sale->total_amount,
                    'payment_method' => $sale->payment_method,
                    'payment_status' => $sale->payment_status,
                    'created_at' => $sale->created_at->format('Y-m-d H:i:s'),
                    'items_count' => $sale->items->count(),
                ];
            });
        
        return response()->json([
            'data' => $sales
        ]);
    } catch (\Exception $e) {
        Log::error('Error fetching recent sales: ' . $e->getMessage());
        return response()->json([
            'message' => 'Error fetching recent sales'
        ], 500);
    }
}

    /**
     * Create or get customer based on details.
     *
     * @param  array  $customerDetails
     * @return \App\Models\Customer
     */
    private function handleCustomerCreation(array $customerDetails)
    {
        try {
            // If customer_id is provided, use existing customer
            if (!empty($customerDetails['customer_id'])) {
                $customer = Customer::find($customerDetails['customer_id']);
                
                if (!$customer) {
                    throw new \Exception('Selected customer not found');
                }
                
                return $customer;
            }
            
            // Otherwise, create or find customer by phone (existing behavior)
            return Customer::firstOrCreate(
                ['phone' => $customerDetails['phone']],
                [
                    'name' => $customerDetails['name'],
                    'status' => 'active'
                ]
            );
        } catch (\Exception $e) {
            Log::error('Error handling customer: ' . $e->getMessage());
            throw new \Exception('Failed to process customer record');
        }
    }

    /**
     * Get or create a default walk-in customer.
     *
     * @return \App\Models\Customer
     */
    private function getOrCreateWalkInCustomer()
    {
        try {
            return Customer::firstOrCreate(
                ['phone' => '0000000000'], 
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
     * Generate a unique receipt number.
     *
     * @return string
     */
    private function generateReceiptNumber()
    {
        $prefix = 'RCP-' . date('Ymd');
        $random = strtoupper(Str::random(5));
        return $prefix . '-' . $random;
    }

    /**
     * Calculate the total amount for a sale.
     *
     * @param  array  $cartItems
     * @return float
     */
    private function calculateTotalAmount(array $cartItems)
    {
        return collect($cartItems)->sum(function ($item) {
            return $item['price'] * $item['quantity'];
        });
    }

    /**
     * Create a new sale record.
     *
     * @param  array  $saleData
     * @return \App\Models\Sale
     */
    private function createSaleRecord(array $saleData)
    {
        try {
            return Sale::create($saleData);
        } catch (\Exception $e) {
            Log::error('Error creating sale record: ' . $e->getMessage());
            throw new \Exception('Failed to create sale record');
        }
    }

    /**
     * Process cart items and create sale items.
     *
     * @param  array  $cartItems
     * @param  int  $saleId
     * @return void
     */
    private function processCartItems(array $cartItems, int $saleId)
    {
        foreach ($cartItems as $item) {
            $product = Product::findOrFail($item['id']);

            // Verify stock availability
            if ($product->stock < $item['quantity']) {
                throw new \Exception("Insufficient stock for {$product->name}");
            }

            try {
                // Use the serial number from the item if available, otherwise use it from the product
                // If neither has a serial number, leave it null (field is now nullable)
                $serialNumber = null;
                if (isset($item['serial_number']) && !empty($item['serial_number'])) {
                    $serialNumber = $item['serial_number'];
                } elseif (isset($product->serial_number) && !empty($product->serial_number)) {
                    $serialNumber = $product->serial_number;
                }

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

    /**
     * Update customer balance for credit sales.
     *
     * @param  \App\Models\Customer  $customer
     * @param  float  $amount
     * @return void
     */
    private function updateCustomerBalance(Customer $customer, float $amount)
    {
        try {
            $customer->increment('balance', $amount);
        } catch (\Exception $e) {
            Log::error('Error updating customer balance: ' . $e->getMessage());
            throw new \Exception('Failed to update customer balance');
        }
    }
    
    /**
     * Check stock availability for a product.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function checkStock(Request $request)
    {
        try {
            $validated = $request->validate([
                'product_id' => 'required|exists:products,id',
                'quantity' => 'required|integer|min:1'
            ]);

            $product = Product::findOrFail($request->product_id);
            
            return response()->json([
                'success' => true,
                'available' => $product->stock >= $request->quantity,
                'current_stock' => $product->stock
            ]);
        } catch (\Exception $e) {
            Log::error('Error checking stock: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error checking stock availability'
            ], 422);
        }
    }
}