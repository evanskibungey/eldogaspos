<?php
// Quick test script to verify POS system setup

echo "=== POS System Health Check ===\n\n";

// Check PHP version
echo "1. PHP Version: " . PHP_VERSION . "\n";
echo "   Status: " . (version_compare(PHP_VERSION, '8.0.0', '>=') ? '✓ OK' : '✗ Update required') . "\n\n";

// Check if in Laravel root
if (!file_exists('artisan')) {
    die("Error: This script must be run from the Laravel root directory!\n");
}

// Check .env file
echo "2. Environment File:\n";
if (file_exists('.env')) {
    echo "   Status: ✓ .env file exists\n";
    $env = parse_ini_file('.env');
    echo "   APP_ENV: " . ($env['APP_ENV'] ?? 'not set') . "\n";
    echo "   APP_DEBUG: " . ($env['APP_DEBUG'] ?? 'not set') . "\n";
    echo "   DB_CONNECTION: " . ($env['DB_CONNECTION'] ?? 'not set') . "\n";
} else {
    echo "   Status: ✗ .env file missing! Copy .env.example to .env\n";
}
echo "\n";

// Check key directories
echo "3. Directory Permissions:\n";
$dirs = [
    'storage/app' => is_writable('storage/app'),
    'storage/framework' => is_writable('storage/framework'),
    'storage/logs' => is_writable('storage/logs'),
    'bootstrap/cache' => is_writable('bootstrap/cache'),
    'public/images' => is_writable('public/images')
];

foreach ($dirs as $dir => $writable) {
    echo "   $dir: " . ($writable ? '✓ Writable' : '✗ Not writable') . "\n";
}
echo "\n";

// Check key files
echo "4. Required Files:\n";
$files = [
    'public/images/placeholder.jpg' => file_exists('public/images/placeholder.jpg'),
    'app/Http/Controllers/Pos/PosController.php' => file_exists('app/Http/Controllers/Pos/PosController.php'),
    'resources/views/pos/dashboard.blade.php' => file_exists('resources/views/pos/dashboard.blade.php'),
    'database/migrations/2025_02_23_014046_create_sales_table.php' => file_exists('database/migrations/2025_02_23_014046_create_sales_table.php')
];

foreach ($files as $file => $exists) {
    echo "   $file: " . ($exists ? '✓ Exists' : '✗ Missing') . "\n";
}
echo "\n";

// Database check (requires running through artisan)
echo "5. Database Connection:\n";
echo "   Run: php artisan tinker\n";
echo "   Then: DB::connection()->getPdo()\n";
echo "   If successful, database is connected\n\n";

// Route check
echo "6. Routes:\n";
echo "   Run: php artisan route:list | grep pos\n";
echo "   Should show POS routes including pos.sales.store\n\n";

// Final recommendations
echo "=== Recommendations ===\n";
echo "1. Clear all caches:\n";
echo "   php artisan cache:clear && php artisan route:clear && php artisan config:clear && php artisan view:clear\n\n";

echo "2. Create placeholder image:\n";
echo "   php public/create_placeholder.php\n\n";

echo "3. Run migrations and seeders:\n";
echo "   php artisan migrate:fresh --seed\n";
echo "   php artisan db:seed --class=QuickFixSeeder\n\n";

echo "4. Start development server:\n";
echo "   php artisan serve\n\n";

echo "5. Access POS Dashboard:\n";
echo "   http://localhost:8000/pos/dashboard\n";
echo "   or\n";
echo "   http://sales.eldogas.co.ke/pos/dashboard\n\n";

echo "=== End of Health Check ===\n";
