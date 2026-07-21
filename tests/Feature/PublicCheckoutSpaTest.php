<?php

namespace Tests\Feature;

use App\Models\CheckoutLink;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicCheckoutSpaTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_checkout_link_response_includes_the_spa_url(): void
    {
        $user = $this->makeVendorUser();
        $product = $this->makeProduct($user);

        $response = $this->actingAs($user)->postJson('/seller/checkout-links', [
            'product_id' => $product->id,
            'name' => 'Oferta principal',
            'status' => 'active',
            'quantity' => 1,
            'unit_price' => 149.90,
            'allow_pix' => true,
            'allow_boleto' => true,
            'allow_credit_card' => true,
            'request_address' => true,
            'pix_discount_type' => 'none',
            'pix_discount_value' => 0,
            'boleto_discount_type' => 'none',
            'boleto_discount_value' => 0,
            'free_shipping' => true,
            'visual_config' => [
                'store_name' => 'Loja Teste',
                'theme' => 'noir',
                'primary_color' => '#111827',
                'navbar_background_color' => '#ffffff',
            ],
        ]);

        $response->assertCreated();
        $publicToken = $response->json('checkout_link.public_token');

        $this->assertSame(route('checkout.public.spa.show', $publicToken), $response->json('checkout_link.public_spa_url'));
        $this->assertSame('noir', $response->json('checkout_link.visual_config.theme'));
    }

    public function test_public_checkout_spa_page_renders_the_initial_payload(): void
    {
        Storage::fake('public');

        $user = $this->makeVendorUser();
        $product = $this->makeProduct($user, [
            'image_path' => UploadedFile::fake()->image('produto.jpg', 320, 240)->store('products', 'public'),
        ]);
        $link = $this->makeCheckoutLink($user, $product, [
            'allow_pix' => true,
            'allow_boleto' => true,
            'allow_credit_card' => true,
            'request_address' => true,
            'visual_config' => [
                'store_name' => 'Loja Teste',
                'theme' => 'noir',
                'primary_color' => '#111827',
                'navbar_background_color' => '#ffffff',
                'offer_message' => 'Oferta especial',
                'footer_text' => 'Atendimento em horário comercial.',
            ],
        ]);

        $response = $this->get(route('checkout.public.spa.show', $link->public_token));

        $response->assertOk();
        $response->assertSee('checkout-spa-body', false);
        $response->assertSee('checkout-spa-root', false);
        $response->assertSee('checkout-spa-data', false);
        $response->assertDontSee('Checkout SPA', false);
        $response->assertDontSee('Uma versão alternativa do checkout com navegação em uma única tela.', false);
        $response->assertSee('allow_pix', false);
        $response->assertSee('allow_boleto', false);
        $response->assertSee('allow_credit_card', false);
        $response->assertSee('request_address', false);
        $response->assertSee('visual_config', false);
        $response->assertSee('noir', false);
        $response->assertSee('cnpjLookupTemplate', false);
        $response->assertSee('checkoutPageMode', false);
        $response->assertSee('threeDsEnv', false);
        $response->assertSee('paymentDetails', false);
        $response->assertSee('Descrição do produto', false);
        $response->assertSee(route('checkout.public.product-image', $link->public_token), false);
        $response->assertDontSee(':5173', false);
        $response->assertSee(route('checkout.public.spa.show', $link->public_token), false);
    }

    public function test_public_checkout_spa_uses_the_public_image_route_when_the_checkout_link_has_no_custom_image(): void
    {
        Storage::fake('public');

        $user = $this->makeVendorUser();
        $product = $this->makeProduct($user, [
            'image_path' => UploadedFile::fake()->image('produto.jpg', 320, 240)->store('products', 'public'),
        ]);
        $link = $this->makeCheckoutLink($user, $product);

        $response = $this->get(route('checkout.public.spa.show', $link->public_token));

        $response->assertOk();
        $response->assertSee(route('checkout.public.product-image', $link->public_token), false);
    }

    public function test_checkout_spa_source_includes_the_3ds_flow(): void
    {
        $source = file_get_contents(resource_path('js/checkout-spa.jsx'));
        $styles = file_get_contents(resource_path('css/checkout-spa.css'));

        $this->assertNotFalse($source);
        $this->assertNotFalse($styles);
        $this->assertStringContainsString('authenticate3DS', $source);
        $this->assertStringContainsString('confirmCreditCard3DS', $source);
        $this->assertStringContainsString('antifraudAuthTemplate', $source);
        $this->assertStringContainsString('threeDsEnv', $source);
        $this->assertStringContainsString('Formas de pagamento', $source);
        $this->assertStringContainsString("{ label: 'Mastercard', variant: 'mastercard' }", $source);
        $this->assertStringContainsString("{ label: 'Elo', variant: 'elo' }", $source);
        $this->assertStringContainsString("{ label: 'Boleto', variant: 'boleto' }", $source);
        $this->assertStringContainsString("{ label: 'Pix', variant: 'pix' }", $source);
        $this->assertStringContainsString("{ label: 'Visa', variant: 'visa' }", $source);
        $this->assertStringContainsString("{ label: 'Amex', variant: 'amex' }", $source);
        $this->assertStringContainsString("{ label: 'Diners', variant: 'diners' }", $source);
        $this->assertStringContainsString("{ label: 'Hiper', variant: 'hiper' }", $source);
        $this->assertStringContainsString('fill="#EB001B"', $source);
        $this->assertStringContainsString('fill="#52B7AA"', $source);
        $this->assertStringNotContainsString('paymentLogoSpriteUrl', $source);
        $this->assertStringContainsString('lookupAddressByZipcode', $source);
        $this->assertStringContainsString('lookupCompanyByCnpj', $source);
        $this->assertStringContainsString('applyCompanyLookupToForm', $source);
        $this->assertStringContainsString('cnpjCompanyLookupCache', $source);
        $this->assertStringContainsString('cnpjLookupTemplate', $source);
        $this->assertStringContainsString('buildIdentificationDrafts', $source);
        $this->assertStringContainsString('mergeIdentificationDrafts', $source);
        $this->assertStringContainsString('handlePersonTypeChange', $source);
        $this->assertStringContainsString('handleIdentificationFieldChange', $source);
        $this->assertStringContainsString('data-person-form={personIsCompany ? \'pj\' : \'pf\'}', $source);
        $this->assertStringContainsString('key="pj"', $source);
        $this->assertStringContainsString('key="pf"', $source);
        $this->assertStringContainsString("defaultValue={identificationDraft.customer_responsible_document || ''}", $source);
        $this->assertStringContainsString('fillFormFieldIfEmpty', $source);
        $this->assertStringContainsString('normalizeIdentificationFormData', $source);
        $this->assertStringContainsString('onChange={handleIdentificationFieldChange}', $source);
        $this->assertStringContainsString('Digite um CPF válido.', $source);
        $this->assertStringContainsString('Digite um CNPJ válido.', $source);
        $this->assertStringContainsString('function validateIdentificationDocument(form, personType, updateFieldErrors)', $source);
        $this->assertStringContainsString('validateIdentificationDocument(form, personType, setFieldErrors)', $source);
        $this->assertStringContainsString('function validateResponsibleDocument(form, personType, updateFieldErrors)', $source);
        $this->assertStringContainsString('validateResponsibleDocument(form, personType, setFieldErrors)', $source);
        $this->assertStringContainsString('Não foi possível ler o formulário.', $source);
        $this->assertStringContainsString('viacep.com.br/ws/${normalizeDigits(zipcode)}/json/', $source);
        $this->assertStringContainsString('Consultando CEP...', $source);
        $this->assertStringContainsString('checkout-spa-step-card--payment-details', $source);
        $this->assertStringContainsString('checkout-spa-step-card--payment-note', $source);
        $this->assertStringContainsString('function renderPaymentLogosStrip()', $source);
        $this->assertStringContainsString('cardMethod ? renderPaymentLogosStrip() : null', $source);
        $this->assertStringNotContainsString('checkout-spa-payment-strip-title', $source);
        $this->assertStringContainsString("const creditCardMethod = methods.find((method) => method.value === 'credit_card');", $source);
        $this->assertStringContainsString('if (creditCardMethod) {', $source);
        $this->assertStringContainsString('return creditCardMethod.value;', $source);
        $this->assertStringContainsString('checkout-spa-payment-method-links', $source);
        $this->assertStringContainsString("method.value === 'pix'", $source);
        $this->assertStringContainsString("method.value === 'boleto'", $source);
        $this->assertStringNotContainsString("<h3>{pixMethod ? 'Pix' : 'Boleto'}</h3>", $source);
        $this->assertStringContainsString('getCreditCardInstallmentAmountError', $source);
        $this->assertStringContainsString('Com duas ou mais parcelas, cada parcela deve ter valor mínimo de R$ 5,00.', $source);
        $this->assertStringNotContainsString('Voltar para identificação', $source);
        $this->assertStringNotContainsString('Voltar para endereço', $source);
        $this->assertStringNotContainsString('Voltar aos métodos', $source);
        $this->assertMatchesRegularExpression('/onClick=\{handleGoToPreviousPaymentStep\}[\s\S]*?>\s*Voltar\s*<\/button>/', $source);
        $this->assertStringContainsString('Continuar para pagamento', $source);
        $this->assertStringNotContainsString('function handleContinueToPaymentDetails()', $source);
        $this->assertStringNotContainsString('onClick={handleContinueToPaymentDetails}', $source);
        $this->assertStringNotContainsString('Método de pagamento selecionado.', $source);
        $this->assertMatchesRegularExpression('/async function handleChoosePaymentMethod\(paymentMethod\)[\s\S]*?setStep\(\'payment-details\'\);/', $source);
        $this->assertStringContainsString("const nextPaymentMethod = allowedMethods.some((method) => method.value === 'credit_card')", $source);
        $this->assertStringContainsString("setStep('payment-details');", $source);
        $this->assertLessThan(
            strpos($source, 'renderPaymentMethodLinks(method)'),
            strpos($source, 'cardMethod ? renderPaymentLogosStrip() : null')
        );
        $this->assertStringContainsString('background: var(--checkout-spa-button, #ffffff);', $styles);
        $this->assertStringContainsString('.checkout-spa-step-card--payment-details .checkout-spa-section-title {', $styles);
        $this->assertStringContainsString('font-size: clamp(1.44rem, 2vw, 2.16rem);', $styles);
        $this->assertStringContainsString('.checkout-spa-payment-strip {', $styles);
        $this->assertStringNotContainsString('.checkout-spa-theme--essential .checkout-spa-payment-strip {', $styles);
        $this->assertStringContainsString('padding: 12px 0 0;', $styles);
        $this->assertStringContainsString('margin-top: 12px;', $styles);
        $this->assertStringContainsString('gap: 22px;', $styles);
        $this->assertStringContainsString('margin-top: 28px;', $styles);
        $this->assertStringContainsString('.checkout-spa-actions--split {', $styles);
        $this->assertStringContainsString('grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);', $styles);
        $this->assertStringContainsString('color: var(--checkout-spa-button-ink, #17120d);', $styles);
        $this->assertStringContainsString('color: var(--checkout-spa-navbar-ink, #17120d);', $styles);
        $this->assertStringContainsString('checkout-spa-theme--${checkoutTheme}', $source);
        $this->assertStringContainsString(": 'essential';", $source);
        $this->assertStringContainsString('.checkout-spa-theme--essential', $styles);
        $this->assertMatchesRegularExpression('/\.checkout-spa-theme--essential \.checkout-spa-shell \{[\s\S]*?width: min\(100%, 1440px\);[\s\S]*?margin: 0 auto;[\s\S]*?padding: 0 clamp\(24px, 2\.6vw, 48px\);/', $styles);
        $this->assertStringContainsString('const isEssentialTheme = checkoutTheme === \'essential\';', $source);
        $this->assertStringContainsString('background: linear-gradient(to right, #ffffff 0 56%, #f7f8fa 56% 100%);', $styles);
        $this->assertStringContainsString('checkout-spa-summary-description', $source);
        $this->assertMatchesRegularExpression('/@media \(max-width: 1000px\) \{[\s\S]*?\.checkout-spa-theme--essential \{[\s\S]*?background: #ffffff;/', $styles);
        $this->assertMatchesRegularExpression('/\.checkout-spa-theme--essential \.checkout-spa-step-card--intro \.checkout-spa-actions \{[\s\S]*?justify-content: flex-end;/', $styles);
        $this->assertStringContainsString('function renderDeliveryStep()', $source);
        $this->assertStringContainsString('checkout-spa-essential-delivery-section', $source);
        $this->assertStringContainsString('checkout-spa-essential-summary', $source);
        $this->assertStringContainsString('checkout-spa-essential-items', $source);
        $this->assertStringContainsString('checkout-spa-essential-item-description', $source);
        $this->assertStringContainsString('checkout-spa-essential-item-quantity-label', $source);
        $this->assertStringContainsString('summaryItems.map((item) => (', $source);
        $this->assertStringContainsString('body: payloadFormData,', $source);
        $this->assertStringContainsString('font-family: Arial, Helvetica, sans-serif;', $styles);
        $this->assertStringContainsString('name="pj_customer_name"', $source);
        $this->assertStringContainsString('name="pj_customer_document"', $source);
        $this->assertStringContainsString('name="pj_customer_email"', $source);
        $this->assertStringContainsString('name="pj_customer_responsible_document"', $source);
        $this->assertStringContainsString('name="pj_customer_responsible_birth_date"', $source);
        $this->assertStringContainsString('name="pj_customer_phone"', $source);
        $this->assertStringContainsString('name="pf_customer_name"', $source);
        $this->assertStringContainsString('name="pf_customer_document"', $source);
        $this->assertStringContainsString('name="pf_customer_email"', $source);
        $this->assertStringContainsString('name="pf_customer_birth_date"', $source);
        $this->assertStringContainsString('name="pf_customer_phone"', $source);
        $this->assertStringContainsString('grid-template-columns: minmax(0, 56%) minmax(0, 44%);', $styles);
        $this->assertMatchesRegularExpression('/\.checkout-spa-theme--essential \.checkout-spa-input,[\s\S]*?height: 45px;[\s\S]*?padding: 16px 14px 4px;[\s\S]*?font-size: 13px;/', $styles);
        $this->assertMatchesRegularExpression('/\.checkout-spa-theme--essential \.checkout-spa-header \{[\s\S]*?grid-template-columns: minmax\(0, 1fr\);[\s\S]*?gap: 24px;/', $styles);
        $this->assertStringContainsString('margin-top: -130px;', $styles);
        $this->assertStringContainsString('margin-left: 2px;', $styles);
        $this->assertStringContainsString('padding: 80px clamp(34px, 4vw, 68px) 25px;', $styles);
        $this->assertMatchesRegularExpression('/@media \(max-width: 640px\) \{[\s\S]*?\.checkout-spa-theme--essential \.checkout-spa-header \{[\s\S]*?gap: 16px;/', $styles);
        $this->assertStringContainsString('@media (max-width: 640px)', $styles);
        $this->assertStringContainsString('.checkout-spa-theme--essential .checkout-spa-identification-fields {', $styles);
        $this->assertStringContainsString('grid-template-columns: repeat(2, minmax(0, 1fr));', $styles);
        $this->assertStringNotContainsString('.checkout-spa-theme--essential .checkout-spa-identification-fields--pf > .checkout-spa-field:has([name="customer_email"]) {', $styles);
        $this->assertStringNotContainsString('.checkout-spa-theme--essential .checkout-spa-identification-fields--pf > .checkout-spa-field:has([name="customer_phone"]) {', $styles);
        $this->assertStringContainsString('.checkout-spa-theme--essential .checkout-spa-delivery-fields {', $styles);
        $this->assertStringContainsString('.checkout-spa-theme--essential .checkout-spa-delivery-fields > .checkout-spa-field:has([name="zipcode"]) {', $styles);
        $this->assertStringContainsString('.checkout-spa-theme--essential .checkout-spa-delivery-fields > .checkout-spa-field:has([name="street"]) {', $styles);
        $this->assertStringContainsString('.checkout-spa-theme--essential .checkout-spa-delivery-fields > .checkout-spa-field--number,', $styles);
        $this->assertStringContainsString('.checkout-spa-theme--essential .checkout-spa-delivery-fields > .checkout-spa-field--complement {', $styles);
        $this->assertStringContainsString('grid-column: span 1;', $styles);
        $this->assertStringNotContainsString('.checkout-spa-theme--essential .checkout-spa-identification-fields--pf > .checkout-spa-field:has([name="customer_document"]) {', $styles);
        $this->assertStringNotContainsString('.checkout-spa-theme--essential .checkout-spa-identification-fields--pf > .checkout-spa-field:has([name="customer_birth_date"]) {', $styles);
        $this->assertStringNotContainsString('.checkout-spa-theme--essential .checkout-spa-identification-fields--pf > .checkout-spa-field:has([name="customer_email"]) {', $styles);
        $this->assertStringNotContainsString('.checkout-spa-theme--essential .checkout-spa-identification-fields--pf > .checkout-spa-field:has([name="customer_phone"]) {', $styles);
        $this->assertMatchesRegularExpression('/@media \(max-width: 1000px\) \{[\s\S]*?\.checkout-spa-theme--essential \.checkout-spa-panel \{[\s\S]*?order: 1;[\s\S]*?\.checkout-spa-theme--essential \.checkout-spa-sidebar \{[\s\S]*?order: 0;/', $styles);
        $this->assertStringContainsString('.checkout-spa-theme--essential .checkout-spa-delivery-fields > .checkout-spa-field:has([name="number"]) {', $styles);
        $this->assertStringContainsString('.checkout-spa-theme--essential .checkout-spa-delivery-fields > .checkout-spa-field:has([name="complement"]) {', $styles);
        $this->assertMatchesRegularExpression('/\.checkout-spa-theme--essential \.checkout-spa-brand \{[\s\S]*?display: flex;/', $styles);
        $this->assertStringContainsString('max-height: 44px;', $styles);
        $this->assertStringContainsString('.checkout-spa-theme--essential .checkout-spa-field:focus-within .checkout-spa-label', $styles);
        $this->assertStringContainsString('.checkout-spa-theme--essential .checkout-spa-input:-webkit-autofill', $styles);
        $this->assertStringContainsString('box-shadow: 0 0 0 1000px #f0f1f3 inset;', $styles);
        $this->assertStringContainsString('-webkit-text-fill-color: #050505;', $styles);
        $this->assertMatchesRegularExpression('/\.checkout-spa-theme--essential \.checkout-spa-method-card \{[\s\S]*?border-color: #d6d8dc;[\s\S]*?background: #f0f1f3;/', $styles);
        $this->assertMatchesRegularExpression('/\.checkout-spa-theme--essential \.checkout-spa-method-card\.is-selected \{[\s\S]*?background: #e1e3e7;/', $styles);
        $this->assertMatchesRegularExpression('/@media \(max-width: 640px\) \{[\s\S]*?\.checkout-spa-theme--essential \.checkout-spa-feedback\.is-success \{[\s\S]*?display: none;/', $styles);
        $this->assertStringContainsString('.checkout-spa-theme--essential .checkout-spa-delivery-fields', $styles);
        $this->assertStringContainsString('.checkout-spa-theme--noir', $styles);
        $this->assertStringContainsString('.checkout-spa-theme--horizon', $styles);
        $this->assertStringContainsString('.checkout-spa-theme--iris', $styles);
        $this->assertStringContainsString('.checkout-spa-theme--atlantic', $styles);
        $this->assertStringContainsString('width: 49px;', $styles);
        $this->assertStringContainsString('height: 34px;', $styles);
        $this->assertStringContainsString('border: 1px solid #c9c9c9;', $styles);
        $this->assertStringContainsString('.checkout-spa-theme--essential .checkout-spa-essential-summary h2', $styles);
        $this->assertStringContainsString('margin: 0 0 21px;', $styles);
        $this->assertStringContainsString('font-size: 19px;', $styles);
        $this->assertStringContainsString('.checkout-spa-theme--essential .checkout-spa-essential-items {', $styles);
        $this->assertStringContainsString('grid-template-columns: 80px minmax(0, 1fr);', $styles);
        $this->assertStringContainsString('.checkout-spa-theme--essential .checkout-spa-essential-item-quantity-label {', $styles);
        $this->assertStringContainsString('font-size: 11px;', $styles);
        $this->assertStringContainsString('font-size: 14px;', $styles);
        $this->assertStringContainsString('padding: 4px;', $styles);
        $this->assertStringContainsString('object-fit: contain;', $styles);
    }

    private function makeVendorUser(): User
    {
        $user = User::query()->create([
            'name' => 'Vendedor Teste',
            'email' => 'vendedor+'.random_int(1, 9999).'@example.com',
            'password' => bcrypt('password'),
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
        return CheckoutLink::query()->create(array_merge([
            'seller_id' => $seller->id,
            'product_id' => $product->id,
            'public_token' => CheckoutLink::generatePublicToken(),
            'name' => 'Link '.random_int(1, 9999),
            'status' => 'active',
            'quantity' => 1,
            'unit_price' => 100.00,
            'total_price' => 100.00,
            'allow_pix' => true,
            'allow_boleto' => true,
            'allow_credit_card' => true,
            'request_address' => true,
            'pix_discount_type' => 'none',
            'pix_discount_value' => 0,
            'boleto_discount_type' => 'none',
            'boleto_discount_value' => 0,
            'free_shipping' => true,
            'visual_config' => [
                'store_name' => 'Loja Teste',
                'primary_color' => '#111827',
                'navbar_background_color' => '#ffffff',
            ],
        ], $overrides));
    }
}
