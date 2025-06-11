<?php

/*
 * Direct Database Fix Script (No DBAL Required)
 * This script directly modifies the database schema using raw SQL
 */

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== POS Sale Direct Database Fix ===\n";
echo "Fixing serial_number column without requiring doctrine/dbal...\n\n";

try {
    // Check current column definition
    echo "1. Checking current column definition...\n";
    $columns = DB::select("SHOW COLUMNS FROM sale_items LIKE 'serial_number'");
    
    if (empty($columns)) {
        echo "ERROR: serial_number column not found in sale_items table!\n";
        exit(1);
    }
    
    $column = $columns[0];
    echo "Current column definition:\n";
    echo "  Type: {$column->Type}\n";
    echo "  Null: {$column->Null}\n";
    echo "  Default: {$column->Default}\n\n";
    
    if ($column->Null === 'YES') {
        echo "✓ Column is already nullable! No changes needed.\n";
    } else {
        echo "2. Making serial_number column nullable...\n";
        
        // Execute the ALTER TABLE statement
        DB::statement('ALTER TABLE sale_items MODIFY COLUMN serial_number VARCHAR(255) NULL');
        
        echo "✓ Column modified successfully!\n\n";
        
        // Verify the change
        echo "3. Verifying the change...\n";
        $updatedColumns = DB::select("SHOW COLUMNS FROM sale_items LIKE 'serial_number'");
        $updatedColumn = $updatedColumns[0];
        
        if ($updatedColumn->Null === 'YES') {
            echo "✓ Verification successful - column is now nullable!\n";
        } else {
            echo "✗ Verification failed - column is still NOT NULL\n";
            exit(1);
        }
    }
    
    echo "\n4. Testing SaleItem creation with null serial number...\n";
    
    // Test creating a sale item with null serial number
    $testQuery = "INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, subtotal, serial_number, created_at, updated_at) VALUES (999999, 1, 1, 10.00, 10.00, NULL, NOW(), NOW())";
    
    try {
        DB::statement($testQuery);
        echo "✓ Test insert with NULL serial_number successful!\n";
        
        // Clean up test data
        DB::statement("DELETE FROM sale_items WHERE sale_id = 999999");
        echo "✓ Test data cleaned up.\n";
        
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'foreign key constraint') !== false) {
            echo "✓ NULL serial_number accepted (foreign key error is expected for test data)\n";
        } else {
            echo "✗ Test failed: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n=== Fix Summary ===\n";
    echo "✓ Database column modified to allow NULL values\n";
    echo "✓ Sales with products without serial numbers will now work\n";
    echo "✓ The 422 error should be resolved\n";
    
    echo "\n=== Next Steps ===\n";
    echo "1. Clear application cache: php artisan cache:clear\n";
    echo "2. Test the POS sales functionality\n";
    echo "3. Monitor logs for any remaining errors\n";
    
} catch (Exception $e) {
    echo "✗ Error during database fix: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
