<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CylinderTransaction;
use App\Models\CylinderTransactionItem;
use App\Models\Customer;
use App\Models\Product;
use App\Services\StockService;
use App\Services\ReferenceNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CylinderController extends Controller
{
    protected $stockService;
    protected $referenceNumberService;

    public function __construct(StockService $stockService, ReferenceNumberService $referenceNumberService)
    {
        $this->stockService = $stockService;
        $this->referenceNumberService = $referenceNumberService;
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
        $period = $request->get('period', 'daily');
        $isPosContext = $request->route() && str_starts_with($request->route()->getName(), 'pos.');

        $query = CylinderTransaction::with(['customer', 'createdBy', 'completedBy', 'items.product.category'])
            ->active()
            ->dropOffs()
            ->paid()
            ->orderBy('created_at', 'desc');

        // Apply date filter
        $query = $this->applyDateFilter($query, $period);

        $perPage = $isPosContext ? 15 : 20;
        $transactions = $query->paginate($perPage);

        $stats = $this->calculatePeriodStats($period);

        return view('admin.cylinders.paid-drop-offs', compact('transactions', 'stats', 'period'));
    }

    public function unpaidDropOffs(Request $request)
    {
        $period = $request->get('period', 'daily');
        $isPosContext = $request->route() && str_starts_with($request->route()->getName(), 'pos.');

        $query = CylinderTransaction::with(['customer', 'createdBy', 'completedBy', 'items.product.category'])
            ->active()
            ->dropOffs()
            ->pending()
            ->orderBy('created_at', 'desc');

        // Apply date filter
        $query = $this->applyDateFilter($query, $period);

        $perPage = $isPosContext ? 15 : 20;
        $transactions = $query->paginate($perPage);

        $stats = $this->calculatePeriodStats($period);

        return view('admin.cylinders.unpaid-drop-offs', compact('transactions', 'stats', 'period'));
    }

    public function pendingPayments(Request $request)
    {
        $period = $request->get('period', 'daily');
        $isPosContext = $request->route() && str_starts_with($request->route()->getName(), 'pos.');

        $query = CylinderTransaction::with(['customer', 'createdBy', 'completedBy', 'items.product.category'])
            ->active()
            ->pending()
            ->orderBy('created_at', 'desc');

        // Apply date filter
        $query = $this->applyDateFilter($query, $period);

        $perPage = $isPosContext ? 15 : 20;
        $transactions = $query->paginate($perPage);

        $stats = $this->calculatePeriodStats($period);

        return view('admin.cylinders.pending-payments', compact('transactions', 'stats', 'period'));
    }

    public function advanceCollections(Request $request)
    {
        $period = $request->get('period', 'daily');
        $isPosContext = $request->route() && str_starts_with($request->route()->getName(), 'pos.');

        $query = CylinderTransaction::with(['customer', 'createdBy', 'completedBy', 'items.product.category'])
            ->active()
            ->advanceCollections()
            ->orderBy('created_at', 'desc');

        // Apply date filter
        $query = $this->applyDateFilter($query, $period);

        $perPage = $isPosContext ? 15 : 20;
        $transactions = $query->paginate($perPage);

        $stats = $this->calculatePeriodStats($period);

        return view('admin.cylinders.advance-collections', compact('transactions', 'stats', 'period'));
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
        $customers = Customer::where('status', 'active')
            ->orderBy('name')
            ->get();

        $products = Product::where('status', 'active')
            ->where('stock', '>', 0)
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
                $customer = Customer::create([
                    'name' => $request->customer_name,
                    'phone' => $request->customer_phone,
                    'status' => 'active',
                ]);
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

            // Handle customer balance for pending advance collection
            if ($request->transaction_type === 'advance_collection' && $request->payment_status === 'pending') {
                $totalWithDeposit = $totalAmount + ($request->deposit_amount ?? 0);
                $customer->increment('balance', $totalWithDeposit);
            }

            // Deduct inventory immediately for ALL transactions using StockService
            // Stock is no longer available once cylinders are out (either for refill or with customer)
            $stockItems = [];
            foreach ($transaction->items as $item) {
                $stockItems[] = [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'serial_number' => null
                ];
            }

            $transactionType = $transaction->isDropOff() ? 'drop-off' : 'advance collection';
            $this->stockService->deductMultipleStock(
                $stockItems,
                'cylinder_transaction',
                $transaction->id,
                "Stock deducted - Cylinder {$transactionType} transaction #{$transaction->id} (Ref: {$transaction->reference_number}, Customer: {$transaction->customer_name}, Product: {product_name}, Qty: {quantity})"
            );

            DB::commit();

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

            if ($cylinder->isDropOff()) {
                // Drop-off completion: customer is collecting the refilled cylinders
                $updates['collection_date'] = now();

                // Allow updating payment status during completion
                if ($request->filled('payment_status')) {
                    $updates['payment_status'] = $request->payment_status;
                }
                // Note: Payment status remains as-is if not specified
                // This allows completing transactions even with pending payment

                // Inventory was already deducted when transaction was created
                // No stock changes needed on completion

            } else {
                // Advance collection completion: customer returning empty cylinders
                $updates['return_date'] = now();

                // Process refund of deposit
                if ($cylinder->deposit_amount > 0) {
                    $cylinder->customer->decrement('balance', $cylinder->deposit_amount);
                }

                // If payment was pending, mark as paid and adjust balance
                if ($cylinder->isPending()) {
                    $updates['payment_status'] = 'paid';
                    $cylinder->customer->decrement('balance', $cylinder->amount);
                }

                // Inventory was already deducted when transaction was created
                // No stock changes needed on completion
            }

            $cylinder->update($updates);

            DB::commit();

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

            // Reverse customer balance changes
            if ($cylinder->isAdvanceCollection() && $cylinder->isPending()) {
                $totalAmount = $cylinder->amount + $cylinder->deposit_amount;
                $cylinder->customer->decrement('balance', $totalAmount);
            }

            // Restore inventory using StockService
            $stockItems = [];
            foreach ($cylinder->items as $item) {
                $stockItems[] = [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'serial_number' => null
                ];
            }

            $transactionType = $cylinder->isDropOff() ? 'drop-off' : 'advance collection';
            $this->stockService->restoreMultipleStock(
                $stockItems,
                'cylinder_cancellation',
                $cylinder->id,
                "Stock restored - Cylinder {$transactionType} transaction #{$cylinder->id} cancelled (Ref: {$cylinder->reference_number}, Customer: {$cylinder->customer_name}, Product: {product_name}, Qty: {quantity})"
            );

            $cylinder->update([
                'status' => 'cancelled',
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

            // Reverse customer balance changes
            if ($cylinder->isAdvanceCollection() && $cylinder->isPending()) {
                $totalAmount = $cylinder->amount + $cylinder->deposit_amount;
                $cylinder->customer->decrement('balance', $totalAmount);
            }

            // Restore inventory using StockService
            $stockItems = [];
            foreach ($cylinder->items as $item) {
                $stockItems[] = [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'serial_number' => null
                ];
            }

            $transactionType = $cylinder->isDropOff() ? 'drop-off' : 'advance collection';
            $this->stockService->restoreMultipleStock(
                $stockItems,
                'cylinder_cancellation',
                $cylinder->id,
                "Stock restored - Cylinder {$transactionType} transaction #{$cylinder->id} deleted (Ref: {$cylinder->reference_number}, Customer: {$cylinder->customer_name}, Product: {product_name}, Qty: {quantity})"
            );

            $cylinder->delete();

            DB::commit();

            return redirect()->route('admin.cylinders.index')
                ->with('success', 'Cylinder transaction deleted successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to delete transaction: ' . $e->getMessage()]);
        }
    }

    public function searchCustomers(Request $request)
    {
        $search = $request->get('q', '');
        
        $customers = Customer::where('status', 'active')
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
