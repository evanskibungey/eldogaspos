<?php
// Simple fix to ensure products exist and have proper status
require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

echo "=== Quick Product Fix for Local Development ===\n\n";

try {
    // Check database connection
    DB::connection()->getPdo();
    echo "✓ Database connection successful\n\n";
    
    // Check current products
    $totalProducts = Product::count();
    $activeProducts = Product::where('status', 'active')->count();
    
    echo "Current products: {$totalProducts} total, {$activeProducts} active\n";
    
    if ($totalProducts == 0) {
        echo "Creating sample products...\n";
        
        // Create category if needed
        $category = Category::firstOrCreate(
            ['name' => 'Gas Refill'],
            [
                'description' => 'Gas cylinder refills',
                'status' => 'active'
            ]
        );
        
        // Create products
        $products = [
            [
                'name' => 'Refill 6kg',
                'description' => '6kg gas cylinder refill',
                'category_id' => $category->id,
                'sku' => 'GAS-6KG-001',
                'serial_number' => 'PRD001',
                'price' => 1000.00,
                'cost_price' => 800.00,
                'stock' => 100,
                'min_stock' => 10,
                'status' => 'active'
            ],
            [
                'name' => 'Refill 13kg',
                'description' => '13kg gas cylinder refill',
                'category_id' => $category->id,
                'sku' => 'GAS-13KG-001',
                'serial_number' => 'PRD002',
                'price' => 2100.00,
                'cost_price' => 1800.00,
                'stock' => 50,
                'min_stock' => 5,
                'status' => 'active'
            ]
        ];
        
        foreach ($products as $productData) {
            Product::create($productData);
            echo "✓ Created: {$productData['name']}\n";
        }
    } else {
        // Fix existing products
        $fixed = 0;
        
        // Fix NULL status
        $nullCount = Product::whereNull('status')->count();
        if ($nullCount > 0) {
            Product::whereNull('status')->update(['status' => 'active']);
            $fixed += $nullCount;
            echo "✓ Fixed {$nullCount} products with NULL status\n";
        }
        
        // Fix empty status
        $emptyCount = Product::where('status', '')->count();
        if ($emptyCount > 0) {
            Product::where('status', '')->update(['status' => 'active']);
            $fixed += $emptyCount;
            echo "✓ Fixed {$emptyCount} products with empty status\n";
        }
        
        if ($fixed == 0) {
            echo "✓ All products already have valid status\n";
        }
    }
    
    // Final check
    $finalActive = Product::where('status', 'active')->count();
    echo "\n✅ Final result: {$finalActive} active products\n";
    
    if ($finalActive > 0) {
        echo "✅ POS should now display products!\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Fix Complete ===\n";
?>