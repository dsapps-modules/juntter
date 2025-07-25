<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\ApiToken;
use Exception;

class ApiClientService
{
    protected string $baseUrl;
    protected int $maxRetries = 10;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.paytime.base_url'), '/');
    }

    public function get(string $endpoint, array $params = []): array
    {
        return $this->request('GET', $endpoint, ['query' => $params]);
    }

    public function post(string $endpoint, array $payload = []): array
    {
        return $this->request('POST', $endpoint, ['json' => $payload]);
    }

    public function put(string $endpoint, array $params = []): array
    {
        return $this->request('PUT', $endpoint, ['query' => $params]);
    }

    private function request(string $method, string $endpoint, array $options): array
    {
        $attempts = 0;

        do {
            $token = $this->getToken();

            $headers = [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => "Bearer {$token}",
            ];

            $response = Http::withHeaders($headers)
                ->{$method}("{$this->baseUrl}/{$endpoint}", $options[$method === 'GET' ? 'query' : 'json'] ?? []);

            if ($response->status() !== 401) {
                return $response->json();
            }

            $this->refreshToken();
            $attempts++;

        } while ($attempts < $this->maxRetries);

        throw new Exception("Erro 401 persistente após {$this->maxRetries} tentativas.");
    }

    private function getToken(): string
    {
        $token = ApiToken::where('key', 'paytime_token')->get()[0] ?? null;

        if ($token && now()->diffInMinutes($token['updated_at']) < 30) {
            echo "\n\nToken reutilizado com sucesso!\n";
            return $token->access_token;
        }

        return $this->refreshToken();
    }

    private function refreshToken(): string
    {

        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ];

        $body = [
            'integration-key' => config('services.paytime.integration_key'),
            'authentication-key' => config('services.paytime.authentication_key'),
            'x-token' => config('services.paytime.x_token'),
        ];

        $response = Http::withHeaders($headers)->post("{$this->baseUrl}/auth/login", $body);

        if (!$response->successful()) {
            throw new Exception('Falha ao renovar token: ' . $response->body());
        }

        echo "\n\nToken renovado com sucesso!\n";
        $accessToken = $response->json()['token'];

        try{
            ApiToken::updateOrCreate(
                ['key' => 'paytime_token'],
                ['access_token' => $accessToken, 'updated_at' => now()]
            );
            return $accessToken;
        }
        catch (Exception $e) {
            throw new Exception('Erro ao atualizar token: ' . $e->getMessage());
        }
    }
}