<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Category;
use App\Models\User;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class ReportController extends Controller
{
    /**
     * Display the sales report page with filtering options
     */
    public function sales(Request $request)
    {
        $dateRange = $request->input('date_range', 'today');
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
        
        return view('admin.reports.sales', compact(
            'sales', 
            'dateRange', 
            'startDate', 
            'endDate', 
            'totalSales', 
            'totalRevenue', 
            'totalItems',
            'salesByPeriod',
            'topProducts',
            'topCategories',
            'cashiers'
        ));
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
            ->keyBy('hour');
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
            ->keyBy('date');
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
     * Display inventory report
     */
    public function inventory(Request $request)
    {
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
        
        $products = $query->orderBy('name')->get();
        
        // Get categories for filter
        $categories = Category::orderBy('name')->get();
        
        // Calculate summary statistics
        $totalProducts = $products->count();
        $totalValue = $products->sum(function ($product) {
            return $product->stock * $product->price;
        });
        $lowStockCount = $products->filter(function ($product) {
            return $product->stock <= $product->min_stock && $product->stock > 0;
        })->count();
        $outOfStockCount = $products->where('stock', 0)->count();
        
        return view('admin.reports.inventory', compact(
            'products', 
            'categories', 
            'totalProducts', 
            'totalValue',
            'lowStockCount',
            'outOfStockCount'
        ));
    }
    
    /**
     * Display users report
     */
    public function users(Request $request)
    {
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
                'name' => $cashier->name,
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
        
        return view('admin.reports.users', compact(
            'cashierPerformance',
            'startDate',
            'endDate'
        ));
    }
    
    /**
     * Display stock movements report
     */
    public function stockMovements(Request $request)
    {
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
        
        $movements = $query->orderBy('created_at', 'desc')->paginate(50);
        
        // Get products for filter
        $products = Product::orderBy('name')->get();
        
        return view('admin.reports.stock-movements', compact(
            'movements',
            'products',
            'startDate',
            'endDate'
        ));
    }
    
    /**
     * Export sales report to CSV
     */
    public function exportSales(Request $request)
    {
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
     * Export inventory report to CSV
     */
    public function exportInventory(Request $request)
    {
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
    }
    
    /**
     * Export users report to CSV
     */
    public function exportUsers(Request $request)
    {
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
    }
    
    /**
     * Export stock movements report to CSV
     */
    public function exportStockMovements(Request $request)
    {
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
    }
}