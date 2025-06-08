<?php
// Quick debug route for testing database queries
// Add this to your routes/web.php temporarily for debugging

// Add this route temporarily to test the database
Route::get('/debug-db', function() {
    try {
        $totalProducts = \App\Models\Product::count();
        $activeProducts = \App\Models\Product::where('status', 'active')->count();
        $inactiveProducts = \App\Models\Product::where('status', 'inactive')->count();
        $nullStatusProducts = \App\Models\Product::whereNull('status')->count();
        $emptyStatusProducts = \App\Models\Product::where('status', '')->count();
        
        $products = \App\Models\Product::with('category')->get();
        
        echo "<h1>Database Debug Information</h1>";
        echo "<h2>Product Counts:</h2>";
        echo "<ul>";
        echo "<li>Total Products: {$totalProducts}</li>";
        echo "<li>Active Products: {$activeProducts}</li>";
        echo "<li>Inactive Products: {$inactiveProducts}</li>";
        echo "<li>Products with NULL status: {$nullStatusProducts}</li>";
        echo "<li>Products with empty status: {$emptyStatusProducts}</li>";
        echo "</ul>";
        
        echo "<h2>All Products:</h2>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Name</th><th>SKU</th><th>Status</th><th>Stock</th><th>Price</th><th>Category</th></tr>";
        
        foreach ($products as $product) {
            $status = $product->status ?? 'NULL';
            $categoryName = $product->category ? $product->category->name : 'No Category';
            
            echo "<tr>";
            echo "<td>{$product->id}</td>";
            echo "<td>{$product->name}</td>";
            echo "<td>{$product->sku}</td>";
            echo "<td style='background: " . ($status === 'active' ? '#90EE90' : ($status === 'inactive' ? '#FFB6C1' : '#FFD700')) . "'>{$status}</td>";
            echo "<td>{$product->stock}</td>";
            echo "<td>KSh {$product->price}</td>";
            echo "<td>{$categoryName}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        // Test the specific queries used by the application
        echo "<h2>Query Tests:</h2>";
        
        // POS Query
        $posProducts = \App\Models\Product::with('category')->where('status', 'active')->get();
        echo "<p><strong>POS Query Result:</strong> " . $posProducts->count() . " products</p>";
        
        // Dashboard Query
        $dashboardTotal = \App\Models\Product::count();
        $dashboardActive = \App\Models\Product::where('status', 'active')->count();
        echo "<p><strong>Dashboard Total:</strong> {$dashboardTotal}</p>";
        echo "<p><strong>Dashboard Active:</strong> {$dashboardActive}</p>";
        
        // Admin Query
        $adminProducts = \App\Models\Product::with('category')->get();
        echo "<p><strong>Admin Query Result:</strong> " . $adminProducts->count() . " products</p>";
        
        return null;
        
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
})->middleware(['auth']);
?>