<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\StockMovement;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Customer;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
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

    public function index()
    {
        // Get settings
        $currencySymbol = $this->getSetting('currency_symbol', '$');
        $lowStockThreshold = (int)$this->getSetting('low_stock_threshold', 5);
        $taxPercentage = (float)$this->getSetting('tax_percentage', 0);
        $companyName = $this->getSetting('company_name', 'Our Store');
        
        // Basic Statistics
        $stats = [
            // User Statistics
            'total_users' => User::count(),
            'total_cashiers' => User::where('role', 'cashier')->count(),
            'active_users' => User::where('status', 'active')->count(),
            
            // Product Statistics
            'total_products' => Product::count(),
            'active_products' => Product::where('status', 'active')->count(),
            'total_categories' => Category::count(),
            'low_stock_products' => Product::whereColumn('stock', '<=', DB::raw('GREATEST(min_stock, ' . $lowStockThreshold . ')'))->count(),
            
            // Stock Movement Statistics
            'total_movements' => StockMovement::count(),
            'total_in' => StockMovement::where('type', 'in')->sum('quantity'),
            'total_out' => StockMovement::where('type', 'out')->sum('quantity'),
            
            // Today's Statistics
            'today_movements' => StockMovement::whereDate('created_at', Carbon::today())->count(),
            'today_sales' => StockMovement::where('type', 'out')
                ->whereDate('created_at', Carbon::today())
                ->count()
        ];
    
        // Recent Users
        $recent_users = User::latest()
            ->take(5)
            ->get();
    
        // Low Stock Products - Using the low stock threshold from settings
        $low_stock_products = Product::with('category')
            ->where(function($query) use ($lowStockThreshold) {
                $query->whereColumn('stock', '<=', 'min_stock')
                      ->orWhere('stock', '<=', $lowStockThreshold);
            })
            ->latest()
            ->take(5)
            ->get();

        // Recent Stock Movements
        $recent_movements = StockMovement::with(['product', 'creator'])
            ->latest()
            ->take(5)
            ->get();

        // Chart Data for Stock Movements
        $chart_data = $this->getStockMovementChartData();

        // Category Distribution Data
        $category_data = $this->getCategoryDistributionData();

        // Add Sales Statistics
        $sales_stats = $this->getSalesStatistics();
        
        // Top Selling Products
        $topProducts = $this->getTopSellingProducts();
        
        // Top Categories
        $topCategories = $this->getTopSellingCategories();
        
        // Sales Trend Data
        $salesTrendData = $this->getSalesTrendData();
        
        // Recent Sales
        $recentSales = $this->getRecentSales();
        
        // Sales by Payment Method
        $salesByPaymentMethod = $this->getSalesByPaymentMethod();

        // Stock Value
        $totalStockValue = $this->getTotalStockValue();

        return view('admin.dashboard', compact(
            'stats',
            'recent_users',
            'low_stock_products',
            'recent_movements',
            'chart_data',
            'category_data',
            'sales_stats',
            'topProducts',
            'topCategories',
            'salesTrendData',
            'recentSales',
            'salesByPaymentMethod',
            'currencySymbol',
            'lowStockThreshold',
            'taxPercentage',
            'companyName',
            'totalStockValue'
        ));
    }

    /**
     * Get stock movement data for charts
     */
    private function getStockMovementChartData()
    {
        $dates = collect(range(6, 0))->map(function ($days) {
            return Carbon::now()->subDays($days)->format('Y-m-d');
        });

        $movements = StockMovement::select(
            DB::raw('DATE(created_at) as date'),
            'type',
            DB::raw('SUM(quantity) as total')
        )
            ->whereDate('created_at', '>=', Carbon::now()->subDays(6))
            ->groupBy('date', 'type')
            ->get();

        $stock_in = [];
        $stock_out = [];

        foreach ($dates as $date) {
            $in = $movements->where('date', $date)
                          ->where('type', 'in')
                          ->first();
            $out = $movements->where('date', $date)
                           ->where('type', 'out')
                           ->first();

            $stock_in[] = $in ? $in->total : 0;
            $stock_out[] = $out ? $out->total : 0;
        }

        return [
            'labels' => $dates->map(function ($date) {
                return Carbon::parse($date)->format('M d');
            }),
            'stock_in' => $stock_in,
            'stock_out' => $stock_out
        ];
    }

    /**
     * Get category distribution data for charts
     */
    private function getCategoryDistributionData()
    {
        $categories = Category::withCount(['products' => function($query) {
            $query->where('status', 'active');
        }])
            ->having('products_count', '>', 0)
            ->get();

        return [
            'labels' => $categories->pluck('name'),
            'data' => $categories->pluck('products_count'),
            'total_active' => $categories->sum('products_count')
        ];
    }

    /**
     * Get stock value by category
     */
    private function getStockValueByCategory()
    {
        return Category::with(['products' => function($query) {
            $query->where('status', 'active');
        }])
        ->get()
        ->map(function($category) {
            return [
                'name' => $category->name,
                'value' => $category->products->sum(function($product) {
                    return $product->stock * $product->cost_price;
                })
            ];
        });
    }
    
    /**
     * Get total stock value of all products
     */
    private function getTotalStockValue() 
    {
        return Product::where('status', 'active')
            ->sum(DB::raw('stock * cost_price'));
    }
    
    /**
     * Get sales statistics for today, this week, and this month
     */
    private function getSalesStatistics()
    {
        // Today's sales
        $todaySales = Sale::whereDate('created_at', Carbon::today())
                        ->where('status', '!=', 'voided');
        
        // This week's sales
        $weekSales = Sale::whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()])
                      ->where('status', '!=', 'voided');
        
        // This month's sales
        $monthSales = Sale::whereMonth('created_at', Carbon::now()->month)
                       ->whereYear('created_at', Carbon::now()->year)
                       ->where('status', '!=', 'voided');
        
        // Get total customers (excluding walk-in customers)
        $totalCustomers = Customer::where('phone', '!=', '0000000000')->count();
        
        return [
            'today' => [
                'count' => $todaySales->count(),
                'amount' => $todaySales->sum('total_amount')
            ],
            'week' => [
                'count' => $weekSales->count(),
                'amount' => $weekSales->sum('total_amount')
            ],
            'month' => [
                'count' => $monthSales->count(),
                'amount' => $monthSales->sum('total_amount')
            ],
            'average_sale' => $monthSales->count() > 0 ? 
                              $monthSales->sum('total_amount') / $monthSales->count() : 0,
            'total_customers' => $totalCustomers
        ];
    }
    
    /**
     * Get top selling products for the current month
     */
    private function getTopSellingProducts($limit = 5)
    {
        return DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->whereMonth('sales.created_at', Carbon::now()->month)
            ->whereYear('sales.created_at', Carbon::now()->year)
            ->where('sales.status', '!=', 'voided')
            ->select(
                'products.id',
                'products.name',
                'products.image',
                DB::raw('SUM(sale_items.quantity) as total_quantity'),
                DB::raw('SUM(sale_items.subtotal) as total_revenue')
            )
            ->groupBy('products.id', 'products.name', 'products.image')
            ->orderByDesc('total_quantity')
            ->limit($limit)
            ->get();
    }
    
    /**
     * Get top selling categories for the current month
     */
    private function getTopSellingCategories($limit = 5)
    {
        return DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->whereMonth('sales.created_at', Carbon::now()->month)
            ->whereYear('sales.created_at', Carbon::now()->year)
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
     * Get sales trend data for the last 14 days
     */
    private function getSalesTrendData()
    {
        $dates = collect(range(13, 0))->map(function ($days) {
            return Carbon::now()->subDays($days)->format('Y-m-d');
        });
        
        $salesData = [];
        
        foreach ($dates as $date) {
            $daySales = Sale::whereDate('created_at', $date)
                          ->where('status', '!=', 'voided');
            
            $salesData[$date] = [
                'count' => $daySales->count(),
                'amount' => $daySales->sum('total_amount')
            ];
        }
        
        return [
            'labels' => $dates->map(function ($date) {
                return Carbon::parse($date)->format('M d');
            }),
            'counts' => collect($salesData)->pluck('count'),
            'amounts' => collect($salesData)->pluck('amount')
        ];
    }
    
    /**
     * Get recent sales
     */
    private function getRecentSales($limit = 5)
    {
        return Sale::with(['customer', 'user', 'items.product'])
                ->where('status', '!=', 'voided')
                ->orderByDesc('created_at')
                ->limit($limit)
                ->get();
    }
    
    /**
     * Get sales by payment method for the current month
     */
    private function getSalesByPaymentMethod()
    {
        return Sale::whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)
                ->where('status', '!=', 'voided')
                ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as total'))
                ->groupBy('payment_method')
                ->get()
                ->keyBy('payment_method');
    }
}