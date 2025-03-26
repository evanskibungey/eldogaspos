<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\StockMovement;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
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
     * Display a listing of the products.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $products = Product::with('category')->paginate(10);
        $currencySymbol = $this->getSetting('currency_symbol', '$');
        $lowStockThreshold = $this->getSetting('low_stock_threshold', 5);
        
        return view('admin.products.index', compact('products', 'currencySymbol', 'lowStockThreshold'));
    }

    /**
     * Show the form for creating a new product.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $categories = Category::where('status', 'active')->get();
        $currencySymbol = $this->getSetting('currency_symbol', '$');
        
        return view('admin.products.create', compact('categories', 'currencySymbol'));
    }

    /**
     * Generate a SKU for a product.
     *
     * @param  \App\Models\Category  $category
     * @return string
     */
    protected function generateSKU($category)
    {
        // Get category prefix (first 4 letters uppercase)
        $prefix = Str::upper(Str::substr($category->name, 0, 4));
        $year = date('Y');
        
        // Get last product with this category prefix
        $lastProduct = Product::where('sku', 'like', $prefix . '-' . $year . '-%')
            ->orderBy('sku', 'desc')
            ->first();

        if ($lastProduct) {
            // Extract the number and increment
            $lastNumber = (int) substr($lastProduct->sku, -5);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        // Format: CATG-YYYY-00001
        return sprintf('%s-%s-%05d', $prefix, $year, $newNumber);
    }

    /**
     * Generate a serial number for a product.
     *
     * @return string
     */
    protected function generateSerialNumber()
    {
        $prefix = 'PRD';
        $date = date('Ymd');
        
        // Get last product created today
        $lastProduct = Product::where('serial_number', 'like', $prefix . $date . '%')
            ->orderBy('serial_number', 'desc')
            ->first();

        if ($lastProduct) {
            // Extract the number and increment
            $lastNumber = (int) substr($lastProduct->serial_number, -5);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        // Format: PRDYYYYMMDD00001
        return sprintf('%s%s%05d', $prefix, $date, $newNumber);
    }

    /**
     * Store a newly created product in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'cost_price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'min_stock' => 'required|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'status' => 'required|in:active,inactive',
        ]);

        // Get category for SKU generation
        $category = Category::findOrFail($request->category_id);

        // Generate SKU and serial number
        $validated['sku'] = $this->generateSKU($category);
        $validated['serial_number'] = $this->generateSerialNumber();

        // Handle image upload
        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        $product = Product::create($validated);

        // Create initial stock movement if stock > 0
        if ($product->stock > 0) {
            StockMovement::create([
                'product_id' => $product->id,
                'type' => 'in',
                'quantity' => $product->stock,
                'reference_type' => 'initial',
                'notes' => 'Initial stock on product creation',
                'created_by' => auth()->id()
            ]);
        }

        return redirect()->route('admin.products.index')
            ->with('success', 'Product created successfully');
    }

    /**
     * Show the form for editing the specified product.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\View\View
     */
    public function edit(Product $product)
    {
        $categories = Category::where('status', 'active')->get();
        $currencySymbol = $this->getSetting('currency_symbol', '$');
        
        return view('admin.products.edit', compact('product', 'categories', 'currencySymbol'));
    }

    /**
     * Update the specified product in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'cost_price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'min_stock' => 'required|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'status' => 'required|in:active,inactive',
        ]);

        // SKU and serial_number are not updateable
        $validated['sku'] = $product->sku;
        $validated['serial_number'] = $product->serial_number;

        // Handle image upload
        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        // Check if stock has changed
        $oldStock = $product->stock;
        $newStock = $validated['stock'];

        if ($oldStock !== $newStock) {
            StockMovement::create([
                'product_id' => $product->id,
                'type' => $newStock > $oldStock ? 'in' : 'out',
                'quantity' => abs($newStock - $oldStock),
                'reference_type' => 'adjustment',
                'notes' => 'Stock adjusted during product update',
                'created_by' => auth()->id()
            ]);
        }

        $product->update($validated);

        return redirect()->route('admin.products.index')
            ->with('success', 'Product updated successfully');
    }

    /**
     * Remove the specified product from storage.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Product $product)
    {
        // Check if product is referenced in any sales
        if (DB::table('sale_items')->where('product_id', $product->id)->exists()) {
            return redirect()->route('admin.products.index')
                ->with('error', 'Cannot delete product as it is referenced in sales records. Consider deactivating it instead.');
        }
    
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }
    
        $product->delete();
        return redirect()->route('admin.products.index')
            ->with('success', 'Product deleted successfully');
    }

    /**
     * Update the stock of a specific product.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateStock(Request $request, Product $product)
    {
        $request->validate([
            'new_stock' => 'required|integer|min:0',
            'notes' => 'nullable|string|max:255'
        ]);

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

        return redirect()->back()->with('success', 'Stock updated successfully');
    }

    /**
     * Display products with low stock.
     *
     * @return \Illuminate\View\View
     */
    public function lowStock()
    {
        $lowStockThreshold = $this->getSetting('low_stock_threshold', 5);
        $currencySymbol = $this->getSetting('currency_symbol', '$');
        
        $products = Product::where('status', 'active')
            ->whereRaw('stock <= min_stock')
            ->with('category')
            ->paginate(10);
            
        return view('admin.products.low_stock', compact('products', 'currencySymbol', 'lowStockThreshold'));
    }
}