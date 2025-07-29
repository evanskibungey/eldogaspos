<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\StockMovement;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class InventoryController extends Controller
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
     * Display inventory overview with search functionality.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $categories = Category::where('status', 'active')->get();
            $lowStockThreshold = Setting::where('key', 'low_stock_threshold')->value('value') ?? 5;
            
            // Get products that are low on stock
            $lowStockProducts = Product::whereRaw('stock <= min_stock')
                ->where('status', 'active')
                ->with('category')
                ->get();
                
            return response()->json([
                'success' => true,
                'data' => [
                    'categories' => $categories,
                    'lowStockProducts' => $lowStockProducts,
                    'lowStockThreshold' => $lowStockThreshold
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error in inventory index: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving inventory data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search products for inventory management.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function search(Request $request)
    {
        try {
            $query = $request->input('query');
            $categoryId = $request->input('category_id');
            
            $productsQuery = Product::with('category');
            
            // Apply search filters
            if ($query) {
                $productsQuery->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhere('sku', 'like', "%{$query}%")
                      ->orWhere('serial_number', 'like', "%{$query}%");
                });
            }
            
            if ($categoryId) {
                $productsQuery->where('category_id', $categoryId);
            }
            
            $products = $productsQuery->paginate(15);
            $categories = Category::where('status', 'active')->get();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'products' => $products,
                    'categories' => $categories,
                    'query' => $query,
                    'selectedCategory' => $categoryId
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error in inventory search: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error searching products: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get active categories for inventory.
     *
     * @return \Illuminate\Http\Response
     */
    public function categories()
    {
        $categories = Category::where('status', 'active')
            ->withCount('products')
            ->get();
            
        return response()->json([
            'data' => $categories
        ]);
    }

    /**
     * Get details for a specific category.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function categoryDetails($id)
    {
        $category = Category::with('products')
            ->withCount('products')
            ->findOrFail($id);
            
        return response()->json([
            'data' => $category
        ]);
    }

    /**
     * Get products by category.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function productsByCategory($id)
    {
        // First verify the category exists
        $category = Category::findOrFail($id);
        
        $products = Product::where('category_id', $id)
            ->where('status', 'active')
            ->get();
            
        return response()->json([
            'category' => $category,
            'data' => $products
        ]);
    }

    /**
     * Get products with low stock levels.
     *
     * @return \Illuminate\Http\Response
     */
    public function lowStockProducts()
    {
        $lowStockThreshold = $this->getSetting('low_stock_threshold', 5);
        
        $products = Product::with('category')
            ->where('status', 'active')
            ->whereRaw('stock <= min_stock')
            ->where('stock', '>', 0)
            ->get();
            
        return response()->json([
            'data' => $products
        ]);
    }

    /**
     * Get out of stock products.
     *
     * @return \Illuminate\Http\Response
     */
    public function outOfStockProducts()
    {
        $products = Product::with('category')
            ->where('status', 'active')
            ->where('stock', 0)
            ->get();
            
        return response()->json([
            'data' => $products
        ]);
    }

    /**
     * Display details for a specific product.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function product($id)
    {
        try {
            $product = Product::with(['category', 'stockMovements' => function($query) {
                $query->latest()->limit(10);
            }])->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $product
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching product details: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching product details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update product stock.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateStock(Request $request, $id)
    {
        try {
            $request->validate([
                'new_stock' => 'required|integer|min:0',
                'notes' => 'nullable|string|max:255'
            ]);
            
            $product = Product::findOrFail($id);
            $oldStock = $product->stock;
            $newStock = $request->new_stock;
            
            // Create stock movement record
            StockMovement::create([
                'product_id' => $product->id,
                'type' => $newStock > $oldStock ? 'in' : 'out',
                'quantity' => abs($newStock - $oldStock),
                'reference_type' => 'manual_adjustment',
                'notes' => $request->notes ?? 'Manual stock adjustment via API',
                'created_by' => auth()->id()
            ]);
            
            // Update the product stock
            $product->update(['stock' => $newStock]);
            
            return response()->json([
                'success' => true,
                'message' => 'Stock updated successfully',
                'data' => [
                    'product' => $product->fresh(),
                    'old_stock' => $oldStock,
                    'new_stock' => $newStock
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating stock: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating stock: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get stock movements for a product.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function stockMovements($id)
    {
        try {
            // First check if the product exists
            $product = Product::with('category')->findOrFail($id);
            
            // Get stock movements, make sure relation exists
            // Handle possible missing fields by selecting specific columns
            $movements = StockMovement::select(
                    'id', 
                    'product_id', 
                    'type', 
                    'quantity', 
                    'reference_type', 
                    'notes', 
                    'created_at'
                )
                ->where('product_id', $id)
                ->orderBy('created_at', 'desc')
                ->paginate(15);
            
            // Return simplified response to avoid potentially missing relations    
            return response()->json([
                'product' => $product,
                'data' => $movements->items(),
                'meta' => [
                    'current_page' => $movements->currentPage(),
                    'last_page' => $movements->lastPage(),
                    'total' => $movements->total(),
                ]
            ]);
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Stock movements error: ' . $e->getMessage());
            
            // Return a clearer error message
            return response()->json([
                'message' => 'Error fetching stock movements: ' . $e->getMessage()
            ], 500);
        }
    }
}