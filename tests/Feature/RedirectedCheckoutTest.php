<?php

namespace Tests\Feature;

use App\Models\CheckoutEvent;
use App\Models\CheckoutLink;
use App\Models\CheckoutSession;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\PaytimeTransaction;
use App\Models\Product;
use App\Models\User;
use App\Services\Payments\Paytime\PaytimePaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RedirectedCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_can_create_a_product(): void
    {
        $user = $this->makeVendorUser();

        $response = $this->actingAs($user)->postJson('/seller/products', [
            'name' => 'Curso Laravel',
            'description' => 'Descrição do curso',
            'short_description' => 'Curso prático',
            'sku' => 'SKU-001',
            'price' => 199.90,
            'status' => 'active',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('products', [
            'seller_id' => $user->id,
            'name' => 'Curso Laravel',
            'slug' => 'curso-laravel',
            'price' => 199.90,
            'status' => 'active',
        ]);
    }

    public function test_vendor_can_create_a_checkout_link(): void
    {
        $user = $this->makeVendorUser();
        $product = $this->makeProduct($user);

        $response = $this->actingAs($user)->postJson('/seller/checkout-links', [
            'product_id' => $product->id,
            'name' => 'Oferta principal',
            'status' => 'active',
            'quantity' => 2,
            'unit_price' => 149.90,
            'allow_pix' => true,
            'allow_boleto' => true,
            'allow_credit_card' => true,
            'pix_discount_type' => 'percentage',
            'pix_discount_value' => 10,
            'boleto_discount_type' => 'fixed',
            'boleto_discount_value' => 20,
            'free_shipping' => true,
        ]);

        $response->assertCreated();
        $this->assertStringStartsWith('chk_', (string) $response->json('checkout_link.public_token'));
        $this->assertSame(route('checkout.public.show', $response->json('checkout_link.public_token')), $response->json('public_url'));

        $this->assertDatabaseHas('checkout_links', [
            'seller_id' => $user->id,
            'product_id' => $product->id,
            'name' => 'Oferta principal',
            'status' => 'active',
            'quantity' => 2,
            'unit_price' => 149.90,
        ]);
    }

    public function test_vendor_can_update_a_product(): void
    {
        $user = $this->makeVendorUser();
        $product = $this->makeProduct($user);

        $response = $this->actingAs($user)->putJson('/seller/products/'.$product->id, [
            'name' => 'Curso Laravel Avançado',
            'slug' => 'curso-laravel-avancado',
            'description' => 'Nova descrição',
            'short_description' => 'Resumo novo',
            'sku' => 'SKU-002',
            'price' => 249.90,
            'status' => 'active',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Curso Laravel Avançado',
            'slug' => 'curso-laravel-avancado',
            'price' => 249.90,
        ]);
    }

    public function test_vendor_can_activate_and_deactivate_a_checkout_link(): void
    {
        $user = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($user, $this->makeProduct($user), ['status' => 'inactive']);

        $this->actingAs($user)
            ->postJson('/seller/checkout-links/'.$link->id.'/activate')
            ->assertOk();

        $this->assertDatabaseHas('checkout_links', [
            'id' => $link->id,
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->postJson('/seller/checkout-links/'.$link->id.'/deactivate')
            ->assertOk();

        $this->assertDatabaseHas('checkout_links', [
            'id' => $link->id,
            'status' => 'inactive',
        ]);
    }

    public function test_vendor_can_delete_a_checkout_link(): void
    {
        $user = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($user, $this->makeProduct($user));

        $this->actingAs($user)
            ->deleteJson('/seller/checkout-links/'.$link->id)
            ->assertOk();

        $this->assertDatabaseMissing('checkout_links', [
            'id' => $link->id,
        ]);
    }

    public function test_vendor_can_delete_a_product(): void
    {
        $user = $this->makeVendorUser();
        $product = $this->makeProduct($user);

        $this->actingAs($user)
            ->deleteJson('/seller/products/'.$product->id)
            ->assertOk();

        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
        ]);
    }

    public function test_vendor_cannot_access_another_vendors_checkout_link(): void
    {
        $sellerOne = $this->makeVendorUser();
        $sellerTwo = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($sellerOne, $this->makeProduct($sellerOne));

        $this->actingAs($sellerTwo)
            ->get('/seller/checkout-links/'.$link->id)
            ->assertForbidden();
    }

    public function test_active_public_checkout_opens_checkout_page(): void
    {
        $user = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($user, $this->makeProduct($user));

        $response = $this->get(route('checkout.public.show', $link->public_token));

        $response->assertOk();
        $response->assertSee($link->product->name);
        $response->assertDontSee('Produto:');
        $response->assertDontSee('Quantidade:');
        $response->assertDontSee('Token da sessão:');
        $response->assertSee('checkout-logo-image', false);
        $response->assertSee('/img/logo/juntter_webp_640_174.webp', false);
        $response->assertSee('id="checkout-public-app"', false);
        $response->assertSee('Resumo do pedido', false);
        $response->assertDontSee('data-step-panel="identification"', false);
        $response->assertDontSee('data-step-panel="waiting"', false);
        $response->assertSee('checkout-public-data', false);
        $response->assertDontSee('Valores e informações da sessão atual.');
        $response->assertDontSee('Started');
        $response->assertDontSee('Pendente');
        $response->assertSee('data-person-type-switch', false);
        $response->assertSee('data-person-form="pf"', false);
        $response->assertSee('data-person-form="pj"', false);
        $response->assertDontSee('Salvar pessoa física', false);
        $response->assertDontSee('Salvar pessoa jurídica', false);
        $this->assertMatchesRegularExpression('/<input id="customer_birth_date_pf" name="customer_birth_date" type="date"[^>]*required[^>]*>/', $response->getContent());
        $response->assertSee('Nome da empresa', false);
        $response->assertSee('Nome do responsável', false);
        $response->assertSee('CPF do responsável', false);
        $response->assertSee('Nascimento do responsável', false);
        $response->assertSee('Celular', false);
        $response->assertDontSee('Inscrição estadual');
        $response->assertDontSee('Razão social');
        $pjFormContent = $response->getContent();
        $this->assertMatchesRegularExpression('/<form id="checkout-identification-pj-form".*?<\/form>/s', $pjFormContent);
        $this->assertMatchesRegularExpression(
            '/<div class="field">\s*<label for="customer_document_pj">CNPJ<\/label>.*?<div class="field">\s*<label for="customer_email_pj">E-mail<\/label>/s',
            $pjFormContent
        );
        preg_match('/<form id="checkout-identification-pj-form".*?<\/form>/s', $pjFormContent, $pjFormMatches);
        $pjFormMarkup = $pjFormMatches[0] ?? '';
        $expectedFieldOrder = [
            'CNPJ',
            'E-mail',
            'Nome da empresa',
            'Nome do responsável',
            'CPF do responsável',
            'Nascimento do responsável',
            'Celular',
        ];

        $lastPosition = -1;
        foreach ($expectedFieldOrder as $expectedField) {
            $position = strpos($pjFormMarkup, $expectedField);
            $this->assertIsInt($position);
            $this->assertGreaterThan($lastPosition, $position);
            $lastPosition = $position;
        }
        $response->assertDontSee('data-installments-wrapper', false);
        $response->assertDontSee('data-card-fields-wrapper', false);
        $response->assertSee('placeholder="(11) 99999-9999"', false);
        $response->assertSee('placeholder="00000-000"', false);
        $response->assertSee('inputmode="numeric"', false);
        $response->assertDontSee('Fluxo guiado');
        $response->assertDontSee('Link público');
        $response->assertDontSee('Sessão');
        $response->assertDontSee('Cadastro com nome completo e CPF.');
        $response->assertDontSee('Cadastro com razão social, CNPJ e dados do responsável.');
        $response->assertDontSee('Escolha o tipo de pessoa e preencha o formulário correspondente.');
        $response->assertDontSee('Isento de inscrição estadual');
        $response->assertDontSee('A confirmação chega via webhook do gateway.', false);
        $response->assertDontSee('O sistema consulta o status do pagamento periodicamente');
        $response->assertDontSee('data-boleto-block', false);
        $response->assertDontSee('data-open-payment', false);

        $componentSource = file_get_contents(base_path('resources/js/checkout-public.js'));

        $this->assertIsString($componentSource);
        $this->assertStringContainsString("const personFormType = String(identificationForm.dataset.personForm || 'pf');", $componentSource);
        $this->assertStringContainsString("const shouldValidateResponsibleDocument = personFormType === 'pj';", $componentSource);
        $this->assertStringContainsString('!validateIdentificationForm(identificationForm)', $componentSource);
        $this->assertStringContainsString('shouldValidateResponsibleDocument && !validateResponsibleCpf(identificationForm, { focusOnError: true })', $componentSource);
        $this->assertStringNotContainsString('window.location.assign(url);', $componentSource);
    }

    public function test_active_public_checkout_refreshes_session_price_when_link_changes_before_payment(): void
    {
        $user = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($user, $this->makeProduct($user), [
            'quantity' => 1,
            'unit_price' => 100.00,
            'total_price' => 100.00,
        ]);
        $session = $this->makeCheckoutSession($link);

        $link->update([
            'unit_price' => 150.00,
            'total_price' => 150.00,
        ]);

        $response = $this->withSession([
            'checkout_session_token.'.$link->public_token => $session->session_token,
        ])->get(route('checkout.public.show', $link->public_token));

        $response->assertOk();
        $response->assertSee('R$ 150,00', false);
        $response->assertDontSee('R$ 100,00', false);

        $this->assertDatabaseHas('checkout_sessions', [
            'id' => $session->id,
            'subtotal' => 150.00,
            'total' => 150.00,
        ]);
    }

    public function test_active_public_checkout_uses_the_seller_logo_when_available(): void
    {
        Storage::fake('public');

        Storage::disk('public')->put('company-logos/custom-logo.png', 'fake-image-content');

        $user = $this->makeVendorUser();
        $user->forceFill([
            'company_logo_path' => 'company-logos/custom-logo.png',
        ])->save();

        $link = $this->makeCheckoutLink($user, $this->makeProduct($user));

        $response = $this->get(route('checkout.public.show', $link->public_token));

        $response->assertOk();
        $response->assertSee('/company-logo?path=company-logos%2Fcustom-logo.png', false);
        $response->assertDontSee(asset('img/logo/juntter_webp_640_174.webp'), false);
    }

    public function test_active_public_checkout_falls_back_to_default_logo_when_company_logo_file_is_missing(): void
    {
        Storage::fake('public');

        $user = $this->makeVendorUser();
        $user->forceFill([
            'company_logo_path' => 'company-logos/missing-logo.png',
        ])->save();

        $link = $this->makeCheckoutLink($user, $this->makeProduct($user));

        $response = $this->get(route('checkout.public.show', $link->public_token));

        $response->assertOk();
        $response->assertSee('/img/logo/juntter_webp_640_174.webp', false);
        $response->assertDontSee('/company-logo?path=company-logos%2Fmissing-logo.png', false);
    }

    public function test_public_checkout_details_page_shows_personal_data_and_address_before_payment(): void
    {
        $user = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($user, $this->makeProduct($user));

        $response = $this->get(route('checkout.public.show', $link->public_token));

        $response->assertOk();
        $response->assertSee('Dados pessoais', false);
        $response->assertSee('Endereço', false);
        $response->assertDontSee('Etapa atual');
        $response->assertDontSee('Método de pagamento');
    }

    public function test_public_checkout_payment_page_shows_only_allowed_payment_methods(): void
    {
        $user = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($user, $this->makeProduct($user), [
            'allow_pix' => true,
            'allow_boleto' => true,
            'allow_credit_card' => false,
        ]);
        $session = $this->makeCheckoutSession($link, [
            'customer_name' => 'Maria Silva',
            'customer_email' => 'maria@example.com',
            'customer_document' => '12345678909',
            'customer_document_type' => 'cpf',
            'customer_phone' => '11999999999',
            'zipcode' => '01001000',
            'street' => 'Rua A',
            'number' => '100',
            'neighborhood' => 'Centro',
            'city' => 'São Paulo',
            'state' => 'SP',
            'recipient_name' => 'Maria Silva',
            'status' => 'delivery_completed',
            'current_step' => 'payment',
        ]);

        $response = $this->get(route('checkout.public.payment.page', $session->session_token));

        $response->assertOk();
        $response->assertSee('Selecione o método de pagamento', false);
        $response->assertDontSee('Escolha o método disponível para este link e conclua o pedido.', false);
        $response->assertDontSee('<label for="payment_method">', false);
        $response->assertSee('Continuar para pagamento', false);
        $response->assertDontSee('Pagar', false);
        $response->assertSee('Pix', false);
        $response->assertSee('Boleto', false);
        $response->assertDontSee('Cartão de crédito', false);
    }

    public function test_public_checkout_payment_method_selection_redirects_to_payment_details_page(): void
    {
        $user = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($user, $this->makeProduct($user));
        $session = $this->makeCheckoutSession($link, [
            'customer_name' => 'Maria Silva',
            'customer_email' => 'maria@example.com',
            'customer_document' => '12345678909',
            'customer_document_type' => 'cpf',
            'customer_phone' => '11999999999',
            'zipcode' => '01001000',
            'street' => 'Rua A',
            'number' => '100',
            'neighborhood' => 'Centro',
            'city' => 'São Paulo',
            'state' => 'SP',
            'recipient_name' => 'Maria Silva',
            'status' => 'delivery_completed',
            'current_step' => 'payment',
        ]);

        $response = $this->post(route('checkout.public.payment.choose', $session->session_token), [
            'payment_method' => 'pix',
        ]);

        $response->assertRedirect(route('checkout.public.payment.details', $session->session_token));
        $this->assertDatabaseHas('checkout_sessions', [
            'id' => $session->id,
            'payment_method' => 'pix',
            'status' => 'payment_started',
            'current_step' => 'payment',
        ]);
    }

    public function test_public_checkout_payment_details_page_shows_pix_status_when_transaction_exists(): void
    {
        $user = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($user, $this->makeProduct($user));
        $session = $this->makeCheckoutSession($link, [
            'customer_name' => 'Maria Silva',
            'customer_email' => 'maria@example.com',
            'customer_document' => '12345678909',
            'customer_document_type' => 'cpf',
            'customer_phone' => '11999999999',
            'zipcode' => '01001000',
            'street' => 'Rua A',
            'number' => '100',
            'neighborhood' => 'Centro',
            'city' => 'São Paulo',
            'state' => 'SP',
            'recipient_name' => 'Maria Silva',
            'status' => 'payment_pending',
            'current_step' => 'payment',
            'payment_method' => 'pix',
        ]);

        $order = Order::query()->create([
            'seller_id' => $link->seller_id,
            'checkout_link_id' => $link->id,
            'checkout_session_id' => $session->id,
            'product_id' => $link->product_id,
            'order_number' => 'JNT-2026-009999',
            'status' => 'pending',
            'customer_name' => 'Maria Silva',
            'customer_email' => 'maria@example.com',
            'customer_document' => '12345678909',
            'customer_phone' => '11999999999',
            'quantity' => 1,
            'unit_price' => 100.00,
            'subtotal' => 100.00,
            'discount_total' => 0,
            'shipping_total' => 0,
            'total' => 100.00,
            'payment_method' => 'pix',
        ]);

        PaymentTransaction::query()->create([
            'order_id' => $order->id,
            'seller_id' => $user->id,
            'gateway' => 'paytime',
            'gateway_transaction_id' => 'pix-checkout-123',
            'gateway_status' => 'PENDING',
            'internal_status' => 'pending',
            'payment_method' => 'pix',
            'amount' => 100.00,
            'pix_copy_paste' => '00020126580014br.gov.bcb.pix...',
            'response_payload' => [
                'gateway_transaction_id' => 'pix-checkout-123',
                'gateway_status' => 'PENDING',
                'internal_status' => 'pending',
                'pix_copy_paste' => '00020126580014br.gov.bcb.pix...',
            ],
        ]);

        $response = $this->get(route('checkout.public.payment.details', $session->session_token));

        $response->assertOk();
        $response->assertSee('Aguardando confirmação', false);
        $response->assertSee('data-step-panel="waiting"', false);
        $response->assertSee('Pix copia e cola', false);
        $response->assertSee('Alterar método', false);
        $response->assertSee('Pagamento', false);
        $response->assertDontSee('Selecione o método de pagamento', false);
    }

    public function test_public_checkout_payment_form_redirects_back_to_payment_details_page_on_success(): void
    {
        $seller = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($seller, $this->makeProduct($seller));
        $session = $this->makeCheckoutSession($link, [
            'customer_name' => 'Maria Silva',
            'customer_email' => 'maria@example.com',
            'customer_document' => '12345678909',
            'customer_document_type' => 'cpf',
            'customer_phone' => '11999999999',
            'zipcode' => '01001000',
            'street' => 'Rua A',
            'number' => '100',
            'neighborhood' => 'Centro',
            'city' => 'SÃ£o Paulo',
            'state' => 'SP',
            'recipient_name' => 'Maria Silva',
            'status' => 'delivery_completed',
            'current_step' => 'payment',
        ]);

        $this->mockPixPaymentService();

        $response = $this->post(route('checkout.public.payment', $session->session_token), [
            'payment_method' => 'pix',
            'installments' => 1,
        ]);

        $response->assertRedirect(route('checkout.public.payment.details', $session->session_token));

        $this->assertDatabaseHas('orders', [
            'checkout_session_id' => $session->id,
            'payment_method' => 'pix',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('payment_transactions', [
            'payment_method' => 'pix',
            'gateway' => 'paytime',
            'internal_status' => 'pending',
            'gateway_transaction_id' => 'pix-checkout-123',
        ]);
    }

    public function test_public_checkout_payment_form_redirects_to_thank_you_when_card_is_approved(): void
    {
        $seller = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($seller, $this->makeProduct($seller), [
            'allow_pix' => false,
            'allow_boleto' => false,
            'allow_credit_card' => true,
        ]);
        $session = $this->makeCheckoutSession($link, [
            'customer_name' => 'Maria Silva',
            'customer_email' => 'maria@example.com',
            'customer_document' => '12345678909',
            'customer_document_type' => 'cpf',
            'customer_phone' => '11999999999',
            'zipcode' => '01001000',
            'street' => 'Rua A',
            'number' => '100',
            'neighborhood' => 'Centro',
            'city' => 'SÃ£o Paulo',
            'state' => 'SP',
            'recipient_name' => 'Maria Silva',
            'status' => 'delivery_completed',
            'current_step' => 'payment',
        ]);

        $paymentService = $this->createMock(PaytimePaymentService::class);
        $paymentService->expects($this->once())
            ->method('createCreditCardPayment')
            ->willReturn([
                'gateway_transaction_id' => 'card-checkout-approved-123',
                'gateway_status' => 'APPROVED',
                'internal_status' => 'authorized',
                'card_last_four' => '4242',
                'card_brand' => 'visa',
            ]);
        $this->app->instance(PaytimePaymentService::class, $paymentService);

        $response = $this->post(route('checkout.public.payment', $session->session_token), [
            'payment_method' => 'credit_card',
            'installments' => 1,
            'card' => [
                'holder_name' => 'Maria Silva',
                'holder_document' => '12345678909',
                'card_number' => '4111111111111111',
                'expiration_month' => 12,
                'expiration_year' => now()->year + 1,
                'security_code' => '123',
            ],
        ]);

        $response->assertRedirect(route('checkout.public.thank-you', $session->session_token));

        $this->assertDatabaseHas('checkout_sessions', [
            'id' => $session->id,
            'status' => 'paid',
            'current_step' => 'payment',
        ]);

        $this->assertDatabaseHas('orders', [
            'checkout_session_id' => $session->id,
            'payment_method' => 'credit_card',
            'status' => 'paid',
        ]);

        $this->assertDatabaseHas('payment_transactions', [
            'payment_method' => 'credit_card',
            'gateway_transaction_id' => 'card-checkout-approved-123',
            'internal_status' => 'authorized',
        ]);
    }

    public function test_public_checkout_payment_form_redirects_back_with_flash_error_when_gateway_fails(): void
    {
        $seller = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($seller, $this->makeProduct($seller), [
            'allow_pix' => false,
            'allow_boleto' => true,
            'allow_credit_card' => false,
        ]);
        $session = $this->makeCheckoutSession($link, [
            'customer_name' => 'Maria Silva',
            'customer_email' => 'maria@example.com',
            'customer_document' => '12345678909',
            'customer_document_type' => 'cpf',
            'customer_phone' => '11999999999',
            'zipcode' => '01001000',
            'street' => 'Rua A',
            'number' => '100',
            'neighborhood' => 'Centro',
            'city' => 'SÃ£o Paulo',
            'state' => 'SP',
            'recipient_name' => 'Maria Silva',
            'status' => 'delivery_completed',
            'current_step' => 'payment',
        ]);

        $paymentService = $this->createMock(PaytimePaymentService::class);
        $paymentService->expects($this->once())
            ->method('createBoletoPayment')
            ->willReturn([
                'gateway_status' => 'FAILED',
                'internal_status' => 'failed',
                'api_boleto' => [
                    'message' => 'Data limite de desconto deve ser menor que a data de vencimento',
                    'status' => 403,
                    'code' => 'BNK000143',
                    'boleto_url' => null,
                    'boleto_barcode' => null,
                    'boleto_digitable_line' => null,
                ],
            ]);

        $this->app->instance(PaytimePaymentService::class, $paymentService);

        $response = $this->followingRedirects()
            ->from(route('checkout.public.payment.details', $session->session_token))
            ->post(route('checkout.public.payment', $session->session_token), [
                'payment_method' => 'boleto',
                'installments' => 1,
            ]);

        $response->assertSee('Data limite de desconto deve ser menor que a data de vencimento', false);

        $this->assertDatabaseHas('checkout_sessions', [
            'id' => $session->id,
            'status' => 'failed',
        ]);

        $this->assertDatabaseHas('orders', [
            'checkout_session_id' => $session->id,
            'payment_method' => 'boleto',
            'status' => 'failed',
        ]);
    }

    public function test_paid_public_checkout_redirects_to_thank_you_page(): void
    {
        $user = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($user, $this->makeProduct($user));
        $session = $this->makeCheckoutSession($link);

        Order::query()->create([
            'seller_id' => $link->seller_id,
            'checkout_link_id' => $link->id,
            'checkout_session_id' => $session->id,
            'product_id' => $link->product_id,
            'order_number' => 'JNT-2026-000999',
            'status' => 'paid',
            'customer_name' => 'Maria Silva',
            'customer_email' => 'maria@example.com',
            'customer_document' => '12345678909',
            'customer_phone' => '11999999999',
            'quantity' => 1,
            'unit_price' => 100.00,
            'subtotal' => 100.00,
            'discount_total' => 0,
            'shipping_total' => 0,
            'total' => 100.00,
            'payment_method' => 'pix',
        ]);

        $response = $this->withSession([
            'checkout_session_token.'.$link->public_token => $session->session_token,
        ])->get(route('checkout.public.show', $link->public_token));

        $response->assertRedirect(route('checkout.public.thank-you', $session->session_token));
    }

    public function test_checkout_link_sales_endpoint_uses_the_payment_transaction_status_for_paid_orders(): void
    {
        $seller = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($seller, $this->makeProduct($seller));
        $session = $this->makeCheckoutSession($link);

        $order = Order::query()->create([
            'seller_id' => $link->seller_id,
            'checkout_link_id' => $link->id,
            'checkout_session_id' => $session->id,
            'product_id' => $link->product_id,
            'order_number' => 'JNT-2026-000123',
            'status' => 'pending',
            'customer_name' => 'Maria Silva',
            'customer_email' => 'maria@example.com',
            'customer_document' => '12345678909',
            'customer_phone' => '11999999999',
            'quantity' => 1,
            'unit_price' => 100.00,
            'subtotal' => 100.00,
            'discount_total' => 0,
            'shipping_total' => 0,
            'total' => 100.00,
            'payment_method' => 'pix',
        ]);

        PaymentTransaction::query()->create([
            'order_id' => $order->id,
            'seller_id' => $seller->id,
            'gateway' => 'paytime',
            'gateway_transaction_id' => 'trx-paid-123',
            'gateway_status' => 'PAID',
            'internal_status' => 'paid',
            'payment_method' => 'pix',
            'amount' => 100.00,
        ]);

        $response = $this->actingAs($seller)->getJson('/seller/checkout-links/'.$link->id.'/sales');

        $response->assertOk();
        $response->assertJsonPath('orders.0.status', 'paid');
        $response->assertJsonPath('total_sales', 100);
    }

    public function test_checkout_link_sales_endpoint_uses_the_synced_paytime_transaction_status_when_payment_transaction_is_still_pending(): void
    {
        $seller = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($seller, $this->makeProduct($seller));
        $session = $this->makeCheckoutSession($link);

        $order = Order::query()->create([
            'seller_id' => $link->seller_id,
            'checkout_link_id' => $link->id,
            'checkout_session_id' => $session->id,
            'product_id' => $link->product_id,
            'order_number' => 'JNT-2026-000124',
            'status' => 'pending',
            'customer_name' => 'Maria Silva',
            'customer_email' => 'maria@example.com',
            'customer_document' => '12345678909',
            'customer_phone' => '11999999999',
            'quantity' => 1,
            'unit_price' => 100.00,
            'subtotal' => 100.00,
            'discount_total' => 0,
            'shipping_total' => 0,
            'total' => 100.00,
            'payment_method' => 'pix',
        ]);

        PaymentTransaction::query()->create([
            'order_id' => $order->id,
            'seller_id' => $seller->id,
            'gateway' => 'paytime',
            'gateway_transaction_id' => 'trx-synced-paid-123',
            'gateway_status' => 'PENDING',
            'internal_status' => 'pending',
            'payment_method' => 'pix',
            'amount' => 100.00,
        ]);

        PaytimeTransaction::query()->create([
            'external_id' => 'trx-synced-paid-123',
            'establishment_id' => 155463,
            'type' => 'PIX',
            'status' => 'PAID',
            'amount' => 10000,
            'original_amount' => 10000,
            'fees' => 0,
            'installments' => 1,
        ]);

        $response = $this->actingAs($seller)->getJson('/seller/checkout-links/'.$link->id.'/sales');

        $response->assertOk();
        $response->assertJsonPath('orders.0.status', 'paid');
        $response->assertJsonPath('total_sales', 100);
    }

    public function test_checkout_link_sales_detail_endpoint_returns_customer_delivery_and_payment_data(): void
    {
        $seller = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($seller, $this->makeProduct($seller));
        $session = $this->makeCheckoutSession($link, [
            'customer_name' => 'Maria Silva',
            'customer_email' => 'maria@example.com',
            'customer_document' => '12345678909',
            'customer_document_type' => 'cpf',
            'customer_phone' => '11999999999',
            'recipient_name' => 'Maria Silva',
            'zipcode' => '01001000',
            'street' => 'Rua A',
            'number' => '100',
            'complement' => 'Apto 12',
            'neighborhood' => 'Centro',
            'city' => 'São Paulo',
            'state' => 'SP',
            'status' => 'delivery_completed',
            'current_step' => 'payment',
        ]);

        $order = Order::query()->create([
            'seller_id' => $link->seller_id,
            'checkout_link_id' => $link->id,
            'checkout_session_id' => $session->id,
            'product_id' => $link->product_id,
            'order_number' => 'JNT-2026-000125',
            'status' => 'pending',
            'customer_name' => 'Maria Silva',
            'customer_email' => 'maria@example.com',
            'customer_document' => '12345678909',
            'customer_phone' => '11999999999',
            'quantity' => 1,
            'unit_price' => 100.00,
            'subtotal' => 100.00,
            'discount_total' => 0,
            'shipping_total' => 0,
            'total' => 100.00,
            'payment_method' => 'pix',
        ]);

        PaymentTransaction::query()->create([
            'order_id' => $order->id,
            'seller_id' => $seller->id,
            'gateway' => 'paytime',
            'gateway_transaction_id' => 'trx-detail-123',
            'gateway_status' => 'PAID',
            'internal_status' => 'pending',
            'payment_method' => 'pix',
            'amount' => 100.00,
        ]);

        PaytimeTransaction::query()->create([
            'external_id' => 'trx-detail-123',
            'establishment_id' => 155463,
            'type' => 'PIX',
            'status' => 'PAID',
            'amount' => 10000,
            'original_amount' => 10000,
            'fees' => 0,
            'installments' => 1,
        ]);

        $response = $this->actingAs($seller)->getJson('/seller/checkout-links/'.$link->id.'/sales/'.$order->id);

        $response->assertOk();
        $response->assertJsonPath('order.status', 'paid');
        $response->assertJsonPath('payment_transaction.internal_status', 'paid');
        $response->assertJsonPath('order.customer_name', 'Maria Silva');
        $response->assertJsonPath('checkout_session.city', 'São Paulo');
        $response->assertJsonPath('checkout_session.neighborhood', 'Centro');
        $response->assertJsonPath('payment_transaction.gateway_transaction_id', 'trx-detail-123');
        $response->assertJsonPath('checkout_link.id', $link->id);
    }

    public function test_inactive_public_checkout_returns_unavailable_page(): void
    {
        $user = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($user, $this->makeProduct($user), ['status' => 'inactive']);

        $response = $this->get(route('checkout.public.show', $link->public_token));

        $response->assertStatus(410);
        $response->assertSee('Este checkout não está disponível no momento.');
    }

    public function test_frontend_price_is_ignored_when_starting_pix_payment(): void
    {
        $user = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($user, $this->makeProduct($user), [
            'quantity' => 1,
            'unit_price' => 150.00,
            'total_price' => 150.00,
            'allow_pix' => true,
            'allow_boleto' => false,
            'allow_credit_card' => false,
        ]);
        $session = $this->makeCheckoutSession($link, [
            'customer_name' => 'Maria Silva',
            'customer_email' => 'maria@example.com',
            'customer_document' => '12345678909',
            'customer_document_type' => 'cpf',
            'customer_phone' => '11999999999',
            'zipcode' => '01001000',
            'street' => 'Rua A',
            'number' => '100',
            'neighborhood' => 'Centro',
            'city' => 'São Paulo',
            'state' => 'SP',
            'recipient_name' => 'Maria Silva',
            'status' => 'delivery_completed',
            'current_step' => 'payment',
        ]);
        $this->mockPixPaymentService();

        $response = $this->postJson('/checkout/session/'.$session->session_token.'/payment/checkout', [
            'payment_method' => 'pix',
            'total' => 1.00,
            'unit_price' => 1.00,
            'installments' => 1,
        ]);

        $response->assertOk();
        $this->assertSame('150.00', (string) $response->json('order.total'));
        $this->assertSame('150.00', (string) $response->json('payment_transaction.amount'));
        $this->assertSame('pix-checkout-123', $response->json('payment_transaction.gateway_transaction_id'));
        $this->assertSame('00020126580014br.gov.bcb.pix...', $response->json('payment_transaction.pix_copy_paste'));
    }

    public function test_credit_card_payment_requires_installments(): void
    {
        $seller = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($seller, $this->makeProduct($seller), [
            'allow_pix' => false,
            'allow_boleto' => false,
            'allow_credit_card' => true,
        ]);
        $session = $this->makeCheckoutSession($link, [
            'customer_name' => 'Maria Silva',
            'customer_email' => 'maria@example.com',
            'customer_document' => '12345678909',
            'customer_document_type' => 'cpf',
            'customer_phone' => '11999999999',
            'zipcode' => '01001000',
            'street' => 'Rua A',
            'number' => '100',
            'neighborhood' => 'Centro',
            'city' => 'São Paulo',
            'state' => 'SP',
            'recipient_name' => 'Maria Silva',
            'status' => 'delivery_completed',
            'current_step' => 'payment',
        ]);

        $paymentService = $this->createMock(PaytimePaymentService::class);
        $paymentService->expects($this->never())
            ->method('createCreditCardPayment');
        $this->app->instance(PaytimePaymentService::class, $paymentService);

        $response = $this->postJson('/checkout/session/'.$session->session_token.'/payment/checkout', [
            'payment_method' => 'credit_card',
            'card' => [
                'holder_name' => 'Maria Silva',
                'holder_document' => '12345678909',
                'card_number' => '4111111111111111',
                'expiration_month' => 12,
                'expiration_year' => now()->year + 1,
                'security_code' => '123',
            ],
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['installments']);
    }

    public function test_credit_card_payment_rejects_invalid_holder_document(): void
    {
        $seller = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($seller, $this->makeProduct($seller), [
            'allow_pix' => false,
            'allow_boleto' => false,
            'allow_credit_card' => true,
        ]);
        $session = $this->makeCheckoutSession($link, [
            'customer_name' => 'Maria Silva',
            'customer_email' => 'maria@example.com',
            'customer_document' => '12345678909',
            'customer_document_type' => 'cpf',
            'customer_phone' => '11999999999',
            'zipcode' => '01001000',
            'street' => 'Rua A',
            'number' => '100',
            'neighborhood' => 'Centro',
            'city' => 'São Paulo',
            'state' => 'SP',
            'recipient_name' => 'Maria Silva',
            'status' => 'delivery_completed',
            'current_step' => 'payment',
        ]);

        $response = $this->postJson('/checkout/session/'.$session->session_token.'/payment/checkout', [
            'payment_method' => 'credit_card',
            'installments' => 3,
            'card' => [
                'holder_name' => 'Maria Silva',
                'holder_document' => '111.111.111-11',
                'card_number' => '4111111111111111',
                'expiration_month' => 12,
                'expiration_year' => now()->year + 1,
                'security_code' => '123',
            ],
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['card.holder_document']);

        $errors = $response->json('errors');

        $this->assertSame('O documento do titular é inválido.', $errors['card.holder_document'][0] ?? null);
    }

    public function test_credit_card_payment_creates_order_with_installments(): void
    {
        $seller = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($seller, $this->makeProduct($seller), [
            'allow_pix' => false,
            'allow_boleto' => false,
            'allow_credit_card' => true,
        ]);
        $session = $this->makeCheckoutSession($link, [
            'customer_name' => 'Maria Silva',
            'customer_email' => 'maria@example.com',
            'customer_document' => '12345678909',
            'customer_document_type' => 'cpf',
            'customer_phone' => '11999999999',
            'zipcode' => '01001000',
            'street' => 'Rua A',
            'number' => '100',
            'neighborhood' => 'Centro',
            'city' => 'São Paulo',
            'state' => 'SP',
            'recipient_name' => 'Maria Silva',
            'status' => 'delivery_completed',
            'current_step' => 'payment',
        ]);

        $paymentService = $this->createMock(PaytimePaymentService::class);
        $paymentService->expects($this->once())
            ->method('createCreditCardPayment')
            ->with(
                $this->callback(function (Order $order) use ($session): bool {
                    return $order->checkout_session_id === $session->id
                        && $order->payment_method === 'credit_card';
                }),
                $this->callback(function (array $cardData): bool {
                    return $cardData['payment_method'] === 'credit_card'
                        && (int) $cardData['installments'] === 3
                        && ($cardData['card']['holder_name'] ?? null) === 'Maria Silva'
                        && ($cardData['card']['card_number'] ?? null) === '4111111111111111'
                        && (int) ($cardData['card']['expiration_month'] ?? 0) === 12;
                }),
            )
            ->willReturn([
                'gateway_transaction_id' => 'card-checkout-123',
                'gateway_status' => 'authorized',
                'internal_status' => 'authorized',
                'card_last_four' => '4242',
                'card_brand' => 'visa',
            ]);
        $this->app->instance(PaytimePaymentService::class, $paymentService);

        $response = $this->postJson('/checkout/session/'.$session->session_token.'/payment/checkout', [
            'payment_method' => 'credit_card',
            'installments' => 3,
            'card' => [
                'holder_name' => 'Maria Silva',
                'holder_document' => '12345678909',
                'card_number' => '4111111111111111',
                'expiration_month' => 12,
                'expiration_year' => now()->year + 1,
                'security_code' => '123',
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('payment_transaction.payment_method', 'credit_card');
        $response->assertJsonPath('payment_transaction.installments', 3);

        $transaction = PaymentTransaction::query()->latest('id')->first();

        $this->assertNotNull($transaction);
        $this->assertSame('credit_card', $transaction->payment_method);
        $this->assertSame(3, $transaction->installments);
        $this->assertArrayNotHasKey('card_number', $transaction->request_payload['card'] ?? []);
        $this->assertArrayNotHasKey('security_code', $transaction->request_payload['card'] ?? []);

        $this->assertDatabaseHas('payment_transactions', [
            'payment_method' => 'credit_card',
            'installments' => 3,
            'card_last_four' => '4242',
            'card_brand' => 'visa',
        ]);
    }

    public function test_credit_card_payment_requires_3ds_returns_challenge_payload(): void
    {
        $seller = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($seller, $this->makeProduct($seller), [
            'allow_pix' => false,
            'allow_boleto' => false,
            'allow_credit_card' => true,
        ]);
        $session = $this->makeCheckoutSession($link, [
            'customer_name' => 'Maria Silva',
            'customer_email' => 'maria@example.com',
            'customer_document' => '12345678909',
            'customer_document_type' => 'cpf',
            'customer_phone' => '11999999999',
            'zipcode' => '01001000',
            'street' => 'Rua A',
            'number' => '100',
            'neighborhood' => 'Centro',
            'city' => 'São Paulo',
            'state' => 'SP',
            'recipient_name' => 'Maria Silva',
            'status' => 'delivery_completed',
            'current_step' => 'payment',
        ]);

        $paymentService = $this->createMock(PaytimePaymentService::class);
        $paymentService->expects($this->once())
            ->method('createCreditCardPayment')
            ->willReturn([
                'gateway_transaction_id' => 'card-checkout-3ds-123',
                'gateway_status' => 'PENDING',
                'internal_status' => 'pending',
                'card_last_four' => '4242',
                'card_brand' => 'visa',
                'requires_3ds' => true,
                'session_id' => '3DS_SESSION_123',
                'transaction_id' => 'card-checkout-3ds-123',
                'message' => 'Transação criada, aguardando autenticação 3DS.',
                'api_transaction' => [
                    '_id' => 'card-checkout-3ds-123',
                    'status' => 'PENDING',
                    'antifraud' => [
                        [
                            'analyse_required' => 'THREEDS',
                            'analyse_status' => 'WAITING_AUTH',
                            'session' => '3DS_SESSION_123',
                        ],
                    ],
                ],
            ]);
        $this->app->instance(PaytimePaymentService::class, $paymentService);

        $response = $this->postJson('/checkout/session/'.$session->session_token.'/payment/checkout', [
            'payment_method' => 'credit_card',
            'installments' => 3,
            'card' => [
                'holder_name' => 'Maria Silva',
                'holder_document' => '12345678909',
                'card_number' => '4111111111111111',
                'expiration_month' => 12,
                'expiration_year' => now()->year + 1,
                'security_code' => '123',
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('requires_3ds', true);
        $response->assertJsonPath('session_id', '3DS_SESSION_123');
        $response->assertJsonPath('payment_transaction.internal_status', 'pending');
        $this->assertDatabaseHas('payment_transactions', [
            'payment_method' => 'credit_card',
            'gateway_transaction_id' => 'card-checkout-3ds-123',
            'internal_status' => 'pending',
        ]);
    }

    public function test_public_checkout_can_confirm_credit_card_3ds_payment(): void
    {
        $seller = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($seller, $this->makeProduct($seller), [
            'allow_pix' => false,
            'allow_boleto' => false,
            'allow_credit_card' => true,
        ]);
        $session = $this->makeCheckoutSession($link, [
            'customer_name' => 'Maria Silva',
            'customer_email' => 'maria@example.com',
            'customer_document' => '12345678909',
            'customer_document_type' => 'cpf',
            'customer_phone' => '11999999999',
            'zipcode' => '01001000',
            'street' => 'Rua A',
            'number' => '100',
            'neighborhood' => 'Centro',
            'city' => 'São Paulo',
            'state' => 'SP',
            'recipient_name' => 'Maria Silva',
            'status' => 'delivery_completed',
            'current_step' => 'payment',
        ]);

        $paymentService = $this->createMock(PaytimePaymentService::class);
        $paymentService->expects($this->once())
            ->method('createCreditCardPayment')
            ->willReturn([
                'gateway_transaction_id' => 'card-checkout-3ds-123',
                'gateway_status' => 'PENDING',
                'internal_status' => 'pending',
                'card_last_four' => '4242',
                'card_brand' => 'visa',
                'requires_3ds' => true,
                'session_id' => '3DS_SESSION_123',
                'transaction_id' => 'card-checkout-3ds-123',
                'message' => 'Transação criada, aguardando autenticação 3DS.',
                'api_transaction' => [
                    '_id' => 'card-checkout-3ds-123',
                    'status' => 'PENDING',
                    'antifraud' => [
                        [
                            'analyse_required' => 'THREEDS',
                            'analyse_status' => 'WAITING_AUTH',
                            'session' => '3DS_SESSION_123',
                        ],
                    ],
                ],
            ]);
        $paymentService->expects($this->once())
            ->method('confirmCreditCard3ds')
            ->with(
                'card-checkout-3ds-123',
                $this->callback(function (array $authData): bool {
                    return $authData['id'] === '3DS_123'
                        && $authData['status'] === 'AUTH_FLOW_COMPLETED'
                        && $authData['authentication_status'] === 'AUTHENTICATED';
                }),
            )
            ->willReturn([
                'gateway_transaction_id' => 'card-checkout-3ds-123',
                'gateway_status' => 'AUTHORIZED',
                'internal_status' => 'authorized',
                'api_transaction' => [
                    '_id' => 'card-checkout-3ds-123',
                    'status' => 'AUTHORIZED',
                ],
            ]);
        $this->app->instance(PaytimePaymentService::class, $paymentService);

        $paymentResponse = $this->postJson('/checkout/session/'.$session->session_token.'/payment/checkout', [
            'payment_method' => 'credit_card',
            'installments' => 3,
            'card' => [
                'holder_name' => 'Maria Silva',
                'holder_document' => '12345678909',
                'card_number' => '4111111111111111',
                'expiration_month' => 12,
                'expiration_year' => now()->year + 1,
                'security_code' => '123',
            ],
        ]);

        $paymentResponse->assertOk();

        $authResponse = $this->postJson(route('checkout.public.payment.antifraud-auth', [
            'sessionToken' => $session->session_token,
            'transactionId' => 'card-checkout-3ds-123',
        ]), [
            'id' => '3DS_123',
            'status' => 'AUTH_FLOW_COMPLETED',
            'authentication_status' => 'AUTHENTICATED',
        ]);

        $authResponse->assertOk();
        $authResponse->assertJsonPath('payment_transaction.internal_status', 'authorized');
        $this->assertDatabaseHas('orders', [
            'checkout_session_id' => $session->id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('payment_transactions', [
            'gateway_transaction_id' => 'card-checkout-3ds-123',
            'internal_status' => 'authorized',
        ]);
    }

    public function test_identification_is_saved(): void
    {
        $seller = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($seller, $this->makeProduct($seller));
        $session = $this->makeCheckoutSession($link);

        $response = $this->postJson('/checkout/session/'.$session->session_token.'/identification', [
            'customer_name' => 'Maria Silva',
            'customer_email' => 'maria@example.com',
            'customer_document' => '123.456.789-09',
            'customer_document_type' => 'cpf',
            'customer_birth_date' => '1990-01-01',
            'customer_phone' => '11999999999',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('checkout_sessions', [
            'id' => $session->id,
            'customer_name' => 'Maria Silva',
            'recipient_name' => 'Maria Silva',
            'status' => 'identification_completed',
            'current_step' => 'delivery',
        ]);
    }

    public function test_identification_rejects_invalid_cpf(): void
    {
        $seller = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($seller, $this->makeProduct($seller));
        $session = $this->makeCheckoutSession($link);

        $response = $this->postJson('/checkout/session/'.$session->session_token.'/identification', [
            'customer_name' => 'Maria Silva',
            'customer_email' => 'maria@example.com',
            'customer_document' => '111.111.111-11',
            'customer_document_type' => 'cpf',
            'customer_phone' => '11999999999',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['customer_document'])
            ->assertJsonPath('errors.customer_document.0', 'O CPF informado é inválido.');

        $this->assertDatabaseMissing('checkout_sessions', [
            'id' => $session->id,
            'customer_name' => 'Maria Silva',
            'status' => 'identification_completed',
        ]);
    }

    public function test_identification_is_saved_for_cnpj(): void
    {
        $seller = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($seller, $this->makeProduct($seller));
        $session = $this->makeCheckoutSession($link);

        $response = $this->postJson('/checkout/session/'.$session->session_token.'/identification', [
            'customer_name' => 'Empresa Exemplo LTDA',
            'customer_email' => 'financeiro@empresaexemplo.test',
            'customer_company_name' => 'Empresa Exemplo LTDA',
            'customer_document' => '04.252.011/0001-10',
            'customer_document_type' => 'cnpj',
            'customer_responsible_document' => '123.456.789-09',
            'customer_responsible_birth_date' => '1990-01-01',
            'customer_phone' => '11999999999',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('checkout_sessions', [
            'id' => $session->id,
            'customer_name' => 'Empresa Exemplo LTDA',
            'customer_company_name' => 'Empresa Exemplo LTDA',
            'customer_responsible_document' => '123.456.789-09',
            'customer_document_type' => 'cnpj',
            'status' => 'identification_completed',
            'current_step' => 'delivery',
        ]);
    }

    public function test_identification_rejects_missing_required_pj_fields(): void
    {
        $seller = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($seller, $this->makeProduct($seller));
        $session = $this->makeCheckoutSession($link);

        $response = $this->postJson('/checkout/session/'.$session->session_token.'/identification', [
            'customer_name' => 'Empresa Exemplo LTDA',
            'customer_email' => 'financeiro@empresaexemplo.test',
            'customer_company_name' => 'Empresa Exemplo LTDA',
            'customer_document' => '04.252.011/0001-10',
            'customer_document_type' => 'cnpj',
            'customer_phone' => '11999999999',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'customer_responsible_document',
                'customer_responsible_birth_date',
            ]);

        $this->assertDatabaseMissing('checkout_sessions', [
            'id' => $session->id,
            'customer_company_name' => 'Empresa Exemplo LTDA',
            'status' => 'identification_completed',
        ]);
    }

    public function test_cnpj_lookup_returns_company_data(): void
    {
        Http::fake([
            'brasilapi.com.br/api/cnpj/v1/*' => Http::response([
                'razao_social' => 'Empresa Exemplo LTDA',
                'nome_fantasia' => 'Empresa Exemplo',
                'email' => '[email protected]',
                'ddd_telefone_1' => '11988887777',
                'qsa' => [
                    [
                        'nome_socio' => 'João da Silva',
                        'cnpj_cpf_do_socio' => '123.456.789-09',
                    ],
                ],
            ], 200),
        ]);

        $response = $this->getJson('/checkout/cnpj/04252011000110');

        $response->assertOk();
        $response->assertJsonPath('cnpj', '04252011000110');
        $response->assertJsonPath('company_name', 'Empresa Exemplo LTDA');
        $response->assertJsonPath('email', '[email protected]');
        $response->assertJsonPath('phone', '11988887777');
        $response->assertJsonPath('responsible_name', 'João da Silva');
        $response->assertJsonPath('responsible_document', '12345678909');
        $response->assertJsonPath('trade_name', 'Empresa Exemplo');
    }

    public function test_identification_rejects_invalid_cnpj(): void
    {
        $seller = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($seller, $this->makeProduct($seller));
        $session = $this->makeCheckoutSession($link);

        $response = $this->postJson('/checkout/session/'.$session->session_token.'/identification', [
            'customer_name' => 'Empresa Exemplo LTDA',
            'customer_email' => 'financeiro@empresaexemplo.test',
            'customer_company_name' => 'Empresa Exemplo LTDA',
            'customer_document' => '11.111.111/1111-11',
            'customer_document_type' => 'cnpj',
            'customer_phone' => '11999999999',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['customer_document'])
            ->assertJsonPath('errors.customer_document.0', 'O CNPJ informado é inválido.');

        $this->assertDatabaseMissing('checkout_sessions', [
            'id' => $session->id,
            'customer_company_name' => 'Empresa Exemplo LTDA',
            'status' => 'identification_completed',
        ]);
    }

    public function test_delivery_is_saved(): void
    {
        $seller = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($seller, $this->makeProduct($seller));
        $session = $this->makeCheckoutSession($link);

        $response = $this->postJson('/checkout/session/'.$session->session_token.'/delivery', [
            'zipcode' => '01001000',
            'street' => 'Rua A',
            'number' => '100',
            'complement' => 'Apto 1',
            'neighborhood' => 'Centro',
            'city' => 'São Paulo',
            'state' => 'SP',
            'recipient_name' => 'Maria Silva',
        ]);

        $response->assertOk();
        $response->assertJsonPath('payment_url', route('checkout.public.payment.page', $session->session_token));
        $this->assertDatabaseHas('checkout_sessions', [
            'id' => $session->id,
            'zipcode' => '01001000',
            'street' => 'Rua A',
            'status' => 'delivery_completed',
            'current_step' => 'payment',
        ]);
    }

    public function test_delivery_is_saved_without_complement_or_recipient_name(): void
    {
        $seller = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($seller, $this->makeProduct($seller));
        $session = $this->makeCheckoutSession($link, [
            'customer_name' => 'Maria Silva',
            'recipient_name' => null,
        ]);

        $response = $this->postJson('/checkout/session/'.$session->session_token.'/delivery', [
            'zipcode' => '01001000',
            'street' => 'Rua A',
            'number' => '100',
            'neighborhood' => 'Centro',
            'city' => 'São Paulo',
            'state' => 'SP',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('checkout_sessions', [
            'id' => $session->id,
            'zipcode' => '01001000',
            'street' => 'Rua A',
            'complement' => null,
            'recipient_name' => 'Maria Silva',
            'status' => 'delivery_completed',
            'current_step' => 'payment',
        ]);
    }

    public function test_pix_payment_creates_order_and_transaction(): void
    {
        $seller = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($seller, $this->makeProduct($seller));
        $session = $this->makeCheckoutSession($link, [
            'customer_name' => 'Maria Silva',
            'customer_email' => 'maria@example.com',
            'customer_document' => '12345678909',
            'customer_document_type' => 'cpf',
            'customer_phone' => '11999999999',
            'zipcode' => '01001000',
            'street' => 'Rua A',
            'number' => '100',
            'neighborhood' => 'Centro',
            'city' => 'São Paulo',
            'state' => 'SP',
            'recipient_name' => 'Maria Silva',
            'status' => 'delivery_completed',
            'current_step' => 'payment',
        ]);
        $this->mockPixPaymentService();

        $response = $this->postJson('/checkout/session/'.$session->session_token.'/payment/checkout', [
            'payment_method' => 'pix',
            'total' => 1.00,
            'unit_price' => 1.00,
            'installments' => 1,
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('orders', [
            'checkout_session_id' => $session->id,
            'payment_method' => 'pix',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('payment_transactions', [
            'payment_method' => 'pix',
            'gateway' => 'paytime',
            'internal_status' => 'pending',
            'gateway_transaction_id' => 'pix-checkout-123',
            'pix_copy_paste' => '00020126580014br.gov.bcb.pix...',
        ]);
    }

    public function test_pix_payment_uses_api_transaction_id_when_gateway_transaction_id_is_missing(): void
    {
        $seller = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($seller, $this->makeProduct($seller));
        $session = $this->makeCheckoutSession($link, [
            'customer_name' => 'Maria Silva',
            'customer_email' => 'maria@example.com',
            'customer_document' => '12345678909',
            'customer_document_type' => 'cpf',
            'customer_phone' => '11999999999',
            'zipcode' => '01001000',
            'street' => 'Rua A',
            'number' => '100',
            'neighborhood' => 'Centro',
            'city' => 'São Paulo',
            'state' => 'SP',
            'recipient_name' => 'Maria Silva',
            'status' => 'delivery_completed',
            'current_step' => 'payment',
        ]);

        $paymentService = $this->createMock(PaytimePaymentService::class);
        $paymentService->expects($this->once())
            ->method('createPixPayment')
            ->willReturn([
                'gateway_status' => 'PENDING',
                'internal_status' => 'pending',
                'pix_qr_code' => '00020126580014br.gov.bcb.pix...',
                'pix_copy_paste' => '00020126580014br.gov.bcb.pix...',
                'api_transaction' => [
                    '_id' => 'pix-checkout-456',
                    'status' => 'PENDING',
                ],
            ]);

        $this->app->instance(PaytimePaymentService::class, $paymentService);

        $response = $this->postJson('/checkout/session/'.$session->session_token.'/payment/checkout', [
            'payment_method' => 'pix',
            'installments' => 1,
        ]);

        $response->assertOk();
        $this->assertSame('pix-checkout-456', $response->json('payment_transaction.gateway_transaction_id'));
        $this->assertDatabaseHas('payment_transactions', [
            'payment_method' => 'pix',
            'gateway' => 'paytime',
            'gateway_transaction_id' => 'pix-checkout-456',
        ]);
    }

    public function test_boleto_payment_creates_order_and_transaction(): void
    {
        $seller = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($seller, $this->makeProduct($seller), [
            'allow_pix' => false,
            'allow_boleto' => true,
            'allow_credit_card' => false,
        ]);
        $session = $this->makeCheckoutSession($link, [
            'customer_name' => 'Maria Silva',
            'customer_email' => 'maria@example.com',
            'customer_document' => '12345678909',
            'customer_document_type' => 'cpf',
            'customer_phone' => '11999999999',
            'zipcode' => '01001000',
            'street' => 'Rua A',
            'number' => '100',
            'neighborhood' => 'Centro',
            'city' => 'São Paulo',
            'state' => 'SP',
            'recipient_name' => 'Maria Silva',
            'status' => 'delivery_completed',
            'current_step' => 'payment',
        ]);
        $this->mockBoletoPaymentService();

        $response = $this->postJson('/checkout/session/'.$session->session_token.'/payment/checkout', [
            'payment_method' => 'boleto',
            'installments' => 1,
        ]);

        $response->assertOk();

        $this->assertSame('boleto', $response->json('order.payment_method'));
        $this->assertNotEmpty($response->json('payment_transaction.boleto_url'));
        $this->assertNotEmpty($response->json('payment_transaction.boleto_digitable_line'));
        $this->assertDatabaseHas('payment_transactions', [
            'payment_method' => 'boleto',
            'gateway' => 'paytime',
            'internal_status' => 'pending',
        ]);

        $pageResponse = $this->get(route('checkout.public.payment.details', $session->session_token));

        $pageResponse->assertOk();
        $pageResponse->assertSee('Seu boleto');
        $pageResponse->assertSee('data-boleto-block', false);
        $pageResponse->assertDontSee('Abrir boleto');
        $pageResponse->assertSee('ABRIR BOLETO', false);
        $pageResponse->assertSee('Linha digitável', false);
        $pageResponse->assertSee('Código de barras', false);
        $pageResponse->assertSee('Pix (copia e cola)', false);
        $pageResponse->assertSee('data-boleto-barcode', false);
        $pageResponse->assertSee('data-boleto-digitable-line', false);
        $pageResponse->assertSee('data-boleto-pix-copy-paste', false);
    }

    public function test_public_checkout_link_remains_accessible_after_payment_selection(): void
    {
        $seller = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($seller, $this->makeProduct($seller), [
            'allow_pix' => true,
            'allow_boleto' => true,
            'allow_credit_card' => true,
        ]);
        $session = $this->makeCheckoutSession($link, [
            'customer_name' => 'Maria Silva',
            'customer_email' => 'maria@example.com',
            'customer_document' => '12345678909',
            'customer_document_type' => 'cpf',
            'customer_phone' => '11999999999',
            'zipcode' => '01001000',
            'street' => 'Rua A',
            'number' => '100',
            'neighborhood' => 'Centro',
            'city' => 'São Paulo',
            'state' => 'SP',
            'recipient_name' => 'Maria Silva',
            'status' => 'delivery_completed',
            'current_step' => 'payment',
            'payment_method' => 'pix',
        ]);

        $response = $this->get(route('checkout.public.show', $link->public_token));

        $response->assertOk();
        $response->assertSee('Dados pessoais', false);
        $response->assertSee('Endereço', false);
        $response->assertDontSee('Aguardando confirmação', false);
        $response->assertDontSee('Seu boleto', false);
    }

    public function test_boleto_payment_rejects_invalid_document_before_gateway_call(): void
    {
        $seller = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($seller, $this->makeProduct($seller), [
            'allow_pix' => false,
            'allow_boleto' => true,
            'allow_credit_card' => false,
        ]);
        $session = $this->makeCheckoutSession($link, [
            'customer_name' => 'Professor Prado',
            'customer_email' => 'profpradoif@gmail.com',
            'customer_document' => '11111111111',
            'customer_document_type' => 'cpf',
            'customer_phone' => '11911112222',
            'zipcode' => '07174000',
            'street' => 'Avenida Papa João Paulo I',
            'number' => '1000',
            'neighborhood' => 'Jardim Presidente Dutra',
            'city' => 'Guarulhos',
            'state' => 'SP',
            'recipient_name' => 'Professor Prado',
            'status' => 'delivery_completed',
            'current_step' => 'payment',
        ]);

        $paymentService = $this->createMock(PaytimePaymentService::class);
        $paymentService->expects($this->never())
            ->method('createBoletoPayment');
        $this->app->instance(PaytimePaymentService::class, $paymentService);

        $response = $this->postJson('/checkout/session/'.$session->session_token.'/payment/checkout', [
            'payment_method' => 'boleto',
            'installments' => 1,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('message', 'O documento do pagador é inválido.');
    }

    public function test_boleto_payment_redirects_back_with_flash_error_when_document_is_invalid(): void
    {
        $seller = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($seller, $this->makeProduct($seller), [
            'allow_pix' => false,
            'allow_boleto' => true,
            'allow_credit_card' => false,
        ]);
        $session = $this->makeCheckoutSession($link, [
            'customer_name' => 'Professor Prado',
            'customer_email' => 'profpradoif@gmail.com',
            'customer_document' => '11111111111',
            'customer_document_type' => 'cpf',
            'customer_phone' => '11911112222',
            'zipcode' => '07174000',
            'street' => 'Avenida Papa João Paulo I',
            'number' => '1000',
            'neighborhood' => 'Jardim Presidente Dutra',
            'city' => 'Guarulhos',
            'state' => 'SP',
            'recipient_name' => 'Professor Prado',
            'status' => 'delivery_completed',
            'current_step' => 'payment',
        ]);

        $this->post(route('checkout.public.payment.choose', $session->session_token), [
            'payment_method' => 'boleto',
        ])->assertRedirect(route('checkout.public.payment.details', $session->session_token));

        $response = $this->followingRedirects()
            ->from(route('checkout.public.payment.details', $session->session_token))
            ->post(route('checkout.public.payment', $session->session_token), [
                'payment_method' => 'boleto',
                'installments' => 1,
            ]);

        $response->assertSee('O documento do pagador é inválido.', false);
        $this->assertDatabaseMissing('payment_transactions', [
            'payment_method' => 'boleto',
            'checkout_session_id' => $session->id,
        ]);
    }

    public function test_boleto_payment_surfaces_gateway_validation_error_when_transaction_id_is_missing(): void
    {
        $seller = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($seller, $this->makeProduct($seller), [
            'allow_pix' => false,
            'allow_boleto' => true,
            'allow_credit_card' => false,
        ]);
        $session = $this->makeCheckoutSession($link, [
            'customer_name' => 'Reginaldo do Prado',
            'customer_email' => 'reginaldo@example.com',
            'customer_document' => '12345678909',
            'customer_document_type' => 'cpf',
            'customer_phone' => '11989013858',
            'zipcode' => '07096000',
            'street' => 'Avenida Suplicy',
            'number' => '519',
            'complement' => 'Sala 01',
            'neighborhood' => 'Jardim Santa Mena',
            'city' => 'Guarulhos',
            'state' => 'SP',
            'recipient_name' => 'Reginaldo do Prado',
            'status' => 'delivery_completed',
            'current_step' => 'payment',
        ]);

        $paymentService = $this->createMock(PaytimePaymentService::class);
        $paymentService->expects($this->once())
            ->method('createBoletoPayment')
            ->willReturn([
                'gateway_status' => 'FAILED',
                'internal_status' => 'failed',
                'api_boleto' => [
                    'message' => 'Data limite de desconto deve ser menor que a data de vencimento',
                    'status' => 403,
                    'code' => 'BNK000143',
                    'boleto_url' => null,
                    'boleto_barcode' => null,
                    'boleto_digitable_line' => null,
                ],
            ]);

        $this->app->instance(PaytimePaymentService::class, $paymentService);

        $response = $this->postJson('/checkout/session/'.$session->session_token.'/payment/checkout', [
            'payment_method' => 'boleto',
            'installments' => 1,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('message', 'Data limite de desconto deve ser menor que a data de vencimento')
            ->assertJsonPath('paytime_response.api_boleto.code', 'BNK000143');

        $this->assertDatabaseCount('payment_transactions', 0);
    }

    public function test_checkout_status_refreshes_incomplete_boleto_details(): void
    {
        $seller = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($seller, $this->makeProduct($seller), [
            'allow_pix' => false,
            'allow_boleto' => true,
            'allow_credit_card' => false,
        ]);
        $session = $this->makeCheckoutSession($link, [
            'customer_name' => 'Maria Silva',
            'customer_email' => 'maria@example.com',
            'customer_document' => '12345678909',
            'customer_document_type' => 'cpf',
            'customer_phone' => '11999999999',
            'zipcode' => '01001000',
            'street' => 'Rua A',
            'number' => '100',
            'neighborhood' => 'Centro',
            'city' => 'São Paulo',
            'state' => 'SP',
            'recipient_name' => 'Maria Silva',
            'status' => 'delivery_completed',
            'current_step' => 'payment',
        ]);

        $paymentService = $this->createMock(PaytimePaymentService::class);
        $paymentService->expects($this->once())
            ->method('createBoletoPayment')
            ->willReturn([
                'gateway_transaction_id' => 'boleto-checkout-123',
                'gateway_status' => 'PROCESSING',
                'internal_status' => 'pending',
                'boleto_url' => null,
                'boleto_barcode' => null,
                'boleto_digitable_line' => null,
                'api_boleto' => [
                    '_id' => 'boleto-checkout-123',
                    'status' => 'PROCESSING',
                ],
            ]);
        $paymentService->expects($this->once())
            ->method('refreshBoletoPayment')
            ->with('boleto-checkout-123')
            ->willReturn([
                'gateway_transaction_id' => 'boleto-checkout-123',
                'gateway_status' => 'PROCESSING',
                'internal_status' => 'pending',
                'boleto_url' => 'https://example.test/boleto.pdf',
                'boleto_barcode' => '12345678901234567890123456789012345678901234',
                'boleto_digitable_line' => '23793.38128 60000.000000 01000.000000 1 98760000002000',
                'api_boleto' => [
                    '_id' => 'boleto-checkout-123',
                    'status' => 'PROCESSING',
                    'url' => 'https://example.test/boleto.pdf',
                ],
            ]);
        $this->app->instance(PaytimePaymentService::class, $paymentService);

        $this->postJson('/checkout/session/'.$session->session_token.'/payment/checkout', [
            'payment_method' => 'boleto',
            'installments' => 1,
        ])->assertOk();

        $statusResponse = $this->getJson('/checkout/session/'.$session->session_token.'/status');

        $statusResponse
            ->assertOk()
            ->assertJsonPath('payment_transaction.boleto_url', 'https://example.test/boleto.pdf')
            ->assertJsonPath('payment_transaction.boleto_digitable_line', '23793.38128 60000.000000 01000.000000 1 98760000002000');
    }

    public function test_paytime_webhook_approves_order(): void
    {
        config()->set('services.paytime.webhook_user', 'webhook-user');
        config()->set('services.paytime.webhook_pass', 'webhook-pass');

        $seller = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($seller, $this->makeProduct($seller));
        $session = $this->makeCheckoutSession($link, [
            'customer_name' => 'Maria Silva',
            'customer_email' => 'maria@example.com',
            'customer_document' => '12345678909',
            'customer_document_type' => 'cpf',
            'customer_phone' => '11999999999',
            'zipcode' => '01001000',
            'street' => 'Rua A',
            'number' => '100',
            'neighborhood' => 'Centro',
            'city' => 'São Paulo',
            'state' => 'SP',
            'recipient_name' => 'Maria Silva',
            'status' => 'delivery_completed',
            'current_step' => 'payment',
        ]);
        $this->mockPixPaymentService();

        $paymentResponse = $this->postJson('/checkout/session/'.$session->session_token.'/payment/checkout', [
            'payment_method' => 'pix',
            'installments' => 1,
        ]);

        $orderNumber = $paymentResponse->json('order.order_number');
        $transactionId = $paymentResponse->json('payment_transaction.gateway_transaction_id');

        $response = $this
            ->withBasicAuth('webhook-user', 'webhook-pass')
            ->postJson('/api/webhook/paytime', [
                'event' => 'payment-approved',
                'order_number' => $orderNumber,
                'gateway_transaction_id' => $transactionId,
                'status' => 'PAID',
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('orders', [
            'order_number' => $orderNumber,
            'status' => 'paid',
        ]);

        $this->assertDatabaseHas('payment_transactions', [
            'gateway_transaction_id' => $transactionId,
            'internal_status' => 'paid',
        ]);

        $this->assertDatabaseHas('checkout_sessions', [
            'id' => $session->id,
            'status' => 'paid',
            'current_step' => 'confirmation',
        ]);
    }

    public function test_duplicate_webhook_does_not_duplicate_approval_event(): void
    {
        config()->set('services.paytime.webhook_user', 'webhook-user');
        config()->set('services.paytime.webhook_pass', 'webhook-pass');

        $seller = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($seller, $this->makeProduct($seller));
        $session = $this->makeCheckoutSession($link, [
            'customer_name' => 'Maria Silva',
            'customer_email' => 'maria@example.com',
            'customer_document' => '12345678909',
            'customer_document_type' => 'cpf',
            'customer_phone' => '11999999999',
            'zipcode' => '01001000',
            'street' => 'Rua A',
            'number' => '100',
            'neighborhood' => 'Centro',
            'city' => 'São Paulo',
            'state' => 'SP',
            'recipient_name' => 'Maria Silva',
            'status' => 'delivery_completed',
            'current_step' => 'payment',
        ]);
        $this->mockPixPaymentService();

        $paymentResponse = $this->postJson('/checkout/session/'.$session->session_token.'/payment/checkout', [
            'payment_method' => 'pix',
            'installments' => 1,
        ]);

        $orderNumber = $paymentResponse->json('order.order_number');
        $transactionId = $paymentResponse->json('payment_transaction.gateway_transaction_id');

        $payload = [
            'event' => 'payment-approved',
            'order_number' => $orderNumber,
            'gateway_transaction_id' => $transactionId,
            'status' => 'PAID',
        ];

        $this->withBasicAuth('webhook-user', 'webhook-pass')->postJson('/api/webhook/paytime', $payload)->assertOk();
        $this->withBasicAuth('webhook-user', 'webhook-pass')->postJson('/api/webhook/paytime', $payload)->assertOk();

        $this->assertSame(1, CheckoutEvent::query()->where('event_type', 'payment_approved')->count());
        $this->assertDatabaseHas('orders', [
            'order_number' => $orderNumber,
            'status' => 'paid',
        ]);
    }

    public function test_checkout_is_marked_as_abandoned(): void
    {
        $seller = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($seller, $this->makeProduct($seller));
        $session = $this->makeCheckoutSession($link, [
            'status' => 'payment_started',
            'current_step' => 'payment',
            'last_activity_at' => now()->subMinutes(31),
        ]);

        $this->artisan('checkout:mark-abandoned')->assertExitCode(0);

        $this->assertDatabaseHas('checkout_sessions', [
            'id' => $session->id,
            'status' => 'abandoned',
        ]);
    }

    public function test_recovery_is_not_sent_for_paid_order(): void
    {
        $seller = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($seller, $this->makeProduct($seller));
        $session = $this->makeCheckoutSession($link, [
            'customer_email' => 'maria@example.com',
            'status' => 'abandoned',
            'current_step' => 'payment',
            'last_activity_at' => now()->subMinutes(31),
        ]);

        Order::query()->create([
            'seller_id' => $link->seller_id,
            'checkout_link_id' => $link->id,
            'checkout_session_id' => $session->id,
            'product_id' => $link->product_id,
            'order_number' => 'JNT-2026-000001',
            'status' => 'paid',
            'customer_name' => 'Maria Silva',
            'customer_email' => 'maria@example.com',
            'customer_document' => '12345678909',
            'customer_phone' => '11999999999',
            'quantity' => 1,
            'unit_price' => 100.00,
            'subtotal' => 100.00,
            'discount_total' => 0,
            'shipping_total' => 0,
            'total' => 100.00,
            'payment_method' => 'pix',
        ]);

        $this->artisan('checkout:send-recovery-messages')->assertExitCode(0);

        $this->assertDatabaseHas('abandoned_checkout_recoveries', [
            'checkout_session_id' => $session->id,
            'status' => 'skipped',
        ]);

        $this->assertDatabaseMissing('abandoned_checkout_recoveries', [
            'checkout_session_id' => $session->id,
            'status' => 'sent',
        ]);
    }

    private function mockPixPaymentService(): void
    {
        $paymentService = $this->createMock(PaytimePaymentService::class);
        $paymentService->expects($this->once())
            ->method('createPixPayment')
            ->willReturn([
                'gateway_transaction_id' => 'pix-checkout-123',
                'gateway_status' => 'PENDING',
                'internal_status' => 'pending',
                'pix_qr_code' => '00020126580014br.gov.bcb.pix...',
                'pix_copy_paste' => '00020126580014br.gov.bcb.pix...',
                'pix_qr_code_image' => 'data:image/png;base64,ZmFrZQ==',
                'pix_expires_at' => now()->addHour()->toDateTimeString(),
                'api_transaction' => [
                    '_id' => 'pix-checkout-123',
                    'status' => 'PENDING',
                ],
                'api_qrcode' => [
                    'qrcode' => 'data:image/png;base64,ZmFrZQ==',
                    'emv' => '00020126580014br.gov.bcb.pix...',
                ],
            ]);

        $this->app->instance(PaytimePaymentService::class, $paymentService);
    }

    private function mockBoletoPaymentService(): void
    {
        $paymentService = $this->createMock(PaytimePaymentService::class);
        $paymentService->expects($this->once())
            ->method('createBoletoPayment')
            ->willReturn([
                'gateway_transaction_id' => null,
                'gateway_status' => 'PROCESSING',
                'internal_status' => 'pending',
                'boleto_url' => 'https://example.test/boleto.pdf',
                'boleto_barcode' => '12345678901234567890123456789012345678901234',
                'boleto_digitable_line' => '23793.38128 60000.000000 01000.000000 1 98760000002000',
                'api_boleto' => [
                    '_id' => 'boleto-checkout-123',
                    'status' => 'PROCESSING',
                ],
            ]);

        $this->app->instance(PaytimePaymentService::class, $paymentService);
    }

    private function makeVendorUser(): User
    {
        $user = User::factory()->create([
            'nivel_acesso' => 'vendedor',
            'email_verified_at' => now(),
        ]);

        $user->vendedor()->create([
            'estabelecimento_id' => (string) random_int(100000, 999999),
            'sub_nivel' => 'admin_loja',
            'status' => 'ativo',
            'must_change_password' => false,
        ]);

        return $user;
    }

    private function makeProduct(User $seller, array $overrides = []): Product
    {
        return Product::query()->create(array_merge([
            'seller_id' => $seller->id,
            'name' => 'Produto '.random_int(1, 9999),
            'slug' => 'produto-'.random_int(1, 9999),
            'description' => 'Descrição do produto',
            'short_description' => 'Resumo',
            'sku' => 'SKU-'.random_int(1, 9999),
            'price' => 100.00,
            'status' => 'active',
        ], $overrides));
    }

    private function makeCheckoutLink(User $seller, Product $product, array $overrides = []): CheckoutLink
    {
        $quantity = $overrides['quantity'] ?? 1;
        $unitPrice = $overrides['unit_price'] ?? 100.00;

        return CheckoutLink::query()->create(array_merge([
            'seller_id' => $seller->id,
            'product_id' => $product->id,
            'public_token' => CheckoutLink::generatePublicToken(),
            'name' => 'Link '.random_int(1, 9999),
            'status' => 'active',
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => round($quantity * $unitPrice, 2),
            'allow_pix' => true,
            'allow_boleto' => true,
            'allow_credit_card' => true,
            'pix_discount_type' => 'none',
            'pix_discount_value' => 0,
            'boleto_discount_type' => 'none',
            'boleto_discount_value' => 0,
            'free_shipping' => true,
            'visual_config' => [
                'store_name' => 'Loja Teste',
                'primary_color' => '#111827',
            ],
        ], $overrides));
    }

    private function makeCheckoutSession(CheckoutLink $link, array $overrides = []): CheckoutSession
    {
        return CheckoutSession::query()->create(array_merge([
            'checkout_link_id' => $link->id,
            'seller_id' => $link->seller_id,
            'product_id' => $link->product_id,
            'session_token' => CheckoutSession::generateSessionToken(),
            'status' => 'started',
            'current_step' => 'identification',
            'subtotal' => $link->total_price,
            'discount_total' => 0,
            'shipping_total' => 0,
            'total' => $link->total_price,
            'last_activity_at' => now(),
        ], $overrides));
    }
}
