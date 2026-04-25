<?php

namespace Tests\Feature;

use App\Models\PaytimeEstablishment;
use App\Models\PaytimeTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SpaVendedorAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendedor_access_overview_returns_rows(): void
    {
        $vendor = User::factory()->create([
            'name' => 'João Vendedor',
            'email' => 'joao@example.com',
            'nivel_acesso' => 'vendedor',
            'email_verified_at' => now(),
        ]);

        $vendor->vendedor()->create([
            'estabelecimento_id' => '9001',
            'sub_nivel' => 'admin_loja',
            'status' => 'ativo',
            'comissao' => 4.5,
            'meta_vendas' => 10000,
            'must_change_password' => true,
        ]);

        PaytimeEstablishment::create([
            'id' => 9001,
            'fantasy_name' => 'Loja Teste',
            'email' => 'loja@example.com',
            'active' => true,
            'status' => 'APPROVED',
            'revenue' => 4520.20,
        ]);

        PaytimeTransaction::create([
            'external_id' => 'trx-v1',
            'establishment_id' => '9001',
            'type' => 'PIX',
            'status' => 'PAID',
            'amount' => 20000,
            'original_amount' => 20000,
            'fees' => 0,
        ]);

        $response = $this->actingAs($vendor)->getJson('/api/spa/vendedores/acesso');

        $response
            ->assertOk()
            ->assertJsonPath('summary.total_vendors', 1)
            ->assertJsonPath('summary.active_vendors', 1)
            ->assertJsonPath('rows.0.name', 'João Vendedor')
            ->assertJsonPath('rows.0.establishment', 'Loja Teste');
    }

    public function test_vendedor_access_crud_flow_works_with_json_requests(): void
    {
        $admin = User::factory()->create([
            'nivel_acesso' => 'admin',
            'email_verified_at' => now(),
        ]);

        PaytimeEstablishment::create([
            'id' => 9101,
            'fantasy_name' => 'Loja Admin',
            'email' => 'admin@example.com',
            'active' => true,
            'status' => 'APPROVED',
            'revenue' => 1000.00,
        ]);

        $this->actingAs($admin);

        $createResponse = $this->postJson('/vendedores/acesso', [
            'establishment_id' => '9101',
            'name' => 'Maria Vendedora',
            'email' => 'maria@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('message', 'Acesso criado com sucesso.');

        $createdUser = User::query()->where('email', 'maria@example.com')->firstOrFail();

        $this->assertDatabaseHas('vendedores', [
            'user_id' => $createdUser->id,
            'estabelecimento_id' => '9101',
        ]);

        $updateResponse = $this->putJson("/vendedores/acesso/{$createdUser->id}", [
            'name' => 'Maria Atualizada',
            'email' => 'maria.atualizada@example.com',
        ]);

        $updateResponse
            ->assertOk()
            ->assertJsonPath('message', 'Dados do vendedor atualizados com sucesso.');

        $this->assertDatabaseHas('users', [
            'id' => $createdUser->id,
            'name' => 'Maria Atualizada',
            'email' => 'maria.atualizada@example.com',
        ]);

        $passwordResponse = $this->patchJson("/vendedores/acesso/{$createdUser->id}/senha", [
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $passwordResponse
            ->assertOk()
            ->assertJsonPath('message', 'Senha atualizada com sucesso.');

        $this->assertTrue(Hash::check('newpassword123', User::query()->findOrFail($createdUser->id)->password));

        $deleteResponse = $this->deleteJson("/vendedores/acesso/{$createdUser->id}");

        $deleteResponse
            ->assertOk()
            ->assertJsonPath('message', 'Acesso removido com sucesso.');

        $this->assertDatabaseMissing('users', [
            'id' => $createdUser->id,
        ]);
    }
}
