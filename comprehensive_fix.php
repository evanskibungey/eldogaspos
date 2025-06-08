<?php
// Comprehensive fix for EldoGas POS product visibility issues
require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use App\Models\Category;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

echo "=== EldoGas POS Comprehensive Fix ===\n\n";

try {
    // Check database connection
    DB::connection()->getPdo();
    echo "✓ Database connection successful\n\n";
    
    // Step 1: Check current database state
    echo "Step 1: Analyzing current database state...\n";
    $totalProducts = Product::count();
    $totalCategories = Category::count();
    
    echo "- Total products: {$totalProducts}\n";
    echo "- Total categories: {$totalCategories}\n";
    
    if ($totalProducts > 0) {
        $activeProducts = Product::where('status', 'active')->count();
        $inactiveProducts = Product::where('status', 'inactive')->count();
        $nullStatusProducts = Product::whereNull('status')->count();
        $emptyStatusProducts = Product::where('status', '')->count();
        
        echo "- Active products: {$activeProducts}\n";
        echo "- Inactive products: {$inactiveProducts}\n";
        echo "- Products with NULL status: {$nullStatusProducts}\n";
        echo "- Products with empty status: {$emptyStatusProducts}\n";
    }
    
    // Step 2: Create sample data if no products exist
    if ($totalProducts == 0) {
        echo "\nStep 2: Creating sample data (no products found)...\n";
        
        // Create a category if none exists
        if ($totalCategories == 0) {
            echo "- Creating default category...\n";
            Category::create([
                'name' => 'Gas Refill',
                'description' => 'Gas cylinder refills and accessories',
                'status' => 'active'
            ]);
        }
        
        // Get the first category
        $category = Category::first();
        
        // Create sample products
        echo "- Creating sample products...\n";
        
        $sampleProducts = [
            [
                'name' => 'Refill 6kg',
                'description' => '6kg gas cylinder refill',
                'category_id' => $category->id,
                'sku' => 'GAS-2025-00001',
                'serial_number' => 'PRD2025060800001',
                'price' => 1000.00,
                'cost_price' => 800.00,
                'stock' => 100,
                'min_stock' => 10,
                'status' => 'active'
            ],
            [
                'name' => 'Refill 3kg gas',
                'description' => '3kg gas cylinder refill',
                'category_id' => $category->id,
                'sku' => 'GAS-2025-00002',
                'serial_number' => 'PRD2025060800002',
                'price' => 600.00,
                'cost_price' => 480.00,
                'stock' => 75,
                'min_stock' => 15,
                'status' => 'active'
            ]
        ];
        
        foreach ($sampleProducts as $productData) {
            Product::create($productData);
            echo "  ✓ Created product: {$productData['name']}\n";
        }
        
        $totalProducts = Product::count();
        echo "- Total products after creation: {$totalProducts}\n";
    }
    
    // Step 3: Fix any products with invalid status
    echo "\nStep 3: Fixing product status issues...\n";
    
    // Fix NULL status
    $nullStatusCount = Product::whereNull('status')->count();
    if ($nullStatusCount > 0) {
        Product::whereNull('status')->update(['status' => 'active']);
        echo "- Fixed {$nullStatusCount} products with NULL status\n";
    }
    
    // Fix empty status
    $emptyStatusCount = Product::where('status', '')->count();
    if ($emptyStatusCount > 0) {
        Product::where('status', '')->update(['status' => 'active']);
        echo "- Fixed {$emptyStatusCount} products with empty status\n";
    }
    
    // Fix any other invalid status values
    $invalidStatusCount = Product::whereNotIn('status', ['active', 'inactive'])->count();
    if ($invalidStatusCount > 0) {
        Product::whereNotIn('status', ['active', 'inactive'])->update(['status' => 'active']);
        echo "- Fixed {$invalidStatusCount} products with invalid status\n";
    }
    
    // Step 4: Ensure required settings exist
    echo "\nStep 4: Ensuring required settings exist...\n";
    
    $requiredSettings = [
        ['key' => 'company_name', 'value' => 'EldoGas'],
        ['key' => 'company_phone', 'value' => '+254724556855'],
        ['key' => 'company_email', 'value' => 'info@eldogas.co.ke'],
        ['key' => 'company_address', 'value' => 'Eldoret, Kenya'],
        ['key' => 'currency_symbol', 'value' => 'KSh'],
        ['key' => 'tax_percentage', 'value' => '0'],
        ['key' => 'low_stock_threshold', 'value' => '10'],
        ['key' => 'receipt_footer', 'value' => 'Thank you for your business!'],
    ];
    
    $settingsCreated = 0;
    foreach ($requiredSettings as $setting) {
        $existing = Setting::where('key', $setting['key'])->first();
        if (!$existing) {
            Setting::create($setting);
            $settingsCreated++;
            echo "- Created setting: {$setting['key']}\n";
        }
    }
    
    if ($settingsCreated == 0) {
        echo "- All required settings already exist\n";
    }
    
    // Step 5: Test all application queries
    echo "\nStep 5: Testing application queries...\n";
    
    // Dashboard queries
    $dashboardTotalProducts = Product::count();
    $dashboardActiveProducts = Product::where('status', 'active')->count();
    echo "- Dashboard total products: {$dashboardTotalProducts}\n";
    echo "- Dashboard active products: {$dashboardActiveProducts}\n";
    
    // POS query
    $posProducts = Product::with('category')->where('status', 'active')->get();
    echo "- POS products (active only): " . $posProducts->count() . "\n";
    
    // Admin Product Management query
    $adminProducts = Product::with('category')->get();
    echo "- Admin products (all): " . $adminProducts->count() . "\n";
    
    // Step 6: Display results
    echo "\n=== FINAL RESULTS ===\n";
    
    if ($posProducts->count() > 0) {
        echo "✓ POS System should now display products!\n";
        echo "✓ Dashboard analytics should show correct counts!\n";
        echo "✓ Product Management should continue working!\n\n";
        
        echo "Products available in POS:\n";
        foreach ($posProducts as $product) {
            echo "- {$product->name} (KSh {$product->price}, Stock: {$product->stock}, Status: {$product->status})\n";
        }
    } else {
        echo "❌ Still no active products found!\n";
        echo "There may be a deeper issue with the database or migrations.\n";
    }
    
    echo "\n=== VERIFICATION STEPS ===\n";
    echo "1. Refresh your browser\n";
    echo "2. Check the POS dashboard (/pos/dashboard)\n";
    echo "3. Check the admin dashboard (/admin/dashboard)\n";
    echo "4. Check product management (/admin/products)\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Fix Complete ===\n";
?>