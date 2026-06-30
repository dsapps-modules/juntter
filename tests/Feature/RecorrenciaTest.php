<?php

namespace Tests\Feature;

use App\Mail\RecorrenciaLinkMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RecorrenciaTest extends TestCase
{
    use RefreshDatabase;

    private function authenticateVendor(): User
    {
        $user = User::factory()->create([
            'nivel_acesso' => 'vendedor',
            'email_verified_at' => now(),
        ]);

        $user->vendedor()->create([
            'estabelecimento_id' => '7001',
            'sub_nivel' => 'admin_loja',
            'status' => 'ativo',
        ]);

        $this->actingAs($user);

        return $user;
    }

    public function test_the_recorrencia_sidebar_item_points_to_the_new_section(): void
    {
        $navigationSource = file_get_contents(base_path('resources/js/spa/navigation/menu.js'));

        $boletoPosition = strpos($navigationSource, 'cobranca.boleto');
        $recorrenciaPosition = strpos($navigationSource, 'recorrencia.index');

        $this->assertStringContainsString("label: 'Recorrência'", $navigationSource);
        $this->assertStringContainsString("path: '/recorrencia'", $navigationSource);
        $this->assertNotFalse($boletoPosition);
        $this->assertNotFalse($recorrenciaPosition);
        $this->assertLessThan($recorrenciaPosition, $boletoPosition);
    }

    public function test_the_recorrencia_pages_are_available_inside_the_spa(): void
    {
        $this->authenticateVendor();

        foreach ([
            '/app/recorrencia',
            '/app/recorrencia/pix',
            '/app/recorrencia/boleto',
            '/app/recorrencia/cartao-credito',
        ] as $path) {
            $response = $this->get($path);

            $response->assertOk();
            $response->assertSee('id="app"', false);
        }
    }

    public function test_the_recorrencia_form_pages_expose_the_expected_steps(): void
    {
        $selectionPageSource = file_get_contents(base_path('resources/js/spa/pages/recorrencia/RecorrenciaPage.jsx'));
        $formPageSource = file_get_contents(base_path('resources/js/spa/pages/recorrencia/RecorrenciaFormPage.jsx'));

        $this->assertStringContainsString('Recorrência', $selectionPageSource);
        $this->assertStringContainsString("title: 'Pix'", $selectionPageSource);
        $this->assertStringContainsString("title: 'Boleto'", $selectionPageSource);
        $this->assertStringContainsString("title: 'Cartão de Crédito'", $selectionPageSource);
        $this->assertStringContainsString('/recorrencia/pix', $selectionPageSource);
        $this->assertStringContainsString('/recorrencia/boleto', $selectionPageSource);
        $this->assertStringContainsString('/recorrencia/cartao-credito', $selectionPageSource);
        $this->assertStringContainsString('Configurar {option.title}', $selectionPageSource);
        $this->assertStringContainsString('Preparar cobrança', $formPageSource);
        $this->assertStringContainsString('Enviar', $formPageSource);
        $this->assertStringContainsString('Enviar por e-mail', $formPageSource);
        $this->assertStringContainsString('Enviar por WhatsApp', $formPageSource);
        $this->assertStringContainsString('Link público da cobrança', $formPageSource);
        $this->assertStringContainsString('Dados do Pix', $formPageSource);
        $this->assertStringContainsString('Dados do boleto', $formPageSource);
        $this->assertStringContainsString('Dados do cartão', $formPageSource);
        $this->assertStringContainsString('pix_expiration_minutes: 2,', $formPageSource);
        $this->assertStringContainsString('addonAfter="dias"', $formPageSource);
        $this->assertStringNotContainsString('Envio rápido com chave, QR Code e copia e cola.', $formPageSource);
        $this->assertStringNotContainsString('Resumo da configuração', $formPageSource);
        $this->assertStringNotContainsString('Assunto da cobrança', $formPageSource);
        $this->assertStringNotContainsString('Mensagem que acompanha o link de pagamento', $formPageSource);
        $this->assertStringNotContainsString('Sua cobrança recorrente via Pix está pronta', $formPageSource);
        $this->assertStringNotContainsString('Olá, segue o link para a sua cobrança recorrente via Pix.', $formPageSource);
        $this->assertStringNotContainsString('Seu boleto recorrente está disponível', $formPageSource);
        $this->assertStringNotContainsString('Olá, segue o link do boleto recorrente.', $formPageSource);
        $this->assertStringNotContainsString('Sua cobrança recorrente no cartão está pronta', $formPageSource);
        $this->assertStringNotContainsString('Olá, segue o link para a cobrança recorrente no cartão de crédito.', $formPageSource);
    }

    public function test_the_recorrencia_email_endpoint_sends_the_mail(): void
    {
        Mail::fake();
        $this->authenticateVendor();

        $response = $this->postJson('/api/spa/recorrencia/email', [
            'send_immediately' => true,
            'send_via_email' => true,
            'send_via_whatsapp' => false,
            'customer_name' => 'Cliente Exemplo',
            'customer_email' => 'cliente@example.com',
            'customer_phone' => '(11) 99999-9999',
            'customer_document' => '12345678901',
            'recipient_email' => 'cliente@example.com',
            'recipient_name' => 'Cliente Exemplo',
            'email_subject' => 'Cobrança recorrente',
            'payment_type' => 'PIX',
            'amount' => 'R$ 100,00',
            'frequency' => 'MENSAL',
            'charge_day' => 10,
            'start_date' => '2026-07-01',
            'end_date' => null,
            'payment_link_url' => 'https://example.com/pagamento',
            'email_message' => 'Segue o link da cobrança recorrente.',
            'whatsapp_number' => '(11) 99999-9999',
            'pix_key' => 'cliente@example.com',
            'pix_copy_paste' => '000201010212...',
            'pix_expiration_minutes' => 2880,
            'boleto_due_days' => 5,
            'boleto_instructions' => 'Pagar até o vencimento.',
            'boleto_interest' => '0,00',
            'boleto_fine' => '0,00',
            'card_installments' => 3,
            'card_descriptor' => 'CLI EXEMPLO',
            'card_capture_mode' => 'AUTO',
        ]);

        $response->assertOk();
        $response->assertJson([
            'message' => 'Recorrência salva e e-mail enviado com sucesso.',
        ]);

        Mail::assertSent(RecorrenciaLinkMail::class, function (RecorrenciaLinkMail $mail): bool {
            return $mail->recipientName === 'Cliente Exemplo'
                && $mail->paymentType === 'PIX'
                && $mail->paymentLinkUrl === 'https://example.com/pagamento';
        });

        $this->assertDatabaseHas('recorrencias', [
            'customer_name' => 'Cliente Exemplo',
            'customer_email' => 'cliente@example.com',
            'payment_type' => 'PIX',
            'frequency' => 'MENSAL',
            'amount_centavos' => 10000,
            'status' => 'ENVIADA',
        ]);
    }

    public function test_the_recorrencia_email_endpoint_can_prepare_without_sending(): void
    {
        Mail::fake();
        $this->authenticateVendor();

        $response = $this->postJson('/api/spa/recorrencia/email', [
            'send_immediately' => false,
            'send_via_email' => true,
            'send_via_whatsapp' => false,
            'customer_name' => 'Cliente Exemplo',
            'customer_email' => 'cliente@example.com',
            'customer_phone' => '(11) 99999-9999',
            'customer_document' => '12345678901',
            'recipient_email' => 'cliente@example.com',
            'recipient_name' => 'Cliente Exemplo',
            'email_subject' => 'Cobrança recorrente',
            'payment_type' => 'PIX',
            'amount' => 'R$ 100,00',
            'frequency' => 'MENSAL',
            'charge_day' => 10,
            'start_date' => '2026-07-01',
            'end_date' => null,
            'payment_link_url' => 'https://example.com/pagamento',
            'email_message' => 'Segue o link da cobrança recorrente.',
            'whatsapp_number' => '(11) 99999-9999',
            'pix_key' => 'cliente@example.com',
            'pix_copy_paste' => '000201010212...',
            'pix_expiration_minutes' => 2880,
            'boleto_due_days' => 5,
            'boleto_instructions' => 'Pagar até o vencimento.',
            'boleto_interest' => '0,00',
            'boleto_fine' => '0,00',
            'card_installments' => 3,
            'card_descriptor' => 'CLI EXEMPLO',
            'card_capture_mode' => 'AUTO',
        ]);

        $response->assertOk();
        $response->assertJson([
            'message' => 'Recorrência salva com sucesso.',
        ]);

        Mail::assertNothingSent();

        $this->assertDatabaseHas('recorrencias', [
            'customer_name' => 'Cliente Exemplo',
            'payment_type' => 'PIX',
            'status' => 'PENDENTE',
        ]);
    }

    public function test_the_recorrencia_email_endpoint_validates_required_fields(): void
    {
        $this->authenticateVendor();

        $response = $this->postJson('/api/spa/recorrencia/email', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'send_immediately',
            'send_via_email',
            'send_via_whatsapp',
            'recipient_name',
            'payment_type',
            'amount',
            'frequency',
            'payment_link_url',
            'customer_name',
        ]);
    }
}
