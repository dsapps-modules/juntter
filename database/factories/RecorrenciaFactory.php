<?php

namespace Database\Factories;

use App\Models\Recorrencia;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Recorrencia>
 */
class RecorrenciaFactory extends Factory
{
    protected $model = Recorrencia::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'estabelecimento_id' => (string) $this->faker->numberBetween(1000, 9999),
            'customer_name' => $this->faker->name(),
            'customer_email' => $this->faker->safeEmail(),
            'customer_phone' => $this->faker->numerify('(##) #####-####'),
            'customer_document' => $this->faker->numerify('###########'),
            'payment_type' => $this->faker->randomElement(['PIX', 'BOLETO', 'CARTAO']),
            'amount' => $this->faker->randomFloat(2, 25, 900),
            'amount_centavos' => $this->faker->numberBetween(2500, 90000),
            'frequency' => $this->faker->randomElement(['SEMANAL', 'QUINZENAL', 'MENSAL']),
            'charge_day' => $this->faker->numberBetween(1, 28),
            'start_date' => $this->faker->date(),
            'end_date' => null,
            'payment_link_url' => $this->faker->url(),
            'send_via_email' => true,
            'send_via_whatsapp' => false,
            'recipient_email' => $this->faker->safeEmail(),
            'email_subject' => 'Sua cobrança recorrente está pronta',
            'email_message' => $this->faker->sentence(),
            'whatsapp_number' => $this->faker->numerify('(##) #####-####'),
            'status' => 'PENDENTE',
            'metadata' => [],
        ];
    }
}
