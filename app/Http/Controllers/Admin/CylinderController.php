<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CylinderTransaction;
use App\Models\CylinderTransactionItem;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SmsLog;
use App\Services\StockService;
use App\Services\ReferenceNumberService;
use App\Services\OrderNumberService;
use App\Services\Sms\PhoneNumber;
use App\Services\Sms\ReceiptMessage;
use App\Services\Sms\SmsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CylinderController extends Controller
{
    protected $stockService;
    protected $referenceNumberService;
    protected $orderNumberService;
    protected $saleRecorder;

    public function __construct(
        StockService $stockService,
        ReferenceNumberService $referenceNumberService,
        OrderNumberService $orderNumberService,
        \App\Services\FulfilmentSaleRecorder $saleRecorder
    ) {
        $this->stockService = $stockService;
        $this->referenceNumberService = $referenceNumberService;
        $this->orderNumberService = $orderNumberService;
        $this->saleRecorder = $saleRecorder;
    }

    public function index(Request $request)
    {
        $query = CylinderTransaction::with(['customer', 'createdBy', 'completedBy', 'items.product.category'])
            ->orderBy('created_at', 'desc');

        $isPosContext = $request->route() && str_starts_with($request->route()->getName(), 'pos.');

        // Show only active transactions by default (unless status filter is applied)
        if (!$request->filled('status')) {
            $query->where('status', 'active');
        } else {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('transaction_type', $request->type);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%");
            });
        }

        $perPage = $isPosContext ? 15 : 20;
        $transactions = $query->paginate($perPage);

        if ($isPosContext) {
            $stats = [
                'active_drop_offs_paid' => CylinderTransaction::active()->dropOffs()->paid()->count(),
                'active_drop_offs_pending' => CylinderTransaction::active()->dropOffs()->pending()->count(),
                'active_advance_collections' => CylinderTransaction::active()->advanceCollections()->count(),
                'today_completed' => CylinderTransaction::whereDate('collection_date', today())->count(),
            ];
        } else {
            $stats = [
                'active_drop_offs_paid' => CylinderTransaction::active()->dropOffs()->paid()->count(),
                'active_drop_offs_pending' => CylinderTransaction::active()->dropOffs()->pending()->count(),
                'active_advance_collections' => CylinderTransaction::active()->advanceCollections()->count(),
                'pending_payments' => CylinderTransaction::active()->pending()->count(),
                'total_pending_amount' => CylinderTransaction::active()->pending()->sum('amount'),
                'total_pending_deposits' => CylinderTransaction::active()->advanceCollections()->sum('deposit_amount'),
            ];
        }

        return view('admin.cylinders.index', compact('transactions', 'stats'));
    }

    public function paidDropOffs(Request $request)
    {
        return $this->renderList($request, 'paid-drop-offs', function ($query) {
            return $query->active()->dropOffs()->paid();
        });
    }

    public function unpaidDropOffs(Request $request)
    {
        return $this->renderList($request, 'unpaid-drop-offs', function ($query) {
            return $query->active()->dropOffs()->pending();
        });
    }

    /**
     * Everything still owed.
     *
     * Deliberately not restricted to active transactions. A drop-off completed
     * while payment was pending stays owed, and filtering on active() made it
     * vanish from the only screen that chases payment - the debt became
     * invisible and, since update() refuses completed rows, unrecordable.
     */
    public function pendingPayments(Request $request)
    {
        return $this->renderList($request, 'pending-payments', function ($query) {
            return $query->pending()->where('status', '!=', 'cancelled');
        });
    }

    public function advanceCollections(Request $request)
    {
        return $this->renderList($request, 'advance-collections', function ($query) {
            return $query->active()->advanceCollections();
        });
    }

    /**
     * Shared body of the four cylinder list screens: same eager loads, same
     * date window, same pagination, same view contract.
     */
    private function renderList(Request $request, string $view, callable $scope)
    {
        $period = $request->get('period', 'daily');
        $isPosContext = $request->route() && str_starts_with($request->route()->getName(), 'pos.');

        [$start, $end, $startDate, $endDate] = $this->resolveDateRange($request, $period);

        $query = $scope(
            CylinderTransaction::with(['customer', 'createdBy', 'completedBy', 'items.product.category'])
        )->orderBy('created_at', 'desc');

        $query = $this->applyDateRange($query, $start, $end);

        $transactions = $query->paginate($isPosContext ? 15 : 20)->withQueryString();
        $stats = $this->calculatePeriodStats($period);

        return view(
            'admin.cylinders.' . $view,
            compact('transactions', 'stats', 'period', 'startDate', 'endDate')
        );
    }

    /**
     * Apply date filter based on period (daily, weekly, monthly)
     */
    private function applyDateFilter($query, $period)
    {
        switch ($period) {
            case 'weekly':
                return $query->where('drop_off_date', '>=', now()->startOfWeek());
            case 'monthly':
                return $query->where('drop_off_date', '>=', now()->startOfMonth());
            case 'daily':
            default:
                return $query->whereDate('drop_off_date', today());
        }
    }

    /**
     * Resolve the date window a list screen is showing.
     *
     * Every list view renders start/end date inputs and echoes the values back
     * into its filter form, export link and pagination. The controller never
     * supplied them, so the views referenced an undefined $startDate and every
     * one of these pages returned a 500. An explicit range wins over the named
     * period; otherwise the period defines the window.
     *
     * @return array{0:?Carbon,1:?Carbon,2:string,3:string} start, end, and the
     *         Y-m-d strings the views echo back.
     */
    private function resolveDateRange(Request $request, string $period): array
    {
        if ($request->filled('start_date') || $request->filled('end_date')) {
            $start = $request->filled('start_date')
                ? Carbon::parse($request->input('start_date'))->startOfDay()
                : Carbon::today()->startOfDay();

            $end = $request->filled('end_date')
                ? Carbon::parse($request->input('end_date'))->endOfDay()
                : Carbon::today()->endOfDay();

            return [$start, $end, $start->toDateString(), $end->toDateString()];
        }

        switch ($period) {
            case 'weekly':
                $start = now()->startOfWeek();
                break;
            case 'monthly':
                $start = now()->startOfMonth();
                break;
            case 'all':
                // No window at all - the views render empty date inputs.
                return [null, null, '', ''];
            default:
                $start = today()->startOfDay();
                break;
        }

        $end = now()->endOfDay();

        return [$start, $end, $start->toDateString(), $end->toDateString()];
    }

    /**
     * Constrain a query to a resolved window. A null start means "everything".
     */
    private function applyDateRange($query, ?Carbon $start, ?Carbon $end)
    {
        if ($start === null) {
            return $query;
        }

        return $query->whereBetween('drop_off_date', [$start, $end]);
    }

    /**
     * Calculate statistics for the selected period
     */
    private function calculatePeriodStats($period)
    {
        $query = CylinderTransaction::active();

        // Apply date filter
        switch ($period) {
            case 'weekly':
                $query->where('drop_off_date', '>=', now()->startOfWeek());
                break;
            case 'monthly':
                $query->where('drop_off_date', '>=', now()->startOfMonth());
                break;
            case 'daily':
            default:
                $query->whereDate('drop_off_date', today());
                break;
        }

        return [
            'total_cylinders_dropped' => $query->clone()->dropOffs()->count(),
            'total_cylinders_collected' => CylinderTransaction::completed()
                ->dropOffs()
                ->when($period === 'weekly', fn($q) => $q->where('collection_date', '>=', now()->startOfWeek()))
                ->when($period === 'monthly', fn($q) => $q->where('collection_date', '>=', now()->startOfMonth()))
                ->when($period === 'daily', fn($q) => $q->whereDate('collection_date', today()))
                ->count(),
            'total_advance_collections' => $query->clone()->advanceCollections()->count(),
            'paid_drop_offs' => $query->clone()->dropOffs()->paid()->count(),
            'unpaid_drop_offs' => $query->clone()->dropOffs()->pending()->count(),
            'pending_payments_count' => $query->clone()->pending()->count(),
            'total_pending_amount' => $query->clone()->pending()->sum('amount'),
            'total_pending_deposits' => $query->clone()->advanceCollections()->sum('deposit_amount'),
            'period' => $period,
            'period_label' => ucfirst($period),
        ];
    }

    public function create()
    {
        $customers = Customer::selectable()
            ->orderBy('name')
            ->get();

        // Only offer what is genuinely sellable: units already reserved for
        // other collections awaiting pickup are excluded.
        $products = Product::where('status', 'active')
            ->inStock()
            ->with('category')
            ->orderBy('created_at', 'asc')
            ->get();

        return view('admin.cylinders.create', compact('customers', 'products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'transaction_type' => 'required|in:drop_off,advance_collection',
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'required_without:customer_id|nullable|string|max:255',
            'customer_phone' => 'required_without:customer_id|nullable|string|max:20',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.brand' => 'nullable|string|max:100',
            'items.*.quantity' => 'required|integer|min:1',
            'payment_status' => 'required|in:paid,pending',
            'deposit_amount' => 'required_if:transaction_type,advance_collection|nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            // Create or find customer
            if ($request->customer_id) {
                $customer = Customer::findOrFail($request->customer_id);
            } else {
                $customer = $this->findOrCreateCustomer(
                    $request->customer_name,
                    $request->customer_phone
                );
            }

            // Calculate total and prepare items
            $totalAmount = 0;
            $itemsData = [];
            
            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);
                
                $subtotal = $product->price * $item['quantity'];
                $totalAmount += $subtotal;
                
                $itemsData[] = [
                    'product' => $product,
                    'brand' => $item['brand'] ?? $product->brand ?? 'N/A',
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->price,
                    'subtotal' => $subtotal,
                ];
            }

            // Generate unique reference number with locking
            $referenceNumber = $this->referenceNumberService->generateCylinderReference();

            // Create cylinder transaction
            $transaction = CylinderTransaction::create([
                'reference_number' => $referenceNumber,
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'customer_phone' => $customer->phone,
                'cylinder_size' => null,
                'cylinder_type' => null,
                'transaction_type' => $request->transaction_type,
                'payment_status' => $request->payment_status,
                'amount' => $totalAmount,
                'deposit_amount' => $request->deposit_amount ?? 0,
                'drop_off_date' => now(),
                'notes' => $request->notes,
                'created_by' => Auth::id(),
            ]);

            // Create transaction items
            foreach ($itemsData as $itemData) {
                CylinderTransactionItem::create([
                    'cylinder_transaction_id' => $transaction->id,
                    'product_id' => $itemData['product']->id,
                    'brand' => $itemData['brand'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'subtotal' => $itemData['subtotal'],
                ]);
            }

            // Customer balance.
            //
            // The deposit is an obligation the customer carries until the empty
            // cylinder comes back, so it is applied whenever an advance
            // collection is created - not only when the gas is unpaid. Applying
            // it unconditionally here is what keeps completion, cancellation and
            // deletion able to reverse it symmetrically; previously a *paid*
            // advance collection added nothing but still had its deposit
            // refunded on completion, driving the balance negative.
            if ($request->transaction_type === 'advance_collection') {
                $deposit = (float) ($request->deposit_amount ?? 0);

                if ($deposit > 0) {
                    $customer->increment('balance', $deposit);
                }

                if ($request->payment_status === 'pending') {
                    $customer->increment('balance', $totalAmount);
                }
            }

            // Stock.
            //
            // A drop-off leaves the cylinder with us: nothing physically moves
            // yet, so the units are reserved. They stop being sellable straight
            // away but are only deducted when the customer collects.
            //
            // An advance collection hands the gas over immediately, so that IS
            // the collection moment and stock is deducted here.
            $transaction->load('items');
            $stockItems = $transaction->stockLines();

            if ($transaction->isDropOff()) {
                $this->stockService->reserveMultipleStock(
                    $stockItems,
                    'cylinder_transaction',
                    $transaction->id
                );

                $transaction->update(['stock_status' => CylinderTransaction::STOCK_RESERVED]);
            } else {
                $stockResults = $this->stockService->deductMultipleStock(
                    $stockItems,
                    'cylinder_transaction',
                    $transaction->id,
                    "Stock deducted - Cylinder advance collection #{$transaction->id} "
                        . "(Ref: {$transaction->reference_number}, Customer: {$transaction->customer_name}, "
                        . 'Product: {product_name}, Qty: {quantity})'
                );

                $transaction->update([
                    'stock_status' => CylinderTransaction::STOCK_COMMITTED,
                    'stock_committed_at' => now(),
                    'order_number' => $this->orderNumberService->forCollection(
                        $stockResults,
                        $transaction->productIdsInOrder()
                    ),
                ]);
            }

            DB::commit();

            // fresh() matters: `status` is not in the create payload, it comes
            // from the column default, so the in-memory model has status=null
            // and isActive() reads false. That silently dropped the "keep this
            // ref for collection" line from every drop-off receipt.
            $this->sendCylinderReceipt($transaction->fresh());

            $isPosContext = $request->route() && str_starts_with($request->route()->getName(), 'pos.');
            $receiptRoute = $isPosContext ? 'pos.cylinders.receipt' : 'admin.cylinders.receipt';

            return response()->json([
                'success' => true,
                'message' => 'Cylinder transaction created successfully with ' . count($itemsData) . ' item(s)!',
                'transaction_id' => $transaction->id,
                'redirect_url' => route($receiptRoute, $transaction)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Cylinder transaction creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create transaction: ' . $e->getMessage()
                ], 422);
            }
            
            return back()->withErrors(['error' => 'Failed to create transaction: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function show(CylinderTransaction $cylinder)
    {
        $cylinder->load(['customer', 'createdBy', 'completedBy', 'items.product.category']);
        return view('admin.cylinders.show', compact('cylinder'));
    }

    public function edit(CylinderTransaction $cylinder)
    {
        if (!$cylinder->isActive()) {
            return redirect()->route('admin.cylinders.show', $cylinder)
                ->with('error', 'Cannot edit completed or cancelled transaction.');
        }

        $cylinder->load(['customer', 'items.product.category']);
        return view('admin.cylinders.edit', compact('cylinder'));
    }

    public function update(Request $request, CylinderTransaction $cylinder)
    {
        if (!$cylinder->isActive()) {
            return redirect()->route('admin.cylinders.show', $cylinder)
                ->with('error', 'Cannot edit completed or cancelled transaction.');
        }

        $request->validate([
            'payment_status' => 'required|in:paid,pending',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            $oldPaymentStatus = $cylinder->payment_status;
            $newPaymentStatus = $request->payment_status;

            // Handle payment status changes for advance collections
            if ($cylinder->isAdvanceCollection() && $oldPaymentStatus !== $newPaymentStatus) {
                if ($oldPaymentStatus === 'pending' && $newPaymentStatus === 'paid') {
                    // Changing from pending to paid: deduct amount from customer balance
                    // (deposit was already added at creation, this is just the gas payment)
                    $cylinder->customer->decrement('balance', $cylinder->amount);
                } elseif ($oldPaymentStatus === 'paid' && $newPaymentStatus === 'pending') {
                    // Changing from paid to pending: add amount back to customer balance
                    $cylinder->customer->increment('balance', $cylinder->amount);
                }
            }

            // Update the transaction
            $cylinder->update([
                'payment_status' => $newPaymentStatus,
                'notes' => $request->notes,
            ]);

            DB::commit();

            return redirect()->route('admin.cylinders.show', $cylinder)
                ->with('success', 'Transaction updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Cylinder transaction update failed', [
                'cylinder_id' => $cylinder->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors(['error' => 'Failed to update transaction: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function complete(Request $request, CylinderTransaction $cylinder)
    {
        if ($cylinder->isCompleted()) {
            return redirect()->route('admin.cylinders.show', $cylinder)
                ->with('error', 'Transaction is already completed.');
        }

        $request->validate([
            'payment_status' => 'sometimes|in:paid,pending',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            $updates = [
                'status' => 'completed',
                'completed_by' => Auth::id(),
                'notes' => $request->notes ?? $cylinder->notes,
            ];

            $cylinder->load('items');

            if ($cylinder->isDropOff()) {
                // Drop-off completion: the customer is collecting the refilled
                // cylinders, so this is the moment stock physically leaves.
                $updates['collection_date'] = now();

                // Convert the reservation taken at drop-off into a real
                // deduction. Guarded on stock_status so a transaction can never
                // be deducted twice - a repeated complete(), or a legacy row
                // that was already deducted at creation, is left alone.
                if ($cylinder->hasReservedStock()) {
                    $stockResults = $this->stockService->commitReservedStock(
                        $cylinder->stockLines(),
                        'cylinder_collection',
                        $cylinder->id,
                        "Stock collected - Cylinder drop-off #{$cylinder->id} "
                            . "(Ref: {$cylinder->reference_number}, Customer: {$cylinder->customer_name}, "
                            . 'Product: {product_name}, Qty: {quantity})'
                    );

                    $updates['stock_status'] = CylinderTransaction::STOCK_COMMITTED;
                    $updates['stock_committed_at'] = now();
                    $updates['order_number'] = $this->orderNumberService->forCollection(
                        $stockResults,
                        $cylinder->productIdsInOrder()
                    );
                } else {
                    Log::info('Cylinder collection skipped stock deduction', [
                        'cylinder_id' => $cylinder->id,
                        'stock_status' => $cylinder->stock_status,
                        'reason' => 'stock was not in a reserved state',
                    ]);
                }
            } else {
                // Advance collection completion: the customer is returning the
                // empty cylinder. The gas left at creation, so stock does not
                // move here - an empty cylinder is not sellable stock.
                $updates['return_date'] = now();

                // Clear the deposit obligation raised at creation.
                if ($cylinder->deposit_amount > 0 && $cylinder->customer) {
                    $cylinder->customer->decrement('balance', $cylinder->deposit_amount);
                }
            }

            // Payment.
            //
            // Handing the cylinders over is the moment money changes hands, so
            // completing settles the transaction. Doing it here rather than in
            // the per-type branches keeps drop-offs and advance collections
            // behaving the same way, and spares the cashier having to find the
            // order again afterwards just to mark it paid.
            //
            // An explicit payment_status=pending still wins, for the case where
            // the customer genuinely collects before paying.
            if ($request->input('payment_status', 'paid') === 'paid' && $cylinder->isPending()) {
                $this->reversePaymentObligation($cylinder);
                $updates['payment_status'] = 'paid';
            }

            // Record the revenue.
            //
            // Completing is the moment the cylinders are handed over and the
            // money is due, so this is the sale. Without it the refill side of
            // the business - about half the shop's takings - never reached the
            // `sales` table, and so was missing from the POS sales badge, the
            // admin Sales Overview and every cashier report.
            //
            // Stock has already moved above under `cylinder_collection`, which
            // is why this does NOT go through StockService: doing so would
            // deduct the same cylinders twice.
            if (!$cylinder->saleAlreadyRecorded() && $cylinder->items->isNotEmpty()) {
                $sale = $this->saleRecorder->record($cylinder->saleLines(), [
                    'user_id' => Auth::id() ?? $cylinder->created_by,
                    'customer_id' => $cylinder->customer_id
                        ?? $this->saleRecorder->walkInCustomer()->id,
                    'order_number' => $updates['order_number'] ?? $cylinder->order_number,
                    'payment_status' => $updates['payment_status'] ?? $cylinder->payment_status,
                    'notes' => "Cylinder {$cylinder->transaction_type} "
                        . "(Ref: {$cylinder->reference_number})",
                ]);

                $updates['sale_id'] = $sale->id;
            }

            $cylinder->update($updates);

            DB::commit();

            $this->sendCylinderThankYou($cylinder->fresh());

            $message = $cylinder->isDropOff()
                ? 'Customer has collected the refilled cylinders!'
                : 'Empty cylinders returned and deposit refunded!';

            $isPosContext = $request->route() && str_starts_with($request->route()->getName(), 'pos.');
            $indexRoute = $isPosContext ? 'pos.cylinders.index' : 'admin.cylinders.index';

            return redirect()->route($indexRoute)
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to complete transaction: ' . $e->getMessage()]);
        }
    }

    public function cancel(CylinderTransaction $cylinder)
    {
        if ($cylinder->isCompleted()) {
            return redirect()->route('admin.cylinders.show', $cylinder)
                ->with('error', 'Cannot cancel completed transaction.');
        }

        try {
            DB::beginTransaction();

            $cylinder->load('items');

            $this->reverseCustomerBalance($cylinder);
            $this->unwindStock($cylinder, 'cancelled');

            $cylinder->update([
                'status' => 'cancelled',
                'stock_status' => CylinderTransaction::STOCK_RELEASED,
                'completed_by' => Auth::id(),
            ]);

            DB::commit();

            return redirect()->route('admin.cylinders.index')
                ->with('success', 'Cylinder transaction cancelled successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to cancel transaction: ' . $e->getMessage()]);
        }
    }

    public function destroy(CylinderTransaction $cylinder)
    {
        if ($cylinder->isCompleted()) {
            return redirect()->route('admin.cylinders.index')
                ->with('error', 'Cannot delete completed transaction.');
        }

        try {
            DB::beginTransaction();

            $cylinder->load('items');

            $this->reverseCustomerBalance($cylinder);
            $this->unwindStock($cylinder, 'deleted');

            // Mark the state before deleting so the reversal is recorded even
            // though the row is about to disappear from the audit trail.
            $cylinder->update(['stock_status' => CylinderTransaction::STOCK_RELEASED]);
            $cylinder->delete();

            DB::commit();

            return redirect()->route('admin.cylinders.index')
                ->with('success', 'Cylinder transaction deleted successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to delete transaction: ' . $e->getMessage()]);
        }
    }

    /**
     * Undo the balance entries raised when an advance collection was created.
     *
     * Mirrors store() exactly: the deposit obligation always applies, the gas
     * amount only while it is still unpaid. Keeping the two symmetric is what
     * stops a deposit being stranded on a customer's account when a paid
     * advance collection is cancelled.
     */
    private function reverseCustomerBalance(CylinderTransaction $cylinder): void
    {
        if (!$cylinder->isAdvanceCollection() || !$cylinder->customer) {
            return;
        }

        if ($cylinder->deposit_amount > 0) {
            $cylinder->customer->decrement('balance', $cylinder->deposit_amount);
        }

        if ($cylinder->isPending()) {
            $cylinder->customer->decrement('balance', $cylinder->amount);
        }
    }

    /**
     * Undo whatever this transaction did to stock, based on what it actually
     * did rather than on its type.
     *
     * A reserved drop-off never moved stock, so the reservation is simply
     * dropped. A committed transaction did move stock, so it is restored.
     * An already-released transaction is left alone, which makes repeated
     * cancel/delete calls harmless.
     */
    private function unwindStock(CylinderTransaction $cylinder, string $action): void
    {
        if ($cylinder->items->isEmpty() || $cylinder->stockAlreadyReleased()) {
            return;
        }

        $stockItems = $cylinder->stockLines();
        $transactionType = $cylinder->isDropOff() ? 'drop-off' : 'advance collection';

        if ($cylinder->hasReservedStock()) {
            $this->stockService->releaseMultipleReservations(
                $stockItems,
                'cylinder_cancellation',
                $cylinder->id
            );

            Log::info('Cylinder reservation released', [
                'cylinder_id' => $cylinder->id,
                'action' => $action,
            ]);

            return;
        }

        $this->stockService->restoreMultipleStock(
            $stockItems,
            'cylinder_cancellation',
            $cylinder->id,
            "Stock restored - Cylinder {$transactionType} transaction #{$cylinder->id} {$action} "
                . "(Ref: {$cylinder->reference_number}, Customer: {$cylinder->customer_name}, "
                . 'Product: {product_name}, Qty: {quantity})'
        );
    }

    /**
     * Queue the opening receipt: what the customer left with us, and what they
     * owe. Sent when the transaction is created.
     */
    private function sendCylinderReceipt(CylinderTransaction $transaction): void
    {
        if (!setting('sms_send_cylinder_receipts', true)) {
            return;
        }

        $this->queueCylinderSms(
            $transaction,
            ReceiptMessage::cylinderCreated($transaction),
            SmsLog::PURPOSE_CYLINDER_RECEIPT
        );
    }

    /**
     * Queue the closing thank-you, which also carries the online-ordering
     * offer. Sent when the transaction is completed.
     *
     * Gated separately from the opening receipt so the promotional message can
     * be switched off without losing the transactional one.
     */
    private function sendCylinderThankYou(CylinderTransaction $transaction): void
    {
        if (!setting('sms_send_thank_you', true)) {
            return;
        }

        $this->queueCylinderSms(
            $transaction,
            ReceiptMessage::cylinderCompleted($transaction),
            SmsLog::PURPOSE_CYLINDER_THANK_YOU
        );
    }

    /**
     * Queue confirmation that a later payment was received.
     */
    private function sendPaymentConfirmation(CylinderTransaction $transaction): void
    {
        if (!setting('sms_send_cylinder_receipts', true)) {
            return;
        }

        $this->queueCylinderSms(
            $transaction,
            ReceiptMessage::paymentReceived($transaction),
            SmsLog::PURPOSE_PAYMENT_RECEIVED
        );
    }

    /**
     * Shared plumbing for the cylinder messages.
     *
     * Cylinder transactions always capture a customer phone, so unlike POS
     * sales these usually do send. Failures are swallowed on purpose: by the
     * time a message is queued the transaction is committed and stock has
     * already moved, so an SMS problem must not surface as a failed action.
     */
    private function queueCylinderSms(CylinderTransaction $transaction, string $message, string $purpose): void
    {
        try {
            $transaction->loadMissing('items');

            app(SmsService::class)->queue(
                $transaction->customer_phone,
                $message,
                $purpose,
                [
                    'reference_type' => 'cylinder_transaction',
                    'reference_id' => $transaction->id,
                    'customer_id' => $transaction->customer_id,
                ]
            );
        } catch (\Throwable $e) {
            Log::error('Failed to queue cylinder SMS', [
                'transaction_id' => $transaction->id,
                'purpose' => $purpose,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Record payment against a transaction, whatever its completion state.
     *
     * This is the missing counterpart to complete(): a drop-off could be
     * completed while unpaid, after which update() refused to touch it and no
     * other code path wrote payment_status. The debt could never be cleared.
     */
    public function recordPayment(Request $request, CylinderTransaction $cylinder)
    {
        if ($cylinder->status === 'cancelled') {
            return $this->paymentResponse($request, false, 'Cannot record payment against a cancelled transaction.');
        }

        if ($cylinder->isPaid()) {
            return $this->paymentResponse($request, false, 'This transaction is already marked paid.');
        }

        try {
            DB::beginTransaction();

            $this->settle($cylinder);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Recording cylinder payment failed', [
                'cylinder_id' => $cylinder->id,
                'error' => $e->getMessage(),
            ]);

            return $this->paymentResponse($request, false, 'Failed to record payment: ' . $e->getMessage());
        }

        $this->sendPaymentConfirmation($cylinder->fresh());

        return $this->paymentResponse($request, true, 'Payment recorded.');
    }

    /**
     * Mark several transactions paid at once, from the list screens' checkboxes.
     */
    public function bulkUpdatePaymentStatus(Request $request)
    {
        $validated = $request->validate([
            'transaction_ids' => 'required|array|min:1',
            'transaction_ids.*' => 'integer|exists:cylinder_transactions,id',
            'payment_status' => 'required|in:paid',
        ], [
            'transaction_ids.required' => 'Select at least one transaction first.',
            'payment_status.in' => 'Only marking transactions as paid is supported.',
        ]);

        $settled = 0;
        $skipped = 0;

        try {
            DB::beginTransaction();

            $transactions = CylinderTransaction::whereIn('id', $validated['transaction_ids'])
                ->lockForUpdate()
                ->get();

            foreach ($transactions as $transaction) {
                // Re-checked per row rather than in the query: a row that was
                // paid or cancelled between rendering the list and submitting
                // it must be skipped, not double-settled.
                if ($transaction->isPaid() || $transaction->status === 'cancelled') {
                    $skipped++;
                    continue;
                }

                $this->settle($transaction);
                $settled++;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk cylinder payment update failed', ['error' => $e->getMessage()]);

            return back()->withErrors(['error' => 'Failed to update payments: ' . $e->getMessage()]);
        }

        $message = "{$settled} transaction(s) marked paid.";
        if ($skipped > 0) {
            $message .= " {$skipped} skipped (already paid or cancelled).";
        }

        return back()->with('success', $message);
    }

    /**
     * Mark one transaction paid and reverse whatever it put on the customer's
     * balance.
     *
     * Only advance collections credit the balance at creation, so only they
     * debit it here. Drop-offs never touched it, and debiting them would push
     * the customer into false credit. Caller owns the transaction boundary.
     */
    private function settle(CylinderTransaction $cylinder): void
    {
        $this->reversePaymentObligation($cylinder);

        $cylinder->update(['payment_status' => 'paid']);
    }

    /**
     * Undo whatever the unpaid gas put on the customer's balance.
     *
     * Only advance collections credit the balance at creation, so only they
     * debit it when settled; debiting a drop-off would push the customer into
     * credit they never had. Shared by complete() and recordPayment() so the
     * two cannot drift apart.
     */
    private function reversePaymentObligation(CylinderTransaction $cylinder): void
    {
        if ($cylinder->isAdvanceCollection() && $cylinder->customer) {
            $cylinder->customer->decrement('balance', $cylinder->amount);
        }
    }

    private function paymentResponse(Request $request, bool $ok, string $message)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => $ok, 'message' => $message], $ok ? 200 : 422);
        }

        return $ok
            ? back()->with('success', $message)
            : back()->withErrors(['error' => $message]);
    }

    /**
     * Every cylinder transaction for one customer.
     *
     * The view for this already existed; only the route and this method were
     * missing, which is why the link from the customer profile 500'd.
     */
    public function customerHistory(Request $request, Customer $customer)
    {
        $query = $customer->cylinderTransactions()
            ->with(['createdBy', 'completedBy', 'items.product.category'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $transactions = $query->paginate(20)->withQueryString();

        $all = $customer->cylinderTransactions();

        $stats = [
            'total_transactions' => $all->clone()->count(),
            'active_transactions' => $all->clone()->where('status', 'active')->count(),
            'total_amount_paid' => $all->clone()->where('payment_status', 'paid')->sum('amount'),
            'total_amount_pending' => $all->clone()
                ->where('payment_status', 'pending')
                ->where('status', '!=', 'cancelled')
                ->sum('amount'),
        ];

        return view('admin.cylinders.customer-history', compact('customer', 'transactions', 'stats'));
    }

    public function customerHistoryExport(Request $request, Customer $customer)
    {
        $transactions = $customer->cylinderTransactions()
            ->with(['items.product'])
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->streamTransactionsCsv(
            $transactions,
            'cylinder_history_' . $customer->id
        );
    }

    /**
     * CSV of whatever a list screen is currently showing.
     *
     * One implementation behind four route names: the views link to a
     * per-screen export, but the only thing that differs is the filter, which
     * the caller supplies.
     */
    public function exportList(Request $request, string $list)
    {
        $scopes = [
            'paid-drop-offs' => fn ($q) => $q->active()->dropOffs()->paid(),
            'unpaid-drop-offs' => fn ($q) => $q->active()->dropOffs()->pending(),
            'pending-payments' => fn ($q) => $q->pending()->where('status', '!=', 'cancelled'),
            'advance-collections' => fn ($q) => $q->active()->advanceCollections(),
        ];

        abort_unless(isset($scopes[$list]), 404);

        $period = $request->get('period', 'daily');
        [$start, $end] = $this->resolveDateRange($request, $period);

        $query = $scopes[$list](CylinderTransaction::with(['customer', 'items.product']))
            ->orderBy('created_at', 'desc');

        $transactions = $this->applyDateRange($query, $start, $end)->get();

        return $this->streamTransactionsCsv($transactions, str_replace('-', '_', $list));
    }

    /**
     * Stream transactions as CSV rather than building the whole file in memory,
     * so a long history cannot exhaust it.
     */
    private function streamTransactionsCsv($transactions, string $filenamePrefix)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filenamePrefix . '_' . now()->format('Y-m-d') . '.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $columns = [
            'Reference', 'Order Number', 'Date', 'Customer', 'Phone', 'Type',
            'Items', 'Quantity', 'Amount', 'Deposit', 'Total',
            'Payment Status', 'Status', 'Collected/Returned',
        ];

        $callback = function () use ($transactions, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($transactions as $t) {
                fputcsv($file, [
                    $t->reference_number,
                    $t->order_number,
                    optional($t->drop_off_date)->format('Y-m-d H:i'),
                    $t->customer_name,
                    $t->customer_phone,
                    $t->transaction_type === 'drop_off' ? 'Drop-off' : 'Advance collection',
                    $t->items->map(fn ($i) => optional($i->product)->name . ' x' . $i->quantity)->implode('; '),
                    $t->items->sum('quantity'),
                    number_format((float) $t->amount, 2, '.', ''),
                    number_format((float) $t->deposit_amount, 2, '.', ''),
                    number_format((float) $t->getTotalAmount(), 2, '.', ''),
                    $t->payment_status,
                    $t->status,
                    optional($t->collection_date ?? $t->return_date)->format('Y-m-d H:i'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Reuse the customer already on file for this number, or create one.
     *
     * Previously this called create() unconditionally. `customers.phone` is
     * uniquely indexed, so any customer already known to the system - anyone
     * who has ever bought on credit at the POS, for instance - made the whole
     * transaction fail with a raw SQL integrity-constraint error.
     *
     * Matching is done across every spelling of the number, because the column
     * is free text and the same person may be stored as 0712..., 254712... or
     * +254712....
     */
    private function findOrCreateCustomer(?string $name, ?string $phone): Customer
    {
        $existing = Customer::whereIn('phone', PhoneNumber::variants($phone))->first();

        if ($existing !== null) {
            // A customer who was inactive is being transacted with again.
            if ($existing->status !== 'active') {
                $existing->update(['status' => 'active']);
            }

            return $existing;
        }

        return Customer::create([
            'name' => $name,
            'phone' => $phone,
            'status' => 'active',
        ]);
    }

    public function searchCustomers(Request $request)
    {
        $search = $request->get('q', '');
        
        $customers = Customer::selectable()
            ->where(function($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
            })
            ->limit(10)
            ->get(['id', 'name', 'phone', 'balance']);

        return response()->json($customers);
    }

    /**
     * Generate and show receipt for a transaction
     */
    public function receipt(CylinderTransaction $cylinder)
    {
        $cylinder->load(['customer', 'items.product.category', 'createdBy']);
        return view('admin.cylinders.receipt', compact('cylinder'));
    }
}
