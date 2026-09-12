<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    public function definition()
    {
        return [
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'category_id' => Category::factory(),
            'brand' => fake()->company(),
            'cylinder_size_kg' => null,
            'sku' => strtoupper(Str::random(4)) . '-' . date('Y') . '-' . fake()->unique()->numerify('#####'),
            'serial_number' => 'PRD' . date('Ymd') . fake()->unique()->numerify('#####'),
            'price' => fake()->randomFloat(2, 100, 5000),
            'cost_price' => fake()->randomFloat(2, 50, 4000),
            'stock' => 100,
            'reserved_stock' => 0,
            'min_stock' => 5,
            'status' => 'active',
        ];
    }

    /**
     * A gas cylinder of a given size, e.g. ->cylinder(13).
     */
    public function cylinder(float $sizeKg = 13)
    {
        return $this->state(fn (array $attributes) => [
            'name' => rtrim(rtrim(number_format($sizeKg, 2, '.', ''), '0'), '.') . 'kg Gas Cylinder',
            'cylinder_size_kg' => $sizeKg,
        ]);
    }

    public function withStock(int $stock, int $reserved = 0)
    {
        return $this->state(fn (array $attributes) => [
            'stock' => $stock,
            'reserved_stock' => $reserved,
        ]);
    }

    public function outOfStock()
    {
        return $this->state(fn (array $attributes) => [
            'stock' => 0,
            'reserved_stock' => 0,
        ]);
    }
}
