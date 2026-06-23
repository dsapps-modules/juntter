<?php

namespace Tests\Feature;

use Tests\TestCase;

class CheckoutVisualConfigTest extends TestCase
{
    public function test_public_checkout_uses_a_fixed_white_navbar_and_neutral_background(): void
    {
        $publicView = file_get_contents(base_path('resources/views/checkout/public.blade.php'));
        $thankYouView = file_get_contents(base_path('resources/views/checkout/thank-you.blade.php'));
        $unavailableView = file_get_contents(base_path('resources/views/checkout/unavailable.blade.php'));

        $this->assertIsString($publicView);
        $this->assertIsString($thankYouView);
        $this->assertIsString($unavailableView);

        $this->assertStringContainsString('--checkout-bg: #f7f7f9;', $publicView);
        $this->assertStringContainsString('--checkout-navbar-bg:', $publicView);
        $this->assertStringContainsString('.checkout-navbar {', $publicView);
        $this->assertStringContainsString('position: fixed;', $publicView);
        $this->assertStringContainsString('height: var(--checkout-navbar-height);', $publicView);
        $this->assertStringContainsString('background: var(--checkout-navbar-bg);', $publicView);
        $this->assertStringContainsString('.checkout-navbar__brand-image', $publicView);
        $this->assertStringContainsString('.checkout-navbar__title', $publicView);
        $this->assertStringContainsString('navbarBackgroundColor', $publicView);
        $this->assertStringContainsString('navbar_background_color', $publicView);
        $this->assertStringContainsString('display: none;', $publicView);
        $this->assertStringNotContainsString('radial-gradient(circle at top left', $publicView);
        $this->assertStringNotContainsString('linear-gradient(180deg, #ffffff 0%, var(--checkout-bg) 100%)', $publicView);
        $this->assertStringNotContainsString('filter: blur(80px);', $publicView);
        $this->assertStringNotContainsString('checkout-auth-logo-image', $publicView);
        $this->assertStringContainsString('.summary-total strong {', $publicView);
        $this->assertStringContainsString('.summary-title__thumb {', $publicView);
        $this->assertStringContainsString('@if(filled($checkoutLink->product_image_path))', $publicView);
        $this->assertStringContainsString('.summary-card {', $publicView);
        $this->assertStringContainsString('order: -1;', $publicView);

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
        $this->assertStringContainsString('navbar_background_color: \'#FFFFFF\'', $formSource);
        $this->assertStringContainsString('primary_color: \'#FFC800\'', $formSource);
        $this->assertStringContainsString('primary_color: visualConfig.primary_color ?? visualDefaults.primary_color', $formSource);
        $this->assertStringContainsString('primary_color: visualDefaults.primary_color', $formSource);
        $this->assertStringContainsString('const { primary_color, navbar_background_color, ...restValues } = values;', $formSource);
        $this->assertStringContainsString('primary_color: primary_color || visualDefaults.primary_color,', $formSource);
        $this->assertStringContainsString('navbar_background_color: visualConfig.navbar_background_color ?? visualDefaults.navbar_background_color', $formSource);
        $this->assertStringContainsString('navbar_background_color: visualDefaults.navbar_background_color', $formSource);
        $this->assertStringContainsString('navbar_background_color: navbar_background_color || visualDefaults.navbar_background_color,', $formSource);
        $this->assertStringContainsString('label="Cor primária"', $formSource);
        $this->assertStringContainsString('label="Cor de fundo da navbar"', $formSource);
        $this->assertStringContainsString('label="Imagem do produto"', $formSource);
        $this->assertStringContainsString('Envie uma imagem quadrada de 250x250 px, preferencialmente.', $formSource);
        $this->assertStringContainsString('accept="image/*"', $formSource);
        $this->assertStringContainsString('productImagePreviewUrl', $formSource);
        $this->assertStringContainsString('FormData', $formSource);
        $this->assertStringContainsString("payload.append('product_image', productImageFile);", $formSource);
        $this->assertStringContainsString("payload.append('_method', 'PUT');", $formSource);
        $this->assertStringContainsString('type="color"', $formSource);
    }
}
