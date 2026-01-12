<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run()
    {
        $categories = [
            [
                'name' => 'Gas Cylinders',
                'description' => 'Various sizes of propane and butane gas cylinders for domestic and commercial use',
                'status' => 'active'
            ],
            [
                'name' => 'Cylinder Accessories',
                'description' => 'Regulators, valves, hoses, and fittings for gas cylinders',
                'status' => 'active'
            ],
            [
                'name' => 'Safety Equipment',
                'description' => 'Safety gear including gloves, masks, goggles, and safety kits',
                'status' => 'active'
            ],
            [
                'name' => 'Refill Services',
                'description' => 'Gas refill services and maintenance packages',
                'status' => 'active'
            ],
            [
                'name' => 'Cooking Equipment',
                'description' => 'Gas stoves, burners, and cooking appliances',
                'status' => 'active'
            ],
            [
                'name' => 'Storage & Handling',
                'description' => 'Storage containers, carts, and handling equipment',
                'status' => 'active'
            ]
        ];

        foreach ($categories as $categoryData) {
            Category::firstOrCreate(
                ['name' => $categoryData['name']],
                $categoryData
            );
        }
    }
}
