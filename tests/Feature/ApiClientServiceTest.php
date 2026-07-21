<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Services\ApiClientService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ApiClientServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_an_empty_array_when_the_api_response_is_not_json_array(): void
    {
        config()->set('services.paytime.base_url', 'https://paytime.example.test');
        config()->set('services.paytime.x_token', 'x-token');
        config()->set('services.paytime.integration_key', 'integration-key');
        config()->set('services.paytime.authentication_key', 'authentication-key');

        ApiToken::query()->create([
            'key' => 'paytime_token',
            'access_token' => 'existing-token',
            'updated_at' => now(),
        ]);

        Http::fake([
            'https://paytime.example.test/*' => Http::response('', 200),
        ]);

        $client = app(ApiClientService::class);

        $response = $client->get('marketplace/transactions');

        $this->assertSame([], $response);
    }
}
