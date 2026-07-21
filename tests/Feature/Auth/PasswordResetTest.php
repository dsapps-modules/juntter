<?php

namespace Tests\Feature\Auth;

use App\Models\PaytimeEstablishment;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get(route('password.request'));

        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $mailMessage = $notification->toMail($user);

            $this->assertStringContainsString('/app/reset-password/'.$notification->token, $mailMessage->actionUrl);
            $this->assertStringContainsString('email='.urlencode($user->email), $mailMessage->actionUrl);

            return true;
        });
    }

    public function test_reset_password_link_can_be_requested_from_the_spa(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->postJson('/forgot-password', ['email' => $user->email]);

        $response->assertOk()->assertJsonStructure(['message']);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_password_link_creates_a_user_for_a_known_establishment_email(): void
    {
        Notification::fake();

        $establishment = PaytimeEstablishment::query()->create([
            'id' => 155463,
            'first_name' => 'Sonia',
            'last_name' => 'Prado',
            'fantasy_name' => 'Loja Sonia',
            'document' => '25984303876',
            'email' => 'soneca@home.com',
            'phone_number' => '11920012001',
            'active' => true,
            'status' => 'APPROVED',
            'risk' => 'PENDING',
            'category' => 'Serviços Profissionais',
            'code' => 'SONL8YRB',
            'revenue' => '10000000',
            'address_json' => [
                'street' => 'Rua Senador Nilo Coelho',
                'number' => '111',
                'city' => 'Guarulhos',
                'state' => 'SP',
            ],
            'responsible_json' => [
                'name' => 'Sonia Prado',
                'email' => 'soneca@home.com',
            ],
        ]);

        $response = $this->postJson('/forgot-password', ['email' => $establishment->email]);

        $response->assertOk()->assertJsonStructure(['message']);

        $user = User::query()
            ->where('email', $establishment->email)
            ->firstOrFail();

        $this->assertSame('Sonia Prado', $user->name);
        $this->assertSame('Loja Sonia', $user->trade_name);
        $this->assertSame('vendedor', $user->nivel_acesso);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNotNull($user->vendedor);
        $this->assertSame((string) $establishment->id, (string) $user->vendedor->estabelecimento_id);
        $this->assertSame('11920012001', $user->vendedor->telefone);
        $this->assertSame(json_encode($establishment->address_json), $user->vendedor->endereco);
        $this->assertTrue($user->vendedor->must_change_password);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_password_link_rejects_unknown_email_without_creating_a_user(): void
    {
        Notification::fake();

        $response = $this->postJson('/forgot-password', ['email' => 'desconhecido@example.com']);

        $response->assertStatus(422)->assertJsonStructure(['message']);

        $this->assertDatabaseMissing('users', [
            'email' => 'desconhecido@example.com',
        ]);

        Notification::assertNothingSent();
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
            $response = $this->get(route('password.reset', ['token' => $notification->token]));

            $response->assertStatus(200);

            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect('/app/login');

            return true;
        });
    }

    public function test_password_can_be_reset_from_the_spa(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $response = $this->postJson('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

            $response
                ->assertOk()
                ->assertJson([
                    'redirect' => '/app/login',
                ]);

            return true;
        });
    }
}
