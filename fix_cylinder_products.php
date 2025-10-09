<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use App\Models\Category;
use App\Models\CylinderTransaction;
use Illuminate\Support\Facades\DB;

echo "=== Fixing Cylinder Transactions without Products ===\n\n";

try {
    DB::beginTransaction();
    
    // Step 1: Check how many transactions need fixing
    $nullProductCount = CylinderTransaction::whereNull('product_id')->count();
    echo "Found {$nullProductCount} cylinder transactions without product_id\n\n";
    
    if ($nullProductCount == 0) {
        echo "No transactions to fix! All transactions have products assigned.\n";
        DB::rollBack();
        exit(0);
    }
    
    // Step 2: Get or create a "Gas Cylinders" category
    $category = Category::firstOrCreate(
        ['name' => 'Gas Cylinders'],
        [
            'name' => 'Gas Cylinders',
            'description' => 'LPG Gas Cylinders',
            'status' => 'active'
        ]
    );
    echo "Using category: {$category->name} (ID: {$category->id})\n\n";
    
    // Step 3: Create a Legacy Cylinder product
    $legacyProduct = Product::where('sku', 'LEGACY-CYL')->first();
    
    if (!$legacyProduct) {
        $legacyProduct = Product::create([
            'name' => 'Legacy Cylinder Transaction',
            'description' => 'Product created for old cylinder transactions without product assignment',
            'category_id' => $category->id,
            'sku' => 'LEGACY-CYL',
            'serial_number' => 'LEGACY-001', // Adding serial number
            'price' => 0,
            'cost_price' => 0,
            'stock' => 0,
            'min_stock' => 0,
            'status' => 'inactive' // Inactive so it doesn't appear in new transactions
        ]);
        echo "✓ Created Legacy Cylinder product (ID: {$legacyProduct->id})\n\n";
    } else {
        echo "✓ Legacy Cylinder product already exists (ID: {$legacyProduct->id})\n\n";
    }
    
    // Step 4: Update all NULL product_id transactions
    $updated = CylinderTransaction::whereNull('product_id')
        ->update(['product_id' => $legacyProduct->id]);
    
    echo "✓ Updated {$updated} cylinder transactions\n\n";
    
    // Step 5: Verify all transactions now have products
    $remainingNull = CylinderTransaction::whereNull('product_id')->count();
    
    if ($remainingNull > 0) {
        echo "⚠ Warning: Still have {$remainingNull} transactions without products!\n";
        DB::rollBack();
        exit(1);
    }
    
    DB::commit();
    
    echo "=== SUCCESS ===\n";
    echo "All cylinder transactions now have products assigned!\n";
    echo "You can now safely run: php artisan migrate\n";
    
} catch (Exception $e) {
    DB::rollBack();
    echo "\n=== ERROR ===\n";
    echo "Failed to fix transactions: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
