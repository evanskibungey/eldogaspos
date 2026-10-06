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
use App\Models\SmsLog;
use App\Services\StockService;
use App\Services\ReferenceNumberService;
use App\Services\OrderNumberService;
use App\Services\Sms\PhoneNumber;
use App\Services\Sms\ReceiptMessage;
use App\Services\Sms\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PosController extends Controller
{
    protected $stockService;
    protected $referenceNumberService;
    protected $orderNumberService;

    public function __construct(
        StockService $stockService,
        ReferenceNumberService $referenceNumberService,
        OrderNumberService $orderNumberService
    ) {
        $this->stockService = $stockService;
        $this->referenceNumberService = $referenceNumberService;
        $this->orderNumberService = $orderNumberService;
    }

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
            $salesStats = $this->getTodaySalesStatistics();
                
            // Format product data for frontend display
            $formattedProducts = $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'category_id' => $product->category_id,
                    'sku' => $product->sku,
                    'serial_number' => $product->serial_number,
                    'price' => (float)$product->price,
                    // `stock` is what the terminal validates and displays, so it
                    // carries the sellable figure - physical stock minus units
                    // reserved for cylinder collections awaiting pickup.
                    'stock' => $product->available_stock,
                    'physical_stock' => (int) $product->stock,
                    'reserved_stock' => (int) $product->reserved_stock,
                    'min_stock' => $product->min_stock,
                    'is_cylinder' => $product->isCylinder(),
                    'cylinder_size_kg' => $product->cylinder_size_kg !== null ? (float) $product->cylinder_size_kg : null,
                    'cylinder_size' => $product->cylinder_size_label,
                    'out_of_stock' => $product->isOutOfStock(),
                    'image' => $product->image ? asset('storage/' . $product->image) : asset('images/placeholder.jpg'),
                    'status' => $product->status,
                    'category_name' => $product->category ? $product->category->name : 'Uncategorized'
                ];
            });
            
            // Add debugging info if no products found
            if ($products->count() === 0) {
                Log::warning('POS Dashboard: No active products found');
                
                $totalProducts = Product::count();
                $inactiveProducts = Product::where('status', 'inactive')->count();
                $nullStatusProducts = Product::whereNull('status')->count();
                
                Log::info('POS Dashboard Debug: Total products=' . $totalProducts . ', Inactive=' . $inactiveProducts . ', Null status=' . $nullStatusProducts);
            }

            return view('pos.dashboard', [
                'products' => $formattedProducts,
                'categories' => $categories,
                'cylinderStats' => $cylinderStats,
                'salesStats' => $salesStats,
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

        // Offline sales are queued in the browser and replayed with this header.
        // The receiver has never existed, so the request used to instantiate a
        // missing class and die as an uncaught Error. Fail cleanly instead.
        if ($request->hasHeader('X-Offline-Sync')) {
            Log::warning('Offline sync sale rejected - no sync receiver is implemented');

            return response()->json([
                'success' => false,
                'message' => 'Offline sale synchronisation is not available on this server. '
                    . 'The queued sale has been kept and can be retried once syncing is enabled.',
                'error_type' => 'offline_sync_unavailable',
            ], 503);
        }

        try {
            // Validate the request
            Log::info('Validating request');
            // Note: prices are NOT taken from the request. The client may send
            // them for display purposes, but every figure that reaches the
            // database is read from the products table under lock below.
            $basicValidation = $request->validate([
                'cart_items' => 'required|array|min:1',
                'cart_items.*.id' => 'required|exists:products,id',
                'cart_items.*.quantity' => 'required|integer|min:1',
                'cart_items.*.serial_number' => 'nullable|string|max:255',
                'payment_method' => 'required|in:cash,credit',
                'idempotency_key' => 'nullable|string|max:64',
            ]);

            // Quick-sale completes a transaction on a single tap, with no cart
            // to review and no confirm step. A double-click, an impatient second
            // tap or a retried request would otherwise deduct stock and take
            // money twice, so a repeat of the same key returns the original sale
            // rather than making another.
            if ($request->filled('idempotency_key')) {
                $existing = Sale::where('idempotency_key', $request->input('idempotency_key'))->first();

                if ($existing) {
                    Log::info('Duplicate sale suppressed by idempotency key', [
                        'sale_id' => $existing->id,
                        'idempotency_key' => $request->input('idempotency_key'),
                    ]);

                    return response()->json($this->saleResponsePayload($existing));
                }
            }

            // Customer details are mandatory on credit - the balance has to be
            // owed by someone - and optional on cash, where naming the customer
            // is what earns them an SMS receipt instead of the anonymous
            // walk-in placeholder. Either way, whatever is supplied must be
            // complete enough to identify a person.
            $customerRules = [
                'customer_details.customer_id' => 'nullable|exists:customers,id',
                'customer_details.name' => 'required_without:customer_details.customer_id|string|max:255',
                'customer_details.phone' => 'required_without:customer_details.customer_id|string|max:20',
            ];

            if ($request->payment_method === 'credit') {
                $request->validate(array_merge(
                    ['customer_details' => 'required|array'],
                    $customerRules
                ));
            } elseif ($this->hasCustomerDetails($request)) {
                $request->validate(array_merge(
                    ['customer_details' => 'array'],
                    $customerRules
                ));
            }
            Log::info('Request validated successfully');

            DB::beginTransaction();
            Log::info('DB transaction started');

            // Attach the sale to a named customer whenever one was given. Cash
            // sales may now name a customer too; without one they fall back to
            // the shared walk-in placeholder as before.
            if ($request->payment_method === 'credit' || $this->hasCustomerDetails($request)) {
                $customer = $this->handleCustomerCreation($request->customer_details);
                Log::info('Customer created/found', ['customer_id' => $customer->id]);
            } else {
                $customer = $this->getOrCreateWalkInCustomer();
                Log::info('Walk-in customer used', ['customer_id' => $customer->id]);
            }

            // Generate unique receipt number with locking
            $receiptNumber = $this->referenceNumberService->generateReceiptNumber();
            Log::info('Receipt number generated', ['receipt_number' => $receiptNumber]);

            // The sale row is created before the total is known because the
            // stock movements written during deduction reference its id. The
            // total and order number are filled in below, inside the same
            // transaction, so no partial state is ever visible.
            Log::info('Creating sale record');
            $sale = $this->createSaleRecord([
                'user_id' => auth()->id(),
                'customer_id' => $customer->id,
                'receipt_number' => $receiptNumber,
                'idempotency_key' => $request->input('idempotency_key'),
                'total_amount' => 0,
                'payment_method' => $request->payment_method,
                'payment_status' => $request->payment_method === 'cash' ? 'paid' : 'pending',
                'status' => Sale::STATUS_COMPLETED,
            ]);
            Log::info('Sale record created', ['sale_id' => $sale->id]);

            // Deducts under row locks, validates against sellable stock, and
            // hands back the pre-deduction levels the order numbers are built
            // from. Throws if any line is short - the catch blocks roll back.
            Log::info('Processing cart items with stock deduction');
            [$saleItems, $totalAmount, $orderNumber] = $this->processCartItems(
                $request->cart_items,
                $sale
            );
            Log::info('Cart items processed', [
                'total' => $totalAmount,
                'order_number' => $orderNumber,
            ]);

            $sale->update([
                'total_amount' => $totalAmount,
                'order_number' => $orderNumber,
            ]);

            // Update customer balance for credit sales
            if ($request->payment_method === 'credit') {
                Log::info('Updating customer balance');
                $this->updateCustomerBalance($customer, $totalAmount);
                Log::info('Customer balance updated');
            }

            DB::commit();
            Log::info('DB transaction committed');

            // After the commit, never inside it: a queued receipt must not be
            // able to roll the sale back, and the job must not read a sale that
            // does not exist yet.
            $this->sendSaleReceipt($sale, $customer);

            Log::info('Sale completed successfully', [
                'receipt_number' => $receiptNumber,
                'order_number' => $orderNumber,
            ]);

            return response()->json([
                'success' => true,
                'receipt_number' => $receiptNumber,
                'order_number' => $orderNumber,
                'message' => 'Sale completed successfully',
                'sale_id' => $sale->id,
                'customer' => $customer ? [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                    'balance' => $customer->fresh()->balance
                ] : null,
                'receipt_data' => [
                    'date' => now()->format('Y-m-d H:i:s'),
                    'items' => $saleItems,
                    'total' => $totalAmount,
                    'order_number' => $orderNumber,
                    'payment_method' => $request->payment_method,
                    'customer' => [
                        'name' => $customer->name,
                        'phone' => $customer->phone
                    ]
                ]
            ]);
        } catch (\App\Exceptions\InsufficientStockException $e) {
            DB::rollBack();
            Log::warning('POS sale refused - insufficient stock: ' . $e->getMessage());

            // 422: the cashier can fix this by changing the cart, so the real
            // reason goes back verbatim rather than being masked as a 500.
            return response()->json($e->toArray(), 422);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            Log::error('Validation error in POS sale:', $e->errors());
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors(),
                'error_type' => 'validation'
            ], 422);
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            Log::error('Database error in POS sale: ' . $e->getMessage());
            Log::error('SQL Error Code: ' . $e->getCode());
            return response()->json([
                'success' => false,
                'message' => 'Database error occurred while processing the sale. Please try again.',
                'error_type' => 'database',
                'error_code' => $e->getCode()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in POS sale: ' . $e->getMessage());
            Log::error('Error in POS sale stack trace: ' . $e->getTraceAsString());
            
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
     * Whether the request actually names a customer.
     *
     * The POS sends `customer_details: null` when the cashier skipped the
     * step, and an empty-ish object is equivalent - neither should be treated
     * as a customer to look up.
     */
    private function hasCustomerDetails(Request $request): bool
    {
        $details = $request->input('customer_details');

        if (!is_array($details)) {
            return false;
        }

        return filled($details['customer_id'] ?? null)
            || filled($details['phone'] ?? null)
            || filled($details['name'] ?? null);
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
            
            // Otherwise, reuse the customer already on file for this number.
            //
            // Matched across every spelling of the number rather than on the
            // literal string: firstOrCreate on the raw value treated
            // "0712345678" and "+254712345678" as two different people, and
            // then hit the unique index when the second spelling collided.
            $existing = Customer::whereIn(
                'phone',
                PhoneNumber::variants($customerDetails['phone'])
            )->first();

            if ($existing !== null) {
                return $existing;
            }

            return Customer::create([
                'phone' => $customerDetails['phone'],
                'name' => $customerDetails['name'],
                'status' => 'active',
            ]);
        } catch (\Exception $e) {
            Log::error('Error handling customer: ' . $e->getMessage());
            throw new \Exception('Failed to process customer record');
        }
    }

    /**
     * Queue an SMS receipt for a completed sale.
     *
     * Silently does nothing for walk-in customers, whose placeholder number is
     * not sendable. Failures are swallowed: the sale is already committed and
     * the money taken, so a gateway problem must not turn a successful
     * checkout into an error on the cashier's screen.
     */
    private function sendSaleReceipt(Sale $sale, ?Customer $customer): void
    {
        if (!setting('sms_send_sale_receipts', true)) {
            return;
        }

        try {
            $sale->loadMissing('items', 'customer');

            app(SmsService::class)->queue(
                $customer ? $customer->phone : null,
                ReceiptMessage::forSale($sale),
                SmsLog::PURPOSE_SALE_RECEIPT,
                [
                    'reference_type' => 'sale',
                    'reference_id' => $sale->id,
                    'customer_id' => $customer ? $customer->id : null,
                ]
            );
        } catch (\Throwable $e) {
            Log::error('Failed to queue sale receipt SMS', [
                'sale_id' => $sale->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

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
     * Total is derived from the locked product rows, never from the request.
     *
     * @param  array  $stockResults  StockService results keyed by product id
     */
    private function calculateTotalAmount(array $cartItems, array $stockResults): float
    {
        $total = 0.0;

        foreach ($cartItems as $item) {
            $productId = (int) $item['id'];
            $price = (float) $stockResults[$productId]['product']->price;

            $total += $price * (int) $item['quantity'];
        }

        return round($total, 2);
    }

    /**
     * Rebuild the checkout response for a sale that already exists.
     *
     * Returned when a request repeats an idempotency key, so the till gets the
     * same receipt number and lines it would have got first time and can print
     * from them. `stock_after` is the product's current sellable stock rather
     * than the level at the time of sale - by now other tills may have sold
     * more, and the grid wants today's truth.
     */
    private function saleResponsePayload(Sale $sale): array
    {
        $sale->loadMissing(['items.product', 'customer']);

        $items = $sale->items->map(fn ($item) => [
            'id' => $item->product_id,
            'name' => optional($item->product)->name,
            'quantity' => $item->quantity,
            'price' => (float) $item->unit_price,
            'subtotal' => (float) $item->subtotal,
            'order_number' => $item->order_number,
            'stock_after' => optional($item->product)->available_stock,
            'serial_number' => $item->serial_number,
        ])->all();

        return [
            'success' => true,
            'duplicate' => true,
            'receipt_number' => $sale->receipt_number,
            'order_number' => $sale->order_number,
            'message' => 'Sale already recorded',
            'sale_id' => $sale->id,
            'customer' => $sale->customer ? [
                'id' => $sale->customer->id,
                'name' => $sale->customer->name,
                'phone' => $sale->customer->phone,
                'balance' => $sale->customer->balance,
            ] : null,
            'receipt_data' => [
                'date' => $sale->created_at->format('Y-m-d H:i:s'),
                'items' => $items,
                'total' => (float) $sale->total_amount,
                'order_number' => $sale->order_number,
                'payment_method' => $sale->payment_method,
                'customer' => $sale->customer ? [
                    'name' => $sale->customer->name,
                    'phone' => $sale->customer->phone,
                ] : null,
            ],
        ];
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

    /**
     * Deduct stock, write the sale lines, and derive the order numbers.
     *
     * Prices and stock levels both come from the rows StockService locked, so
     * the money and the numbering are consistent with what was actually sold.
     *
     * @return array{0: \Illuminate\Support\Collection, 1: float, 2: int|null}
     *         [receipt line data, authoritative total, headline order number]
     */
    private function processCartItems(array $cartItems, Sale $sale): array
    {
        $stockItems = [];
        $productIdsInOrder = [];

        foreach ($cartItems as $item) {
            $productId = (int) $item['id'];
            $productIdsInOrder[] = $productId;

            $stockItems[] = [
                'product_id' => $productId,
                'quantity' => (int) $item['quantity'],
                // unit_price is resolved from the locked product row by the
                // service when null, so the ledger records the real price.
                'unit_price' => null,
                'serial_number' => !empty($item['serial_number']) ? $item['serial_number'] : null,
            ];
        }

        try {
            $stockResults = $this->stockService->deductMultipleStock(
                $stockItems,
                'sale',
                $sale->id,
                "Stock deducted from POS sale #{$sale->id} (Receipt: {$sale->receipt_number}) "
                    . '- Product: {product_name}, Qty: {quantity}'
            );

            $totalAmount = $this->calculateTotalAmount($cartItems, $stockResults);
            $orderNumber = $this->orderNumberService->headline($stockResults, $productIdsInOrder);

            $lines = collect();

            foreach ($cartItems as $item) {
                $productId = (int) $item['id'];
                $result = $stockResults[$productId];
                $product = $result['product'];

                $quantity = (int) $item['quantity'];
                $unitPrice = (float) $product->price;
                $lineOrderNumber = $this->orderNumberService->forLine($result);

                $saleItem = SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => round($unitPrice * $quantity, 2),
                    'order_number' => $lineOrderNumber,
                    'serial_number' => !empty($item['serial_number']) ? $item['serial_number'] : null,
                ]);

                $lines->push([
                    'id' => $productId,
                    'name' => $product->name,
                    'cylinder_size' => $product->cylinder_size_label,
                    'quantity' => $quantity,
                    'price' => $unitPrice,
                    'subtotal' => $saleItem->subtotal,
                    'order_number' => $lineOrderNumber,
                    'stock_after' => $result['available_after'],
                    'serial_number' => $saleItem->serial_number,
                ]);
            }

            return [$lines, $totalAmount, $orderNumber];
        } catch (\Exception $e) {
            Log::error('Error processing cart items: ' . $e->getMessage());
            throw $e;
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
            $availableStock = $product->available_stock;
            $hasStock = $availableStock >= (int) $request->quantity;

            return response()->json([
                'success' => true,
                'available' => $hasStock,
                'current_stock' => $availableStock,
                'physical_stock' => (int) $product->stock,
                'reserved_stock' => (int) $product->reserved_stock,
                'out_of_stock' => $product->isOutOfStock(),
                // What the order number would be if this line were sold now.
                'next_order_number' => $availableStock,
                'message' => $hasStock
                    ? null
                    : ($product->isOutOfStock()
                        ? "{$product->name} is out of stock."
                        : "Only {$availableStock} unit(s) of {$product->name} remain."),
            ]);
        } catch (\Exception $e) {
            Log::error('Error checking stock: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error checking stock availability'
            ], 422);
        }
    }

    /**
     * Get cylinder transaction statistics for POS dashboard
     */
    /**
     * Today's takings, for the badge in the POS header.
     *
     * Voided sales are excluded, the same rule the admin Sales Overview uses -
     * a sale that was reversed is not a sale, and a cashier comparing the two
     * screens must not see two different numbers for the same day.
     *
     * This is the figure at page load. The terminal increments it as sales are
     * taken, because the POS screen does not reload between them.
     */
    private function getTodaySalesStatistics()
    {
        $today = Sale::whereDate('created_at', Carbon::today())->notVoided();

        return [
            'count' => (clone $today)->count(),
            'amount' => (float) (clone $today)->sum('total_amount'),
        ];
    }

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
