<?php

// Quick test to verify offline sync fix

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Product;

echo "=== Testing Offline Sync User ID Fix ===\n\n";

// Get a user
$user = User::first();
if (!$user) {
    echo "❌ No users found. Please create a user first.\n";
    exit(1);
}

echo "✅ Using user: {$user->name} (ID: {$user->id})\n";

// Get a product
$product = Product::where('status', 'active')->first();
if (!$product) {
    echo "❌ No active products found.\n";
    exit(1);
}

echo "✅ Using product: {$product->name}\n";

// Test the sync endpoint
$controller = new \App\Http\Controllers\API\OfflineSyncController();

$testData = [
    'cart_items' => [
        [
            'id' => $product->id,
            'quantity' => 1,
            'price' => $product->price,
            'serial_number' => $product->serial_number
        ]
    ],
    'payment_method' => 'cash',
    'offline_receipt_number' => 'OFF-QUICK-TEST-' . time(),
    'offline_created_at' => now()->toISOString(),
    'user_id' => $user->id  // Include user ID
];

$request = new \Illuminate\Http\Request($testData);
$request->setUserResolver(function() use ($user) { return $user; });

try {
    $response = $controller->syncOfflineSale($request);
    $responseData = json_decode($response->getContent(), true);
    
    if ($responseData['success'] ?? false) {
        echo "\n✅ SUCCESS! Offline sale synced successfully.\n";
        echo "   Receipt: " . ($responseData['server_receipt_number'] ?? 'N/A') . "\n";
        echo "   Sale ID: " . ($responseData['sale_id'] ?? 'N/A') . "\n";
        
        // Clean up the test sale
        if (isset($responseData['sale_id'])) {
            DB::table('sale_items')->where('sale_id', $responseData['sale_id'])->delete();
            DB::table('sales')->where('id', $responseData['sale_id'])->delete();
            DB::table('offline_sync_logs')->where('sale_id', $responseData['sale_id'])->delete();
            echo "\n✅ Test data cleaned up.\n";
        }
    } else {
        echo "\n❌ Sync failed: " . ($responseData['message'] ?? 'Unknown error') . "\n";
    }
} catch (\Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";
