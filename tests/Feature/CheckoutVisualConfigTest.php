<?php

namespace Tests\Feature;

use Tests\TestCase;

class CheckoutVisualConfigTest extends TestCase
{
    public function test_public_checkout_spa_uses_a_fixed_white_navbar_and_neutral_background(): void
    {
        $publicView = file_get_contents(base_path('resources/views/checkout/spa.blade.php'));
        $thankYouView = file_get_contents(base_path('resources/views/checkout/thank-you.blade.php'));
        $unavailableView = file_get_contents(base_path('resources/views/checkout/unavailable.blade.php'));
        $spaShellView = file_get_contents(base_path('resources/views/spa.blade.php'));
        $notFoundView = file_get_contents(base_path('resources/views/errors/404.blade.php'));
        $faviconComponent = file_get_contents(base_path('resources/views/components/favicon.blade.php'));

        $this->assertIsString($publicView);
        $this->assertIsString($thankYouView);
        $this->assertIsString($unavailableView);
        $this->assertIsString($spaShellView);
        $this->assertIsString($notFoundView);
        $this->assertIsString($faviconComponent);

        $this->assertStringContainsString('<x-favicon />', $publicView);
        $this->assertStringContainsString('<x-favicon />', $thankYouView);
        $this->assertStringContainsString('<x-favicon />', $unavailableView);
        $this->assertStringContainsString('<x-favicon />', $spaShellView);
        $this->assertStringContainsString('<x-favicon />', $notFoundView);
        $this->assertStringContainsString("asset('img/logo/juntter_png_256.png')", $faviconComponent);
        $this->assertStringContainsString('checkout-spa-data', $publicView);
        $this->assertStringContainsString('checkout-spa-root', $publicView);
        $this->assertStringContainsString('threeDsEnv', $publicView);
        $this->assertStringContainsString('paymentDetails', $publicView);
        $this->assertStringContainsString('$checkoutSpaAssets[\'css\']', $publicView);
        $this->assertStringContainsString('$checkoutSpaAssets[\'js\']', $publicView);
        $this->assertStringNotContainsString('checkout-public', $publicView);
        $this->assertStringNotContainsString('@vite([\'resources/js/checkout-public.js\'])', $publicView);

        $this->assertStringContainsString('--checkout-bg: #f7f7f9;', $thankYouView);
        $this->assertStringContainsString('--checkout-bg: #f7f7f9;', $unavailableView);
        $this->assertStringNotContainsString('radial-gradient(circle at top left', $thankYouView);
        $this->assertStringNotContainsString('radial-gradient(circle at top left', $unavailableView);
        $this->assertStringNotContainsString('filter: blur(80px);', $thankYouView);
        $this->assertStringNotContainsString('filter: blur(80px);', $unavailableView);
    }

    public function test_checkout_link_form_exposes_the_navbar_background_color_field(): void
    {
        $formSource = file_get_contents(base_path('resources/js/spa/pages/checkout/CheckoutLinkFormPage.jsx'));

        $this->assertIsString($formSource);
        $this->assertStringContainsString("title={isEditing ? 'Editar link de checkout' : 'Criar'}", $formSource);
        $this->assertStringContainsString('Cor do topo', $formSource);
        $this->assertStringContainsString('Cor do botão', $formSource);
        $this->assertStringContainsString('Letra do topo', $formSource);
        $this->assertStringContainsString('Letra do botão', $formSource);
        $this->assertStringContainsString('navbar_text_color', $formSource);
        $this->assertStringContainsString('button_text_color', $formSource);
        $this->assertStringContainsString('handleNavbarBackgroundColorChange', $formSource);
        $this->assertStringContainsString('handleNavbarTextColorChange', $formSource);
        $this->assertStringContainsString('const showCheckoutVisualControls = false;', $formSource);
        $this->assertStringContainsString('hidden={!showCheckoutVisualControls}', $formSource);
        $this->assertStringContainsString('button_text_color: button_text_color || navbar_text_color || visualDefaults.button_text_color,', $formSource);
    }
}
