<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreditController extends Controller
{
    /**
     * Display a listing of customers with credit balances
     */
    public function index()
    {
        // Get all customers with credit balances greater than zero
        $customers = Customer::where('balance', '>', 0)
            ->orderBy('balance', 'desc')
            ->paginate(20);
            
        // Calculate total credit amount
        $totalCredit = Customer::where('balance', '>', 0)->sum('balance');
        
        return view('pos.credits.index', compact('customers', 'totalCredit'));
    }
    
    /**
     * Show customer credit details with all credit sales
     */
    public function show(Customer $customer)
    {
        // Get all credit sales for this customer
        $creditSales = Sale::with(['items.product'])
            ->where('customer_id', $customer->id)
            ->where('payment_method', 'credit')
            ->orderBy('created_at', 'desc')
            ->get();
            
        // Get all payments made by this customer
        $payments = Payment::where('customer_id', $customer->id)
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('pos.credits.show', compact('customer', 'creditSales', 'payments'));
    }
    
    /**
     * Display form to record a payment for a customer
     */
    public function recordPaymentForm(Customer $customer)
    {
        return view('pos.credits.payment', compact('customer'));
    }
    
    /**
     * Process payment for a customer's credit
     */
    public function recordPayment(Request $request, Customer $customer)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $customer->balance,
            'payment_method' => 'required|in:cash,bank_transfer,mobile_money',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);
        
        try {
            DB::beginTransaction();
            
            // Create a new payment record
            $payment = new Payment([
                'customer_id' => $customer->id,
                'user_id' => auth()->id(),
                'amount' => $request->amount,
                'payment_method' => $request->payment_method,
                'reference_number' => $request->reference_number,
                'notes' => $request->notes,
            ]);
            
            $payment->save();
            
            // Update customer balance
            $customer->balance -= $request->amount;
            $customer->save();
            
            // If this payment fully pays the customer's balance, update related sales
            if ($customer->balance <= 0) {
                Sale::where('customer_id', $customer->id)
                    ->where('payment_method', 'credit')
                    ->where('payment_status', 'pending')
                    ->update(['payment_status' => 'paid']);
            }
            
            DB::commit();
            
            return redirect()
                ->route('pos.credits.show', $customer)
                ->with('success', 'Payment of $' . number_format($request->amount, 2) . ' recorded successfully!');
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error recording payment: ' . $e->getMessage());
            
            return back()
                ->withInput()
                ->with('error', 'Error recording payment: ' . $e->getMessage());
        }
    }
}