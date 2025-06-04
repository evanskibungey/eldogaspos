<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Customer;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SaleController extends Controller
{
    // Settings helper method removed - using global helper function instead

    /**
     * Display the sales creation page.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $products = Product::where('status', 'active')->get();
        $currencySymbol = $this->getSetting('currency_symbol', '$');
        $taxPercentage = (float)$this->getSetting('tax_percentage', 0);
        $companyName = $this->getSetting('company_name', 'Our Store');
        
        return view('pos.sales.create', compact('products', 'currencySymbol', 'taxPercentage', 'companyName'));
    }

    /**
     * Store a newly created sale in the database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        Log::info('Sale store request:', $request->all());

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
                'customer_details.name' => 'required_if:payment_method,credit|string',
                'customer_details.phone' => 'required_if:payment_method,credit|string'
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

            DB::commit();
            Log::info('DB transaction committed');

            Log::info('Sale completed successfully', ['receipt_number' => $receiptNumber]);

            $currencySymbol = $this->getSetting('currency_symbol', '$');
            $companyName = $this->getSetting('company_name', 'Our Store');
            $receiptFooter = $this->getSetting('receipt_footer', 'Thank you for your business!');

            return response()->json([
                'success' => true,
                'receipt_number' => $receiptNumber,
                'message' => 'Sale completed successfully',
                'sale_id' => $sale->id,
                'receipt_data' => [
                    'company_name' => $companyName,
                    'currency_symbol' => $currencySymbol,
                    'date' => now()->format('Y-m-d H:i:s'),
                    'items' => $request->cart_items,
                    'total' => $totalAmount,
                    'payment_method' => $request->payment_method,
                    'receipt_footer' => $receiptFooter,
                    'customer' => [
                        'name' => $customer->name,
                        'phone' => $customer->phone
                    ]
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            Log::error('Validation error in sale:', $e->errors());
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in sale: ' . $e->getMessage());
            Log::error('Error stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the sale: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display sales history.
     *
     * @return \Illuminate\View\View
     */
    public function history(Request $request)
    {
        // Get settings
        $currencySymbol = $this->getSetting('currency_symbol', '$');
        $companyName = $this->getSetting('company_name', 'Our Store');
        
        // Build query
        $query = Sale::with(['customer', 'items.product']);
        
        // Apply filters
        if ($request->has('date_from') && !empty($request->date_from)) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->has('date_to') && !empty($request->date_to)) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        if ($request->has('payment_method') && !empty($request->payment_method)) {
            $query->where('payment_method', $request->payment_method);
        }
        
        // Filter by user for non-admin users
        if (!auth()->user()->isAdmin()) {
            $query->where('user_id', auth()->id());
        }
        
        // Get paginated results
        $sales = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();
            
        return view('pos.sales.history', compact('sales', 'currencySymbol', 'companyName'));
    }

    /**
     * Display the specified sale.
     *
     * @param  \App\Models\Sale  $sale
     * @return \Illuminate\View\View
     */
    public function show(Sale $sale)
    {
        // Ensure the sale belongs to the authenticated user or user is admin
        if ($sale->user_id !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized action.');
        }
        
        $sale->load(['customer', 'items.product', 'user']);
        
        $currencySymbol = $this->getSetting('currency_symbol', '$');
        $taxPercentage = (float)$this->getSetting('tax_percentage', 0);
        $companyName = $this->getSetting('company_name', 'Our Store');
        $companyAddress = $this->getSetting('company_address', '');
        $companyPhone = $this->getSetting('company_phone', '');
        $companyEmail = $this->getSetting('company_email', '');
        $receiptFooter = $this->getSetting('receipt_footer', 'Thank you for your business!');
        
        return view('pos.sales.show', compact(
            'sale', 
            'currencySymbol', 
            'taxPercentage', 
            'companyName', 
            'companyAddress',
            'companyPhone',
            'companyEmail',
            'receiptFooter'
        ));
    }

    /**
     * Void the specified sale.
     *
     * @param  \App\Models\Sale  $sale
     * @return \Illuminate\Http\RedirectResponse
     */
    public function void(Sale $sale)
    {
        // Ensure the sale belongs to the authenticated user or user is admin
        if ($sale->user_id !== auth()->id() && !auth()->user()->isAdmin()) {
            return back()->with('error', 'Unauthorized action.');
        }
        
        try {
            DB::beginTransaction();
            
            // Update sale status
            $sale->status = 'voided';
            $sale->save();
            
            // Return items to inventory
            foreach ($sale->items as $item) {
                $product = $item->product;
                $product->increment('stock', $item->quantity);
            }
            
            // If this was a credit sale, adjust customer balance
            if ($sale->payment_method === 'credit' && $sale->customer) {
                $sale->customer->decrement('balance', $sale->total_amount);
            }
            
            DB::commit();
            
            return back()->with('success', 'Sale voided successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error voiding sale: ' . $e->getMessage());
            
            return back()->with('error', 'Error voiding sale: ' . $e->getMessage());
        }
    }

    /**
     * Create or retrieve an existing customer.
     *
     * @param  array  $customerDetails
     * @return \App\Models\Customer
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
     * Get or create default walk-in customer.
     *
     * @return \App\Models\Customer
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
     * Calculate the total amount for the sale.
     *
     * @param  array  $cartItems
     * @return float
     */
    private function calculateTotalAmount(array $cartItems)
    {
        $subtotal = collect($cartItems)->sum(function($item) {
            return $item['price'] * $item['quantity'];
        });
        
        // Apply tax if configured
        $taxPercentage = (float)$this->getSetting('tax_percentage', 0);
        if ($taxPercentage > 0) {
            $taxAmount = $subtotal * ($taxPercentage / 100);
            return $subtotal + $taxAmount;
        }
        
        return $subtotal;
    }

    /**
     * Create the sale record in the database.
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
}