<?php
// Targeted diagnostic script for the specific issue shown in images
require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

echo "=== EldoGas POS Targeted Diagnostic ===\n\n";

try {
    // Check database connection
    DB::connection()->getPdo();
    echo "✓ Database connection successful\n\n";
    
    // 1. Check categories and their product counts
    echo "=== CATEGORY ANALYSIS ===\n";
    $categories = Category::withCount('products')->get();
    
    foreach ($categories as $category) {
        echo "Category: {$category->name}\n";
        echo "- Status: {$category->status}\n";
        echo "- Products count (via relationship): {$category->products_count}\n";
        
        // Manual count of products in this category
        $manualCount = Product::where('category_id', $category->id)->count();
        $activeCount = Product::where('category_id', $category->id)->where('status', 'active')->count();
        
        echo "- Products count (manual query): {$manualCount}\n";
        echo "- Active products count: {$activeCount}\n";
        echo "---\n";
    }
    
    // 2. Check all products directly
    echo "\n=== DIRECT PRODUCT ANALYSIS ===\n";
    $allProducts = Product::all();
    echo "Total products (direct query): " . $allProducts->count() . "\n";
    
    if ($allProducts->count() > 0) {
        echo "\nAll products in database:\n";
        foreach ($allProducts as $product) {
            echo "- ID: {$product->id}\n";
            echo "  Name: {$product->name}\n";
            echo "  Category ID: {$product->category_id}\n";
            echo "  Status: " . ($product->status ?? 'NULL') . "\n";
            echo "  SKU: {$product->sku}\n";
            echo "  Stock: {$product->stock}\n";
            echo "  Created: {$product->created_at}\n";
            
            // Check if category exists
            $category = Category::find($product->category_id);
            if ($category) {
                echo "  Category: {$category->name} (Status: {$category->status})\n";
            } else {
                echo "  Category: NOT FOUND! (ID: {$product->category_id})\n";
            }
            echo "\n";
        }
    }
    
    // 3. Test specific queries used by the application
    echo "=== APPLICATION QUERY TESTS ===\n";
    
    // Dashboard query
    $dashboardQuery = Product::count();
    echo "Dashboard total count: {$dashboardQuery}\n";
    
    $dashboardActiveQuery = Product::where('status', 'active')->count();
    echo "Dashboard active count: {$dashboardActiveQuery}\n";
    
    // POS query  
    $posQuery = Product::with('category')->where('status', 'active')->get();
    echo "POS query result: " . $posQuery->count() . " products\n";
    
    // Category management query (this one shows 2 products)
    $categoryQuery = DB::table('products')
        ->join('categories', 'products.category_id', '=', 'categories.id')
        ->where('products.status', 'active')
        ->where('categories.status', 'active')
        ->count();
    echo "Category management style query: {$categoryQuery}\n";
    
    // 4. Check for potential issues
    echo "\n=== POTENTIAL ISSUES CHECK ===\n";
    
    // Check for foreign key constraints
    $orphanedProducts = Product::whereNotExists(function($query) {
        $query->select(DB::raw(1))
              ->from('categories')
              ->whereRaw('categories.id = products.category_id');
    })->count();
    echo "Orphaned products (no matching category): {$orphanedProducts}\n";
    
    // Check for products with inactive categories
    $productsWithInactiveCategories = Product::join('categories', 'products.category_id', '=', 'categories.id')
        ->where('categories.status', 'inactive')
        ->count();
    echo "Products with inactive categories: {$productsWithInactiveCategories}\n";
    
    // Check for null values in critical fields
    $nullCategoryId = Product::whereNull('category_id')->count();
    $nullStatus = Product::whereNull('status')->count();
    $emptyStatus = Product::where('status', '')->count();
    
    echo "Products with NULL category_id: {$nullCategoryId}\n";
    echo "Products with NULL status: {$nullStatus}\n";
    echo "Products with empty status: {$emptyStatus}\n";
    
    // 5. Test the exact query that should be working
    echo "\n=== TESTING EXACT QUERIES ===\n";
    
    // This is the exact query from PosController
    echo "Testing PosController query...\n";
    $posControllerQuery = Product::with('category')->where('status', 'active');
    echo "Query SQL: " . $posControllerQuery->toSql() . "\n";
    $posResults = $posControllerQuery->get();
    echo "Results: " . $posResults->count() . " products\n";
    
    if ($posResults->count() > 0) {
        echo "Products found by POS query:\n";
        foreach ($posResults as $product) {
            echo "- {$product->name} (ID: {$product->id}, Status: {$product->status})\n";
        }
    }
    
    // 6. Check database table structure
    echo "\n=== DATABASE STRUCTURE CHECK ===\n";
    
    $productsTableExists = DB::select("SHOW TABLES LIKE 'products'");
    $categoriesTableExists = DB::select("SHOW TABLES LIKE 'categories'");
    
    echo "Products table exists: " . (count($productsTableExists) > 0 ? 'YES' : 'NO') . "\n";
    echo "Categories table exists: " . (count($categoriesTableExists) > 0 ? 'YES' : 'NO') . "\n";
    
    if (count($productsTableExists) > 0) {
        $productColumns = DB::select("DESCRIBE products");
        echo "\nProducts table columns:\n";
        foreach ($productColumns as $column) {
            echo "- {$column->Field} ({$column->Type}) - " . ($column->Null === 'YES' ? 'NULL allowed' : 'NOT NULL') . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== DIAGNOSTIC COMPLETE ===\n";
echo "Please run this script and share the output to help identify the exact issue.\n";
?>