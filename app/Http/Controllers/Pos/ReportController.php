<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Product;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class ReportController extends Controller
{
    /**
     * Get settings helper function
     * 
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    protected function getSetting($key = null, $default = null)
    {
        if ($key) {
            return Setting::where('key', $key)->value('value') ?? $default;
        }
        
        return Setting::pluck('value', 'key')->toArray();
    }

    /**
     * Display reports dashboard
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Get company settings
        $currencySymbol = $this->getSetting('currency_symbol', '$');
        $companyName = $this->getSetting('company_name', 'Our Store');
        
        // Today's statistics
        $today = Carbon::today();
        
        $todaySales = Sale::whereDate('created_at', $today)
            ->where('status', '!=', 'voided')
            ->count();
            
        $todayRevenue = Sale::whereDate('created_at', $today)
            ->where('status', '!=', 'voided')
            ->sum('total_amount');
            
        // This month's statistics
        $startOfMonth = Carbon::now()->startOfMonth();
        
        $monthSales = Sale::whereBetween('created_at', [$startOfMonth, Carbon::now()])
            ->where('status', '!=', 'voided')
            ->count();
            
        $monthRevenue = Sale::whereBetween('created_at', [$startOfMonth, Carbon::now()])
            ->where('status', '!=', 'voided')
            ->sum('total_amount');
            
        // Low stock products
        $lowStockThreshold = (int)$this->getSetting('low_stock_threshold', 10);
        
        $lowStockProducts = Product::where('stock', '<=', $lowStockThreshold)
            ->where('stock', '>', 0)
            ->count();
            
        $outOfStockProducts = Product::where('stock', 0)
            ->count();
            
        // Recent sales
        $recentSales = Sale::with(['user', 'customer'])
            ->where('status', '!=', 'voided')
            ->latest()
            ->limit(5)
            ->get();
            
        // Sales by day for the last 7 days
        $salesByDay = DB::table('sales')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as total'))
            ->where('status', '!=', 'voided')
            ->whereDate('created_at', '>=', Carbon::now()->subDays(6))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->keyBy('date');
            
        // Create a complete series for the last 7 days
        $salesChartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $salesData = $salesByDay[$date] ?? null;
            
            $salesChartData[$date] = [
                'count' => $salesData ? $salesData->count : 0,
                'total' => $salesData ? $salesData->total : 0,
                'display' => Carbon::parse($date)->format('M d')
            ];
        }
        
        return view('admin.reports.index', compact(
            'currencySymbol',
            'companyName',
            'todaySales',
            'todayRevenue',
            'monthSales',
            'monthRevenue',
            'lowStockProducts',
            'outOfStockProducts',
            'recentSales',
            'salesChartData'
        ));
    }

    /**
     * Display sales report
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function sales(Request $request)
    {
        // Get company settings
        $currencySymbol = $this->getSetting('currency_symbol', '$');
        $companyName = $this->getSetting('company_name', 'Our Store');
        $taxPercentage = (float)$this->getSetting('tax_percentage', 0);
        
        // Handle date range selection
        $dateRange = $request->input('date_range', 'today');
        
        // Set start and end dates based on selected range
        $startDate = Carbon::today();
        $endDate = Carbon::today();
        
        switch ($dateRange) {
            case 'yesterday':
                $startDate = Carbon::yesterday();
                $endDate = Carbon::yesterday();
                break;
            case 'this_week':
                $startDate = Carbon::now()->startOfWeek();
                break;
            case 'last_week':
                $startDate = Carbon::now()->subWeek()->startOfWeek();
                $endDate = Carbon::now()->subWeek()->endOfWeek();
                break;
            case 'this_month':
                $startDate = Carbon::now()->startOfMonth();
                break;
            case 'last_month':
                $startDate = Carbon::now()->subMonth()->startOfMonth();
                $endDate = Carbon::now()->subMonth()->endOfMonth();
                break;
            case 'custom':
                $startDate = $request->filled('start_date') 
                    ? Carbon::parse($request->input('start_date')) 
                    : Carbon::today()->subDays(30);
                $endDate = $request->filled('end_date') 
                    ? Carbon::parse($request->input('end_date')) 
                    : Carbon::today();
                break;
        }
        
        // Start building the sales query
        $salesQuery = Sale::with(['customer', 'user', 'items.product'])
            ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->where('status', '!=', 'voided');
        
        // Apply payment method filter if provided
        if ($request->filled('payment_method')) {
            $salesQuery->where('payment_method', $request->input('payment_method'));
        }
        
        // Apply cashier filter if provided
        if ($request->filled('cashier')) {
            $salesQuery->where('user_id', $request->input('cashier'));
        }
        
        // Get sales for the filtered period
        $sales = $salesQuery->latest()->paginate(20);
        
        // Get sales summary by period (day, week, or month)
        $groupBy = 'date'; // Default to daily grouping
        if ($startDate->diffInDays($endDate) > 31) {
            $groupBy = 'month';
        } elseif ($startDate->diffInDays($endDate) > 7) {
            $groupBy = 'week';
        }
        
        $salesByPeriod = $this->getSalesByPeriod($salesQuery->getQuery(), $groupBy, $startDate, $endDate);
        
        // Get top products for the period
        $topProducts = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->whereBetween('sales.created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->where('sales.status', '!=', 'voided')
            ->select(
                'products.id',
                'products.name',
                DB::raw('SUM(sale_items.quantity) as total_quantity'),
                DB::raw('SUM(sale_items.subtotal) as total_revenue')
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_quantity')
            ->limit(10)
            ->get();
            
        // Get top categories for the period
        $topCategories = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->whereBetween('sales.created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->where('sales.status', '!=', 'voided')
            ->select(
                'categories.id',
                'categories.name',
                DB::raw('SUM(sale_items.quantity) as total_quantity'),
                DB::raw('SUM(sale_items.subtotal) as total_revenue')
            )
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total_quantity')
            ->limit(5)
            ->get();
            
        // Get all the cashiers for the filter dropdown
        $cashiers = User::where('role', 'cashier')->get();
        
        // Calculate totals
        $allSales = $salesQuery->get();
        $totalSales = $allSales->count();
        $totalItems = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->whereBetween('sales.created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->where('sales.status', '!=', 'voided')
            ->sum('sale_items.quantity');
        $totalRevenue = $allSales->sum('total_amount');
        
        // Calculate average sale value
        $averageSaleValue = $totalSales > 0 ? $totalRevenue / $totalSales : 0;
        
        // Get payment method breakdown
        $paymentMethodBreakdown = $allSales->groupBy('payment_method')
            ->map(function ($salesGroup) {
                return [
                    'count' => $salesGroup->count(),
                    'total' => $salesGroup->sum('total_amount')
                ];
            });
        
        return view('admin.reports.sales', compact(
            'sales',
            'salesByPeriod',
            'topProducts',
            'topCategories',
            'totalSales',
            'totalRevenue',
            'totalItems',
            'averageSaleValue',
            'dateRange',
            'startDate',
            'endDate',
            'cashiers',
            'paymentMethodBreakdown',
            'currencySymbol',
            'companyName',
            'taxPercentage'
        ));
    }
    
    /**
     * Get sales grouped by period (day, week, or month)
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string  $groupBy
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     * @return array
     */
    protected function getSalesByPeriod($query, $groupBy, $startDate, $endDate)
    {
        $result = [];
        
        switch ($groupBy) {
            case 'month':
                $rawSelect = "DATE_FORMAT(created_at, '%Y-%m') as period";
                $format = 'Y-m';
                $periodFormat = 'M Y';
                break;
            case 'week':
                $rawSelect = "DATE_FORMAT(created_at, '%Y-%u') as period";
                $format = 'Y-W';
                $periodFormat = 'Week %W, %Y';
                break;
            default: // day
                $rawSelect = "DATE(created_at) as period";
                $format = 'Y-m-d';
                $periodFormat = 'M d, Y';
                break;
        }
        
        $data = (clone $query)
            ->select(DB::raw($rawSelect), DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as total'))
            ->groupBy(DB::raw('period'))
            ->orderBy(DB::raw('period'))
            ->get();
            
        // Create a complete series of dates
        $current = clone $startDate;
        while ($current->lte($endDate)) {
            $periodKey = $current->format($format);
            
            if ($groupBy === 'week') {
                // For weeks, we need special handling to match MySQL's week numbering
                $periodKey = $current->format('Y') . '-' . $current->format('W');
            }
            
            $displayDate = $current->copy()->format($periodFormat);
            if ($groupBy === 'week') {
                $displayDate = 'Week ' . $current->format('W') . ', ' . $current->format('Y');
            }
            
            $result[$displayDate] = [
                'count' => 0,
                'total' => 0
            ];
            
            // Advance to next period
            switch ($groupBy) {
                case 'month':
                    $current->addMonth();
                    break;
                case 'week':
                    $current->addWeek();
                    break;
                default:
                    $current->addDay();
                    break;
            }
        }
        
        // Fill in actual data
        foreach ($data as $item) {
            $date = Carbon::parse($item->period);
            $displayDate = $date->format($periodFormat);
            
            if ($groupBy === 'week') {
                // For weeks, construct the display name manually
                $year = substr($item->period, 0, 4);
                $week = substr($item->period, 5);
                $displayDate = "Week {$week}, {$year}";
            }
            
            if (isset($result[$displayDate])) {
                $result[$displayDate] = [
                    'count' => $item->count,
                    'total' => $item->total
                ];
            }
        }
        
        return $result;
    }
    
    /**
     * Export sales report to CSV
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportSales(Request $request)
    {
        // Get settings
        $currencySymbol = $this->getSetting('currency_symbol', '$');
        $companyName = $this->getSetting('company_name', 'Our Store');
        $sanitizedCompanyName = preg_replace('/[^A-Za-z0-9_\-]/', '', $companyName); // Sanitize for filename
        
        // Handle date range selection (similar to sales method)
        $dateRange = $request->input('date_range', 'today');
        
        // Set start and end dates based on selected range
        $startDate = Carbon::today();
        $endDate = Carbon::today();
        
        switch ($dateRange) {
            case 'yesterday':
                $startDate = Carbon::yesterday();
                $endDate = Carbon::yesterday();
                break;
            case 'this_week':
                $startDate = Carbon::now()->startOfWeek();
                break;
            case 'last_week':
                $startDate = Carbon::now()->subWeek()->startOfWeek();
                $endDate = Carbon::now()->subWeek()->endOfWeek();
                break;
            case 'this_month':
                $startDate = Carbon::now()->startOfMonth();
                break;
            case 'last_month':
                $startDate = Carbon::now()->subMonth()->startOfMonth();
                $endDate = Carbon::now()->subMonth()->endOfMonth();
                break;
            case 'custom':
                $startDate = $request->filled('start_date') 
                    ? Carbon::parse($request->input('start_date')) 
                    : Carbon::today()->subDays(30);
                $endDate = $request->filled('end_date') 
                    ? Carbon::parse($request->input('end_date')) 
                    : Carbon::today();
                break;
        }
        
        // Start building the sales query
        $salesQuery = Sale::with(['customer', 'user', 'items.product'])
            ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->where('status', '!=', 'voided');
        
        // Apply payment method filter if provided
        if ($request->filled('payment_method')) {
            $salesQuery->where('payment_method', $request->input('payment_method'));
        }
        
        // Apply cashier filter if provided
        if ($request->filled('cashier')) {
            $salesQuery->where('user_id', $request->input('cashier'));
        }
        
        $sales = $salesQuery->orderBy('created_at')->get();
        
        // Prepare date range for filename
        $dateRangeText = $startDate->format('Y-m-d');
        if ($startDate->format('Y-m-d') !== $endDate->format('Y-m-d')) {
            $dateRangeText .= '_to_' . $endDate->format('Y-m-d');
        }
        
        $filename = "{$sanitizedCompanyName}_sales_{$dateRangeText}.csv";
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];
        
        $callback = function() use ($sales, $companyName, $startDate, $endDate, $currencySymbol) {
            $file = fopen('php://output', 'w');
            
            // Add header with company name and date range
            fputcsv($file, [$companyName . ' - Sales Report']);
            
            if ($startDate->format('Y-m-d') === $endDate->format('Y-m-d')) {
                fputcsv($file, ['Date: ' . $startDate->format('Y-m-d')]);
            } else {
                fputcsv($file, ['Date Range: ' . $startDate->format('Y-m-d') . ' to ' . $endDate->format('Y-m-d')]);
            }
            
            fputcsv($file, []); // Empty line
            
            if ($sales->count() > 0) {
                // Define CSV headers
                $headers = [
                    'Receipt Number',
                    'Date & Time',
                    'Customer',
                    'Cashier',
                    'Payment Method',
                    'Payment Status',
                    'Items',
                    'Total Amount'
                ];
                
                fputcsv($file, $headers);
                
                // Add sale rows
                foreach ($sales as $sale) {
                    $row = [
                        $sale->receipt_number,
                        $sale->created_at->format('Y-m-d H:i:s'),
                        $sale->customer ? $sale->customer->name : 'Walk-in Customer',
                        $sale->user ? $sale->user->name : 'Unknown',
                        ucfirst($sale->payment_method),
                        ucfirst($sale->payment_status),
                        $sale->items->sum('quantity'),
                        $currencySymbol . number_format($sale->total_amount, 2)
                    ];
                    
                    fputcsv($file, $row);
                }
                
                // Add summary row
                fputcsv($file, []); // Empty line
                fputcsv($file, ['SUMMARY']);
                fputcsv($file, ['Total Sales', $sales->count()]);
                fputcsv($file, ['Total Items', $sales->sum(function ($sale) { return $sale->items->sum('quantity'); })]);
                fputcsv($file, ['Total Revenue', $currencySymbol . number_format($sales->sum('total_amount'), 2)]);
                
                // Add payment method breakdown
                fputcsv($file, []); // Empty line
                fputcsv($file, ['PAYMENT METHOD BREAKDOWN']);
                
                $paymentMethods = $sales->groupBy('payment_method');
                foreach ($paymentMethods as $method => $salesGroup) {
                    fputcsv($file, [
                        ucfirst($method),
                        $salesGroup->count() . ' sales',
                        $currencySymbol . number_format($salesGroup->sum('total_amount'), 2)
                    ]);
                }
            } else {
                fputcsv($file, ['No sales data for this period']);
            }
            
            fclose($file);
        };
        
        return Response::stream($callback, 200, $headers);
    }
    
    /**
     * Display inventory report
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function inventory(Request $request)
    {
        // Get company settings
        $currencySymbol = $this->getSetting('currency_symbol', '$');
        $companyName = $this->getSetting('company_name', 'Our Store');
        $lowStockThreshold = (int)$this->getSetting('low_stock_threshold', 10);
        
        // Filter parameters
        $categoryId = $request->input('category');
        $stockStatus = $request->input('stock_status', 'all');
        $search = $request->input('search');
        
        // Start building the query
        $query = Product::with('category');
        
        // Apply category filter
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }
        
        // Apply stock status filter
        switch ($stockStatus) {
            case 'in_stock':
                $query->where('stock', '>', 0);
                break;
            case 'low_stock':
                $query->where('stock', '<=', $lowStockThreshold)
                    ->where('stock', '>', 0);
                break;
            case 'out_of_stock':
                $query->where('stock', 0);
                break;
        }
        
        // Apply search filter
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }
        
        // Get products
        $products = $query->orderBy('name')->paginate(20);
        
        // Get all categories for filter dropdown
        $categories = Category::orderBy('name')->get();
        
        // Stock summary statistics
        $totalProducts = Product::count();
        $inStockProducts = Product::where('stock', '>', 0)->count();
        $lowStockProducts = Product::where('stock', '<=', $lowStockThreshold)
            ->where('stock', '>', 0)
            ->count();
        $outOfStockProducts = Product::where('stock', 0)->count();
        
        // Inventory value
        $inventoryValue = Product::sum(DB::raw('stock * cost_price'));
        $retailValue = Product::sum(DB::raw('stock * price'));
        
        return view('admin.reports.inventory', compact(
            'products',
            'categories',
            'categoryId',
            'stockStatus',
            'search',
            'totalProducts',
            'inStockProducts',
            'lowStockProducts',
            'outOfStockProducts',
            'inventoryValue',
            'retailValue',
            'lowStockThreshold',
            'currencySymbol',
            'companyName'
        ));
    }
    
    /**
     * Export inventory report to CSV
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportInventory(Request $request)
    {
        // Get settings
        $currencySymbol = $this->getSetting('currency_symbol', '$');
        $companyName = $this->getSetting('company_name', 'Our Store');
        $sanitizedCompanyName = preg_replace('/[^A-Za-z0-9_\-]/', '', $companyName); // Sanitize for filename
        $lowStockThreshold = (int)$this->getSetting('low_stock_threshold', 10);
        
        // Filter parameters (similar to inventory method)
        $categoryId = $request->input('category');
        $stockStatus = $request->input('stock_status', 'all');
        $search = $request->input('search');
        
        // Start building the query
        $query = Product::with('category');
        
        // Apply category filter
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }
        
        // Apply stock status filter
        switch ($stockStatus) {
            case 'in_stock':
                $query->where('stock', '>', 0);
                break;
            case 'low_stock':
                $query->where('stock', '<=', $lowStockThreshold)
                    ->where('stock', '>', 0);
                break;
            case 'out_of_stock':
                $query->where('stock', 0);
                break;
        }
        
        // Apply search filter
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }
        
        $products = $query->orderBy('name')->get();
        
        $filename = "{$sanitizedCompanyName}_inventory_" . date('Y-m-d') . ".csv";
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];
        
        $callback = function() use ($products, $companyName, $currencySymbol, $lowStockThreshold) {
            $file = fopen('php://output', 'w');
            
            // Add header with company name
            fputcsv($file, [$companyName . ' - Inventory Report']);
            fputcsv($file, ['Generated on: ' . date('Y-m-d H:i')]);
            fputcsv($file, []); // Empty line
            
            if ($products->count() > 0) {
                // Define CSV headers
                $headers = [
                    'Product ID',
                    'Name',
                    'SKU',
                    'Barcode',
                    'Category',
                    'Stock Level',
                    'Stock Status',
                    'Cost Price',
                    'Selling Price',
                    'Stock Value (Cost)',
                    'Stock Value (Retail)'
                ];
                
                fputcsv($file, $headers);
                
                // Add product rows
                $totalCostValue = 0;
                $totalRetailValue = 0;
                
                foreach ($products as $product) {
                    $costValue = $product->stock * $product->cost_price;
                    $retailValue = $product->stock * $product->price;
                    
                    $totalCostValue += $costValue;
                    $totalRetailValue += $retailValue;
                    
                    // Determine stock status
                    $stockStatus = 'In Stock';
                    if ($product->stock == 0) {
                        $stockStatus = 'Out of Stock';
                    } elseif ($product->stock <= $lowStockThreshold) {
                        $stockStatus = 'Low Stock';
                    }
                    
                    $row = [
                        $product->id,
                        $product->name,
                        $product->sku,
                        $product->barcode,
                        $product->category ? $product->category->name : 'N/A',
                        $product->stock,
                        $stockStatus,
                        $currencySymbol . number_format($product->cost_price, 2),
                        $currencySymbol . number_format($product->price, 2),
                        $currencySymbol . number_format($costValue, 2),
                        $currencySymbol . number_format($retailValue, 2)
                    ];
                    
                    fputcsv($file, $row);
                }
                
                // Add summary row
                fputcsv($file, []); // Empty line
                fputcsv($file, ['SUMMARY']);
                fputcsv($file, ['Total Products', $products->count()]);
                fputcsv($file, ['Total Inventory Cost Value', $currencySymbol . number_format($totalCostValue, 2)]);
                fputcsv($file, ['Total Inventory Retail Value', $currencySymbol . number_format($totalRetailValue, 2)]);
            } else {
                fputcsv($file, ['No products match the selected criteria']);
            }
            
            fclose($file);
        };
        
        return Response::stream($callback, 200, $headers);
    }
    
    /**
     * Display customers report
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function customers(Request $request)
    {
        // Get company settings
        $currencySymbol = $this->getSetting('currency_symbol', '$');
        $companyName = $this->getSetting('company_name', 'Our Store');
        
        // Filter parameters
        $search = $request->input('search');
        $creditStatus = $request->input('credit_status', 'all');
        $sortBy = $request->input('sort_by', 'sales');
        
        // Start building the query
        $query = Customer::withCount('sales')
            ->withSum('sales', 'total_amount');
        
        // Apply search filter
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        
        // Apply credit status filter
        if ($creditStatus === 'with_balance') {
            $query->whereHas('credits', function($q) {
                $q->where('balance', '>', 0);
            });
        }
        
        // Apply sorting
        switch ($sortBy) {
            case 'name':
                $query->orderBy('name');
                break;
            case 'sales_count':
                $query->orderByDesc('sales_count');
                break;
            case 'sales_amount':
                $query->orderByDesc('sales_sum_total_amount');
                break;
            default:
                $query->orderByDesc('sales_count');
                break;
        }
        
        // Get customers
        $customers = $query->paginate(20);
        
        // Customer statistics
        $totalCustomers = Customer::count();
        $customersWithCredit = Customer::whereHas('credits', function($q) {
            $q->where('balance', '>', 0);
        })->count();
        
        $totalCreditAmount = DB::table('credits')
            ->sum('balance');
        
        return view('admin.reports.customers', compact(
            'customers',
            'search',
            'creditStatus',
            'sortBy',
            'totalCustomers',
            'customersWithCredit',
            'totalCreditAmount',
            'currencySymbol',
            'companyName'
        ));
    }
    
    /**
     * Display cashier performance report
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function cashierPerformance(Request $request)
    {
        // Get company settings
        $currencySymbol = $this->getSetting('currency_symbol', '$');
        $companyName = $this->getSetting('company_name', 'Our Store');
        
        // Handle date range selection (similar to sales method)
        $dateRange = $request->input('date_range', 'this_month');
        
        // Set start and end dates based on selected range
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now();
        
        switch ($dateRange) {
            case 'today':
                $startDate = Carbon::today();
                $endDate = Carbon::today();
                break;
            case 'yesterday':
                $startDate = Carbon::yesterday();
                $endDate = Carbon::yesterday();
                break;
            case 'this_week':
                $startDate = Carbon::now()->startOfWeek();
                break;
            case 'last_week':
                $startDate = Carbon::now()->subWeek()->startOfWeek();
                $endDate = Carbon::now()->subWeek()->endOfWeek();
                break;
            case 'last_month':
                $startDate = Carbon::now()->subMonth()->startOfMonth();
                $endDate = Carbon::now()->subMonth()->endOfMonth();
                break;
            case 'custom':
                $startDate = $request->filled('start_date') 
                    ? Carbon::parse($request->input('start_date')) 
                    : Carbon::now()->subDays(30);
                $endDate = $request->filled('end_date') 
                    ? Carbon::parse($request->input('end_date')) 
                    : Carbon::now();
                break;
        }
        
        // Get cashier performance data
        $cashiers = User::where('role', 'cashier')
            ->withCount(['sales' => function($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
                      ->where('status', '!=', 'voided');
            }])
            ->withSum(['sales' => function($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
                      ->where('status', '!=', 'voided');
            }], 'total_amount')
            ->get();
            
        // Calculate average sale value and items per sale for each cashier
        foreach ($cashiers as $cashier) {
            $cashier->avgSaleValue = $cashier->sales_count > 0 
                ? ($cashier->sales_sum_total_amount / $cashier->sales_count) 
                : 0;
                
            // Get total items sold by this cashier
            $totalItems = DB::table('sale_items')
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->where('sales.user_id', $cashier->id)
                ->whereBetween('sales.created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
                ->where('sales.status', '!=', 'voided')
                ->sum('sale_items.quantity');
                
            $cashier->totalItems = $totalItems;
            $cashier->itemsPerSale = $cashier->sales_count > 0 
                ? ($totalItems / $cashier->sales_count) 
                : 0;
        }
        
        // Sort cashiers by total sales amount
        $cashiers = $cashiers->sortByDesc('sales_sum_total_amount');
        
        return view('admin.reports.cashier-performance', compact(
            'cashiers',
            'dateRange',
            'startDate',
            'endDate',
            'currencySymbol',
            'companyName'
        ));
    }
}