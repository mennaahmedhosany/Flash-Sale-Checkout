<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Order;
use App\Models\Product;
use App\Models\Hold;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        // Make sure a product exists
        $product = Product::inRandomOrder()->first() ?? Product::factory()->create();
        $hold = Hold::factory()->create(['product_id' => $product->id]);

        return [
            'product_id' => $product->id,
            'hold_id' => $hold->id,
            'payment_idempotency_key' => $this->faker->unique()->uuid,
            'status' => $this->faker->randomElement(['pending_payment', 'paid', 'cancelled', 'failed']),
            'qty' => $hold->quantity,
            'amount_cents' => $product->price * $hold->quantity * 100, // store in cents
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
