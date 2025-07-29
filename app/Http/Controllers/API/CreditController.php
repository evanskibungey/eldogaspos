<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class CreditController extends Controller
{
    /**
     * Get all customers with credit balances
     */
    public function index()
    {
        try {
            // Get all customers with credit balances greater than zero
            $customers = Customer::where('balance', '>', 0)
                ->orderBy('balance', 'desc')
                ->get();
                
            // Calculate total credit amount
            $totalCredit = Customer::where('balance', '>', 0)->sum('balance');
            
            // Ensure each customer has the required fields
            $customers = $customers->map(function ($customer) {
                // Ensure phone and email exist
                $customer->phone = $customer->phone ?? '';
                $customer->email = $customer->email ?? null;
                
                return $customer;
            });
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'customers' => $customers,
                    'totalCredit' => $totalCredit
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('API Error - Get credit customers: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve customers with credit: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get customer credit details with all credit sales and payments
     */
    public function show($customerId)
    {
        try {
            // Find the customer
            $customer = Customer::findOrFail($customerId);
            
            // Ensure customer has required fields
            $customer->phone = $customer->phone ?? '';
            $customer->email = $customer->email ?? null;
            
            // Get all credit sales for this customer
            $creditSales = Sale::with(['items.product'])
                ->where('customer_id', $customer->id)
                ->where('payment_method', 'credit')
                ->orderBy('created_at', 'desc')
                ->get();
                
            // Ensure sales have required fields
            $creditSales = $creditSales->map(function ($sale) {
                $sale->reference_no = $sale->reference_no ?? '';
                $sale->payment_method = $sale->payment_method ?? 'credit';
                $sale->payment_status = $sale->payment_status ?? 'pending';
                $sale->status = $sale->status ?? 'completed';
                
                // Process all items in the sale
                if ($sale->items) {
                    $sale->items->map(function ($item) {
                        // Ensure product exists and has a name
                        if ($item->product) {
                            $item->product->name = $item->product->name ?? 'Unknown Product';
                        }
                        return $item;
                    });
                }
                
                return $sale;
            });
                
            // Get all payments made by this customer
            $payments = Payment::with('user:id,name')
                ->where('customer_id', $customer->id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($payment) {
                    // Include the user name for each payment
                    $payment->user_name = $payment->user->name ?? null;
                    $payment->payment_method = $payment->payment_method ?? 'unknown';
                    return $payment;
                });
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'customer' => $customer,
                    'creditSales' => $creditSales,
                    'payments' => $payments
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('API Error - Get customer credit details: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve customer credit details: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Record a payment for a customer's credit
     */
    public function recordPayment(Request $request, $customerId)
    {
        try {
            // Find the customer
            $customer = Customer::findOrFail($customerId);
            
            // Validate request data
            $validated = $request->validate([
                'amount' => 'required|numeric|min:0.01|max:' . $customer->balance,
                'payment_method' => 'required|in:cash,bank_transfer,mobile_money',
                'reference_number' => 'nullable|string|max:255',
                'notes' => 'nullable|string|max:1000',
            ]);
            
            DB::beginTransaction();
            
            // Create a new payment record
            $payment = new Payment([
                'customer_id' => $customer->id,
                'user_id' => Auth::id(),
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'reference_number' => $validated['reference_number'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);
            
            $payment->save();
            
            // Update customer balance
            $customer->balance -= $validated['amount'];
            $customer->save();
            
            // If this payment fully pays the customer's balance, update related sales
            if ($customer->balance <= 0) {
                Sale::where('customer_id', $customer->id)
                    ->where('payment_method', 'credit')
                    ->where('payment_status', 'pending')
                    ->update(['payment_status' => 'paid']);
            }
            
            DB::commit();
            
            // Get the user name for the payment response
            $payment->user_name = Auth::user()->name;
            
            return response()->json([
                'status' => 'success',
                'message' => 'Payment recorded successfully',
                'data' => [
                    'payment' => $payment,
                    'customer' => $customer->fresh()
                ]
            ]);
                
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('API Error - Record payment: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to record payment: ' . $e->getMessage()
            ], 500);
        }
    }
}