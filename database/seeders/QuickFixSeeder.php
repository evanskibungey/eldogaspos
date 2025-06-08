<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;
use App\Models\Category;
use App\Models\Product;

class QuickFixSeeder extends Seeder
{
    public function run()
    {
        // Add default settings if they don't exist
        $settings = [
            ['key' => 'company_name', 'value' => 'EldoGas'],
            ['key' => 'company_phone', 'value' => '+254724556855'],
            ['key' => 'company_email', 'value' => 'info@eldogas.co.ke'],
            ['key' => 'company_address', 'value' => 'Eldoret, Kenya'],
            ['key' => 'currency_symbol', 'value' => 'KSh'],
            ['key' => 'tax_percentage', 'value' => '0'],
            ['key' => 'receipt_footer', 'value' => 'Thank you for your business!'],
        ];

        foreach ($settings as $setting) {
            Setting::firstOrCreate(
                ['key' => $setting['key']],
                ['value' => $setting['value']]
            );
        }

        // Create a sample category if none exist
        if (Category::count() === 0) {
            $category = Category::create([
                'name' => 'Gas Cylinders',
                'description' => 'Various gas cylinder sizes',
                'status' => 'active'
            ]);

            // Create sample products if none exist
            if (Product::count() === 0) {
            $products = [
            [
            'name' => 'REFILL 13KG GAS',
            'description' => '13kg gas cylinder refill',
            'category_id' => $category->id,
            'sku' => 'GAS-13KG-REFILL',
            'serial_number' => 'PRD2025060800001',
            'price' => 2100,
            'cost_price' => 1800,
                'stock' => 50,
                'min_stock' => 10,
            'status' => 'active'
            ],
            [
            'name' => 'REFILL 6KG GAS',
            'description' => '6kg gas cylinder refill',
            'category_id' => $category->id,
            'sku' => 'GAS-6KG-REFILL',
                'serial_number' => 'PRD2025060800002',
                'price' => 1200,
            'cost_price' => 1000,
            'stock' => 30,
            'min_stock' => 5,
            'status' => 'active'
            ],
            [
            'name' => 'EMPTY CYLINDER 13KG',
                'description' => '13kg empty gas cylinder',
                'category_id' => $category->id,
            'sku' => 'CYL-13KG-EMPTY',
            'serial_number' => 'PRD2025060800003',
            'price' => 5500,
            'cost_price' => 4500,
            'stock' => 10,
            'min_stock' => 2,
            'status' => 'active'
            ],
                [
                    'name' => 'EMPTY CYLINDER 6KG',
                    'description' => '6kg empty gas cylinder',
                'category_id' => $category->id,
                    'sku' => 'CYL-6KG-EMPTY',
                        'serial_number' => 'PRD2025060800004',
                    'price' => 3500,
                    'cost_price' => 2800,
                    'stock' => 15,
                    'min_stock' => 3,
                    'status' => 'active'
                ]
            ];

            foreach ($products as $product) {
                Product::create($product);
            }
            
            echo "Created " . count($products) . " sample products.\n";
        }
        
        // Fix any existing products with invalid status
        $fixedCount = 0;
        
        // Fix NULL status
        $nullCount = Product::whereNull('status')->count();
        if ($nullCount > 0) {
            Product::whereNull('status')->update(['status' => 'active']);
            $fixedCount += $nullCount;
            echo "Fixed {$nullCount} products with NULL status.\n";
        }
        
        // Fix empty status
        $emptyCount = Product::where('status', '')->count();
        if ($emptyCount > 0) {
            Product::where('status', '')->update(['status' => 'active']);
            $fixedCount += $emptyCount;
            echo "Fixed {$emptyCount} products with empty status.\n";
        }
        
        if ($fixedCount > 0) {
            echo "Total products fixed: {$fixedCount}\n";
        }
        }

        echo "Quick fix seeder completed successfully!\n";
    }
}
