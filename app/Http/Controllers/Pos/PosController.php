<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Customer;
use App\Models\Category;
use App\Models\Setting;
use App\Models\CylinderTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PosController extends Controller
{
    /**
     * Get a setting value.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    private function getSetting($key, $default = null)
    {
        return Setting::get($key, $default);
    }
    public function index()
    {
        try {
            Log::info('POS Dashboard: Starting to load products');
            
            // Get all active products with their categories
            $products = Product::with('category')
                ->where('status', 'active')
                ->get();
                
            Log::info('POS Dashboard: Found ' . $products->count() . ' active products');
                
            // Get all categories for filtering
            $categories = Category::where('status', 'active')->get();
            
            Log::info('POS Dashboard: Found ' . $categories->count() . ' active categories');
            
            // Get cylinder statistics for POS dashboard
            $cylinderStats = $this->getCylinderStatistics();
                
            // Format product data for frontend display
            $formattedProducts = $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'category_id' => $product->category_id,
                    'sku' => $product->sku,
                    'serial_number' => $product->serial_number,
                    'price' => (float)$product->price,
                    'stock' => $product->stock,
                    'min_stock' => $product->min_stock,
                    // Format image URL properly
                    'image' => $product->image ? asset('storage/' . $product->image) : asset('images/placeholder.jpg'),
                    'status' => $product->status,
                    'category_name' => $product->category ? $product->category->name : 'Uncategorized'
                ];
            });
            
            // Add debugging info if no products found
            if ($products->count() === 0) {
                Log::warning('POS Dashboard: No active products found');
                
                // Check if there are any products at all
                $totalProducts = Product::count();
                $inactiveProducts = Product::where('status', 'inactive')->count();
                $nullStatusProducts = Product::whereNull('status')->count();
                
                Log::info('POS Dashboard Debug: Total products=' . $totalProducts . ', Inactive=' . $inactiveProducts . ', Null status=' . $nullStatusProducts);
            }

            return view('pos.dashboard', [
                'products' => $formattedProducts,
                'categories' => $categories,
                'cylinderStats' => $cylinderStats
            ]);
        } catch (\Exception $e) {
            Log::error('Error in POS index: ' . $e->getMessage());
            Log::error('POS index stack trace: ' . $e->getTraceAsString());
            return back()->with('error', 'Error loading products. Please try again.');
        }
    }

    public function store(Request $request)
    {
        Log::info('POS sale request:', $request->all());

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
            $validated = $request->validate([
                'cart_items' => 'required|array',
                'cart_items.*.id' => 'required|exists:products,id',
                'cart_items.*.quantity' => 'required|integer|min:1',
                'cart_items.*.price' => 'required|numeric|min:0',
                'payment_method' => 'required|in:cash,credit',
                'customer_details' => 'required_if:payment_method,credit',
                'customer_details.customer_id' => 'nullable|exists:customers,id',
                'customer_details.name' => 'required_if:payment_method,credit|required_without:customer_details.customer_id|nullable|string',
                'customer_details.phone' => 'required_if:payment_method,credit|required_without:customer_details.customer_id|nullable|string'
            ]);
            Log::info('Request validated successfully');

            DB::beginTransaction();
            Log::info('DB transaction started');

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
            Log::error('Validation error in POS sale:', $e->errors());
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in POS sale: ' . $e->getMessage());
            Log::error('Error in POS sale stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the sale: ' . $e->getMessage()
            ], 500);
        }
    }

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

    private function generateReceiptNumber()
    {
        $prefix = 'RCP-' . date('Ymd');
        $random = strtoupper(Str::random(5));
        return $prefix . '-' . $random;
    }

    private function calculateTotalAmount(array $cartItems)
    {
        return collect($cartItems)->sum(function ($item) {
            return $item['price'] * $item['quantity'];
        });
    }

    private function createSaleRecord(array $saleData)
    {
        try {
            return Sale::create($saleData);
        } catch (\Exception $e) {
            Log::error('Error creating sale record: ' . $e->getMessage());
            throw new \Exception('Failed to create sale record');
        }
    }

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

    private function updateCustomerBalance(Customer $customer, float $amount)
    {
        try {
            $customer->increment('balance', $amount);
        } catch (\Exception $e) {
            Log::error('Error updating customer balance: ' . $e->getMessage());
            throw new \Exception('Failed to update customer balance');
        }
    }

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

    public function debugSale(Request $request)
    {
        try {
            // Sample cart items
            $cartItems = [
                [
                    'id' => 1, // Replace with an actual product ID
                    'quantity' => 1,
                    'price' => 10.00
                ]
            ];

            // Begin transaction
            DB::beginTransaction();

            // Get default customer
            $customer = $this->getOrCreateWalkInCustomer();

            // Generate receipt number
            $receiptNumber = $this->generateReceiptNumber();

            // Calculate total amount
            $totalAmount = $this->calculateTotalAmount($cartItems);

            // Create sale record
            $sale = $this->createSaleRecord([
                'user_id' => auth()->id(),
                'customer_id' => $customer->id,
                'receipt_number' => $receiptNumber,
                'total_amount' => $totalAmount,
                'payment_method' => 'cash',
                'payment_status' => 'paid',
                'status' => 'completed'
            ]);

            // Process cart items
            $this->processCartItems($cartItems, $sale->id);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Debug sale successful'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Get cylinder transaction statistics for POS dashboard
     */
    private function getCylinderStatistics()
    {
        return [
            'active_drop_offs' => CylinderTransaction::active()->dropOffs()->count(),
            'active_advance_collections' => CylinderTransaction::active()->advanceCollections()->count(),
            'today_completed' => CylinderTransaction::whereDate('collection_date', Carbon::today())
                               ->orWhereDate('return_date', Carbon::today())
                               ->count(),
        ];
    }
}