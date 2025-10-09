<?php
/**
 * Stock Movement Verification Script
 * 
 * This script helps verify that stock movements are being created correctly
 * for sales and sale voids.
 * 
 * Usage: php verify_stock_movements.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Sale;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

echo "===========================================\n";
echo "Stock Movement Verification Script\n";
echo "===========================================\n\n";

// Check if there are any sales
$totalSales = Sale::count();
echo "Total Sales in System: {$totalSales}\n";

// Check sales with stock movements
$salesWithMovements = DB::table('sales')
    ->join('stock_movements', function($join) {
        $join->on('sales.id', '=', 'stock_movements.reference_id')
             ->where('stock_movements.reference_type', '=', 'sale');
    })
    ->distinct()
    ->count('sales.id');

echo "Sales with Stock Movements: {$salesWithMovements}\n";

// Check voided sales
$voidedSales = Sale::where('status', 'voided')->count();
echo "Voided Sales: {$voidedSales}\n";

// Check voided sales with stock movements
$voidedSalesWithMovements = DB::table('sales')
    ->where('sales.status', 'voided')
    ->join('stock_movements', function($join) {
        $join->on('sales.id', '=', 'stock_movements.reference_id')
             ->where('stock_movements.reference_type', '=', 'sale_void');
    })
    ->distinct()
    ->count('sales.id');

echo "Voided Sales with Stock Movements: {$voidedSalesWithMovements}\n\n";

// Show recent stock movements by type
echo "===========================================\n";
echo "Recent Stock Movements (Last 10)\n";
echo "===========================================\n";

$recentMovements = StockMovement::with(['product', 'user'])
    ->whereIn('reference_type', ['sale', 'sale_void'])
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

if ($recentMovements->count() > 0) {
    foreach ($recentMovements as $movement) {
        $product = $movement->product ? $movement->product->name : 'Unknown';
        $user = $movement->user ? $movement->user->name : 'Unknown';
        $type = strtoupper($movement->type);
        $refType = $movement->reference_type;
        
        echo "Date: {$movement->created_at}\n";
        echo "Product: {$product}\n";
        echo "Type: {$type} | Reference: {$refType}\n";
        echo "Quantity: {$movement->quantity} | Price: \${$movement->unit_price}\n";
        echo "User: {$user}\n";
        echo "Notes: {$movement->notes}\n";
        echo "-------------------------------------------\n";
    }
} else {
    echo "No stock movements found for sales or voids.\n";
}

echo "\n===========================================\n";
echo "Stock Movement Statistics\n";
echo "===========================================\n";

$stats = [
    'total_movements' => StockMovement::count(),
    'sale_movements' => StockMovement::where('reference_type', 'sale')->count(),
    'void_movements' => StockMovement::where('reference_type', 'sale_void')->count(),
    'manual_adjustments' => StockMovement::where('reference_type', 'manual_adjustment')->count(),
    'initial_stock' => StockMovement::where('reference_type', 'initial')->count(),
];

foreach ($stats as $label => $count) {
    $formattedLabel = ucwords(str_replace('_', ' ', $label));
    echo "{$formattedLabel}: {$count}\n";
}

echo "\n===========================================\n";
echo "Integrity Checks\n";
echo "===========================================\n";

// Check for sales without stock movements (should be old sales from before implementation)
$salesWithoutMovements = Sale::whereDoesntHave('stockMovements', function($query) {
    $query->where('reference_type', 'sale');
})->where('status', '!=', 'voided')->count();

echo "Sales without Stock Movements: {$salesWithoutMovements}\n";
echo "(Note: This is normal for sales created before the implementation)\n\n";

// Check for voided sales without void movements
$voidedWithoutMovements = Sale::where('status', 'voided')
    ->whereDoesntHave('stockMovements', function($query) {
        $query->where('reference_type', 'sale_void');
    })->count();

echo "Voided Sales without Void Movements: {$voidedWithoutMovements}\n";
echo "(Note: This is normal for voids created before the implementation)\n\n";

echo "===========================================\n";
echo "Verification Complete!\n";
echo "===========================================\n";

// Add relationship method hint if needed
echo "\nNote: If you see errors about 'stockMovements' relationship,\n";
echo "add this method to the Sale model:\n\n";
echo "public function stockMovements()\n";
echo "{\n";
echo "    return \$this->hasMany(StockMovement::class, 'reference_id')\n";
echo "                 ->where('reference_type', 'sale');\n";
echo "}\n";
