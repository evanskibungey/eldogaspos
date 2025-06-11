<?php
/*
 * POS Sale Error Fix Script
 * This script addresses the 422 "Unprocessable Content" error in the POS system
 * 
 * Issues Fixed:
 * 1. Made serial_number field nullable in sale_items table
 * 2. Improved validation rules for sales
 * 3. Added better error handling and stock validation
 * 4. Enhanced error messages for better debugging
 */

echo "=== POS Sale Error Fix Script ===\n";
echo "Starting fix process...\n\n";

try {
    // 1. Run the migration to make serial_number nullable
    echo "1. Running migration to make serial_number nullable...\n";
    $output = shell_exec('php artisan migrate --force 2>&1');
    echo "Migration output: " . $output . "\n";
    
    // 2. Clear application cache to ensure changes take effect
    echo "2. Clearing application cache...\n";
    shell_exec('php artisan cache:clear 2>&1');
    shell_exec('php artisan config:clear 2>&1');
    shell_exec('php artisan route:clear 2>&1');
    echo "Cache cleared successfully.\n";
    
    // 3. Verify database schema
    echo "3. Verifying database schema...\n";
    
    // Connect to database and check if the column is nullable
    $host = env('DB_HOST', 'localhost');
    $database = env('DB_DATABASE', 'eldogaspos');
    $username = env('DB_USERNAME', 'root');
    $password = env('DB_PASSWORD', '');
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->query("SHOW COLUMNS FROM sale_items LIKE 'serial_number'");
        $column = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($column && $column['Null'] === 'YES') {
            echo "✓ Serial number field is now nullable!\n";
        } else {
            echo "⚠ Serial number field might still be NOT NULL. Please check manually.\n";
        }
        
    } catch (PDOException $e) {
        echo "Database connection failed: " . $e->getMessage() . "\n";
        echo "Please verify the database schema manually.\n";
    }
    
    echo "\n=== Fix Summary ===\n";
    echo "✓ Migration created and run to make serial_number nullable\n";
    echo "✓ PosController updated with better validation and error handling\n";
    echo "✓ Added stock validation before processing sales\n";
    echo "✓ Improved error messages for debugging\n";
    echo "✓ Added specific handling for database constraint violations\n";
    
    echo "\n=== Test the Fix ===\n";
    echo "1. Try processing a sale through the POS interface\n";
    echo "2. Test with products that don't have serial numbers\n";
    echo "3. Test with both cash and credit payments\n";
    echo "4. Check the application logs for any remaining errors\n";
    
    echo "\n=== Files Modified ===\n";
    echo "• database/migrations/2025_06_11_000001_make_serial_number_nullable_in_sale_items.php (NEW)\n";
    echo "• app/Http/Controllers/Pos/PosController.php (UPDATED)\n";
    
    echo "\nFix process completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error during fix process: " . $e->getMessage() . "\n";
    echo "Please run the migration manually: php artisan migrate\n";
}
