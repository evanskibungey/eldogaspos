<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Category;
use App\Models\StockMovement;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run()
    {
        // Get categories (ensure they exist)
        $gasCylinders = Category::where('name', 'Gas Cylinders')->first();
        $accessories = Category::where('name', 'Cylinder Accessories')->first();
        $safety = Category::where('name', 'Safety Equipment')->first();
        $refills = Category::where('name', 'Refill Services')->first();
        $cooking = Category::where('name', 'Cooking Equipment')->first();
        $storage = Category::where('name', 'Storage & Handling')->first();

        $products = [
            // Gas Cylinders (4 products)
            [
                'name' => '12kg Propane Cylinder',
                'description' => 'Standard 12kg propane cylinder for residential use. Perfect for heating and cooking.',
                'category_id' => $gasCylinders->id,
                'brand' => 'EldoGas Premium',
                'price' => 3500.00,
                'cost_price' => 2800.00,
                'stock' => 50,
                'min_stock' => 10,
                'status' => 'active'
            ],
            [
                'name' => '5kg Propane Cylinder',
                'description' => 'Compact 5kg propane cylinder ideal for small households and portable use.',
                'category_id' => $gasCylinders->id,
                'brand' => 'EldoGas Premium',
                'price' => 1800.00,
                'cost_price' => 1400.00,
                'stock' => 80,
                'min_stock' => 15,
                'status' => 'active'
            ],
            [
                'name' => '25kg Commercial Cylinder',
                'description' => 'Heavy-duty 25kg cylinder for commercial and industrial applications.',
                'category_id' => $gasCylinders->id,
                'brand' => 'EldoGas Industrial',
                'price' => 6500.00,
                'cost_price' => 5200.00,
                'stock' => 30,
                'min_stock' => 8,
                'status' => 'active'
            ],
            [
                'name' => '3kg Portable Cylinder',
                'description' => 'Lightweight 3kg portable gas cylinder for camping and outdoor activities.',
                'category_id' => $gasCylinders->id,
                'brand' => 'EldoGas Portable',
                'price' => 1200.00,
                'cost_price' => 900.00,
                'stock' => 100,
                'min_stock' => 20,
                'status' => 'active'
            ],

            // Cylinder Accessories (4 products)
            [
                'name' => 'Pressure Regulator (Low Pressure)',
                'description' => 'Standard low-pressure regulator with safety valve. Compatible with most cylinders.',
                'category_id' => $accessories->id,
                'brand' => 'SafeGas',
                'price' => 450.00,
                'cost_price' => 300.00,
                'stock' => 120,
                'min_stock' => 20,
                'status' => 'active'
            ],
            [
                'name' => 'Gas Hose (3 meter)',
                'description' => '3-meter reinforced gas hose with connectors. Food-grade silicone.',
                'category_id' => $accessories->id,
                'brand' => 'FlexiGas',
                'price' => 280.00,
                'cost_price' => 180.00,
                'stock' => 150,
                'min_stock' => 25,
                'status' => 'active'
            ],
            [
                'name' => 'Cylinder Valve (Quick Connect)',
                'description' => 'Quick-connect valve with anti-leak mechanism. Brass construction.',
                'category_id' => $accessories->id,
                'brand' => 'ProValve',
                'price' => 650.00,
                'cost_price' => 450.00,
                'stock' => 60,
                'min_stock' => 12,
                'status' => 'active'
            ],
            [
                'name' => 'Cylinder Adapter Kit',
                'description' => 'Universal adapter kit for connecting different cylinder sizes to equipment.',
                'category_id' => $accessories->id,
                'brand' => 'UniversalFit',
                'price' => 320.00,
                'cost_price' => 200.00,
                'stock' => 90,
                'min_stock' => 15,
                'status' => 'active'
            ],

            // Safety Equipment (3 products)
            [
                'name' => 'Safety Gloves (Leather)',
                'description' => 'Heat-resistant leather gloves for safe gas cylinder handling. One size fits all.',
                'category_id' => $safety->id,
                'brand' => 'SafeHands',
                'price' => 180.00,
                'cost_price' => 120.00,
                'stock' => 200,
                'min_stock' => 30,
                'status' => 'active'
            ],
            [
                'name' => 'Gas Leak Detector',
                'description' => 'Portable gas leak detector with alarm. Battery-powered and highly sensitive.',
                'category_id' => $safety->id,
                'brand' => 'DetectSafe',
                'price' => 850.00,
                'cost_price' => 600.00,
                'stock' => 25,
                'min_stock' => 5,
                'status' => 'active'
            ],
            [
                'name' => 'Fire Extinguisher (2kg)',
                'description' => 'Dry powder fire extinguisher suitable for gas-related fires.',
                'category_id' => $safety->id,
                'brand' => 'FireSafe',
                'price' => 1200.00,
                'cost_price' => 850.00,
                'stock' => 40,
                'min_stock' => 8,
                'status' => 'active'
            ],

            // Cooking Equipment (2 products)
            [
                'name' => 'Single Burner Gas Stove',
                'description' => 'Portable single burner gas stove with stable base. Easy to use and clean.',
                'category_id' => $cooking->id,
                'brand' => 'CookMaster',
                'price' => 1500.00,
                'cost_price' => 1100.00,
                'stock' => 35,
                'min_stock' => 7,
                'status' => 'active'
            ],
            [
                'name' => 'Double Burner Gas Cooktop',
                'description' => 'Heavy-duty double burner gas cooktop for commercial and home kitchens.',
                'category_id' => $cooking->id,
                'brand' => 'CookMaster Pro',
                'price' => 3200.00,
                'cost_price' => 2400.00,
                'stock' => 20,
                'min_stock' => 5,
                'status' => 'active'
            ],

            // Storage & Handling (2 products)
            [
                'name' => 'Cylinder Storage Cart',
                'description' => 'Metal cart with safety straps for transporting and storing gas cylinders safely.',
                'category_id' => $storage->id,
                'brand' => 'StorePro',
                'price' => 2800.00,
                'cost_price' => 2000.00,
                'stock' => 15,
                'min_stock' => 3,
                'status' => 'active'
            ],
            [
                'name' => 'Cylinder Stand (Metal)',
                'description' => 'Sturdy metal stand for safe storage of gas cylinders in upright position.',
                'category_id' => $storage->id,
                'brand' => 'StorePro',
                'price' => 890.00,
                'cost_price' => 650.00,
                'stock' => 45,
                'min_stock' => 10,
                'status' => 'active'
            ]
        ];

        // Create products and their initial stock movements
        foreach ($products as $productData) {
            // Generate SKU if not exists
            if (!isset($productData['sku'])) {
                $productData['sku'] = $this->generateSKU($productData['category_id']);
            }

            // Generate serial number if not exists
            if (!isset($productData['serial_number'])) {
                $productData['serial_number'] = $this->generateSerialNumber();
            }

            $product = Product::firstOrCreate(
                ['name' => $productData['name']],
                $productData
            );

            // Create initial stock movement if this is a new product with stock
            if ($product->wasRecentlyCreated && $productData['stock'] > 0) {
                StockMovement::create([
                    'product_id' => $product->id,
                    'type' => 'in',
                    'quantity' => $productData['stock'],
                    'unit_price' => $productData['cost_price'],
                    'reference_type' => 'initial',
                    'notes' => 'Initial stock',
                    'created_by' => 1  // Admin user
                ]);
            }
        }
    }

    /**
     * Generate a unique SKU for a product based on category.
     * Format: XXXX-YYYY-00001 (4-letter category prefix + year + 5-digit sequence)
     */
    protected function generateSKU($categoryId)
    {
        $category = Category::find($categoryId);
        if (!$category) {
            return 'UNKN-' . date('Y') . '-00001';
        }

        // Get category prefix (first 4 letters uppercase)
        $prefix = Str::upper(Str::substr($category->name, 0, 4));
        $year = date('Y');

        // Get last product with this category prefix
        $lastProduct = Product::where('sku', 'like', $prefix . '-' . $year . '-%')
            ->orderBy('sku', 'desc')
            ->first();

        if ($lastProduct) {
            // Extract the number and increment
            $lastNumber = (int) substr($lastProduct->sku, -5);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        // Format: XXXX-YYYY-00001
        return sprintf('%s-%s-%05d', $prefix, $year, $newNumber);
    }

    /**
     * Generate a unique serial number for a product.
     * Format: PRDYYYYMMDD00001 (PRD + date + 5-digit daily sequence)
     */
    protected function generateSerialNumber()
    {
        $prefix = 'PRD';
        $date = date('Ymd');

        // Get last product created today
        $lastProduct = Product::where('serial_number', 'like', $prefix . $date . '%')
            ->orderBy('serial_number', 'desc')
            ->first();

        if ($lastProduct) {
            // Extract the number and increment
            $lastNumber = (int) substr($lastProduct->serial_number, -5);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        // Format: PRDYYYYMMDD00001
        return sprintf('%s%s%05d', $prefix, $date, $newNumber);
    }
}
