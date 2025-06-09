<?php

// Cleanup script for offline sync test data

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\OfflineSyncLog;
use App\Models\Sale;

echo "=== EldoGas POS Offline Sync Cleanup ===\n\n";

// Color output helpers
function success($message) {
    echo "\033[32m✅ {$message}\033[0m\n";
}

function error($message) {
    echo "\033[31m❌ {$message}\033[0m\n";
}

function info($message) {
    echo "\033[36mℹ️  {$message}\033[0m\n";
}

function warning($message) {
    echo "\033[33m⚠️  {$message}\033[0m\n";
}

// Confirm action
echo "This script will:\n";
echo "1. Delete all offline sync logs\n";
echo "2. Remove test sales (with OFF- receipt numbers)\n";
echo "3. Reset sync counters\n";
echo "\n";

echo "Do you want to continue? (yes/no) [no]: ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);
$answer = trim($line);

if ($answer !== 'yes') {
    info("Cleanup cancelled.");
    exit(0);
}

echo "\n";

// Clean offline sync logs
try {
    info("Cleaning offline sync logs...");
    
    $testLogs = OfflineSyncLog::where('offline_receipt_number', 'like', 'OFF-TEST-%')->count();
    $allLogs = OfflineSyncLog::count();
    
    if ($testLogs > 0) {
        OfflineSyncLog::where('offline_receipt_number', 'like', 'OFF-TEST-%')->delete();
        success("Deleted {$testLogs} test sync logs");
    }
    
    echo "Found {$allLogs} total sync logs. Delete all? (yes/no) [no]: ";
    $line = fgets($handle);
    $answer = trim($line);
    
    if ($answer === 'yes') {
        OfflineSyncLog::truncate();
        success("Deleted all sync logs");
    } else {
        info("Kept existing sync logs");
    }
    
} catch (\Exception $e) {
    error("Failed to clean sync logs: " . $e->getMessage());
}

// Clean test sales
try {
    info("\nCleaning test sales...");
    
    $testSales = Sale::where('offline_receipt_number', 'like', 'OFF-TEST-%')->count();
    
    if ($testSales > 0) {
        // Delete sale items first
        $saleIds = Sale::where('offline_receipt_number', 'like', 'OFF-TEST-%')->pluck('id');
        DB::table('sale_items')->whereIn('sale_id', $saleIds)->delete();
        
        // Delete sales
        Sale::where('offline_receipt_number', 'like', 'OFF-TEST-%')->delete();
        success("Deleted {$testSales} test sales and their items");
    } else {
        info("No test sales found");
    }
    
} catch (\Exception $e) {
    error("Failed to clean test sales: " . $e->getMessage());
}

// Show current status
echo "\n";
info("Current Status:");
echo str_repeat('-', 50) . "\n";

try {
    $pendingSync = OfflineSyncLog::where('sync_status', 'pending')->count();
    $failedSync = OfflineSyncLog::where('sync_status', 'failed')->count();
    $syncedCount = OfflineSyncLog::where('sync_status', 'synced')->count();
    $offlineSales = Sale::where('is_offline_sync', true)->count();
    
    echo "Pending syncs: {$pendingSync}\n";
    echo "Failed syncs: {$failedSync}\n";
    echo "Completed syncs: {$syncedCount}\n";
    echo "Offline sales: {$offlineSales}\n";
    
} catch (\Exception $e) {
    error("Failed to get status: " . $e->getMessage());
}

// Clear browser data reminder
echo "\n";
warning("Remember to clear browser data:");
echo "1. Open Chrome DevTools (F12)\n";
echo "2. Go to Application tab\n";
echo "3. Clear Storage > Clear site data\n";
echo "\n";

success("Cleanup completed!");
echo "\n";

fclose($handle);
