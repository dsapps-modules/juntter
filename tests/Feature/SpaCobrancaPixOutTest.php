<?php

namespace Tests\Feature;

use App\Mail\SecurityCodeMail;
use App\Models\PixPayoutRequest;
use App\Models\User;
use App\Services\BalanceService;
use App\Services\PixPayoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SpaCobrancaPixOutTest extends TestCase
{
    use RefreshDatabase;

    public function test_pix_out_overview_returns_balance_fee_and_available_after_fee(): void
    {
        $user = $this->createVendor();

        $balanceService = $this->createMock(BalanceService::class);
        $balanceService->expects($this->once())
            ->method('saldoAtual')
            ->with($this->callback(function (array $filters): bool {
                return ($filters['extra_headers']['establishment_id'] ?? null) === '5001';
            }))
            ->willReturn([
                'balance' => 25000,
                'blocked_balance' => 500,
                'total_balance' => 25500,
            ]);

        $this->app->instance(BalanceService::class, $balanceService);

        $response = $this->actingAs($user)->getJson('/api/spa/cobranca/pix-out');

        $response
            ->assertOk()
            ->assertJsonPath('balance.available_label', 'R$ 250,00')
            ->assertJsonPath('balance.blocked_label', 'R$ 5,00')
            ->assertJsonPath('fee.label', 'R$ 0,29')
            ->assertJsonPath('available_after_fee.label', 'R$ 249,71')
            ->assertJsonPath('electronic_signature.configured', false);
    }

    public function test_pix_out_overview_exposes_the_requested_pix_key_types_in_order(): void
    {
        $user = $this->createVendor();

        $this->mock(BalanceService::class, function ($mock): void {
            $mock->shouldReceive('saldoAtual')
                ->once()
                ->andReturn([
                    'balance' => 50000,
                    'blocked_balance' => 0,
                    'total_balance' => 50000,
                ]);
        });

        $response = $this->actingAs($user)->getJson('/api/spa/cobranca/pix-out');

        $response
            ->assertOk()
            ->assertJsonPath('pix_key_types.0.value', 'PHONE')
            ->assertJsonPath('pix_key_types.0.label', 'Celular')
            ->assertJsonPath('pix_key_types.1.value', 'CPF')
            ->assertJsonPath('pix_key_types.1.label', 'CPF')
            ->assertJsonPath('pix_key_types.2.value', 'EMAIL')
            ->assertJsonPath('pix_key_types.2.label', 'E-mail')
            ->assertJsonPath('pix_key_types.3.value', 'CNPJ')
            ->assertJsonPath('pix_key_types.3.label', 'CNPJ');
    }

    public function test_signature_code_flow_saves_new_signature_after_email_confirmation(): void
    {
        Mail::fake();

        $user = $this->createVendor();
        $code = null;

        $response = $this->actingAs($user)->postJson('/api/spa/cobranca/pix-out/assinatura-eletronica', [
            'electronic_signature' => 'nova-senha',
            'electronic_signature_confirmation' => 'nova-senha',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('requires_code', true);

        Mail::assertSent(SecurityCodeMail::class, function (SecurityCodeMail $mail) use (&$code): bool {
            $code = $mail->code;

            return $mail->purpose === 'assinatura eletrônica';
        });

        $this->assertNotNull($code);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
        ]);

        $this->assertTrue(Hash::check('nova-senha', (string) $user->fresh()->electronic_signature_pending_hash));

        $confirmResponse = $this->actingAs($user)->postJson('/api/spa/cobranca/pix-out/assinatura-eletronica/confirmar', [
            'verification_code' => $code,
        ]);

        $confirmResponse
            ->assertOk()
            ->assertJsonPath('electronic_signature.configured', true);

        $this->assertTrue(Hash::check('nova-senha', (string) $user->fresh()->electronic_signature_hash));
    }

    public function test_pix_out_store_initiates_transaction_and_sends_confirmation_code(): void
    {
        Mail::fake();

        $user = $this->createVendor();
        $user->forceFill([
            'electronic_signature_hash' => Hash::make('assinatura-secreta'),
            'electronic_signature_verified_at' => now(),
        ])->save();

        $balanceService = $this->createMock(BalanceService::class);
        $balanceService->expects($this->once())
            ->method('saldoAtual')
            ->willReturn([
                'balance' => 50000,
                'blocked_balance' => 0,
                'total_balance' => 50000,
            ]);

        $pixPayoutService = $this->createMock(PixPayoutService::class);
        $pixPayoutService->expects($this->once())
            ->method('initiate')
            ->with($this->callback(function (array $payload): bool {
                return ($payload['type'] ?? null) === 'PHONE'
                    && ($payload['key'] ?? null) === '11999998888'
                    && ! isset($payload['amount']);
            }))
            ->willReturn([
                'status' => 'PROCESSING',
                'gateway_authorization' => 'CELCOIN',
                'expected_at' => '2026-06-24T12:00:00.000Z',
                'init_id' => 'E13935893202604281747Y3pL5YlGFRs',
                '_id' => '69f0f2cf71ad0804c940575a',
            ]);

        $this->app->instance(BalanceService::class, $balanceService);
        $this->app->instance(PixPayoutService::class, $pixPayoutService);

        $response = $this->actingAs($user)->postJson('/api/spa/cobranca/pix-out', [
            'amount' => 'R$ 100,00',
            'pix_key_type' => 'PHONE',
            'pix_key' => '11999998888',
            'description' => 'Retirada semanal',
            'electronic_signature' => 'assinatura-secreta',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('payout_request.status', 'awaiting_confirmation')
            ->assertJsonPath('payout_request.init_id', 'E13935893202604281747Y3pL5YlGFRs')
            ->assertJsonPath('review.amount_label', 'R$ 100,00')
            ->assertJsonPath('review.fee_label', 'R$ 0,29');

        Mail::assertSent(SecurityCodeMail::class, function (SecurityCodeMail $mail): bool {
            return $mail->purpose === 'confirmação do envio PIX';
        });

        $this->assertDatabaseHas('pix_payout_requests', [
            'seller_id' => $user->id,
            'establishment_id' => '5001',
            'amount' => 10000,
            'pix_key_type' => 'PHONE',
            'status' => 'awaiting_confirmation',
            'init_id' => 'E13935893202604281747Y3pL5YlGFRs',
            'description' => 'Retirada semanal',
        ]);
    }

    public function test_pix_out_store_rejects_an_invalid_signature_before_processing(): void
    {
        $user = $this->createVendor();
        $user->forceFill([
            'electronic_signature_hash' => Hash::make('assinatura-secreta'),
        ])->save();

        $balanceService = $this->createMock(BalanceService::class);
        $balanceService->expects($this->never())
            ->method('saldoAtual');

        $pixPayoutService = $this->createMock(PixPayoutService::class);
        $pixPayoutService->expects($this->never())
            ->method('initiate');

        $this->app->instance(BalanceService::class, $balanceService);
        $this->app->instance(PixPayoutService::class, $pixPayoutService);

        $response = $this->actingAs($user)->postJson('/api/spa/cobranca/pix-out', [
            'amount' => 'R$ 100,00',
            'pix_key_type' => 'CPF',
            'pix_key' => '12345678901',
            'electronic_signature' => 'assinatura-incorreta',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('message', 'Assinatura eletrônica inválida.');

        $this->assertDatabaseCount('pix_payout_requests', 0);
    }

    public function test_pix_out_confirm_rejects_an_incorrect_code(): void
    {
        $user = $this->createVendor();

        $payoutRequest = PixPayoutRequest::factory()->create([
            'seller_id' => $user->id,
            'establishment_id' => '5001',
            'amount' => 10000,
            'pix_key_type' => 'CPF',
            'pix_key' => '12345678901',
            'description' => 'Retirada',
            'status' => 'awaiting_confirmation',
            'init_id' => 'E13935893202604281747Y3pL5YlGFRs',
            'confirmation_code_hash' => Hash::make('123456'),
            'confirmation_code_sent_at' => now(),
            'confirmation_code_expires_at' => now()->addMinutes(5),
        ]);

        $response = $this->actingAs($user)->postJson("/api/spa/cobranca/pix-out/{$payoutRequest->id}/confirmar", [
            'verification_code' => '000000',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('remaining_attempts', 2);

        $this->assertDatabaseHas('pix_payout_requests', [
            'id' => $payoutRequest->id,
            'status' => 'awaiting_confirmation',
            'confirmation_code_attempts' => 1,
        ]);
    }

    public function test_pix_out_confirm_confirms_with_a_valid_code(): void
    {
        $user = $this->createVendor();

        $payoutRequest = PixPayoutRequest::factory()->create([
            'seller_id' => $user->id,
            'establishment_id' => '5001',
            'amount' => 10000,
            'pix_key_type' => 'CPF',
            'pix_key' => '12345678901',
            'description' => 'Retirada',
            'status' => 'awaiting_confirmation',
            'init_id' => 'E13935893202604281747Y3pL5YlGFRs',
            'confirmation_code_hash' => Hash::make('123456'),
            'confirmation_code_sent_at' => now(),
            'confirmation_code_expires_at' => now()->addMinutes(5),
        ]);

        $pixPayoutService = $this->createMock(PixPayoutService::class);
        $pixPayoutService->expects($this->once())
            ->method('confirm')
            ->with($this->callback(function (array $payload): bool {
                return ($payload['type'] ?? null) === 'CPF'
                    && ($payload['key'] ?? null) === '12345678901'
                    && ($payload['amount'] ?? null) === 10000
                    && ($payload['init_id'] ?? null) === 'E13935893202604281747Y3pL5YlGFRs';
            }))
            ->willReturn([
                '_id' => '69f0f2cf71ad0804c940575a',
                'status' => 'PROCESSING',
                'init_id' => 'E13935893202604281747Y3pL5YlGFRs',
                'receipt_url' => 'https://example.test/comprovante',
            ]);

        $this->app->instance(PixPayoutService::class, $pixPayoutService);

        $response = $this->actingAs($user)->postJson("/api/spa/cobranca/pix-out/{$payoutRequest->id}/confirmar", [
            'verification_code' => '123456',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('payout_request.status', 'confirmed')
            ->assertJsonPath('payout_request.gateway_transaction_id', '69f0f2cf71ad0804c940575a')
            ->assertJsonPath('payout_request.receipt_url', 'https://example.test/comprovante');

        $this->assertDatabaseHas('pix_payout_requests', [
            'id' => $payoutRequest->id,
            'status' => 'confirmed',
            'gateway_transaction_id' => '69f0f2cf71ad0804c940575a',
        ]);

        $this->assertNotNull($payoutRequest->fresh()->confirmation_code_verified_at);
    }

    private function createVendor(string $password = 'secret'): User
    {
        $user = User::factory()->create([
            'nivel_acesso' => 'vendedor',
            'password' => $password,
            'email_verified_at' => now(),
        ]);

        $user->vendedor()->create([
            'estabelecimento_id' => '5001',
            'sub_nivel' => 'admin_loja',
            'status' => 'ativo',
            'must_change_password' => false,
        ]);

        return $user;
    }
}
