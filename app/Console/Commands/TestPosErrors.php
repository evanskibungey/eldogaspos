<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Setting;

class TestPosErrors extends Command
{
    protected $signature = 'pos:test-errors {type?}';
    protected $description = 'Create test scenarios for POS error handling';

    public function handle()
    {
        $type = $this->argument('type');

        if (!$type) {
            $this->info('Available test scenarios:');
            $this->line('1. stock     - Create products with various stock levels');
            $this->line('2. validate  - Test validation scenarios');
            $this->line('3. network   - Show network error test commands');
            $this->line('4. clean     - Remove test data');
            $this->line('');
            $this->line('Usage: php artisan pos:test-errors {type}');
            return;
        }

        switch ($type) {
            case 'stock':
                $this->createStockTestData();
                break;
            case 'validate':
                $this->showValidationTests();
                break;
            case 'network':
                $this->showNetworkTests();
                break;
            case 'clean':
                $this->cleanTestData();
                break;
            default:
                $this->error('Unknown test type: ' . $type);
        }
    }

    private function createStockTestData()
    {
        $this->info('Creating stock test products...');

        // Ensure we have a category
        $category = Category::firstOrCreate(
            ['name' => 'Test Products'],
            ['description' => 'Products for testing', 'status' => 'active']
        );

        // Create test products
        $products = [
            [
                'name' => 'TEST - Out of Stock',
                'sku' => 'TEST-OOS',
                'price' => 100,
                'stock' => 0,
                'min_stock' => 5,
                'description' => 'This product is out of stock'
            ],
            [
                'name' => 'TEST - Low Stock (2 left)',
                'sku' => 'TEST-LOW',
                'price' => 200,
                'stock' => 2,
                'min_stock' => 10,
                'description' => 'Only 2 items left in stock'
            ],
            [
                'name' => 'TEST - Single Item',
                'sku' => 'TEST-ONE',
                'price' => 150,
                'stock' => 1,
                'min_stock' => 5,
                'description' => 'Only 1 item left'
            ],
            [
                'name' => 'TEST - Normal Stock',
                'sku' => 'TEST-NORMAL',
                'price' => 300,
                'stock' => 50,
                'min_stock' => 10,
                'description' => 'Normal stock levels'
            ],
            [
                'name' => 'TEST - No Price',
                'sku' => 'TEST-NOPRICE',
                'price' => 0,
                'stock' => 10,
                'min_stock' => 5,
                'description' => 'Product with no price set'
            ]
        ];

        foreach ($products as $productData) {
            $product = Product::updateOrCreate(
                ['sku' => $productData['sku']],
                array_merge($productData, [
                    'category_id' => $category->id,
                    'status' => 'active'
                ])
            );
            
            $this->line("✓ Created: {$product->name} (Stock: {$product->stock})");
        }

        $this->info('');
        $this->info('Test products created! Try these scenarios in POS:');
        $this->line('1. Add "TEST - Out of Stock" → Should show "This product is out of stock"');
        $this->line('2. Add "TEST - Low Stock" 3 times → Should show "Cannot add more. Only 2 available"');
        $this->line('3. Add "TEST - Single Item" 2 times → Should show "Cannot add more. Only 1 available"');
        $this->line('4. Add "TEST - No Price" → Check if it handles zero price correctly');
    }

    private function showValidationTests()
    {
        $this->info('Validation Test Scenarios:');
        $this->line('');
        
        $this->line('1. Credit Sale without Customer Details:');
        $this->line('   - Select Credit payment');
        $this->line('   - Leave name and phone empty');
        $this->line('   - Click Complete Sale');
        $this->line('   → Should show validation error');
        $this->line('');
        
        $this->line('2. Empty Cart Sale:');
        $this->line('   - Don\'t add any products');
        $this->line('   - Click Complete Sale');
        $this->line('   → Should show "Cart is empty" error');
        $this->line('');
        
        $this->line('3. Invalid Phone Number:');
        $this->line('   - Select Credit payment');
        $this->line('   - Enter name but invalid phone (e.g., "abc")');
        $this->line('   → Should show phone validation error');
        $this->line('');
        
        $this->line('4. Test in Browser Console:');
        $this->comment('fetch("/pos/sales", {
    method: "POST",
    headers: {
        "Content-Type": "application/json",
        "X-CSRF-TOKEN": document.querySelector(\'meta[name="csrf-token"]\').content
    },
    body: JSON.stringify({
        cart_items: [],
        payment_method: "credit",
        customer_details: {name: "", phone: ""}
    })
}).then(r => r.json()).then(console.log);');
    }

    private function showNetworkTests()
    {
        $this->info('Network Error Test Scenarios:');
        $this->line('');
        
        $this->line('1. Database Connection Error:');
        $this->line('   - Stop MySQL in XAMPP Control Panel');
        $this->line('   - Try to complete a sale');
        $this->line('   → Should show "Network error or server exception occurred"');
        $this->line('');
        
        $this->line('2. Server Timeout:');
        $this->line('   - Add sleep(35) to PosController@store method');
        $this->line('   - Try to complete a sale');
        $this->line('   → Should timeout after 30 seconds');
        $this->line('');
        
        $this->line('3. CSRF Token Expired:');
        $this->line('   - Open POS dashboard');
        $this->line('   - Wait 2+ hours (or modify session lifetime)');
        $this->line('   - Try to complete a sale');
        $this->line('   → Should show session expired error');
        $this->line('');
        
        $this->line('4. Invalid Route:');
        $this->comment('// Test in console:
fetch("/pos/invalid-route", {
    method: "POST",
    headers: {"X-CSRF-TOKEN": document.querySelector(\'meta[name="csrf-token"]\').content}
}).then(r => console.log("Status:", r.status));');
    }

    private function cleanTestData()
    {
        $this->info('Cleaning test data...');
        
        $deleted = Product::where('sku', 'LIKE', 'TEST-%')->delete();
        $this->line("✓ Deleted $deleted test products");
        
        $category = Category::where('name', 'Test Products')->first();
        if ($category && $category->products()->count() === 0) {
            $category->delete();
            $this->line('✓ Deleted empty test category');
        }
        
        $this->info('Test data cleaned!');
    }
}
