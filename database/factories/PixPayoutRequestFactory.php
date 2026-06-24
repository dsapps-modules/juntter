<?php

namespace Database\Factories;

use App\Models\PixPayoutRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PixPayoutRequest>
 */
class PixPayoutRequestFactory extends Factory
{
    protected $model = PixPayoutRequest::class;

    public function definition(): array
    {
        return [
            'seller_id' => User::factory(),
            'establishment_id' => (string) $this->faker->randomNumber(5),
            'amount' => $this->faker->numberBetween(100, 500000),
            'pix_key_type' => $this->faker->randomElement(['CPF', 'CNPJ', 'EMAIL', 'PHONE', 'RANDOM', 'HASH']),
            'pix_key' => $this->faker->uuid(),
            'hash_code' => null,
            'description' => $this->faker->sentence(6),
            'status' => 'draft',
            'init_id' => null,
            'gateway_authorization' => null,
            'pin_hash' => null,
            'pin_attempts' => 0,
            'pin_expires_at' => null,
            'expires_at' => null,
            'confirmation_code_hash' => null,
            'confirmation_code_attempts' => 0,
            'confirmation_code_sent_at' => null,
            'confirmation_code_expires_at' => null,
            'confirmation_code_verified_at' => null,
            'init_payload' => [],
            'init_response' => [],
            'confirm_payload' => [],
            'confirm_response' => [],
            'webhook_payload' => [],
            'gateway_transaction_id' => null,
            'last_error' => null,
            'confirmed_at' => null,
        ];
    }
}
