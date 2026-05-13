<?php

namespace Tests\Unit;

use App\Http\Requests\BoletoRequest;
use App\Http\Requests\CobrancaCartaoRequest;
use App\Http\Requests\CobrancaPixRequest;
use App\Http\Requests\LinkBoletoRequest;
use App\Http\Requests\StartCheckoutPaymentRequest;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DocumentRequestValidationTest extends TestCase
{
    #[DataProvider('validRequestProvider')]
    public function test_request_rules_accept_valid_documents(string $requestClass, array $payload): void
    {
        $validator = Validator::make($payload, (new $requestClass)->rules());

        $this->assertFalse($validator->fails(), $validator->errors()->first());
    }

    #[DataProvider('invalidRequestProvider')]
    public function test_request_rules_reject_invalid_documents(string $requestClass, array $payload, string $errorKey): void
    {
        $validator = Validator::make($payload, (new $requestClass)->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey($errorKey, $validator->errors()->toArray());
    }

    public static function validRequestProvider(): array
    {
        return [
            [
                CobrancaPixRequest::class,
                [
                    'payment_type' => 'PIX',
                    'amount' => '10,00',
                    'interest' => 'CLIENT',
                    'client' => [
                        'document' => '123.456.789-09',
                    ],
                ],
            ],
            [
                CobrancaCartaoRequest::class,
                [
                    'payment_type' => 'CREDIT',
                    'amount' => '10,00',
                    'installments' => 1,
                    'interest' => 'CLIENT',
                    'client' => [
                        'first_name' => 'Maria',
                        'last_name' => 'Silva',
                        'document' => '04.252.011/0001-10',
                        'phone' => '(11) 99999-9999',
                        'email' => 'maria@example.com',
                        'address' => [
                            'street' => 'Rua A',
                            'number' => '100',
                            'neighborhood' => 'Centro',
                            'city' => 'São Paulo',
                            'state' => 'SP',
                            'zip_code' => '01001-000',
                        ],
                    ],
                    'card' => [
                        'holder_name' => 'Maria Silva',
                        'holder_document' => '123.456.789-09',
                        'card_number' => '4111111111111111',
                        'expiration_month' => '07',
                        'expiration_year' => now()->year + 1,
                        'security_code' => '123',
                    ],
                ],
            ],
            [
                BoletoRequest::class,
                [
                    'amount' => '10,00',
                    'expiration' => now()->addDay()->format('Y-m-d'),
                    'recharge' => false,
                    'client' => [
                        'first_name' => 'Maria',
                        'last_name' => 'Silva',
                        'document' => '123.456.789-09',
                        'email' => 'maria@example.com',
                        'address' => [
                            'street' => 'Rua A',
                            'number' => '100',
                            'neighborhood' => 'Centro',
                            'city' => 'São Paulo',
                            'state' => 'SP',
                            'zip_code' => '01001-000',
                        ],
                    ],
                    'instruction' => [
                        'booklet' => false,
                        'late_fee' => ['amount' => '2,00'],
                        'interest' => ['amount' => '1,00'],
                        'discount' => [
                            'amount' => '5,00',
                            'limit_date' => now()->toDateString(),
                        ],
                    ],
                ],
            ],
            [
                LinkBoletoRequest::class,
                [
                    'valor' => '10,00',
                    'data_vencimento' => now()->addDay()->format('Y-m-d'),
                    'juros' => 'CLIENT',
                    'dados_cliente_preenchidos' => [
                        'nome' => 'Maria',
                        'sobrenome' => 'Silva',
                        'email' => 'maria@example.com',
                        'telefone' => '(11) 99999-9999',
                        'documento' => '04.252.011/0001-10',
                        'endereco' => [
                            'rua' => 'Rua A',
                            'numero' => '100',
                            'bairro' => 'Centro',
                            'cidade' => 'São Paulo',
                            'estado' => 'SP',
                            'cep' => '01001-000',
                        ],
                    ],
                    'instrucoes_boleto' => [
                        'late_fee' => ['amount' => '2,00'],
                        'interest' => ['amount' => '1,00'],
                        'discount' => [
                            'amount' => '5,00',
                            'limit_date' => now()->toDateString(),
                        ],
                    ],
                ],
            ],
            [
                StartCheckoutPaymentRequest::class,
                [
                    'payment_method' => 'credit_card',
                    'installments' => 1,
                    'card' => [
                        'holder_name' => 'Maria Silva',
                        'holder_document' => '123.456.789-09',
                        'card_number' => '4111111111111111',
                        'expiration_month' => '07',
                        'expiration_year' => now()->year + 1,
                        'security_code' => '123',
                    ],
                ],
            ],
        ];
    }

    public static function invalidRequestProvider(): array
    {
        return [
            [
                CobrancaPixRequest::class,
                [
                    'payment_type' => 'PIX',
                    'amount' => '10,00',
                    'interest' => 'CLIENT',
                    'client' => [
                        'document' => '111.111.111-11',
                    ],
                ],
                'client.document',
            ],
            [
                CobrancaCartaoRequest::class,
                [
                    'payment_type' => 'CREDIT',
                    'amount' => '10,00',
                    'installments' => 1,
                    'interest' => 'CLIENT',
                    'client' => [
                        'first_name' => 'Maria',
                        'last_name' => 'Silva',
                        'document' => '111.111.111-11',
                        'phone' => '(11) 99999-9999',
                        'email' => 'maria@example.com',
                        'address' => [
                            'street' => 'Rua A',
                            'number' => '100',
                            'neighborhood' => 'Centro',
                            'city' => 'São Paulo',
                            'state' => 'SP',
                            'zip_code' => '01001-000',
                        ],
                    ],
                    'card' => [
                        'holder_name' => 'Maria Silva',
                        'holder_document' => '111.111.111-11',
                        'card_number' => '4111111111111111',
                        'expiration_month' => 12,
                        'expiration_year' => now()->year + 1,
                        'security_code' => '123',
                    ],
                ],
                'client.document',
            ],
            [
                BoletoRequest::class,
                [
                    'amount' => '10,00',
                    'expiration' => now()->addDay()->format('Y-m-d'),
                    'recharge' => false,
                    'client' => [
                        'first_name' => 'Maria',
                        'last_name' => 'Silva',
                        'document' => '111.111.111-11',
                        'email' => 'maria@example.com',
                        'address' => [
                            'street' => 'Rua A',
                            'number' => '100',
                            'neighborhood' => 'Centro',
                            'city' => 'São Paulo',
                            'state' => 'SP',
                            'zip_code' => '01001-000',
                        ],
                    ],
                    'instruction' => [
                        'booklet' => false,
                        'late_fee' => ['amount' => '2,00'],
                        'interest' => ['amount' => '1,00'],
                        'discount' => [
                            'amount' => '5,00',
                            'limit_date' => now()->toDateString(),
                        ],
                    ],
                ],
                'client.document',
            ],
            [
                LinkBoletoRequest::class,
                [
                    'valor' => '10,00',
                    'data_vencimento' => now()->addDay()->format('Y-m-d'),
                    'juros' => 'CLIENT',
                    'dados_cliente_preenchidos' => [
                        'nome' => 'Maria',
                        'sobrenome' => 'Silva',
                        'email' => 'maria@example.com',
                        'telefone' => '(11) 99999-9999',
                        'documento' => '111.111.111-11',
                        'endereco' => [
                            'rua' => 'Rua A',
                            'numero' => '100',
                            'bairro' => 'Centro',
                            'cidade' => 'São Paulo',
                            'estado' => 'SP',
                            'cep' => '01001-000',
                        ],
                    ],
                    'instrucoes_boleto' => [
                        'late_fee' => ['amount' => '2,00'],
                        'interest' => ['amount' => '1,00'],
                        'discount' => [
                            'amount' => '5,00',
                            'limit_date' => now()->toDateString(),
                        ],
                    ],
                ],
                'dados_cliente_preenchidos.documento',
            ],
            [
                StartCheckoutPaymentRequest::class,
                [
                    'payment_method' => 'credit_card',
                    'installments' => 1,
                    'card' => [
                        'holder_name' => 'Maria Silva',
                        'holder_document' => '111.111.111-11',
                        'card_number' => '4111111111111111',
                        'expiration_month' => 12,
                        'expiration_year' => now()->year + 1,
                        'security_code' => '123',
                    ],
                ],
                'card.holder_document',
            ],
        ];
    }
}
