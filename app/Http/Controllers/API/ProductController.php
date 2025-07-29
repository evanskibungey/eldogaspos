<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\StockMovement;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

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
     * Display a listing of products with filters
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Product::with('category');

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%");
            });
        }

        // Apply category filter
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Apply status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Apply stock status filter
        if ($request->filled('stock_status')) {
            switch ($request->stock_status) {
                case 'in_stock':
                    $query->where('stock', '>', 0);
                    break;
                case 'out_of_stock':
                    $query->where('stock', 0);
                    break;
                case 'low_stock':
                    $query->whereRaw('stock <= min_stock')->where('stock', '>', 0);
                    break;
            }
        }

        // Apply sorting
        $sort = $request->get('sort', 'name');
        $direction = 'asc';
        
        if (in_array($sort, ['created_at', 'stock'])) {
            $direction = 'desc';
        }
        
        $query->orderBy($sort, $direction);

        // Get paginated results
        $products = $query->paginate(10);

        return response()->json($products);
    }

    /**
     * Store a newly created product in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
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

        return response()->json($product, 201);
    }

    /**
     * Display the specified product.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $product = Product::with('category')->findOrFail($id);
        return response()->json($product);
    }

    /**
     * Update the specified product in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        
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

        return response()->json($product);
    }

    /**
     * Remove the specified product from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        
        // Check if product is referenced in any sales
        if (DB::table('sale_items')->where('product_id', $product->id)->exists()) {
            return response()->json([
                'message' => 'Cannot delete product as it is referenced in sales records. Consider deactivating it instead.'
            ], 422);
        }
    
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }
    
        $product->delete();
        
        return response()->json([
            'message' => 'Product deleted successfully'
        ]);
    }

    /**
     * Update the stock of a specific product.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateStock(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        
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

        return response()->json([
            'message' => 'Stock updated successfully',
            'product' => $product->fresh()
        ]);
    }

    /**
     * Get categories for product management.
     *
     * @return \Illuminate\Http\Response
     */
    public function categories()
    {
        $categories = Category::where('status', 'active')->get();
        return response()->json($categories);
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
}