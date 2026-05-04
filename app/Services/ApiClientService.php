<?php

namespace App\Services;

use App\Models\ApiToken;
use Exception;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

    public function put(string $endpoint, array $payload = []): array
    {
        return $this->request('PUT', $endpoint, ['json' => $payload]);
    }

    public function delete(string $endpoint): array
    {
        return $this->request('DELETE', $endpoint, []);
    }

    private function request(string $method, string $endpoint, array $options): array
    {

        $attempts = 0;

        do {
            $token = $this->getToken();

            $headers = [
                'Authorization' => "Bearer {$token}",
                'x-token' => config('services.paytime.x_token'),
                'integration-key' => config('services.paytime.integration_key'),
            ];

            // Trata extra_headers para POST requests (json)
            if (isset($options['json']['extra_headers'])) {
                foreach ($options['json']['extra_headers'] as $key => $value) {
                    $headers[$key] = $value;
                }
                unset($options['json']['extra_headers']);
            }

            Log::info('Paytime API Request', [
                'method' => $method,
                'endpoint' => "{$this->baseUrl}/{$endpoint}",
                'headers' => $headers,
                'payload' => $this->sanitizeSensitiveData($options[$method === 'GET' ? 'query' : 'json'] ?? []),
            ]);

            // Trata extra_headers para GET requests (query)
            if (isset($options['query']['extra_headers'])) {
                foreach ($options['query']['extra_headers'] as $key => $value) {
                    $headers[$key] = $value;
                }
                unset($options['query']['extra_headers']);
            }

            $response = Http::withHeaders($headers)
                ->withOptions([
                    'force_ip_resolve' => 'v4',
                    'connect_timeout' => 10,
                    'timeout' => 30,
                ])
                ->beforeSending(function (Request $request) {
                    // Log::info('Headers enviados', ['headers' => $request->headers()]);
                    // Log::info('Query enviada',    ['query'   => $request->getUri()]);
                })
                ->{$method}("{$this->baseUrl}/{$endpoint}", $options[$method === 'GET' ? 'query' : 'json'] ?? []);

            Log::info('Paytime API Response', [
                'status' => $response->status(),
                'body' => $this->sanitizeSensitiveData($response->json() ?? []),
            ]);

            if ($response->status() !== 401) {
                return $response->json();
            }

            $this->refreshToken();
            $attempts++;

        } while ($attempts < $this->maxRetries);

        throw new Exception("Erro 401 persistente após {$this->maxRetries} tentativas.");
    }

    private function sanitizeSensitiveData(array $payload): array
    {
        if (isset($payload['card']) && is_array($payload['card'])) {
            unset($payload['card']['card_number'], $payload['card']['security_code'], $payload['card']['holder_document']);
        }

        if (isset($payload['client']) && is_array($payload['client'])) {
            if (isset($payload['client']['document']) && is_string($payload['client']['document'])) {
                $payload['client']['document'] = str_repeat('*', max(strlen($payload['client']['document']) - 4, 0)).substr($payload['client']['document'], -4);
            }
        }

        return $payload;
    }

    private function getToken(): string
    {
        $token = ApiToken::where('key', 'paytime_token')->get()[0] ?? null;

        if ($token && now()->diffInMinutes($token['updated_at']) < 30) {
            return $token['access_token'];
        }

        return $this->refreshToken();
    }

    private function refreshToken(): string
    {

        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        $body = [
            'integration-key' => config('services.paytime.integration_key'),
            'authentication-key' => config('services.paytime.authentication_key'),
            'x-token' => config('services.paytime.x_token'),
        ];

        $response = Http::withHeaders($headers)
            ->withOptions([
                'force_ip_resolve' => 'v4',
                'connect_timeout' => 10,
                'timeout' => 30,
            ])
            ->post("{$this->baseUrl}/auth/login", $body);

        if (! $response->successful()) {
            throw new Exception('Falha ao renovar token: '.$response->body());
        }

        // echo "\n\nToken renovado com sucesso!\n";
        $accessToken = $response->json()['token'];

        try {
            ApiToken::updateOrCreate(
                ['key' => 'paytime_token'],
                ['access_token' => $accessToken, 'updated_at' => now()]
            );

            return $accessToken;
        } catch (Exception $e) {
            throw new Exception('Erro ao atualizar token: '.$e->getMessage());
        }
    }
}
