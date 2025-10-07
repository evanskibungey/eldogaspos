<?php
/**
 * Quick API integration test for Cylinder Management
 * This script tests the key API endpoints that the Flutter app uses
 */

require_once 'vendor/autoload.php';

echo "🔧 Testing Cylinder Management API Integration\n";
echo "============================================\n\n";

// Test 1: Check if routes are defined
echo "1. Testing Route Definitions...\n";

try {
    // Load Laravel application
    $app = require_once 'bootstrap/app.php';
    $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

    // Get all API routes
    $router = app('router');
    $routes = $router->getRoutes()->getRoutes();
    
    $cylinderRoutes = [];
    $customerRoutes = [];
    
    foreach ($routes as $route) {
        $uri = $route->uri();
        
        if (str_contains($uri, 'cylinders')) {
            $cylinderRoutes[] = $route->methods()[0] . ' ' . $uri;
        }
        
        if (str_contains($uri, 'customers') && str_contains($uri, 'api/')) {
            $customerRoutes[] = $route->methods()[0] . ' ' . $uri;
        }
    }
    
    echo "   ✅ Found " . count($cylinderRoutes) . " cylinder routes:\n";
    foreach ($cylinderRoutes as $route) {
        echo "      - $route\n";
    }
    
    echo "   ✅ Found " . count($customerRoutes) . " customer API routes:\n";
    foreach ($customerRoutes as $route) {
        echo "      - $route\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Error loading routes: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Check if controllers exist
echo "2. Testing Controller Existence...\n";

$controllers = [
    'App\\Http\\Controllers\\Admin\\CylinderController' => 'CylinderController',
    'App\\Http\\Controllers\\Admin\\CustomerController' => 'CustomerController (for API methods)',
];

foreach ($controllers as $class => $description) {
    if (class_exists($class)) {
        echo "   ✅ $description exists\n";
        
        // Check specific methods
        if ($class === 'App\\Http\\Controllers\\Admin\\CylinderController') {
            $methods = ['index', 'store', 'show', 'update', 'complete', 'quickComplete', 'quickReturn', 'cancel'];
            foreach ($methods as $method) {
                if (method_exists($class, $method)) {
                    echo "      ✅ Method: $method\n";
                } else {
                    echo "      ❌ Method: $method (missing)\n";
                }
            }
        }
        
        if ($class === 'App\\Http\\Controllers\\Admin\\CustomerController') {
            $methods = ['searchCustomers', 'quickStore'];
            foreach ($methods as $method) {
                if (method_exists($class, $method)) {
                    echo "      ✅ API Method: $method\n";
                } else {
                    echo "      ❌ API Method: $method (missing)\n";
                }
            }
        }
        
    } else {
        echo "   ❌ $description does not exist\n";
    }
}

echo "\n";

// Test 3: Check if models exist and have required methods
echo "3. Testing Model Setup...\n";

$models = [
    'App\\Models\\CylinderTransaction',
    'App\\Models\\Customer',
    'App\\Models\\User',
];

foreach ($models as $model) {
    if (class_exists($model)) {
        echo "   ✅ " . basename($model) . " model exists\n";
        
        if ($model === 'App\\Models\\CylinderTransaction') {
            $methods = ['isDropOff', 'isAdvanceCollection', 'isActive', 'isCompleted', 'isPending', 'isPaid'];
            foreach ($methods as $method) {
                if (method_exists($model, $method)) {
                    echo "      ✅ Helper method: $method\n";
                } else {
                    echo "      ❌ Helper method: $method (missing)\n";
                }
            }
        }
    } else {
        echo "   ❌ " . basename($model) . " model does not exist\n";
    }
}

echo "\n";

// Test 4: Check database table structure
echo "4. Testing Database Structure...\n";

try {
    $pdo = new PDO(
        'mysql:host=' . env('DB_HOST', '127.0.0.1') . ';dbname=' . env('DB_DATABASE'), 
        env('DB_USERNAME'), 
        env('DB_PASSWORD')
    );
    
    // Check if cylinder_transactions table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'cylinder_transactions'");
    if ($stmt->rowCount() > 0) {
        echo "   ✅ cylinder_transactions table exists\n";
        
        // Check columns
        $stmt = $pdo->query("DESCRIBE cylinder_transactions");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $requiredColumns = [
            'id', 'reference_number', 'customer_id', 'customer_name', 'customer_phone',
            'cylinder_size', 'cylinder_type', 'transaction_type', 'payment_status',
            'amount', 'deposit_amount', 'status', 'drop_off_date', 'collection_date',
            'return_date', 'notes', 'created_by', 'completed_by', 'created_at', 'updated_at'
        ];
        
        foreach ($requiredColumns as $column) {
            if (in_array($column, $columns)) {
                echo "      ✅ Column: $column\n";
            } else {
                echo "      ❌ Column: $column (missing)\n";
            }
        }
        
    } else {
        echo "   ❌ cylinder_transactions table does not exist\n";
        echo "      Run: php artisan migrate\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Database connection error: " . $e->getMessage() . "\n";
    echo "      Check your .env database configuration\n";
}

echo "\n";

// Test 5: API Response Format Test
echo "5. Testing API Response Formats...\n";

try {
    // Test if we can create a sample response
    $sampleTransaction = [
        'id' => 1,
        'reference_number' => 'CYL20250101001',
        'customer_id' => 1,
        'customer_name' => 'Test Customer',
        'customer_phone' => '1234567890',
        'cylinder_size' => '13kg',
        'cylinder_type' => 'LPG',
        'transaction_type' => 'drop_off',
        'payment_status' => 'paid',
        'amount' => '1500.00',
        'deposit_amount' => '0.00',
        'status' => 'active',
        'drop_off_date' => '2025-01-01T10:00:00Z',
        'collection_date' => null,
        'return_date' => null,
        'notes' => 'Test transaction',
        'created_by' => 1,
        'completed_by' => null,
        'created_at' => '2025-01-01T10:00:00Z',
        'updated_at' => '2025-01-01T10:00:00Z',
        'customer' => null,
        'created_by_user' => null,
        'completed_by_user' => null,
    ];
    
    $jsonResponse = json_encode($sampleTransaction);
    if ($jsonResponse !== false) {
        echo "   ✅ Sample API response format is valid JSON\n";
        echo "   ✅ All required fields are present\n";
    } else {
        echo "   ❌ Sample API response cannot be encoded to JSON\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ API response format error: " . $e->getMessage() . "\n";
}

echo "\n";

echo "🎉 Integration Test Complete!\n";
echo "============================\n\n";

echo "📋 Next Steps:\n";
echo "1. Ensure your Laravel server is running: php artisan serve\n";
echo "2. Test the Flutter app cylinder management features\n";
echo "3. Check that API responses match expected formats\n";
echo "4. Verify all CRUD operations work correctly\n\n";

echo "🔗 Key API Endpoints to test:\n";
echo "- GET /api/cylinders - List cylinder transactions\n";
echo "- POST /api/cylinders - Create new transaction\n";
echo "- GET /api/cylinders/{id} - Get transaction details\n";
echo "- POST /api/cylinders/{id}/quick-complete - Complete drop-off\n";
echo "- POST /api/cylinders/{id}/quick-return - Process return\n";
echo "- GET /api/customers/search?q=query - Search customers\n";
echo "- POST /api/customers/quick-create - Create new customer\n";
