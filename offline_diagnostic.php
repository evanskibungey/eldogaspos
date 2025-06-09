<?php

// Quick diagnostic script to check offline sync implementation

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== EldoGas POS Offline Sync Diagnostic ===\n\n";

// Check database connection
try {
    DB::connection()->getPdo();
    echo "✅ Database connection successful\n";
} catch (\Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Check offline configuration
echo "\n--- Offline Configuration ---\n";
$offlineEnabled = config('offline.enabled');
echo "Offline Mode: " . ($offlineEnabled ? '✅ ENABLED' : '❌ DISABLED') . "\n";
echo "Database Name: " . config('offline.database.name') . "\n";
echo "Sync Interval: " . (config('offline.sync.auto_sync_interval') / 1000) . " seconds\n";

// Check if tables exist
echo "\n--- Database Tables ---\n";
$tables = [
    'offline_sync_logs' => 'Offline sync logs table',
    'sales' => 'Sales table',
    'products' => 'Products table',
    'customers' => 'Customers table'
];

foreach ($tables as $table => $description) {
    if (Schema::hasTable($table)) {
        echo "✅ {$description} ({$table})\n";
    } else {
        echo "❌ {$description} ({$table}) - NOT FOUND\n";
    }
}

// Check offline sync columns in sales table
if (Schema::hasTable('sales')) {
    echo "\n--- Sales Table Columns ---\n";
    $columns = ['is_offline_sync', 'offline_receipt_number', 'offline_created_at'];
    foreach ($columns as $column) {
        if (Schema::hasColumn('sales', $column)) {
            echo "✅ Column '{$column}' exists\n";
        } else {
            echo "❌ Column '{$column}' missing\n";
        }
    }
}

// Check API routes
echo "\n--- API Routes ---\n";
$routes = [
    '/api/v1/offline/products' => 'GET',
    '/api/v1/offline/sync-sale' => 'POST',
    '/api/v1/offline/sync-status' => 'GET'
];

$routeCollection = app('router')->getRoutes();
foreach ($routes as $uri => $method) {
    $found = false;
    foreach ($routeCollection as $route) {
        if ($route->uri() === ltrim($uri, '/') && in_array($method, $route->methods())) {
            $found = true;
            break;
        }
    }
    echo ($found ? "✅" : "❌") . " {$method} {$uri}\n";
}

// Check models
echo "\n--- Models ---\n";
$models = [
    'App\Models\OfflineSyncLog',
    'App\Models\Sale',
    'App\Models\Product',
    'App\Models\Customer'
];

foreach ($models as $model) {
    if (class_exists($model)) {
        echo "✅ {$model}\n";
    } else {
        echo "❌ {$model} - NOT FOUND\n";
    }
}

// Check controller
echo "\n--- Controllers ---\n";
$controller = 'App\Http\Controllers\API\OfflineSyncController';
if (class_exists($controller)) {
    echo "✅ {$controller}\n";
} else {
    echo "❌ {$controller} - NOT FOUND\n";
}

// Check pending migrations
echo "\n--- Migrations ---\n";
try {
    $pendingMigrations = app('migrator')->getMigrationFiles(database_path('migrations'));
    $ranMigrations = app('migrator')->getRepository()->getRan();
    
    $offlineMigrations = [
        '2025_06_04_000001_create_offline_sync_logs_table',
        '2025_06_04_000002_add_offline_sync_columns_to_sales_table'
    ];
    
    foreach ($offlineMigrations as $migration) {
        $found = false;
        foreach ($ranMigrations as $ran) {
            if (strpos($ran, $migration) !== false) {
                $found = true;
                break;
            }
        }
        echo ($found ? "✅" : "❌") . " {$migration}\n";
    }
} catch (\Exception $e) {
    echo "❌ Could not check migrations: " . $e->getMessage() . "\n";
}

// Check for any offline sync logs
if (Schema::hasTable('offline_sync_logs')) {
    echo "\n--- Offline Sync Status ---\n";
    try {
        $pending = DB::table('offline_sync_logs')->where('sync_status', 'pending')->count();
        $synced = DB::table('offline_sync_logs')->where('sync_status', 'synced')->count();
        $failed = DB::table('offline_sync_logs')->where('sync_status', 'failed')->count();
        
        echo "Pending: {$pending}\n";
        echo "Synced: {$synced}\n";
        echo "Failed: {$failed}\n";
    } catch (\Exception $e) {
        echo "❌ Could not query offline_sync_logs: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Diagnostic Complete ===\n";

// Recommendations
echo "\n--- Recommendations ---\n";
if (!$offlineEnabled) {
    echo "⚠️  Offline mode is currently DISABLED\n";
    echo "   Run: php artisan pos:offline-mode enable\n";
}

echo "\nFor any missing migrations, run:\n";
echo "   php artisan migrate\n";

echo "\nTo rebuild assets:\n";
echo "   npm run build\n";

echo "\nTo clear caches:\n";
echo "   php artisan config:clear\n";
echo "   php artisan cache:clear\n";
