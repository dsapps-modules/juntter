<?php

namespace Tests\Feature;

use App\Models\PaytimeEstablishment;
use App\Models\User;
use App\Services\EstabelecimentoService;
use App\Services\SplitPreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpaEstabelecimentoFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_establishment_detail_route_returns_form_data(): void
    {
        $user = User::factory()->create([
            'nivel_acesso' => 'admin',
            'email_verified_at' => now(),
        ]);

        $establishment = PaytimeEstablishment::create([
            'id' => 8001,
            'type' => 'COMPANY',
            'first_name' => 'Loja',
            'last_name' => 'Teste',
            'email' => 'loja@example.com',
            'phone_number' => '11999999999',
            'active' => true,
            'status' => 'APPROVED',
            'risk' => 'LOW',
            'revenue' => 1000.00,
            'address_json' => ['city' => 'São Paulo'],
        ]);

        $response = $this->actingAs($user)->getJson('/api/spa/estabelecimentos/'.$establishment->id);

        $response
            ->assertOk()
            ->assertJsonPath('establishment.email', 'loja@example.com')
            ->assertJsonPath('establishment.access_type', 'COMPANY');
    }

    public function test_establishment_update_can_return_json_from_the_spa(): void
    {
        $user = User::factory()->create([
            'nivel_acesso' => 'admin',
            'email_verified_at' => now(),
        ]);

        $this->mock(EstabelecimentoService::class, function ($mock) {
            $mock->shouldReceive('atualizarEstabelecimento')
                ->once()
                ->andReturn(['success' => true]);
        });

        $this->mock(SplitPreService::class, function ($mock) {
            $mock->shouldIgnoreMissing();
        });

        $response = $this->actingAs($user)->putJson('/estabelecimentos/9001', [
            'access_type' => 'ACQUIRER',
            'first_name' => 'Loja',
            'last_name' => 'Atualizada',
            'phone_number' => '11999999999',
            'revenue' => 2500,
            'format' => 'LTDA',
            'email' => 'loja@example.com',
            'gmv' => 5000,
            'birthdate' => now()->subYears(2)->format('Y-m-d'),
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('redirect', '/app/estabelecimentos');
    }
}
