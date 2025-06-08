<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * Get all customers with optional search
     */
    public function index(Request $request)
    {
        $query = Customer::where('status', 'active');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $customers = $query->orderBy('name')->paginate($perPage);

        return response()->json([
            'success' => true,
            'customers' => $customers->items(),
            'pagination' => [
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
            ]
        ]);
    }

    /**
     * Search customers by name or phone
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:1'
        ]);

        $search = $request->q;
        
        $customers = Customer::where('status', 'active')
            ->where(function($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
            })
            ->limit(10)
            ->get(['id', 'name', 'phone', 'balance']);

        return response()->json([
            'success' => true,
            'customers' => $customers
        ]);
    }

    /**
     * Get a specific customer
     */
    public function show($id)
    {
        $customer = Customer::find($id);

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'balance' => $customer->balance,
                'credit_limit' => $customer->credit_limit,
                'status' => $customer->status,
                'created_at' => $customer->created_at->format('Y-m-d H:i:s'),
            ]
        ]);
    }
}