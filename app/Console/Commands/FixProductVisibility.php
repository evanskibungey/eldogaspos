<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class FixProductVisibility extends Command
{
    protected $signature = 'pos:fix-products';
    protected $description = 'Fix product visibility issues in POS dashboard';

    public function handle()
    {
        $this->info('🔧 EldoGas POS Product Visibility Fix');
        $this->newLine();

        try {
            // Step 1: Diagnose the issue
            $this->info('📊 Diagnosing current state...');
            
            $totalProducts = Product::count();
            $activeProducts = Product::where('status', 'active')->count();
            $categories = Category::withCount('products')->get();
            
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Total Products (Direct)', $totalProducts],
                    ['Active Products', $activeProducts],
                    ['Categories', $categories->count()],
                ]
            );
            
            // Show category breakdown
            if ($categories->count() > 0) {
                $this->info('📂 Category breakdown:');
                foreach ($categories as $category) {
                    $this->line("  - {$category->name}: {$category->products_count} products");
                }
                $this->newLine();
            }
            
            // Check for issues
            $issues = [];
            
            // Check for products with NULL status
            $nullStatus = Product::whereNull('status')->count();
            if ($nullStatus > 0) {
                $issues[] = "{$nullStatus} products with NULL status";
            }
            
            // Check for products with empty status
            $emptyStatus = Product::where('status', '')->count();
            if ($emptyStatus > 0) {
                $issues[] = "{$emptyStatus} products with empty status";
            }
            
            // Check for orphaned products
            $orphaned = Product::whereNotExists(function($query) {
                $query->select(DB::raw(1))
                      ->from('categories')
                      ->whereRaw('categories.id = products.category_id');
            })->count();
            if ($orphaned > 0) {
                $issues[] = "{$orphaned} orphaned products (category doesn't exist)";
            }
            
            if (count($issues) > 0) {
                $this->warn('🚨 Issues found:');
                foreach ($issues as $issue) {
                    $this->line("  - {$issue}");
                }
                $this->newLine();
                
                if ($this->confirm('Would you like to fix these issues?')) {
                    $this->fixIssues();
                }
            } else if ($totalProducts == 0) {
                $this->warn('📦 No products found in database.');
                if ($this->confirm('Would you like to create sample products?')) {
                    $this->createSampleProducts();
                }
            } else {
                $this->info('✅ No obvious issues found with product data.');
                $this->testQueries();
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
    
    private function fixIssues()
    {
        $this->info('🔧 Fixing issues...');
        
        // Fix NULL status
        $nullStatusCount = Product::whereNull('status')->count();
        if ($nullStatusCount > 0) {
            Product::whereNull('status')->update(['status' => 'active']);
            $this->line("  ✓ Fixed {$nullStatusCount} products with NULL status");
        }
        
        // Fix empty status
        $emptyStatusCount = Product::where('status', '')->count();
        if ($emptyStatusCount > 0) {
            Product::where('status', '')->update(['status' => 'active']);
            $this->line("  ✓ Fixed {$emptyStatusCount} products with empty status");
        }
        
        // Fix orphaned products
        $orphanedCount = Product::whereNotExists(function($query) {
            $query->select(DB::raw(1))
                  ->from('categories')
                  ->whereRaw('categories.id = products.category_id');
        })->count();
        
        if ($orphanedCount > 0) {
            $category = Category::where('status', 'active')->first();
            if (!$category) {
                $category = Category::create([
                    'name' => 'Gas Refill',
                    'description' => 'Gas cylinder refills',
                    'status' => 'active'
                ]);
                $this->line("  ✓ Created category: {$category->name}");
            }
            
            Product::whereNotExists(function($query) {
                $query->select(DB::raw(1))
                      ->from('categories')
                      ->whereRaw('categories.id = products.category_id');
            })->update(['category_id' => $category->id]);
            
            $this->line("  ✓ Fixed {$orphanedCount} orphaned products");
        }
        
        $this->newLine();
        $this->testQueries();
    }
    
    private function createSampleProducts()
    {
        $this->info('📦 Creating sample products...');
        
        // Ensure we have a category
        $category = Category::where('status', 'active')->first();
        if (!$category) {
            $category = Category::create([
                'name' => 'Gas Refill',
                'description' => 'Gas cylinder refills and accessories',
                'status' => 'active'
            ]);
            $this->line("  ✓ Created category: {$category->name}");
        }
        
        $sampleProducts = [
            [
                'name' => 'Refill 6kg',
                'description' => '6kg gas cylinder refill',
                'category_id' => $category->id,
                'sku' => 'GAS-6KG-' . date('Ymd'),
                'serial_number' => 'PRD' . date('Ymd') . '001',
                'price' => 1000.00,
                'cost_price' => 800.00,
                'stock' => 100,
                'min_stock' => 10,
                'status' => 'active'
            ],
            [
                'name' => 'Refill 13kg',
                'description' => '13kg gas cylinder refill',
                'category_id' => $category->id,
                'sku' => 'GAS-13KG-' . date('Ymd'),
                'serial_number' => 'PRD' . date('Ymd') . '002',
                'price' => 2100.00,
                'cost_price' => 1800.00,
                'stock' => 50,
                'min_stock' => 5,
                'status' => 'active'
            ]
        ];
        
        foreach ($sampleProducts as $productData) {
            Product::create($productData);
            $this->line("  ✓ Created: {$productData['name']}");
        }
        
        $this->newLine();
        $this->testQueries();
    }
    
    private function testQueries()
    {
        $this->info('🧪 Testing application queries...');
        
        // Dashboard query
        $dashboardTotal = Product::count();
        $dashboardActive = Product::where('status', 'active')->count();
        
        // POS query
        $posProducts = Product::with('category')->where('status', 'active')->get();
        
        $this->table(
            ['Query Type', 'Result'],
            [
                ['Dashboard Total', $dashboardTotal],
                ['Dashboard Active', $dashboardActive],
                ['POS Products', $posProducts->count()],
            ]
        );
        
        if ($posProducts->count() > 0) {
            $this->info('✅ SUCCESS! Products should now appear in POS and Dashboard.');
            $this->newLine();
            $this->info('📋 Products available in POS:');
            foreach ($posProducts as $product) {
                $this->line("  - {$product->name} (KSh {$product->price}, Stock: {$product->stock})");
            }
        } else {
            $this->error('❌ Products still not found by POS query!');
            $this->warn('Please check Laravel logs for more details.');
        }
        
        $this->newLine();
        $this->info('🔄 Please refresh your browser to see changes.');
    }
}