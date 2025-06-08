<?php
// Fix script to resolve product status issues
require_once 'vendor/autoload.php';

// Bootstrap Laravel to use Eloquent models
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use App\Models\Category;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

echo "=== EldoGas POS Product Status Fix ===\n\n";

try {
    // Check database connection
    DB::connection()->getPdo();
    echo "✓ Database connection: SUCCESS\n\n";
    
    // Check current state
    $totalProducts = Product::count();
    echo "Total products in database: {$totalProducts}\n";
    
    if ($totalProducts == 0) {
        echo "No products found! Running QuickFixSeeder to create sample data...\n";
        
        // Check if categories exist first
        $totalCategories = Category::count();
        if ($totalCategories == 0) {
            echo "No categories found! Creating default category...\n";
            Category::create([
                'name' => 'Gas Refill',
                'description' => 'Gas cylinder refills',
                'status' => 'active'
            ]);
        }
        
        // Run the QuickFixSeeder
        $seeder = new \Database\Seeders\QuickFixSeeder();
        $seeder->run();
        
        echo "QuickFixSeeder completed!\n\n";
    }
    
    // Check for products with NULL status and fix them
    $nullStatusProducts = Product::whereNull('status')->count();
    echo "Products with NULL status: {$nullStatusProducts}\n";
    
    if ($nullStatusProducts > 0) {
        echo "Fixing products with NULL status...\n";
        Product::whereNull('status')->update(['status' => 'active']);
        echo "✓ Fixed {$nullStatusProducts} products with NULL status\n";
    }
    
    // Check for products with empty status and fix them
    $emptyStatusProducts = Product::where('status', '')->count();
    echo "Products with empty status: {$emptyStatusProducts}\n";
    
    if ($emptyStatusProducts > 0) {
        echo "Fixing products with empty status...\n";
        Product::where('status', '')->update(['status' => 'active']);
        echo "✓ Fixed {$emptyStatusProducts} products with empty status\n";
    }
    
    // Show final status
    echo "\n=== Final Status ===\n";
    $totalProducts = Product::count();
    $activeProducts = Product::where('status', 'active')->count();
    $inactiveProducts = Product::where('status', 'inactive')->count();
    
    echo "Total products: {$totalProducts}\n";
    echo "Active products: {$activeProducts}\n";
    echo "Inactive products: {$inactiveProducts}\n\n";
    
    // Test the queries used by the application
    echo "=== Testing Application Queries ===\n";
    
    // POS query (shows products in POS)
    $posProducts = Product::with('category')->where('status', 'active')->get();
    echo "POS query (active products): " . $posProducts->count() . " products\n";
    
    // Admin query (shows products in Product Management)
    $adminProducts = Product::with('category')->get();
    echo "Admin query (all products): " . $adminProducts->count() . " products\n";
    
    // Dashboard query (used for analytics)
    $dashboardTotalProducts = Product::count();
    $dashboardActiveProducts = Product::where('status', 'active')->count();
    echo "Dashboard total products: {$dashboardTotalProducts}\n";
    echo "Dashboard active products: {$dashboardActiveProducts}\n";
    
    if ($posProducts->count() > 0) {
        echo "\n✓ POS should now show products!\n";
        echo "Products that will appear in POS:\n";
        foreach ($posProducts as $product) {
            echo "- {$product->name} (KSh {$product->price}, Stock: {$product->stock})\n";
        }
    }
    
    if ($dashboardTotalProducts > 0) {
        echo "\n✓ Dashboard analytics should now show correct counts!\n";
    }
    
    echo "\n=== Checking Settings ===\n";
    
    // Ensure required settings exist
    $requiredSettings = [
        ['key' => 'company_name', 'value' => 'EldoGas'],
        ['key' => 'currency_symbol', 'value' => 'KSh'],
        ['key' => 'low_stock_threshold', 'value' => '5'],
        ['key' => 'tax_percentage', 'value' => '0'],
    ];
    
    foreach ($requiredSettings as $setting) {
        $existing = Setting::where('key', $setting['key'])->first();
        if (!$existing) {
            Setting::create($setting);
            echo "✓ Created setting: {$setting['key']} = {$setting['value']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Fix Complete ===\n";
echo "Please refresh your browser to see the changes.\n";
?>