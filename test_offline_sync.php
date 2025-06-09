<?php

// Test script for offline sync functionality

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\OfflineSyncLog;
use App\Models\Sale;
use App\Models\Product;
use Carbon\Carbon;

echo "=== EldoGas POS Offline Sync Test Suite ===\n\n";

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

// Test 1: Check Offline Configuration
echo "TEST 1: Checking Offline Configuration\n";
echo str_repeat('-', 50) . "\n";

$offlineEnabled = config('offline.enabled');
if ($offlineEnabled) {
    success("Offline mode is ENABLED");
} else {
    warning("Offline mode is DISABLED");
    info("Run 'php artisan pos:offline-mode enable' to enable offline features");
}

// Test 2: Create Test Offline Sync Log
echo "\nTEST 2: Creating Test Offline Sync Log\n";
echo str_repeat('-', 50) . "\n";

try {
    $testLog = OfflineSyncLog::create([
        'offline_receipt_number' => 'OFF-TEST-' . time(),
        'sync_status' => 'pending',
        'original_data' => [
            'cart_items' => [
                ['id' => 1, 'quantity' => 2, 'price' => 100],
            ],
            'payment_method' => 'cash',
            'total_amount' => 200,
        ],
        'offline_created_at' => now(),
        'sync_attempts' => 0
    ]);
    
    success("Created test offline sync log with ID: {$testLog->id}");
    
    // Clean up
    $testLog->delete();
    info("Test log cleaned up");
    
} catch (\Exception $e) {
    error("Failed to create test log: " . $e->getMessage());
}

// Test 3: Test API Endpoints
echo "\nTEST 3: Testing API Endpoints\n";
echo str_repeat('-', 50) . "\n";

// Get a test user
$user = \App\Models\User::first();
if (!$user) {
    error("No users found in database. Please create a user first.");
} else {
    info("Using user: {$user->name} (ID: {$user->id})");
    
    // Test sync status endpoint
    try {
        $response = app()->handle(
            \Illuminate\Http\Request::create('/api/v1/offline/sync-status', 'GET')
                ->setUserResolver(function() use ($user) { return $user; })
        );
        
        if ($response->getStatusCode() == 200) {
            success("Sync status endpoint working");
            $data = json_decode($response->getContent(), true);
            info("Pending syncs: " . ($data['sync_status']['pending_sync_count'] ?? 0));
            info("Failed syncs: " . ($data['sync_status']['failed_sync_count'] ?? 0));
        } else {
            error("Sync status endpoint returned: " . $response->getStatusCode());
        }
    } catch (\Exception $e) {
        error("Failed to test sync status endpoint: " . $e->getMessage());
    }
}

// Test 4: Check Products for Offline Use
echo "\nTEST 4: Checking Products for Offline Use\n";
echo str_repeat('-', 50) . "\n";

$productCount = Product::where('status', 'active')->count();
if ($productCount > 0) {
    success("Found {$productCount} active products");
    
    // Show sample product
    $sampleProduct = Product::where('status', 'active')->first();
    info("Sample product: {$sampleProduct->name} (Stock: {$sampleProduct->stock})");
} else {
    warning("No active products found");
    info("Run 'php quick_product_fix.php' to create sample products");
}

// Test 5: Simulate Offline Sale Sync
echo "\nTEST 5: Simulating Offline Sale Sync\n";
echo str_repeat('-', 50) . "\n";

if ($productCount > 0 && $user) {
    try {
        // Create a test sync log
        $offlineReceipt = 'OFF-TEST-' . date('Ymd') . '-' . substr(uniqid(), -6);
        
        $syncLog = OfflineSyncLog::create([
            'offline_receipt_number' => $offlineReceipt,
            'sync_status' => 'pending',
            'original_data' => [
                'cart_items' => [
                    [
                        'id' => $sampleProduct->id,
                        'quantity' => 1,
                        'price' => $sampleProduct->price,
                        'serial_number' => $sampleProduct->serial_number
                    ]
                ],
                'payment_method' => 'cash',
                'customer_details' => null,
                'offline_receipt_number' => $offlineReceipt,
                'offline_created_at' => now()->toISOString(),
                'user_id' => $user->id  // Include user ID in the sync data
            ],
            'offline_created_at' => now(),
            'sync_attempts' => 0
        ]);
        
        success("Created pending sync log");
        
        // Attempt to sync
        $controller = new \App\Http\Controllers\API\OfflineSyncController();
        $request = new \Illuminate\Http\Request($syncLog->original_data);
        $request->setUserResolver(function() use ($user) { return $user; });
        
        $response = $controller->syncOfflineSale($request);
        $responseData = json_decode($response->getContent(), true);
        
        if ($responseData['success'] ?? false) {
            success("Successfully synced offline sale!");
            info("Server receipt: " . ($responseData['server_receipt_number'] ?? 'N/A'));
            
            // Check if sale was created
            $sale = Sale::find($responseData['sale_id'] ?? 0);
            if ($sale) {
                success("Sale record created with ID: {$sale->id}");
            }
        } else {
            error("Sync failed: " . ($responseData['message'] ?? 'Unknown error'));
        }
        
    } catch (\Exception $e) {
        error("Failed to simulate offline sync: " . $e->getMessage());
    }
}

// Test 6: Check Service Worker and Assets
echo "\nTEST 6: Checking Frontend Assets\n";
echo str_repeat('-', 50) . "\n";

$publicPath = public_path();
$assets = [
    'sw.js' => 'Service Worker',
    'js/pos-system.js' => 'POS System JavaScript',
    'css/offline.css' => 'Offline Styles'
];

foreach ($assets as $file => $description) {
    if (file_exists($publicPath . '/' . $file)) {
        success("{$description} found at: {$file}");
    } else {
        error("{$description} NOT FOUND at: {$file}");
    }
}

// Summary
echo "\n" . str_repeat('=', 50) . "\n";
echo "TEST SUMMARY\n";
echo str_repeat('=', 50) . "\n";

if ($offlineEnabled) {
    success("Offline mode is properly configured");
    echo "\nNext steps:\n";
    echo "1. Access the POS at: /pos/dashboard\n";
    echo "2. Open browser DevTools (F12)\n";
    echo "3. Go to Network tab and set to 'Offline'\n";
    echo "4. Try processing a sale\n";
    echo "5. Set back to 'Online' and watch sync\n";
} else {
    warning("Offline mode is currently disabled");
    echo "\nTo enable offline features:\n";
    echo "1. Run: php artisan pos:offline-mode enable\n";
    echo "2. Run: php artisan config:clear\n";
    echo "3. Run: npm run build\n";
    echo "4. Run this test again\n";
}

echo "\n";
