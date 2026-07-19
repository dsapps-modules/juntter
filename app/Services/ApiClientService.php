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
                ->withOptions($this->buildHttpOptions())
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

        foreach (['authentication-key', 'integration-key', 'x-token', 'pin', 'hash_code', 'pix_key', 'key'] as $sensitiveKey) {
            if (isset($payload[$sensitiveKey]) && is_scalar($payload[$sensitiveKey])) {
                $payload[$sensitiveKey] = $this->maskScalar((string) $payload[$sensitiveKey]);
            }
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildHttpOptions(): array
    {
        $options = [
            'force_ip_resolve' => 'v4',
            'connect_timeout' => 10,
            'timeout' => 30,
        ];

        $certPath = $this->resolveMtlsCertificatePath();
        $keyPath = $this->resolveMtlsKeyPath();
        $caPath = config('services.paytime.mtls_ca_path');
        $verifyPeer = config('services.paytime.mtls_verify_peer');

        if (is_string($certPath) && $certPath !== '' && is_file($certPath) && is_readable($certPath)) {
            $options['cert'] = $certPath;
            if (is_string($keyPath) && $keyPath !== '' && is_file($keyPath) && is_readable($keyPath)) {
                $options['ssl_key'] = $keyPath;
            } elseif ($this->certificateContainsPrivateKey($certPath)) {
                $options['ssl_key'] = $certPath;
            }
        }

        if (is_string($caPath) && $caPath !== '') {
            $options['verify'] = $caPath;
        } elseif (is_bool($verifyPeer)) {
            $options['verify'] = $verifyPeer;
        }

        return $options;
    }

    private function certificateContainsPrivateKey(string $certPath): bool
    {
        $contents = @file_get_contents($certPath);

        if (! is_string($contents) || trim($contents) === '') {
            return false;
        }

        return str_contains($contents, 'BEGIN PRIVATE KEY')
            || str_contains($contents, 'BEGIN RSA PRIVATE KEY')
            || str_contains($contents, 'BEGIN ENCRYPTED PRIVATE KEY');
    }

    private function resolveMtlsCertificatePath(): ?string
    {
        $configuredPath = config('services.paytime.mtls_cert_path');
        if (is_string($configuredPath) && $this->isReadableCertificateFile($configuredPath)) {
            return $configuredPath;
        }

        foreach ([
            base_path('.keys/client.crt'),
            base_path('.keys/client.pem'),
            base_path('.keys/client.cer'),
            base_path('.keys/cert.crt'),
            base_path('.keys/cert.pem'),
            base_path('.keys/certificate.crt'),
            base_path('.keys/certificate.pem'),
        ] as $candidate) {
            if ($this->isReadableCertificateFile($candidate)) {
                return $candidate;
            }
        }

        foreach ($this->findKeysDirectoryFiles(['*.crt', '*.pem', '*.cer']) as $candidate) {
            if ($this->isReadableCertificateFile($candidate)) {
                return $candidate;
            }
        }

        return is_string($configuredPath) && $configuredPath !== '' ? $configuredPath : null;
    }

    private function resolveMtlsKeyPath(): ?string
    {
        $configuredPath = config('services.paytime.mtls_key_path');
        if (is_string($configuredPath) && $this->isReadablePrivateKeyFile($configuredPath)) {
            return $configuredPath;
        }

        foreach ([
            base_path('.keys/client.key'),
            base_path('.keys/cert.key'),
            base_path('.keys/private.key'),
            base_path('.keys/client.pem'),
        ] as $candidate) {
            if ($this->isReadablePrivateKeyFile($candidate)) {
                return $candidate;
            }
        }

        foreach ($this->findKeysDirectoryFiles(['*.key', '*.pem']) as $candidate) {
            if ($this->isReadablePrivateKeyFile($candidate)) {
                return $candidate;
            }
        }

        return is_string($configuredPath) && $configuredPath !== '' ? $configuredPath : null;
    }

    /**
     * @return array<int, string>
     */
    private function findKeysDirectoryFiles(array $patterns): array
    {
        $files = [];

        foreach ($patterns as $pattern) {
            foreach (glob(base_path('.keys'.DIRECTORY_SEPARATOR.$pattern)) ?: [] as $candidate) {
                if (is_string($candidate)) {
                    $files[] = $candidate;
                }
            }
        }

        return array_values(array_unique($files));
    }

    private function isReadableCertificateFile(string $path): bool
    {
        if (! is_file($path) || ! is_readable($path)) {
            return false;
        }

        $contents = @file_get_contents($path);

        if (! is_string($contents) || trim($contents) === '') {
            return false;
        }

        if (str_contains($contents, 'BEGIN CERTIFICATE REQUEST')) {
            return false;
        }

        return str_contains($contents, 'BEGIN CERTIFICATE');
    }

    private function isReadablePrivateKeyFile(string $path): bool
    {
        if (! is_file($path) || ! is_readable($path)) {
            return false;
        }

        $contents = @file_get_contents($path);

        if (! is_string($contents) || trim($contents) === '') {
            return false;
        }

        return str_contains($contents, 'BEGIN PRIVATE KEY')
            || str_contains($contents, 'BEGIN RSA PRIVATE KEY')
            || str_contains($contents, 'BEGIN ENCRYPTED PRIVATE KEY');
    }

    private function maskScalar(string $value): string
    {
        $length = strlen($value);

        if ($length <= 4) {
            return str_repeat('*', max($length, 1));
        }

        return str_repeat('*', $length - 4).substr($value, -4);
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
            ->withOptions($this->buildHttpOptions())
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
