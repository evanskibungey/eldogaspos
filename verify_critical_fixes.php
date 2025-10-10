<?php

/**
 * Verification Script for Critical Fixes
 * Run this after deploying changes to verify everything works
 * 
 * Usage: php artisan tinker
 * Then: include 'verify_critical_fixes.php';
 */

echo "\n";
echo "========================================\n";
echo "CRITICAL FIXES VERIFICATION SCRIPT\n";
echo "========================================\n\n";

// Test 1: Check if Services are accessible
echo "Test 1: Checking Services...\n";
try {
    $stockService = app(\App\Services\StockService::class);
    echo "  ✅ StockService loaded\n";
    
    $referenceService = app(\App\Services\ReferenceNumberService::class);
    echo "  ✅ ReferenceNumberService loaded\n";
} catch (Exception $e) {
    echo "  ❌ Service loading failed: " . $e->getMessage() . "\n";
}

// Test 2: Check Database Indexes
echo "\nTest 2: Checking Database Indexes...\n";
try {
    $indexes = [
        'sales' => ['idx_sales_receipt_number', 'idx_sales_status_created'],
        'sale_items' => ['idx_sale_items_sale_product'],
        'stock_movements' => ['idx_stock_movements_product_created', 'idx_stock_movements_reference'],
        'products' => ['idx_products_status_stock'],
        'cylinder_transactions' => ['idx_cylinder_customer_created'],
    ];
    
    foreach ($indexes as $table => $indexNames) {
        $tableIndexes = DB::select("SHOW INDEX FROM {$table}");
        $existingIndexNames = array_unique(array_column($tableIndexes, 'Key_name'));
        
        foreach ($indexNames as $indexName) {
            if (in_array($indexName, $existingIndexNames)) {
                echo "  ✅ {$table}.{$indexName}\n";
            } else {
                echo "  ❌ {$table}.{$indexName} NOT FOUND\n";
            }
        }
    }
} catch (Exception $e) {
    echo "  ❌ Index check failed: " . $e->getMessage() . "\n";
}

// Test 3: Test Reference Number Generation
echo "\nTest 3: Testing Reference Number Generation...\n";
try {
    $referenceService = app(\App\Services\ReferenceNumberService::class);
    
    $receipt1 = $referenceService->generateReceiptNumber();
    $receipt2 = $referenceService->generateReceiptNumber();
    
    if ($receipt1 !== $receipt2) {
        echo "  ✅ Receipt numbers are unique\n";
        echo "     Generated: {$receipt1}, {$receipt2}\n";
    } else {
        echo "  ❌ Receipt numbers collision!\n";
    }
    
    $cylinder1 = $referenceService->generateCylinderReference();
    $cylinder2 = $referenceService->generateCylinderReference();
    
    if ($cylinder1 !== $cylinder2) {
        echo "  ✅ Cylinder references are unique\n";
        echo "     Generated: {$cylinder1}, {$cylinder2}\n";
    } else {
        echo "  ❌ Cylinder references collision!\n";
    }
} catch (Exception $e) {
    echo "  ❌ Reference number generation failed: " . $e->getMessage() . "\n";
}

// Test 4: Test Stock Service Methods
echo "\nTest 4: Testing Stock Service Methods...\n";
try {
    $stockService = app(\App\Services\StockService::class);
    
    // Find a product with stock
    $product = \App\Models\Product::where('stock', '>', 0)->first();
    
    if ($product) {
        $hasStock = $stockService->hasStock($product->id, 1);
        $currentStock = $stockService->getCurrentStock($product->id);
        
        echo "  ✅ hasStock() works - Product {$product->id}: " . ($hasStock ? 'Yes' : 'No') . "\n";
        echo "  ✅ getCurrentStock() works - Product {$product->id}: {$currentStock} units\n";
    } else {
        echo "  ⚠️  No products with stock to test\n";
    }
} catch (Exception $e) {
    echo "  ❌ Stock service test failed: " . $e->getMessage() . "\n";
}

// Test 5: Check StockMovement Model
echo "\nTest 5: Checking StockMovement Model...\n";
try {
    $stockMovement = new \App\Models\StockMovement();
    $fillable = $stockMovement->getFillable();
    
    if (in_array('created_by', $fillable)) {
        echo "  ✅ 'created_by' is in fillable array\n";
    } else {
        echo "  ❌ 'created_by' NOT in fillable array\n";
    }
    
    if (in_array('unit_price', $fillable)) {
        echo "  ✅ 'unit_price' is in fillable array\n";
    } else {
        echo "  ❌ 'unit_price' NOT in fillable array\n";
    }
} catch (Exception $e) {
    echo "  ❌ StockMovement model check failed: " . $e->getMessage() . "\n";
}

// Test 6: Controller Dependency Injection
echo "\nTest 6: Checking Controller Dependencies...\n";
try {
    $posController = app(\App\Http\Controllers\Pos\PosController::class);
    echo "  ✅ PosController can be instantiated\n";
    
    $saleController = app(\App\Http\Controllers\Pos\SaleController::class);
    echo "  ✅ SaleController can be instantiated\n";
    
    $cylinderController = app(\App\Http\Controllers\Admin\CylinderController::class);
    echo "  ✅ CylinderController can be instantiated\n";
} catch (Exception $e) {
    echo "  ❌ Controller instantiation failed: " . $e->getMessage() . "\n";
}

// Summary
echo "\n========================================\n";
echo "VERIFICATION COMPLETE\n";
echo "========================================\n";
echo "\nNext Steps:\n";
echo "1. If all tests passed (✅), system is ready\n";
echo "2. Test actual POS sale through the UI\n";
echo "3. Test cylinder transaction through the UI\n";
echo "4. Monitor logs for any issues\n";
echo "\nLog files:\n";
echo "  - storage/logs/laravel.log\n";
echo "\n";
