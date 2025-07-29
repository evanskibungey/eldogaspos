<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Customer;
use App\Models\Category;
use App\Models\Setting;
use App\Models\CylinderTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PosController extends Controller
{
    /**
     * Get a setting value.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    private function getSetting($key, $default = null)
    {
        return Setting::get($key, $default);
    }

    /**
     * Get POS dashboard data including products, categories, and cylinder stats.
     *
     * @return \Illuminate\Http\Response
     */
    public function dashboard()
    {
        try {
            Log::info('API POS Dashboard: Starting to load products');
            
            // Get all active products with their categories
            $products = Product::with('category')
                ->where('status', 'active')
                ->get();
                
            Log::info('API POS Dashboard: Found ' . $products->count() . ' active products');
                
            // Get all categories for filtering
            $categories = Category::where('status', 'active')->get();
            
            Log::info('API POS Dashboard: Found ' . $categories->count() . ' active categories');
            
            // Get cylinder statistics for POS dashboard
            $cylinderStats = $this->getCylinderStatistics();
                
            // Format product data for frontend display
            $formattedProducts = $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'category_id' => $product->category_id,
                    'sku' => $product->sku,
                    'serial_number' => $product->serial_number,
                    'price' => (float)$product->price,
                    'stock' => $product->stock,
                    'min_stock' => $product->min_stock,
                    // Format image URL properly
                    'image' => $product->image ? asset('storage/' . $product->image) : asset('images/placeholder.jpg'),
                    'status' => $product->status,
                    'category_name' => $product->category ? $product->category->name : 'Uncategorized'
                ];
            });
            
            // Add debugging info if no products found
            if ($products->count() === 0) {
                Log::warning('API POS Dashboard: No active products found');
                
                // Check if there are any products at all
                $totalProducts = Product::count();
                $inactiveProducts = Product::where('status', 'inactive')->count();
                $nullStatusProducts = Product::whereNull('status')->count();
                
                Log::info('API POS Dashboard Debug: Total products=' . $totalProducts . ', Inactive=' . $inactiveProducts . ', Null status=' . $nullStatusProducts);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'products' => $formattedProducts,
                    'categories' => $categories,
                    'cylinderStats' => $cylinderStats
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error in API POS dashboard: ' . $e->getMessage());
            Log::error('API POS dashboard stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Error loading POS dashboard data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check stock availability for a product.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function checkStock(Request $request)
    {
        try {
            $validated = $request->validate([
                'product_id' => 'required|exists:products,id',
                'quantity' => 'required|integer|min:1'
            ]);

            $product = Product::findOrFail($request->product_id);
            
            return response()->json([
                'success' => true,
                'available' => $product->stock >= $request->quantity,
                'current_stock' => $product->stock
            ]);
        } catch (\Exception $e) {
            Log::error('Error checking stock: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error checking stock availability'
            ], 422);
        }
    }

    /**
     * Get recent sales for POS dashboard.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function recentSales(Request $request)
    {
        try {
            $limit = $request->input('limit', 5);
            
            $sales = Sale::with(['customer', 'user', 'items.product'])
                ->where('status', '!=', 'voided')
                ->orderByDesc('created_at')
                ->limit($limit)
                ->get()
                ->map(function ($sale) {
                    return [
                        'id' => $sale->id,
                        'receipt_number' => $sale->receipt_number,
                        'customer_name' => $sale->customer->name,
                        'total_amount' => $sale->total_amount,
                        'payment_method' => $sale->payment_method,
                        'payment_status' => $sale->payment_status,
                        'created_at' => $sale->created_at->format('Y-m-d H:i:s'),
                        'items_count' => $sale->items->count(),
                    ];
                });
            
            return response()->json([
                'success' => true,
                'data' => $sales
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching recent sales: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching recent sales'
            ], 500);
        }
    }

    /**
     * Get products by category for POS filtering.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function productsByCategory(Request $request)
    {
        try {
            $categoryId = $request->input('category_id');
            
            $query = Product::with('category')->where('status', 'active');
            
            if ($categoryId) {
                $query->where('category_id', $categoryId);
            }
            
            $products = $query->get()->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'category_id' => $product->category_id,
                    'sku' => $product->sku,
                    'serial_number' => $product->serial_number,
                    'price' => (float)$product->price,
                    'stock' => $product->stock,
                    'min_stock' => $product->min_stock,
                    'image' => $product->image ? asset('storage/' . $product->image) : asset('images/placeholder.jpg'),
                    'status' => $product->status,
                    'category_name' => $product->category ? $product->category->name : 'Uncategorized'
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $products
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching products by category: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching products by category'
            ], 500);
        }
    }

    /**
     * Get POS statistics for dashboard widgets.
     *
     * @return \Illuminate\Http\Response
     */
    public function getStatistics()
    {
        try {
            $today = Carbon::today();
            
            // Today's sales stats
            $todaySales = Sale::whereDate('created_at', $today)
                ->where('status', '!=', 'voided')
                ->count();
                
            $todayRevenue = Sale::whereDate('created_at', $today)
                ->where('status', '!=', 'voided')
                ->sum('total_amount');
            
            // Low stock products count
            $lowStockCount = Product::whereRaw('stock <= min_stock')
                ->where('status', 'active')
                ->count();
            
            // Pending credit sales
            $pendingCreditSales = Sale::where('payment_method', 'credit')
                ->where('payment_status', 'pending')
                ->count();
            
            // Cylinder statistics
            $cylinderStats = $this->getCylinderStatistics();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'today_sales' => $todaySales,
                    'today_revenue' => $todayRevenue,
                    'low_stock_count' => $lowStockCount,
                    'pending_credit_sales' => $pendingCreditSales,
                    'cylinder_stats' => $cylinderStats
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching POS statistics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching POS statistics'
            ], 500);
        }
    }

    /**
     * Get cylinder transaction statistics for POS dashboard
     *
     * @return array
     */
    private function getCylinderStatistics()
    {
        try {
            return [
                'active_drop_offs' => CylinderTransaction::active()->dropOffs()->count(),
                'active_advance_collections' => CylinderTransaction::active()->advanceCollections()->count(),
                'today_completed' => CylinderTransaction::whereDate('collection_date', Carbon::today())
                                   ->orWhereDate('return_date', Carbon::today())
                                   ->count(),
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching cylinder statistics: ' . $e->getMessage());
            // Return default values if cylinder transactions are not available
            return [
                'active_drop_offs' => 0,
                'active_advance_collections' => 0,
                'today_completed' => 0,
            ];
        }
    }
}
