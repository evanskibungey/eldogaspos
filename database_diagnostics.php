<?php
// Database diagnostics script to check products and their status
require_once 'vendor/autoload.php';

// Bootstrap Laravel to use Eloquent models
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use App\Models\Category;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

echo "=== EldoGas POS Database Diagnostics ===\n\n";

try {
    // Check database connection
    DB::connection()->getPdo();
    echo "✓ Database connection: SUCCESS\n\n";
    
    // Check total products
    $totalProducts = Product::count();
    echo "Total products in database: {$totalProducts}\n";
    
    if ($totalProducts > 0) {
        // Check products by status
        $activeProducts = Product::where('status', 'active')->count();
        $inactiveProducts = Product::where('status', 'inactive')->count();
        $nullStatusProducts = Product::whereNull('status')->count();
        
        echo "Active products: {$activeProducts}\n";
        echo "Inactive products: {$inactiveProducts}\n";
        echo "Products with NULL status: {$nullStatusProducts}\n\n";
        
        // Show all products with their details
        echo "=== All Products Details ===\n";
        $products = Product::with('category')->get();
        
        foreach ($products as $product) {
            echo "ID: {$product->id}\n";
            echo "Name: {$product->name}\n";
            echo "SKU: {$product->sku}\n";
            echo "Status: " . ($product->status ?? 'NULL') . "\n";
            echo "Stock: {$product->stock}\n";
            echo "Price: {$product->price}\n";
            echo "Category: " . ($product->category ? $product->category->name : 'No Category') . "\n";
            echo "Created: {$product->created_at}\n";
            echo "---\n";
        }
    } else {
        echo "No products found in database!\n\n";
        
        // Check if categories exist
        $totalCategories = Category::count();
        echo "Total categories in database: {$totalCategories}\n";
        
        if ($totalCategories == 0) {
            echo "No categories found either!\n";
            echo "Running QuickFixSeeder to create sample data...\n";
            
            // Run the QuickFixSeeder
            $seeder = new \Database\Seeders\QuickFixSeeder();
            $seeder->run();
            
            echo "QuickFixSeeder completed!\n\n";
            
            // Re-check products
            $totalProducts = Product::count();
            $activeProducts = Product::where('status', 'active')->count();
            echo "After seeding:\n";
            echo "Total products: {$totalProducts}\n";
            echo "Active products: {$activeProducts}\n";
        }
    }
    
    // Check specific issue with POS query
    echo "\n=== Testing POS Query ===\n";
    $posProducts = Product::with('category')->where('status', 'active')->get();
    echo "POS query result count: " . $posProducts->count() . "\n";
    
    if ($posProducts->count() > 0) {
        echo "Products returned by POS query:\n";
        foreach ($posProducts as $product) {
            echo "- {$product->name} (Status: {$product->status})\n";
        }
    }
    
    // Check admin query (used by Product Management)
    echo "\n=== Testing Admin Query ===\n";
    $adminProducts = Product::with('category')->get();
    echo "Admin query result count: " . $adminProducts->count() . "\n";
    
    if ($adminProducts->count() > 0) {
        echo "Products returned by Admin query:\n";
        foreach ($adminProducts as $product) {
            echo "- {$product->name} (Status: " . ($product->status ?? 'NULL') . ")\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Database connection FAILED: " . $e->getMessage() . "\n";
}

echo "\n=== Diagnostics Complete ===\n";
?>