<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Customer;
use App\Models\Setting;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SaleController extends Controller
{
    protected $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Display the sales creation page.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $products = Product::where('status', 'active')->get();
        $currencySymbol = setting('currency_symbol', '$');
        $taxPercentage = (float)setting('tax_percentage', 0);
        $companyName = setting('company_name', 'Our Store');
        
        return view('pos.sales.create', compact('products', 'currencySymbol', 'taxPercentage', 'companyName'));
    }

    /**
     * Display sales history.
     *
     * @return \Illuminate\View\View
     */
    public function history(Request $request)
    {
        // Get settings
        $currencySymbol = setting('currency_symbol', '$');
        $companyName = setting('company_name', 'Our Store');
        
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
        
        $currencySymbol = setting('currency_symbol', '$');
        $taxPercentage = (float)setting('tax_percentage', 0);
        $companyName = setting('company_name', 'Our Store');
        $companyAddress = setting('company_address', '');
        $companyPhone = setting('company_phone', '');
        $companyEmail = setting('company_email', '');
        $receiptFooter = setting('receipt_footer', 'Thank you for your business!');
        
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
            
            // Prepare items for batch stock restoration
            $stockItems = [];
            foreach ($sale->items as $item) {
                $stockItems[] = [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'serial_number' => $item->serial_number
                ];
            }

            // Restore stock using stock service
            $this->stockService->restoreMultipleStock(
                $stockItems,
                'sale_void',
                $sale->id,
                'Stock returned from voided sale #' . $sale->id . ' (Receipt: ' . $sale->receipt_number . ') - Product: {product_name}, Qty: {quantity}'
            );
            
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
}
