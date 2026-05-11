<?php

namespace Tests\Feature;

use App\Models\LinkPagamento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PagamentoClienteControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_payment_page_uses_the_company_logo_when_available(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('company-logos/logo-publico.png', 'fake-image-content');

        $user = $this->makeVendorUser('155161', 'company-logos/logo-publico.png');
        $link = $this->makeLinkPagamento('155161');

        $response = $this->get(route('pagamento.link', $link->codigo_unico));

        $response->assertOk();
        $response->assertSee('/company-logo?path=company-logos%2Flogo-publico.png', false);
        $response->assertSee("onerror=\"this.onerror=null;this.src='/img/logo/juntter_webp_640_174.webp';\"", false);
    }

    public function test_public_payment_page_falls_back_to_default_logo_when_company_logo_is_missing(): void
    {
        $this->makeVendorUser('155161');
        $link = $this->makeLinkPagamento('155161');

        $response = $this->get(route('pagamento.link', $link->codigo_unico));

        $response->assertOk();
        $response->assertSee('/img/logo/juntter_webp_640_174.webp', false);
    }

    private function makeVendorUser(string $establishmentId, ?string $companyLogoPath = null): User
    {
        $user = User::factory()->create([
            'nivel_acesso' => 'vendedor',
            'email_verified_at' => now(),
            'company_logo_path' => $companyLogoPath,
        ]);

        $user->vendedor()->create([
            'estabelecimento_id' => $establishmentId,
            'sub_nivel' => 'admin_loja',
            'status' => 'ativo',
            'must_change_password' => false,
        ]);

        return $user;
    }

    private function makeLinkPagamento(string $establishmentId): LinkPagamento
    {
        return LinkPagamento::query()->create([
            'estabelecimento_id' => $establishmentId,
            'codigo_unico' => LinkPagamento::gerarCodigoUnico(),
            'descricao' => 'Link público de teste',
            'valor' => 10.00,
            'valor_centavos' => 1000,
            'parcelas' => [1],
            'juros' => 'CLIENT',
            'status' => 'ATIVO',
            'tipo_pagamento' => 'CARTAO',
        ]);
    }
}
