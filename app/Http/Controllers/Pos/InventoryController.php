<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\StockMovement;
use App\Models\Setting;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    /**
     * Display inventory management page for POS.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $categories = Category::where('status', 'active')->get();
        $lowStockThreshold = Setting::where('key', 'low_stock_threshold')->value('value') ?? 5;
        
        // Get products that are low on stock
        $lowStockProducts = Product::whereRaw('stock <= min_stock')
            ->where('status', 'active')
            ->with('category')
            ->get();
            
        return view('pos.inventory.index', [
            'categories' => $categories,
            'lowStockProducts' => $lowStockProducts,
            'lowStockThreshold' => $lowStockThreshold
        ]);
    }
    
    /**
     * Search products for inventory management.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function search(Request $request)
    {
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
        
        return view('pos.inventory.search', [
            'products' => $products,
            'categories' => $categories,
            'query' => $query,
            'selectedCategory' => $categoryId
        ]);
    }
    
    /**
     * Display details for a specific product.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function product($id)
    {
        $product = Product::with(['category', 'stockMovements' => function($query) {
            $query->latest()->limit(10);
        }])->findOrFail($id);
        
        return view('pos.inventory.product', [
            'product' => $product
        ]);
    }
    
    /**
     * Show form to update product stock.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function updateStockForm($id)
    {
        $product = Product::with('category')->findOrFail($id);
        
        return view('pos.inventory.update-stock', [
            'product' => $product
        ]);
    }
    
    /**
     * Process stock update.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateStock(Request $request, $id)
    {
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
            'notes' => $request->notes ?? 'Manual stock adjustment',
            'created_by' => auth()->id()
        ]);
        
        // Update the product stock
        $product->update(['stock' => $newStock]);
        
        return redirect()->route('pos.inventory.product', $product->id)
            ->with('success', 'Stock updated successfully');
    }
}