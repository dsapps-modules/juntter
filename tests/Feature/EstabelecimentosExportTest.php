<?php

namespace Tests\Feature;

use App\Models\PaytimeEstablishment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EstabelecimentosExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_download_establishments_excel_file(): void
    {
        $user = User::factory()->create([
            'nivel_acesso' => 'admin',
            'email_verified_at' => now(),
        ]);

        PaytimeEstablishment::query()->create([
            'id' => 1001,
            'fantasy_name' => 'Loja Centro com Nome Maior',
            'document' => '12345678000199',
            'email' => 'centro@example.com',
            'phone_number' => '11999999999',
            'active' => true,
            'address_json' => [
                'city' => 'Sao Paulo',
                'state' => 'SP',
            ],
        ]);

        PaytimeEstablishment::query()->create([
            'id' => 1002,
            'first_name' => 'Maria',
            'last_name' => 'Silva',
            'document' => '98765432100',
            'email' => 'maria@example.com',
            'phone_number' => '21999999999',
            'active' => false,
            'address_json' => [
                'city' => 'Rio de Janeiro',
                'state' => 'RJ',
            ],
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('estabelecimentos.export'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.ms-excel; charset=UTF-8');
        $response->assertHeader(
            'content-disposition',
            'attachment; filename="estabelecimentos-'.now()->format('Y-m-d').'.xls"'
        );
        $response->assertSee('Loja Centro com Nome Maior', false);
        $response->assertSee('Maria Silva', false);
        $response->assertSee('12345678000199', false);
        $response->assertSee('98765432100', false);
        $response->assertSee('mso-number-format: "\\@"', false);
        $response->assertSee('<col width="26">', false);
    }
}
