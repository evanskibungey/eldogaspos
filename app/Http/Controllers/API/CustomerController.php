<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * Search for customers by name or phone
     */
    public function search(Request $request)
    {
        $search = $request->get('search', '');
        $limit = $request->get('limit', 20);

        $customers = Customer::where('status', 'active')
            ->where(function ($query) use ($search) {
                if (!empty($search)) {
                    $query->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('phone', 'LIKE', "%{$search}%");
                }
            })
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'phone', 'balance']);

        return response()->json([
            'success' => true,
            'customers' => $customers
        ]);
    }

    /**
     * Get all active customers for dropdown
     */
    public function index(Request $request)
    {
        $customers = Customer::where('status', 'active')
            ->whereNotIn('phone', ['0000000000']) // Exclude walk-in customer
            ->orderBy('name')
            ->get(['id', 'name', 'phone', 'balance']);

        return response()->json([
            'success' => true,
            'customers' => $customers
        ]);
    }

    /**
     * Get customer details by ID
     */
    public function show($id)
    {
        $customer = Customer::where('status', 'active')
            ->find($id, ['id', 'name', 'phone', 'balance', 'credit_limit']);

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'customer' => $customer
        ]);
    }
}
