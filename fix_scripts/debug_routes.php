<?php

/**
 * Debug Routes Script - Lists all cylinder-related routes for troubleshooting
 */

require_once 'vendor/autoload.php';

echo "🔍 Debug: Cylinder Management Routes\n";
echo "===================================\n\n";

try {
    // Load Laravel application
    $app = require_once 'bootstrap/app.php';
    $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    
    $router = app('router');
    $routes = $router->getRoutes()->getRoutes();
    
    echo "📋 All Cylinder Routes Found:\n";
    echo "-----------------------------\n";
    
    foreach ($routes as $route) {
        $uri = $route->uri();
        
        // Check for cylinder-related routes
        if (str_contains($uri, 'cylinders') || str_contains($uri, 'cylinder')) {
            $methods = implode('|', $route->methods());
            $name = $route->getName() ?? 'unnamed';
            $action = $route->getActionName();
            
            echo sprintf(
                "%-8s %-40s %-20s %s\n", 
                $methods, 
                $uri, 
                $name,
                $action
            );
        }
    }
    
    echo "\n";
    
    // Specifically check for the problematic route
    echo "🎯 Checking Specific Route: api/cylinders/{cylinder}/quick-return\n";
    echo "----------------------------------------------------------------\n";
    
    $found = false;
    foreach ($routes as $route) {
        $uri = $route->uri();
        if (str_contains($uri, 'cylinders') && str_contains($uri, 'quick-return')) {
            $methods = implode('|', $route->methods());
            echo "✅ Found: $methods $uri\n";
            echo "   Action: " . $route->getActionName() . "\n";
            echo "   Parameters: " . implode(', ', $route->parameterNames()) . "\n";
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        echo "❌ Route not found! This is the issue.\n";
        echo "   Expected: POST api/cylinders/{cylinder}/quick-return\n";
    }
    
    echo "\n";
    
    // Check controller method
    echo "🔧 Checking Controller Method:\n";
    echo "------------------------------\n";
    
    $controllerClass = 'App\\Http\\Controllers\\Admin\\CylinderController';
    if (class_exists($controllerClass)) {
        if (method_exists($controllerClass, 'quickReturn')) {
            echo "✅ CylinderController::quickReturn method exists\n";
            
            // Get method reflection to check parameters
            $reflection = new ReflectionMethod($controllerClass, 'quickReturn');
            $parameters = $reflection->getParameters();
            
            echo "   Parameters: ";
            if (empty($parameters)) {
                echo "none\n";
            } else {
                $paramNames = array_map(function($p) { 
                    return '$' . $p->getName() . ($p->hasType() ? ': ' . $p->getType() : '');
                }, $parameters);
                echo implode(', ', $paramNames) . "\n";
            }
            
        } else {
            echo "❌ CylinderController::quickReturn method does not exist\n";
        }
    } else {
        echo "❌ CylinderController class does not exist\n";
    }
    
    echo "\n";
    
    // Test route matching manually
    echo "🧪 Manual Route Test:\n";
    echo "--------------------\n";
    
    try {
        $request = \Illuminate\Http\Request::create('/api/cylinders/1/quick-return', 'POST');
        $route = $router->getRoutes()->match($request);
        
        if ($route) {
            echo "✅ Route matches successfully\n";
            echo "   URI: " . $route->uri() . "\n";
            echo "   Methods: " . implode('|', $route->methods()) . "\n";
            echo "   Action: " . $route->getActionName() . "\n";
        }
    } catch (Exception $e) {
        echo "❌ Route matching failed: " . $e->getMessage() . "\n";
        echo "   This confirms the route issue\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n🎯 Troubleshooting Steps:\n";
echo "========================\n";
echo "1. If route not found: Check routes/api.php file\n";
echo "2. If method not found: Check CylinderController.php\n";
echo "3. Clear caches: php artisan route:clear && php artisan route:cache\n";
echo "4. Restart server: php artisan serve\n";

