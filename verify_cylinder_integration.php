<?php
/**
 * Cylinder Inventory Integration Verification Script
 * 
 * This script helps verify that cylinder transactions are properly integrated
 * with product inventory and stock movements.
 * 
 * Usage: php verify_cylinder_integration.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\CylinderTransaction;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

echo "===========================================\n";
echo "Cylinder Inventory Integration Verification\n";
echo "===========================================\n\n";

// Check total cylinder transactions
$totalCylinders = CylinderTransaction::count();
echo "Total Cylinder Transactions: {$totalCylinders}\n";

// Check cylinder transactions with products linked
$cylindersWithProducts = CylinderTransaction::whereNotNull('product_id')->count();
echo "Cylinder Transactions with Products: {$cylindersWithProducts}\n";

// Check cylinder transactions without products
$cylindersWithoutProducts = CylinderTransaction::whereNull('product_id')->count();
echo "Cylinder Transactions without Products: {$cylindersWithoutProducts}\n\n";

// Check by transaction type
echo "===========================================\n";
echo "Transactions by Type\n";
echo "===========================================\n";

$dropOffs = CylinderTransaction::dropOffs()->count();
$advanceCollections = CylinderTransaction::advanceCollections()->count();

echo "Drop-Offs: {$dropOffs}\n";
echo "Advance Collections: {$advanceCollections}\n\n";

// Check cylinder stock movements
echo "===========================================\n";
echo "Cylinder Stock Movements\n";
echo "===========================================\n";

$cylinderMovements = StockMovement::cylinderTransactions()->count();
$cylinderCancellations = StockMovement::cylinderCancellations()->count();

echo "Cylinder Transaction Movements: {$cylinderMovements}\n";
echo "Cylinder Cancellation Movements: {$cylinderCancellations}\n";
echo "Total Cylinder-Related Movements: " . ($cylinderMovements + $cylinderCancellations) . "\n\n";

// Recent cylinder movements
echo "===========================================\n";
echo "Recent Cylinder Stock Movements (Last 10)\n";
echo "===========================================\n";

$recentMovements = StockMovement::whereIn('reference_type', ['cylinder_transaction', 'cylinder_cancellation'])
    ->with(['product', 'user'])
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

if ($recentMovements->count() > 0) {
    foreach ($recentMovements as $movement) {
        $product = $movement->product ? $movement->product->name : 'Unknown';
        $user = $movement->user ? $movement->user->name : 'Unknown';
        $type = strtoupper($movement->type);
        $refType = str_replace('_', ' ', ucfirst($movement->reference_type));
        
        echo "Date: {$movement->created_at}\n";
        echo "Product: {$product}\n";
        echo "Type: {$type} | Reference: {$refType}\n";
        echo "Quantity: {$movement->quantity} | Price: \${$movement->unit_price}\n";
        echo "User: {$user}\n";
        echo "Notes: {$movement->notes}\n";
        echo "-------------------------------------------\n";
    }
} else {
    echo "No cylinder stock movements found.\n";
}

// Check transactions with movements
echo "\n===========================================\n";
echo "Transaction-Movement Correlation\n";
echo "===========================================\n";

$transactionsWithMovements = DB::table('cylinder_transactions as ct')
    ->join('stock_movements as sm', function($join) {
        $join->on('ct.id', '=', 'sm.reference_id')
             ->where('sm.reference_type', '=', 'cylinder_transaction');
    })
    ->where('ct.product_id', '!=', null)
    ->distinct()
    ->count('ct.id');

$activeWithProducts = CylinderTransaction::where('product_id', '!=', null)
    ->whereIn('status', ['active', 'completed'])
    ->count();

echo "Active/Completed Transactions with Products: {$activeWithProducts}\n";
echo "Transactions with Stock Movements: {$transactionsWithMovements}\n";

if ($activeWithProducts > 0) {
    $coveragePercent = round(($transactionsWithMovements / $activeWithProducts) * 100, 2);
    echo "Coverage: {$coveragePercent}%\n";
}

// Integrity checks
echo "\n===========================================\n";
echo "Integrity Checks\n";
echo "===========================================\n";

// Check for completed drop-offs with products but no movements
$completedDropOffsWithoutMovements = CylinderTransaction::where('transaction_type', 'drop_off')
    ->where('status', 'completed')
    ->whereNotNull('product_id')
    ->whereNotNull('collection_date')
    ->whereDoesntHave('stockMovements')
    ->count();

echo "Completed Drop-Offs without Movements: {$completedDropOffsWithoutMovements}\n";
echo "(Should be 0 for complete integration)\n\n";

// Check for advance collections with products but no movements
$advanceCollectionsWithoutMovements = CylinderTransaction::where('transaction_type', 'advance_collection')
    ->whereNotNull('product_id')
    ->whereDoesntHave('stockMovements')
    ->where('status', '!=', 'cancelled')
    ->count();

echo "Advance Collections without Movements: {$advanceCollectionsWithoutMovements}\n";
echo "(Should be 0 for complete integration)\n\n";

// Check for orphaned cylinder movements
$orphanedMovements = DB::table('stock_movements as sm')
    ->leftJoin('cylinder_transactions as ct', 'sm.reference_id', '=', 'ct.id')
    ->whereIn('sm.reference_type', ['cylinder_transaction', 'cylinder_cancellation'])
    ->whereNull('ct.id')
    ->count();

echo "Orphaned Cylinder Movements: {$orphanedMovements}\n";
echo "(Should be 0 - movements without valid transaction)\n\n";

// Products linked to cylinders
echo "===========================================\n";
echo "Products Used in Cylinder Transactions\n";
echo "===========================================\n";

$cylinderProducts = DB::table('cylinder_transactions')
    ->select('product_id', DB::raw('COUNT(*) as usage_count'))
    ->whereNotNull('product_id')
    ->groupBy('product_id')
    ->get();

if ($cylinderProducts->count() > 0) {
    foreach ($cylinderProducts as $cp) {
        $product = Product::find($cp->product_id);
        if ($product) {
            echo "Product: {$product->name} (ID: {$product->id})\n";
            echo "  Current Stock: {$product->stock}\n";
            echo "  Used in Transactions: {$cp->usage_count}\n";
            echo "  ---\n";
        }
    }
} else {
    echo "No products linked to cylinder transactions yet.\n";
}

// Statistics summary
echo "\n===========================================\n";
echo "Summary Statistics\n";
echo "===========================================\n";

$stats = [
    'total_transactions' => $totalCylinders,
    'with_products' => $cylindersWithProducts,
    'without_products' => $cylindersWithoutProducts,
    'drop_offs' => $dropOffs,
    'advance_collections' => $advanceCollections,
    'completed' => CylinderTransaction::completed()->count(),
    'active' => CylinderTransaction::active()->count(),
    'cancelled' => CylinderTransaction::where('status', 'cancelled')->count(),
    'stock_movements' => $cylinderMovements,
    'cancellation_movements' => $cylinderCancellations,
];

foreach ($stats as $label => $count) {
    $formattedLabel = ucwords(str_replace('_', ' ', $label));
    echo "{$formattedLabel}: {$count}\n";
}

// Recommendations
echo "\n===========================================\n";
echo "Recommendations\n";
echo "===========================================\n";

if ($cylindersWithoutProducts > 0 && $totalCylinders > 0) {
    $percentWithout = round(($cylindersWithoutProducts / $totalCylinders) * 100, 2);
    echo "⚠ {$percentWithout}% of transactions don't have products linked.\n";
    echo "  Consider linking products to track inventory better.\n\n";
}

if ($completedDropOffsWithoutMovements > 0) {
    echo "⚠ Found completed drop-offs without stock movements.\n";
    echo "  These may be from before the integration was implemented.\n\n";
}

if ($advanceCollectionsWithoutMovements > 0) {
    echo "⚠ Found advance collections without stock movements.\n";
    echo "  These transactions may need attention.\n\n";
}

if ($orphanedMovements > 0) {
    echo "⚠ Found orphaned stock movements.\n";
    echo "  These movements reference deleted transactions.\n\n";
}

if ($completedDropOffsWithoutMovements == 0 && 
    $advanceCollectionsWithoutMovements == 0 && 
    $orphanedMovements == 0) {
    echo "✓ All integrity checks passed!\n";
    echo "✓ Integration appears to be working correctly.\n\n";
}

echo "===========================================\n";
echo "Verification Complete!\n";
echo "===========================================\n";

echo "\nNext Steps:\n";
echo "1. Review any warnings above\n";
echo "2. Test creating new cylinder transactions with products\n";
echo "3. Test completing transactions and verify inventory changes\n";
echo "4. Test cancelling transactions and verify inventory restoration\n";
echo "5. Review the CYLINDER_INVENTORY_INTEGRATION.md documentation\n";
