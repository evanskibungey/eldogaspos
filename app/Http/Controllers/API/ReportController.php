<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Category;
use App\Models\Customer;
use App\Models\User;
use App\Models\StockMovement;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;

class ReportController extends Controller
{
    /**
     * Get sales report with filtering options
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function salesReport(Request $request)
    {
        try {
            $dateRange = $request->input('date_range', 'this_month');
            $startDate = null;
            $endDate = null;
            
            // Set date range based on selection
            switch ($dateRange) {
                case 'today':
                    $startDate = Carbon::today();
                    $endDate = Carbon::today()->endOfDay();
                    break;
                case 'yesterday':
                    $startDate = Carbon::yesterday();
                    $endDate = Carbon::yesterday()->endOfDay();
                    break;
                case 'this_week':
                    $startDate = Carbon::now()->startOfWeek();
                    $endDate = Carbon::now()->endOfWeek();
                    break;
                case 'last_week':
                    $startDate = Carbon::now()->subWeek()->startOfWeek();
                    $endDate = Carbon::now()->subWeek()->endOfWeek();
                    break;
                case 'this_month':
                    $startDate = Carbon::now()->startOfMonth();
                    $endDate = Carbon::now()->endOfMonth();
                    break;
                case 'last_month':
                    $startDate = Carbon::now()->subMonth()->startOfMonth();
                    $endDate = Carbon::now()->subMonth()->endOfMonth();
                    break;
                case 'custom':
                    $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : Carbon::today()->subDays(30);
                    $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : Carbon::today()->endOfDay();
                    break;
                default:
                    $startDate = Carbon::today();
                    $endDate = Carbon::today()->endOfDay();
            }
            
            // Get sales data within date range (with eager loading)
            $salesQuery = Sale::with(['items.product', 'customer', 'user'])
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where('status', '!=', 'voided');
                
            // Apply any additional filters
            if ($request->filled('payment_method')) {
                $salesQuery->where('payment_method', $request->input('payment_method'));
            }
            
            if ($request->filled('cashier')) {
                $salesQuery->where('user_id', $request->input('cashier'));
            }
            
            $sales = $salesQuery->orderBy('created_at', 'desc')->get();
            
            // Calculate summary statistics
            $totalSales = $sales->count();
            $totalRevenue = $sales->sum('total_amount');
            $totalItems = $sales->sum(function ($sale) {
                return $sale->items->sum('quantity');
            });
            
            // Get sales by hour (for today) or by day (for other periods)
            $salesByPeriod = [];
            if ($dateRange === 'today' || $dateRange === 'yesterday') {
                // Sales by hour
                $salesByPeriod = $this->getSalesByHour($startDate, $endDate);
            } else {
                // Sales by day
                $salesByPeriod = $this->getSalesByDay($startDate, $endDate);
            }
            
            // Get top products
            $topProducts = $this->getTopProducts($startDate, $endDate, 10);
            
            // Get top categories
            $topCategories = $this->getTopCategories($startDate, $endDate, 5);
            
            // Get all cashiers for filter
            $cashiers = User::where('role', 'cashier')->get();
            
            return response()->json([
                'sales' => $sales,
                'date_range' => $dateRange,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'cashiers' => $cashiers,
                'summary' => [
                    'total_sales' => $totalSales,
                    'total_revenue' => $totalRevenue,
                    'total_items' => $totalItems,
                ],
                'sales_by_period' => $salesByPeriod,
                'top_products' => $topProducts,
                'top_categories' => $topCategories
            ]);
        } catch (\Exception $e) {
            Log::error('Error in sales report API: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving sales report: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get sales aggregated by hour
     */
    private function getSalesByHour($startDate, $endDate)
    {
        // Using a database-agnostic way to extract the hour
        return Sale::where('status', '!=', 'voided')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%H") as hour'), 
                DB::raw('COUNT(*) as count'), 
                DB::raw('SUM(total_amount) as total')
            )
            ->groupBy(DB::raw('DATE_FORMAT(created_at, "%H")'))
            ->orderBy('hour')
            ->get()
            ->keyBy('hour')
            ->toArray();
    }
    
    /**
     * Get sales aggregated by day
     */
    private function getSalesByDay($startDate, $endDate)
    {
        // Using a database-agnostic way to extract the date
        return Sale::where('status', '!=', 'voided')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(created_at) as date'), 
                DB::raw('COUNT(*) as count'), 
                DB::raw('SUM(total_amount) as total')
            )
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->keyBy('date')
            ->toArray();
    }
    
    /**
     * Get top selling products within date range
     */
    private function getTopProducts($startDate, $endDate, $limit = 10)
    {
        return DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->whereBetween('sales.created_at', [$startDate, $endDate])
            ->where('sales.status', '!=', 'voided')
            ->select(
                'products.id',
                'products.name',
                DB::raw('SUM(sale_items.quantity) as total_quantity'),
                DB::raw('SUM(sale_items.subtotal) as total_revenue')
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_quantity')
            ->limit($limit)
            ->get();
    }
    
    /**
     * Get top selling categories within date range
     */
    private function getTopCategories($startDate, $endDate, $limit = 5)
    {
        return DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->whereBetween('sales.created_at', [$startDate, $endDate])
            ->where('sales.status', '!=', 'voided')
            ->select(
                'categories.id',
                'categories.name',
                DB::raw('SUM(sale_items.quantity) as total_quantity'),
                DB::raw('SUM(sale_items.subtotal) as total_revenue')
            )
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total_quantity')
            ->limit($limit)
            ->get();
    }
    
    /**
     * Export sales report to CSV
     */
    public function exportSalesReport(Request $request)
    {
        try {
            $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : Carbon::today()->subDays(30);
            $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : Carbon::today()->endOfDay();
            
            $sales = Sale::with(['items.product', 'customer', 'user'])
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where('status', '!=', 'voided')
                ->orderBy('created_at', 'desc')
                ->get();
                
            $csvData = $this->formatSalesForCSV($sales);
            
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="sales_report_' . now()->format('Y-m-d') . '.csv"',
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0'
            ];
            
            $callback = function() use ($csvData) {
                $file = fopen('php://output', 'w');
                
                if (!empty($csvData)) {
                    fputcsv($file, array_keys($csvData[0]));
                    
                    foreach ($csvData as $row) {
                        fputcsv($file, $row);
                    }
                } else {
                    fputcsv($file, ['No sales data found']);
                }
                
                fclose($file);
            };
            
            return Response::stream($callback, 200, $headers);
        } catch (\Exception $e) {
            Log::error('Error exporting sales report: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error exporting sales report: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Format sales data for CSV export
     */
    private function formatSalesForCSV($sales)
    {
        $data = [];
        
        foreach ($sales as $sale) {
            $row = [
                'Receipt Number' => $sale->receipt_number,
                'Date' => $sale->created_at->format('Y-m-d H:i:s'),
                'Customer' => $sale->customer ? $sale->customer->name : 'N/A',
                'Cashier' => $sale->user ? $sale->user->name : 'N/A',
                'Payment Method' => ucfirst($sale->payment_method),
                'Payment Status' => ucfirst($sale->payment_status),
                'Total Amount' => number_format($sale->total_amount, 2),
                'Status' => ucfirst($sale->status),
                'Items' => $sale->items->sum('quantity'),
                'Products' => $sale->items->count()
            ];
            
            $data[] = $row;
        }
        
        return $data;
    }
    
    /**
     * Get inventory report with filtering options
     */
    public function inventoryReport(Request $request)
{
    try {
        // Get all products with their categories
        $query = Product::with('category');
        
        // Apply filters
        if ($request->filled('category')) {
            $query->where('category_id', $request->input('category'));
        }
        
        if ($request->filled('stock_status')) {
            switch ($request->input('stock_status')) {
                case 'low':
                    $query->whereColumn('stock', '<=', 'min_stock')
                          ->where('stock', '>', 0);
                    break;
                case 'out':
                    $query->where('stock', 0);
                    break;
                case 'in':
                    $query->where('stock', '>', 0);
                    break;
            }
        }
        
        // Get pagination parameters
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 50);

        // Get categories for filter
        $categories = Category::orderBy('name')->get();

        // Paginate the results
        $paginatedProducts = $query->orderBy('name')->paginate($perPage);

        // Clone query for statistics calculation
        $statsQuery = clone $query;
        $statsQuery->getQuery()->orders = null; // Remove ordering for efficiency
        $productsForStats = $statsQuery->get();
        
        // Calculate summary statistics
        $totalProducts = $paginatedProducts->total();
        $totalValue = $productsForStats->sum(function ($product) {
            return $product->stock * $product->price;
        });
        $lowStockCount = $productsForStats->filter(function ($product) {
            return $product->stock <= $product->min_stock && $product->stock > 0;
        })->count();
        $outOfStockCount = $productsForStats->where('stock', 0)->count();
        
        return response()->json([
            'products' => $paginatedProducts->items(),
            'categories' => $categories,
            'summary' => [
                'total_products' => $totalProducts,
                'total_value' => $totalValue,
                'low_stock_count' => $lowStockCount,
                'out_of_stock_count' => $outOfStockCount
            ],
            'current_page' => $paginatedProducts->currentPage(),
            'last_page' => $paginatedProducts->lastPage(),
            'hasMorePages' => $paginatedProducts->hasMorePages(),
            'total' => $paginatedProducts->total(),
            'per_page' => $perPage
        ]);
    } catch (\Exception $e) {
        Log::error('Error in inventory report API: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error retrieving inventory report: ' . $e->getMessage()
        ], 500);
    }
}
    
    /**
     * Export inventory report to CSV
     */
    public function exportInventoryReport(Request $request)
    {
        try {
            $query = Product::with('category');
            
            if ($request->filled('category')) {
                $query->where('category_id', $request->input('category'));
            }
            
            if ($request->filled('stock_status')) {
                switch ($request->input('stock_status')) {
                    case 'low':
                        $query->whereColumn('stock', '<=', 'min_stock')
                              ->where('stock', '>', 0);
                        break;
                    case 'out':
                        $query->where('stock', 0);
                        break;
                    case 'in':
                        $query->where('stock', '>', 0);
                        break;
                }
            }
            
            $products = $query->orderBy('name')->get();
            
            $csvData = [];
            
            foreach ($products as $product) {
                $row = [
                    'ID' => $product->id,
                    'Name' => $product->name,
                    'SKU' => $product->sku,
                    'Category' => $product->category ? $product->category->name : 'Uncategorized',
                    'Current Stock' => $product->stock,
                    'Min Stock' => $product->min_stock,
                    'Price' => number_format($product->price, 2),
                    'Stock Value' => number_format($product->stock * $product->price, 2),
                    'Status' => ucfirst($product->status)
                ];
                
                $csvData[] = $row;
            }
            
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="inventory_report_' . now()->format('Y-m-d') . '.csv"',
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0'
            ];
            
            $callback = function() use ($csvData) {
                $file = fopen('php://output', 'w');
                
                if (!empty($csvData)) {
                    fputcsv($file, array_keys($csvData[0]));
                    
                    foreach ($csvData as $row) {
                        fputcsv($file, $row);
                    }
                } else {
                    fputcsv($file, ['No product data found']);
                }
                
                fclose($file);
            };
            
            return Response::stream($callback, 200, $headers);
        } catch (\Exception $e) {
            Log::error('Error exporting inventory report: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error exporting inventory report: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get users report with performance metrics
     */
    public function usersReport(Request $request)
    {
        try {
            $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : Carbon::today()->subDays(30);
            $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : Carbon::today()->endOfDay();
            
            // Get all cashiers (users with role 'cashier')
            $cashiers = User::where('role', 'cashier')->get();
            
            // Get sales performance for each cashier
            $cashierPerformance = [];
            
            foreach ($cashiers as $cashier) {
                // Get all sales for this cashier in the date range
                $salesQuery = Sale::where('user_id', $cashier->id)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->where('status', '!=', 'voided');
                    
                $salesCount = $salesQuery->count();
                $salesTotal = $salesQuery->sum('total_amount');
                    
                // Get items sold by this cashier
                $itemsSold = DB::table('sale_items')
                    ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                    ->where('sales.user_id', $cashier->id)
                    ->whereBetween('sales.created_at', [$startDate, $endDate])
                    ->where('sales.status', '!=', 'voided')
                    ->sum('sale_items.quantity');
                    
                $averagePerSale = $salesCount > 0 ? $salesTotal / $salesCount : 0;
                
                $cashierPerformance[$cashier->id] = [
                    'id' => $cashier->id,
                    'name' => $cashier->name,
                    'email' => $cashier->email,
                    'sales_count' => $salesCount,
                    'sales_total' => $salesTotal,
                    'items_sold' => $itemsSold,
                    'average_per_sale' => $averagePerSale
                ];
            }
            
            // Sort by sales total (descending)
            uasort($cashierPerformance, function ($a, $b) {
                return $b['sales_total'] <=> $a['sales_total'];
            });
            
            return response()->json([
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'cashiers' => array_values($cashierPerformance) // Convert to indexed array
            ]);
        } catch (\Exception $e) {
            Log::error('Error in users report API: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving users report: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Export users report to CSV
     */
    public function exportUsersReport(Request $request)
    {
        try {
            $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : Carbon::today()->subDays(30);
            $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : Carbon::today()->endOfDay();
            
            $users = User::where('role', 'cashier')->get();
            $csvData = [];
            
            foreach ($users as $user) {
                $salesCount = Sale::where('user_id', $user->id)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->where('status', '!=', 'voided')
                    ->count();
                    
                $salesTotal = Sale::where('user_id', $user->id)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->where('status', '!=', 'voided')
                    ->sum('total_amount');
                    
                $itemsSold = DB::table('sale_items')
                    ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                    ->where('sales.user_id', $user->id)
                    ->whereBetween('sales.created_at', [$startDate, $endDate])
                    ->where('sales.status', '!=', 'voided')
                    ->sum('sale_items.quantity');
                    
                $row = [
                    'ID' => $user->id,
                    'Name' => $user->name,
                    'Email' => $user->email,
                    'Sales Count' => $salesCount,
                    'Total Revenue' => number_format($salesTotal, 2),
                    'Items Sold' => $itemsSold,
                    'Average Per Sale' => $salesCount > 0 ? number_format($salesTotal / $salesCount, 2) : '0.00'
                ];
                
                $csvData[] = $row;
            }
            
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="user_report_' . now()->format('Y-m-d') . '.csv"',
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0'
            ];
            
            $callback = function() use ($csvData) {
                $file = fopen('php://output', 'w');
                
                if (!empty($csvData)) {
                    fputcsv($file, array_keys($csvData[0]));
                    
                    foreach ($csvData as $row) {
                        fputcsv($file, $row);
                    }
                } else {
                    fputcsv($file, ['No user data found']);
                }
                
                fclose($file);
            };
            
            return Response::stream($callback, 200, $headers);
        } catch (\Exception $e) {
            Log::error('Error exporting users report: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error exporting users report: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get stock movements report with filtering options
     */
    public function stockMovementsReport(Request $request)
    {
        try {
            $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : Carbon::today()->subDays(30);
            $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : Carbon::today()->endOfDay();
            
            $query = StockMovement::with(['product', 'user'])
                ->whereBetween('created_at', [$startDate, $endDate]);
                
            // Apply filters
            if ($request->filled('type')) {
                $query->where('type', $request->input('type'));
            }
            
            if ($request->filled('product')) {
                $query->where('product_id', $request->input('product'));
            }
            
            // Get paginated results
            $page = $request->input('page', 1);
            $limit = $request->input('limit', 50);
            $movements = $query->orderBy('created_at', 'desc')->paginate($limit, ['*'], 'page', $page);
            
            // Get products for filter
            $products = Product::orderBy('name')->get();
            
            return response()->json([
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'movements' => $movements,
                'products' => $products->map(function($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name
                    ];
                })
            ]);
        } catch (\Exception $e) {
            Log::error('Error in stock movements report API: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving stock movements report: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Export stock movements report to CSV
     */
    public function exportStockMovementsReport(Request $request)
    {
        try {
            $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : Carbon::today()->subDays(30);
            $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : Carbon::today()->endOfDay();
            
            $query = StockMovement::with(['product', 'user'])
                ->whereBetween('created_at', [$startDate, $endDate]);
                
            if ($request->filled('type')) {
                $query->where('type', $request->input('type'));
            }
            
            if ($request->filled('product')) {
                $query->where('product_id', $request->input('product'));
            }
            
            $movements = $query->orderBy('created_at', 'desc')->get();
            
            $csvData = [];
            
            foreach ($movements as $movement) {
                $row = [
                    'ID' => $movement->id,
                    'Date' => $movement->created_at->format('Y-m-d H:i:s'),
                    'Product' => $movement->product ? $movement->product->name : 'Unknown Product',
                    'Type' => ucfirst($movement->type),
                    'Quantity' => $movement->quantity,
                    'Reference' => $movement->reference_type . ' #' . $movement->reference_id,
                    'User' => $movement->user ? $movement->user->name : 'System',
                    'Notes' => $movement->notes
                ];
                
                $csvData[] = $row;
            }
            
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="stock_movements_' . now()->format('Y-m-d') . '.csv"',
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0'
            ];
            
            $callback = function() use ($csvData) {
                $file = fopen('php://output', 'w');
                
                if (!empty($csvData)) {
                    fputcsv($file, array_keys($csvData[0]));
                    
                    foreach ($csvData as $row) {
                        fputcsv($file, $row);
                    }
                } else {
                    fputcsv($file, ['No stock movement data found']);
                }
                
                fclose($file);
            };
            
            return Response::stream($callback, 200, $headers);
        } catch (\Exception $e) {
            Log::error('Error exporting stock movements report: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error exporting stock movements report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get customers report with credit analysis
     */
    public function customersReport(Request $request)
    {
        try {
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
                $query->where('balance', '>', 0);
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
            
            // Get paginated results
            $customers = $query->paginate(20);
            
            // Customer statistics
            $totalCustomers = Customer::count();
            $customersWithCredit = Customer::where('balance', '>', 0)->count();
            $totalCreditAmount = Customer::where('balance', '>', 0)->sum('balance');
            
            return response()->json([
                'customers' => $customers,
                'search' => $search,
                'credit_status' => $creditStatus,
                'sort_by' => $sortBy,
                'summary' => [
                    'total_customers' => $totalCustomers,
                    'customers_with_credit' => $customersWithCredit,
                    'total_credit_amount' => $totalCreditAmount
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error in customers report API: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving customers report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export customers report to CSV
     */
    public function exportCustomersReport(Request $request)
    {
        try {
            $query = Customer::withCount('sales')
                ->withSum('sales', 'total_amount');
            
            // Apply same filters as the main report
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            }
            
            if ($request->input('credit_status') === 'with_balance') {
                $query->where('balance', '>', 0);
            }
            
            $customers = $query->orderBy('name')->get();
            
            $csvData = [];
            
            foreach ($customers as $customer) {
                $row = [
                    'ID' => $customer->id,
                    'Name' => $customer->name,
                    'Email' => $customer->email ?: 'N/A',
                    'Phone' => $customer->phone,
                    'Total Sales' => $customer->sales_count,
                    'Total Amount' => number_format($customer->sales_sum_total_amount ?: 0, 2),
                    'Credit Balance' => number_format($customer->balance ?: 0, 2),
                    'Status' => ucfirst($customer->status ?: 'active'),
                    'Created Date' => $customer->created_at->format('Y-m-d')
                ];
                
                $csvData[] = $row;
            }
            
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="customers_report_' . now()->format('Y-m-d') . '.csv"',
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0'
            ];
            
            $callback = function() use ($csvData) {
                $file = fopen('php://output', 'w');
                
                if (!empty($csvData)) {
                    fputcsv($file, array_keys($csvData[0]));
                    
                    foreach ($csvData as $row) {
                        fputcsv($file, $row);
                    }
                } else {
                    fputcsv($file, ['No customer data found']);
                }
                
                fclose($file);
            };
            
            return Response::stream($callback, 200, $headers);
        } catch (\Exception $e) {
            Log::error('Error exporting customers report: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error exporting customers report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get cashier performance report
     */
    public function cashierPerformanceReport(Request $request)
    {
        try {
            // Handle date range selection
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
            
            return response()->json([
                'cashiers' => $cashiers->values(),
                'date_range' => $dateRange,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d')
            ]);
        } catch (\Exception $e) {
            Log::error('Error in cashier performance report API: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving cashier performance report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get dashboard report with key metrics
     */
    public function dashboardReport(Request $request)
    {
        try {
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
            $lowStockThreshold = Setting::where('key', 'low_stock_threshold')->value('value') ?? 10;
            
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
            
            return response()->json([
                'today_sales' => $todaySales,
                'today_revenue' => $todayRevenue,
                'month_sales' => $monthSales,
                'month_revenue' => $monthRevenue,
                'low_stock_products' => $lowStockProducts,
                'out_of_stock_products' => $outOfStockProducts,
                'recent_sales' => $recentSales,
                'sales_chart_data' => $salesChartData
            ]);
        } catch (\Exception $e) {
            Log::error('Error in dashboard report API: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving dashboard report: ' . $e->getMessage()
            ], 500);
        }
    }
}