<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SpaProfileOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_overview_returns_authenticated_user_data(): void
    {
        Storage::fake('public');

        Storage::disk('public')->put('company-logos/logo.png', 'fake-image-content');

        $user = User::factory()->create([
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'nivel_acesso' => 'vendedor',
            'email_verified_at' => now(),
            'company_logo_path' => 'company-logos/logo.png',
        ]);

        $response = $this->actingAs($user)->getJson('/api/spa/perfil');

        $response
            ->assertOk()
            ->assertJsonPath('profile.name', 'Maria Silva')
            ->assertJsonPath('profile.email', 'maria@example.com')
            ->assertJsonPath('profile.nivel_acesso', 'vendedor')
            ->assertJsonPath('profile.avatar_url', '/company-logo?path=company-logos%2Flogo.png')
            ->assertJsonPath('profile.company_logo_url', '/company-logo?path=company-logos%2Flogo.png');
    }

    public function test_profile_page_name_field_is_read_only(): void
    {
        $pageSource = file_get_contents(base_path('resources/js/spa/pages/ProfilePage.jsx'));

        $this->assertStringContainsString('<Typography.Text strong>Nome</Typography.Text>', $pageSource);
        $this->assertStringContainsString('value={profileForm.name}', $pageSource);
        $this->assertStringContainsString('readOnly', $pageSource);
        $this->assertStringNotContainsString('setProfileForm((current) => ({ ...current, name: event.target.value }))', $pageSource);
    }
}
