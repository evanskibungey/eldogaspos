<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CylinderTransaction;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CylinderController extends Controller
{
    public function index(Request $request)
    {
        $query = CylinderTransaction::with(['customer', 'createdBy', 'completedBy'])
            ->orderBy('created_at', 'desc');

        // Detect if this is being called from POS routes
        $isPosContext = $request->route() && str_starts_with($request->route()->getName(), 'pos.');
        
        // Filter active transactions by default for POS context
        if ($isPosContext && !$request->filled('status')) {
            $query->where('status', 'active');
        } else if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by transaction type
        if ($request->filled('type')) {
            $query->where('transaction_type', $request->type);
        }

        // Filter by payment status
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Search by customer name, phone, or reference
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%");
            });
        }

        // Adjust pagination based on context
        $perPage = $isPosContext ? 15 : 20;
        $transactions = $query->paginate($perPage);

        // Get summary statistics (different for POS vs Admin)
        if ($isPosContext) {
            $stats = [
                'active_drop_offs' => CylinderTransaction::active()->dropOffs()->count(),
                'active_advance_collections' => CylinderTransaction::active()->advanceCollections()->count(),
                'today_completed' => CylinderTransaction::whereDate('collection_date', today())->count(),
            ];
        } else {
            $stats = [
                'active_drop_offs' => CylinderTransaction::active()->dropOffs()->count(),
                'active_advance_collections' => CylinderTransaction::active()->advanceCollections()->count(),
                'pending_payments' => CylinderTransaction::active()->pending()->count(),
                'total_pending_amount' => CylinderTransaction::active()->pending()->sum('amount'),
                'total_pending_deposits' => CylinderTransaction::active()->advanceCollections()->sum('deposit_amount'),
            ];
        }

        return view('admin.cylinders.index', compact('transactions', 'stats'));
    }

    public function create()
    {
        $customers = Customer::where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('admin.cylinders.create', compact('customers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'transaction_type' => 'required|in:drop_off,advance_collection',
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'required_without:customer_id|nullable|string|max:255',
            'customer_phone' => 'required_without:customer_id|nullable|string|max:20',
            'cylinder_size' => 'required|string|max:50',
            'cylinder_type' => 'required|string|max:50',
            'payment_status' => 'required|in:paid,pending',
            'amount' => 'required|numeric|min:0',
            'deposit_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            // Create or find customer
            $customer = null;
            if ($request->customer_id) {
                $customer = Customer::findOrFail($request->customer_id);
            } else {
                // Create new customer
                $customer = Customer::create([
                    'name' => $request->customer_name,
                    'phone' => $request->customer_phone,
                    'status' => 'active',
                ]);
            }

            $transaction = CylinderTransaction::create([
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'customer_phone' => $customer->phone,
                'cylinder_size' => $request->cylinder_size,
                'cylinder_type' => $request->cylinder_type,
                'transaction_type' => $request->transaction_type,
                'payment_status' => $request->payment_status,
                'amount' => $request->amount,
                'deposit_amount' => $request->deposit_amount ?? 0,
                'drop_off_date' => now(),
                'notes' => $request->notes,
                'created_by' => Auth::id(),
            ]);

            // If it's an advance collection and payment is pending, add to customer balance
            if ($request->transaction_type === 'advance_collection' && $request->payment_status === 'pending') {
                $totalAmount = $request->amount + ($request->deposit_amount ?? 0);
                $customer->increment('balance', $totalAmount);
            }

            DB::commit();

            // Detect if this is being called from POS routes
            $isPosContext = $request->route() && str_starts_with($request->route()->getName(), 'pos.');
            $showRoute = $isPosContext ? 'pos.cylinders.show' : 'admin.cylinders.show';

            return redirect()->route($showRoute, $transaction)
                ->with('success', 'Cylinder transaction created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create transaction: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function show(CylinderTransaction $cylinder)
    {
        $cylinder->load(['customer', 'createdBy', 'completedBy']);
        return view('admin.cylinders.show', compact('cylinder'));
    }

    public function edit(CylinderTransaction $cylinder)
    {
        if ($cylinder->isCompleted()) {
            return redirect()->route('admin.cylinders.show', $cylinder)
                ->with('error', 'Cannot edit completed transaction.');
        }

        $customers = Customer::where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('admin.cylinders.edit', compact('cylinder', 'customers'));
    }

    public function update(Request $request, CylinderTransaction $cylinder)
    {
        if ($cylinder->isCompleted()) {
            return redirect()->route('admin.cylinders.show', $cylinder)
                ->with('error', 'Cannot update completed transaction.');
        }

        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'cylinder_size' => 'required|string|max:50',
            'cylinder_type' => 'required|string|max:50',
            'payment_status' => 'required|in:paid,pending',
            'amount' => 'required|numeric|min:0',
            'deposit_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            $customer = Customer::findOrFail($request->customer_id);

            // Handle balance adjustments for advance collections
            if ($cylinder->isAdvanceCollection() && $cylinder->isPending()) {
                // Remove old amount from customer balance
                $oldTotal = $cylinder->amount + $cylinder->deposit_amount;
                $customer->decrement('balance', $oldTotal);

                // Add new amount if still pending
                if ($request->payment_status === 'pending') {
                    $newTotal = $request->amount + ($request->deposit_amount ?? 0);
                    $customer->increment('balance', $newTotal);
                }
            }

            $cylinder->update([
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'customer_phone' => $customer->phone,
                'cylinder_size' => $request->cylinder_size,
                'cylinder_type' => $request->cylinder_type,
                'payment_status' => $request->payment_status,
                'amount' => $request->amount,
                'deposit_amount' => $request->deposit_amount ?? 0,
                'notes' => $request->notes,
            ]);

            DB::commit();

            return redirect()->route('admin.cylinders.show', $cylinder)
                ->with('success', 'Cylinder transaction updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
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
                // Customer is collecting refilled cylinder
                $updates['collection_date'] = now();
                
                // Update payment status if provided
                if ($request->filled('payment_status')) {
                    $updates['payment_status'] = $request->payment_status;
                }

                // If payment is made now and was pending, handle customer balance
                if ($cylinder->isPending() && $request->payment_status === 'paid') {
                    // For drop-offs, we don't typically affect customer balance
                    // unless they had credit before
                }

            } else {
                // Customer is returning empty cylinder for advance collection
                $updates['return_date'] = now();

                // Process refund of deposit
                if ($cylinder->deposit_amount > 0) {
                    // Reduce customer balance by deposit amount
                    $cylinder->customer->decrement('balance', $cylinder->deposit_amount);
                }

                // If payment was pending, mark as paid since empty cylinder is returned
                if ($cylinder->isPending()) {
                    $updates['payment_status'] = 'paid';
                    // Reduce customer balance by the gas amount
                    $cylinder->customer->decrement('balance', $cylinder->amount);
                }
            }

            $cylinder->update($updates);

            DB::commit();

            $message = $cylinder->isDropOff() 
                ? 'Customer has collected the refilled cylinder!' 
                : 'Empty cylinder returned and deposit refunded!';

            // Detect if this is being called from POS routes
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

            // Reverse any balance changes
            if ($cylinder->isAdvanceCollection() && $cylinder->isPending()) {
                $totalAmount = $cylinder->amount + $cylinder->deposit_amount;
                $cylinder->customer->decrement('balance', $totalAmount);
            }

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

            // Reverse any balance changes
            if ($cylinder->isAdvanceCollection() && $cylinder->isPending()) {
                $totalAmount = $cylinder->amount + $cylinder->deposit_amount;
                $cylinder->customer->decrement('balance', $totalAmount);
            }

            $cylinder->delete();

            DB::commit();

            return redirect()->route('admin.cylinders.index')
                ->with('success', 'Cylinder transaction deleted successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to delete transaction: ' . $e->getMessage()]);
        }
    }

    // POS-specific methods
    
    // Quick action for completing drop-off collections
    public function quickComplete(CylinderTransaction $cylinder)
    {
        if (!$cylinder->isDropOff() || $cylinder->isCompleted()) {
            return response()->json(['error' => 'Invalid transaction for quick completion'], 400);
        }

        try {
            DB::beginTransaction();

            $cylinder->update([
                'status' => 'completed',
                'collection_date' => now(),
                'completed_by' => Auth::id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cylinder collection completed successfully!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to complete transaction'], 500);
        }
    }

    // Quick action for processing return of advance collection
    public function quickReturn(CylinderTransaction $cylinder)
    {
        if (!$cylinder->isAdvanceCollection() || $cylinder->isCompleted()) {
            return response()->json(['error' => 'Invalid transaction for quick return'], 400);
        }

        try {
            DB::beginTransaction();

            $updates = [
                'status' => 'completed',
                'return_date' => now(),
                'completed_by' => Auth::id(),
            ];

            // Process refund of deposit
            if ($cylinder->deposit_amount > 0) {
                $cylinder->customer->decrement('balance', $cylinder->deposit_amount);
            }

            // If payment was pending, mark as paid since empty cylinder is returned
            if ($cylinder->isPending()) {
                $updates['payment_status'] = 'paid';
                $cylinder->customer->decrement('balance', $cylinder->amount);
            }

            $cylinder->update($updates);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Empty cylinder return processed successfully!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to process return'], 500);
        }
    }

    // API endpoint for searching customers
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
}