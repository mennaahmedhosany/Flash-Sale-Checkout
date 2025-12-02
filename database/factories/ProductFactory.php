<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Product;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(10),
            'price' => $this->faker->randomFloat(2, 100, 10000), // between 100.00 and 10,000.00
            'stock_available' => 5,
            'stock_reserved' => 0,
            'version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
