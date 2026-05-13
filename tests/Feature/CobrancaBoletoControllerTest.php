<?php

namespace Tests\Feature;

use App\Http\Controllers\CobrancaController;
use App\Http\Requests\BoletoRequest;
use App\Models\User;
use App\Models\Vendedor;
use App\Services\BoletoService;
use App\Services\CreditoService;
use App\Services\EstabelecimentoService;
use App\Services\PixService;
use App\Services\TransacaoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Tests\TestCase;

class CobrancaBoletoControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('session.driver', 'array');
    }

    public function test_criar_boleto_trata_processing_com_id_valido_como_sucesso(): void
    {
        $dadosValidados = [
            'amount' => '20,00',
            'expiration' => '2026-04-20',
            'payment_limit_date' => '2026-04-21',
            'recharge' => '0',
            'client' => [
                'first_name' => 'Jhonny',
                'last_name' => 'Quest',
                'document' => '582.463.740-79',
                'email' => 'projetojuntter@gmail.com',
                'address' => [
                    'street' => 'Rua Teste',
                    'number' => '123',
                    'complement' => 'Sala 1',
                    'neighborhood' => 'Centro',
                    'city' => 'Botucatu',
                    'state' => 'SP',
                    'zip_code' => '18600-000',
                ],
            ],
            'instruction' => [
                'booklet' => '0',
                'description' => 'Boleto de teste 03',
                'late_fee' => [
                    'amount' => '3,00',
                ],
                'interest' => [
                    'amount' => '4,00',
                ],
                'discount' => [
                    'amount' => '6,00',
                    'limit_date' => '2026-04-19',
                ],
            ],
        ];

        $user = User::factory()->make([
            'nivel_acesso' => 'vendedor',
        ]);

        $user->setRelation('vendedor', new Vendedor([
            'user_id' => $user->id,
            'estabelecimento_id' => '127700',
            'sub_nivel' => 'admin_loja',
            'status' => 'ativo',
            'must_change_password' => false,
        ]));

        $boletoService = $this->createMock(BoletoService::class);
        $boletoService->expects($this->once())
            ->method('organiza')
            ->willReturnArgument(0);
        $boletoService->expects($this->once())
            ->method('normalizarResposta')
            ->willReturnArgument(0);
        $boletoService->expects($this->once())
            ->method('gerarBoletoComConsulta')
            ->willReturn([
                '_id' => 'boleto-123',
                'status' => 'PROCESSING',
                'boleto_url' => 'https://example.test/boleto.pdf',
                'boleto_barcode' => '12345678901234567890123456789012345678901234',
                'boleto_digitable_line' => '23793.38128 60000.000000 01000.000000 1 98760000002000',
                'amount' => 1810,
            ]);

        $request = $this->makeRequest(false, $dadosValidados);

        $this->be($user);
        $this->app['session']->start();

        $controller = new CobrancaController(
            $this->createMock(TransacaoService::class),
            $this->createMock(CreditoService::class),
            $this->createMock(PixService::class),
            $boletoService,
            $this->createMock(EstabelecimentoService::class),
        );

        $response = $controller->criarBoleto($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(route('cobranca.index'), $response->getTargetUrl());
        $this->assertSame('Boleto criado com sucesso!', session('success'));
        $this->assertNull(session('error'));
        $this->assertSame([
            'boleto_id' => 'boleto-123',
            'boleto_url' => 'https://example.test/boleto.pdf',
            'boleto_barcode' => '12345678901234567890123456789012345678901234',
            'boleto_digitable_line' => '23793.38128 60000.000000 01000.000000 1 98760000002000',
            'amount' => 1810,
            'status' => 'PROCESSING',
        ], session('boleto_data'));
    }

    public function test_criar_boleto_retorna_json_quando_requisicao_espera_json(): void
    {
        $dadosValidados = [
            'amount' => '20,00',
            'expiration' => '2026-04-20',
            'payment_limit_date' => '2026-04-21',
            'recharge' => '0',
            'client' => [
                'first_name' => 'Jhonny',
                'last_name' => 'Quest',
                'document' => '582.463.740-79',
                'email' => 'projetojuntter@gmail.com',
                'address' => [
                    'street' => 'Rua Teste',
                    'number' => '123',
                    'complement' => 'Sala 1',
                    'neighborhood' => 'Centro',
                    'city' => 'Botucatu',
                    'state' => 'SP',
                    'zip_code' => '18600-000',
                ],
            ],
            'instruction' => [
                'booklet' => '0',
                'description' => 'Boleto de teste 03',
                'late_fee' => [
                    'amount' => '3,00',
                ],
                'interest' => [
                    'amount' => '4,00',
                ],
                'discount' => [
                    'amount' => '6,00',
                    'limit_date' => '2026-04-19',
                ],
            ],
        ];

        $user = User::factory()->make([
            'nivel_acesso' => 'vendedor',
        ]);

        $user->setRelation('vendedor', new Vendedor([
            'user_id' => $user->id,
            'estabelecimento_id' => '127700',
            'sub_nivel' => 'admin_loja',
            'status' => 'ativo',
            'must_change_password' => false,
        ]));

        $boletoService = $this->createMock(BoletoService::class);
        $boletoService->expects($this->once())
            ->method('organiza')
            ->willReturnArgument(0);
        $boletoService->expects($this->once())
            ->method('normalizarResposta')
            ->willReturnArgument(0);
        $boletoService->expects($this->once())
            ->method('gerarBoletoComConsulta')
            ->willReturn([
                '_id' => 'boleto-123',
                'status' => 'PROCESSING',
                'boleto_url' => 'https://example.test/boleto.pdf',
                'boleto_barcode' => '12345678901234567890123456789012345678901234',
                'boleto_digitable_line' => '23793.38128 60000.000000 01000.000000 1 98760000002000',
                'amount' => 1810,
            ]);

        $request = $this->makeRequest(true, $dadosValidados);

        $this->be($user);
        $this->app['session']->start();

        $controller = new CobrancaController(
            $this->createMock(TransacaoService::class),
            $this->createMock(CreditoService::class),
            $this->createMock(PixService::class),
            $boletoService,
            $this->createMock(EstabelecimentoService::class),
        );

        $response = $controller->criarBoleto($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'message' => 'Boleto criado com sucesso!',
            'boleto_data' => [
                'boleto_id' => 'boleto-123',
                'boleto_url' => 'https://example.test/boleto.pdf',
                'boleto_barcode' => '12345678901234567890123456789012345678901234',
                'boleto_digitable_line' => '23793.38128 60000.000000 01000.000000 1 98760000002000',
                'amount' => 1810,
                'status' => 'PROCESSING',
            ],
        ], $response->getData(true));
    }

    public function test_criar_transacao_credito_accepts_zero_padded_expiration_month(): void
    {
        $dadosValidados = [
            'payment_type' => 'CREDIT',
            'amount' => '20,00',
            'installments' => 1,
            'interest' => 'CLIENT',
            'client' => [
                'first_name' => 'Maria',
                'last_name' => 'Silva',
                'document' => '123.456.789-09',
                'phone' => '(11) 99999-9999',
                'email' => 'maria@example.com',
                'address' => [
                    'street' => 'Rua A',
                    'number' => '100',
                    'complement' => 'Apto 1',
                    'neighborhood' => 'Centro',
                    'city' => 'Botucatu',
                    'state' => 'SP',
                    'zip_code' => '18600-000',
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
        ];

        $user = User::factory()->make([
            'nivel_acesso' => 'vendedor',
        ]);

        $user->setRelation('vendedor', new Vendedor([
            'user_id' => $user->id,
            'estabelecimento_id' => '127700',
            'sub_nivel' => 'admin_loja',
            'status' => 'ativo',
            'must_change_password' => false,
        ]));

        $creditoService = $this->createMock(CreditoService::class);
        $creditoService->expects($this->once())
            ->method('organiza')
            ->with($this->callback(function (array $dados): bool {
                return ($dados['card']['expiration_month'] ?? null) === '07';
            }))
            ->willReturnArgument(0);
        $creditoService->expects($this->once())
            ->method('criarTransacaoCredito')
            ->willReturn([
                '_id' => 'credit-123',
                'status' => 'AUTHORIZED',
            ]);

        $this->instance(CreditoService::class, $creditoService);
        $this->instance(TransacaoService::class, $this->createMock(TransacaoService::class));
        $this->instance(PixService::class, $this->createMock(PixService::class));
        $this->instance(BoletoService::class, $this->createMock(BoletoService::class));
        $this->instance(EstabelecimentoService::class, $this->createMock(EstabelecimentoService::class));

        $response = $this->actingAs($user)->postJson('/cobranca/transacao/credito', $dadosValidados);

        $response->assertOk();
        $response->assertExactJson([
            'success' => true,
            'message' => 'Pagamento processado com sucesso',
        ]);
    }

    public function test_criar_boleto_retorna_erro_json_quando_api_nao_entrega_id(): void
    {
        $dadosValidados = [
            'amount' => '20,00',
            'expiration' => '2026-04-20',
            'payment_limit_date' => '2026-04-21',
            'recharge' => '0',
            'client' => [
                'first_name' => 'Jhonny',
                'last_name' => 'Quest',
                'document' => '582.463.740-79',
                'email' => 'projetojuntter@gmail.com',
                'address' => [
                    'street' => 'Rua Teste',
                    'number' => '123',
                    'complement' => 'Sala 1',
                    'neighborhood' => 'Centro',
                    'city' => 'Botucatu',
                    'state' => 'SP',
                    'zip_code' => '18600-000',
                ],
            ],
            'instruction' => [
                'booklet' => '0',
                'description' => 'Boleto de teste 03',
                'late_fee' => [
                    'amount' => '3,00',
                ],
                'interest' => [
                    'amount' => '4,00',
                ],
                'discount' => [
                    'amount' => '6,00',
                    'limit_date' => '2026-04-19',
                ],
            ],
        ];

        $user = User::factory()->make([
            'nivel_acesso' => 'vendedor',
        ]);

        $user->setRelation('vendedor', new Vendedor([
            'user_id' => $user->id,
            'estabelecimento_id' => '127700',
            'sub_nivel' => 'admin_loja',
            'status' => 'ativo',
            'must_change_password' => false,
        ]));

        $boletoService = $this->createMock(BoletoService::class);
        $boletoService->expects($this->once())
            ->method('organiza')
            ->willReturnArgument(0);
        $boletoService->expects($this->once())
            ->method('normalizarResposta')
            ->willReturnArgument(0);
        $boletoService->expects($this->once())
            ->method('gerarBoletoComConsulta')
            ->willReturn([
                'error' => 'API indisponível',
                'message' => 'Falha temporária',
            ]);

        $request = $this->makeRequest(true, $dadosValidados);

        $this->be($user);
        $this->app['session']->start();

        $controller = new CobrancaController(
            $this->createMock(TransacaoService::class),
            $this->createMock(CreditoService::class),
            $this->createMock(PixService::class),
            $boletoService,
            $this->createMock(EstabelecimentoService::class),
        );

        $response = $controller->criarBoleto($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Erro ao criar boleto: Falha temporária',
            'paytime_error' => [
                'error' => 'API indisponível',
                'message' => 'Falha temporária',
            ],
        ], $response->getData(true));
    }

    private function makeRequest(bool $expectsJson, array $dadosValidados): BoletoRequest
    {
        return new class($expectsJson, $dadosValidados) extends BoletoRequest
        {
            public function __construct(private bool $expectsJson, private array $dadosValidados) {}

            public function validated($key = null, $default = null): array
            {
                return $this->dadosValidados;
            }

            public function expectsJson(): bool
            {
                return $this->expectsJson;
            }

            public function ajax(): bool
            {
                return $this->expectsJson;
            }

            public function boolean($key = null, $default = false): bool
            {
                return false;
            }
        };
    }
}
