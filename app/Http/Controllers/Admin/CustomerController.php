<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\CylinderTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers
     */
    public function index(Request $request)
    {
        $query = Customer::query();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Order by name
        $customers = $query->orderBy('name')->paginate(20);

        return view('admin.customers.index', compact('customers'));
    }

    /**
     * Show customer details
     */
    public function show(Customer $customer)
    {
        // Load customer with relationships
        $customer->load(['sales', 'cylinderTransactions']);

        // Get customer statistics
        $stats = [
            'total_sales' => $customer->sales()->where('status', '!=', 'voided')->count(),
            'total_spent' => $customer->sales()->where('status', '!=', 'voided')->sum('total_amount'),
            'pending_credit' => $customer->balance,
            'active_cylinders' => $customer->cylinderTransactions()->where('status', 'active')->count(),
            'total_cylinders' => $customer->cylinderTransactions()->count(),
        ];

        // Recent sales
        $recentSales = $customer->sales()
            ->where('status', '!=', 'voided')
            ->with(['items.product', 'user'])
            ->orderByDesc('created_at')
            ->take(10)
            ->get();

        // Active cylinder transactions
        $activeCylinders = $customer->cylinderTransactions()
            ->where('status', 'active')
            ->with(['createdBy'])
            ->orderByDesc('created_at')
            ->get();

        // Recent cylinder transactions
        $recentCylinders = $customer->cylinderTransactions()
            ->with(['createdBy', 'completedBy'])
            ->orderByDesc('created_at')
            ->take(10)
            ->get();

        return view('admin.customers.show', compact(
            'customer', 
            'stats', 
            'recentSales', 
            'activeCylinders', 
            'recentCylinders'
        ));
    }

    /**
     * Show the form for creating a new customer
     */
    public function create()
    {
        return view('admin.customers.create');
    }

    /**
     * Store a newly created customer
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:customers,phone',
            'credit_limit' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        try {
            Customer::create([
                'name' => $request->name,
                'phone' => $request->phone,
                'credit_limit' => $request->credit_limit ?? 0,
                'balance' => 0,
                'status' => $request->status,
            ]);

            return redirect()->route('admin.customers.index')
                ->with('success', 'Customer created successfully!');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to create customer: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Show the form for editing a customer
     */
    public function edit(Customer $customer)
    {
        return view('admin.customers.edit', compact('customer'));
    }

    /**
     * Update the specified customer
     */
    public function update(Request $request, Customer $customer)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:customers,phone,' . $customer->id,
            'credit_limit' => 'nullable|numeric|min:0',
            'balance' => 'nullable|numeric',
            'status' => 'required|in:active,inactive',
        ]);

        try {
            $customer->update([
                'name' => $request->name,
                'phone' => $request->phone,
                'credit_limit' => $request->credit_limit ?? 0,
                'balance' => $request->balance ?? 0,
                'status' => $request->status,
            ]);

            return redirect()->route('admin.customers.show', $customer)
                ->with('success', 'Customer updated successfully!');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to update customer: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Remove the specified customer
     */
    public function destroy(Customer $customer)
    {
        try {
            // Check if customer has active transactions
            $activeSales = $customer->sales()->where('status', '!=', 'voided')->count();
            $activeCylinders = $customer->cylinderTransactions()->where('status', 'active')->count();

            if ($activeSales > 0 || $activeCylinders > 0) {
                return back()->withErrors(['error' => 'Cannot delete customer with active transactions.']);
            }

            $customer->delete();

            return redirect()->route('admin.customers.index')
                ->with('success', 'Customer deleted successfully!');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to delete customer: ' . $e->getMessage()]);
        }
    }

    /**
     * Quick customer creation API endpoint for forms
     */
    public function quickStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:customers,phone',
        ]);

        try {
            $customer = Customer::create([
                'name' => $request->name,
                'phone' => $request->phone,
                'credit_limit' => 0,
                'balance' => 0,
                'status' => 'active',
            ]);

            return response()->json([
                'success' => true,
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                    'balance' => $customer->balance,
                ],
                'message' => 'Customer created successfully!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create customer: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Search customers API endpoint
     */
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
     * Adjust customer balance
     */
    public function adjustBalance(Request $request, Customer $customer)
    {
        $request->validate([
            'adjustment_type' => 'required|in:add,subtract,set',
            'amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $oldBalance = $customer->balance;

            switch ($request->adjustment_type) {
                case 'add':
                    $customer->increment('balance', $request->amount);
                    break;
                case 'subtract':
                    $customer->decrement('balance', $request->amount);
                    break;
                case 'set':
                    $customer->update(['balance' => $request->amount]);
                    break;
            }

            $newBalance = $customer->fresh()->balance;

            // You could log this adjustment to an audit table here
            // AuditLog::create([...]);

            DB::commit();

            return back()->with('success', 
                "Customer balance adjusted from KSh " . number_format($oldBalance, 2) . 
                " to KSh " . number_format($newBalance, 2));

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to adjust balance: ' . $e->getMessage()]);
        }
    }
}