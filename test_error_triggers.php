<?php
// Test script to check what could trigger POS errors

use App\Models\Product;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

// This script helps identify potential error triggers in the POS system

echo "=== POS Error Trigger Test ===\n\n";

// 1. Check for products with problematic stock levels
echo "1. Products with Stock Issues:\n";
$outOfStock = Product::where('stock', '<=', 0)->count();
$lowStock = Product::whereColumn('stock', '<=', 'min_stock')->count();
$negativeStock = Product::where('stock', '<', 0)->count();

echo "   - Out of stock products: $outOfStock\n";
echo "   - Low stock products: $lowStock\n";
echo "   - Negative stock products: $negativeStock (should be 0)\n\n";

// 2. Check for missing required data
echo "2. Missing Required Data:\n";
$noPrice = Product::where('price', 0)->orWhereNull('price')->count();
$noCategory = Product::whereNull('category_id')->count();
$inactiveProducts = Product::where('status', '!=', 'active')->count();

echo "   - Products without price: $noPrice\n";
echo "   - Products without category: $noCategory\n";
echo "   - Inactive products: $inactiveProducts\n\n";

// 3. Check database connectivity
echo "3. Database Connection:\n";
try {
    DB::connection()->getPdo();
    echo "   - Status: ✓ Connected\n";
} catch (\Exception $e) {
    echo "   - Status: ✗ Connection failed\n";
    echo "   - Error: " . $e->getMessage() . "\n";
}
echo "\n";

// 4. Check for missing settings
echo "4. Required Settings:\n";
$requiredSettings = [
    'company_name',
    'currency_symbol',
    'tax_percentage'
];

foreach ($requiredSettings as $key) {
    $exists = Setting::where('key', $key)->exists();
    echo "   - $key: " . ($exists ? '✓ Set' : '✗ Missing') . "\n";
}
echo "\n";

// 5. Check for potential sale blockers
echo "5. Sale Processing Checks:\n";
$hasWalkInCustomer = Customer::where('phone', '0000000000')->exists();
echo "   - Walk-in customer exists: " . ($hasWalkInCustomer ? '✓ Yes' : '✗ No') . "\n";

// Check if sales table exists
$tablesExist = DB::select("SHOW TABLES LIKE 'sales'");
echo "   - Sales table exists: " . (count($tablesExist) > 0 ? '✓ Yes' : '✗ No') . "\n";

$tablesExist = DB::select("SHOW TABLES LIKE 'sale_items'");
echo "   - Sale items table exists: " . (count($tablesExist) > 0 ? '✓ Yes' : '✗ No') . "\n\n";

// 6. Common error scenarios
echo "6. Common Error Scenarios:\n";
echo "   a) Stock Errors:\n";
echo "      - Adding out-of-stock item → 'This product is out of stock'\n";
echo "      - Exceeding available quantity → 'Cannot add more. Only X available'\n";
echo "   \n";
echo "   b) Network Errors:\n";
echo "      - Server timeout → 'Network error or server exception occurred'\n";
echo "      - Database down → 'Network error or server exception occurred'\n";
echo "   \n";
echo "   c) Validation Errors:\n";
echo "      - Credit sale without customer name → 'The customer details.name field is required'\n";
echo "      - Credit sale without phone → 'The customer details.phone field is required'\n";
echo "   \n";
echo "   d) Server Errors:\n";
echo "      - Missing CSRF token → '419 Page Expired'\n";
echo "      - Route not found → '404 Not Found'\n";
echo "      - PHP errors → '500 Server Error'\n\n";

// 7. Test data creation
echo "7. Create Test Scenarios:\n";
echo "   Run these commands to test different error scenarios:\n";
echo "   \n";
echo "   # Create out-of-stock product:\n";
echo "   php artisan tinker\n";
echo "   >>> Product::factory()->create(['name' => 'TEST OUT OF STOCK', 'stock' => 0, 'price' => 100])\n";
echo "   \n";
echo "   # Create low-stock product:\n";
echo "   >>> Product::factory()->create(['name' => 'TEST LOW STOCK', 'stock' => 2, 'min_stock' => 5, 'price' => 200])\n";
echo "   \n";
echo "   # Test network error (stop MySQL in XAMPP)\n";
echo "   # Test validation error (try credit sale without customer details)\n";

echo "\n=== End of Error Trigger Test ===\n";
