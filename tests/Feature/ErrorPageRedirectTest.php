<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorPageRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_404_page_redirects_to_the_public_home_after_five_seconds(): void
    {
        $response = $this->get('/pagina-inexistente');

        $response->assertNotFound();
        $response->assertSee('content="5;url=/"', false);
        $response->assertSee('/img/logo/juntter_webp_640_174.webp', false);
        $response->assertSee('Checkout fora da rota', false);
        $response->assertSee('window.location.href = "\/"', false);
        $response->assertSee('}, 5000);', false);
    }

    public function test_authenticated_404_page_redirects_to_the_internal_home_after_five_seconds(): void
    {
        $user = User::factory()->create([
            'nivel_acesso' => 'vendedor',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/pagina-inexistente');

        $response->assertNotFound();
        $response->assertSee('content="5;url=/app/home"', false);
        $response->assertSee('/img/logo/juntter_webp_640_174.webp', false);
        $response->assertSee('Voltar para o fluxo', false);
        $response->assertSee('window.location.href = "\/app\/home"', false);
        $response->assertSee('}, 5000);', false);
    }
}
