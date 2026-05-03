<?php

namespace Database\Factories;

use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shop>
 */
class ShopFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shop_name' => fake()->company(),
            'email' => fake()->unique()->safeEmail(),
            'shop_mobile' => fake()->phoneNumber(),
            'shop_website' => fake()->optional()->url(),
            'shop_address' => fake()->optional()->address(),
        ];
    }
}
