<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CheckoutShippingOption>
 */
class CheckoutShippingOptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'seller_id' => User::factory(),
            'name' => fake()->randomElement(['Frete padrão', 'Expresso', 'Motoboy']),
            'price' => fake()->randomFloat(2, 0, 39.9),
            'eta_days' => fake()->numberBetween(1, 10),
            'is_default' => false,
            'is_active' => true,
        ];
    }
}
