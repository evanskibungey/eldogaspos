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
                        'category_id' => $category->id,
                        'sku' => 'GAS-13KG-REFILL',
                        'price' => 2100,
                        'stock' => 50,
                        'min_stock' => 10,
                        'status' => 'active'
                    ],
                    [
                        'name' => 'REFILL 6KG GAS',
                        'category_id' => $category->id,
                        'sku' => 'GAS-6KG-REFILL',
                        'price' => 1200,
                        'stock' => 30,
                        'min_stock' => 5,
                        'status' => 'active'
                    ],
                    [
                        'name' => 'EMPTY CYLINDER 13KG',
                        'category_id' => $category->id,
                        'sku' => 'CYL-13KG-EMPTY',
                        'price' => 5500,
                        'stock' => 10,
                        'min_stock' => 2,
                        'status' => 'active'
                    ],
                    [
                        'name' => 'EMPTY CYLINDER 6KG',
                        'category_id' => $category->id,
                        'sku' => 'CYL-6KG-EMPTY',
                        'price' => 3500,
                        'stock' => 15,
                        'min_stock' => 3,
                        'status' => 'active'
                    ]
                ];

                foreach ($products as $product) {
                    Product::create($product);
                }
            }
        }

        echo "Quick fix seeder completed successfully!\n";
    }
}
