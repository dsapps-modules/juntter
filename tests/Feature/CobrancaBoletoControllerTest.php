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
            ->method('gerarBoleto')
            ->willReturn([
                '_id' => '69cbfe074dcdced7abfe4544',
                'status' => 'PROCESSING',
                'url' => null,
                'barcode' => null,
                'digitable_line' => null,
                'amount' => 1810,
            ]);
        $boletoService->expects($this->once())
            ->method('normalizarResposta')
            ->willReturnCallback(function (array $boleto): array {
                $boleto['boleto_url'] = $boleto['url'] ?? null;
                $boleto['boleto_barcode'] = $boleto['barcode'] ?? null;
                $boleto['boleto_digitable_line'] = $boleto['digitable_line'] ?? null;

                return $boleto;
            });

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
            'boleto_url' => null,
            'boleto_barcode' => null,
            'boleto_digitable_line' => null,
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
            ->method('gerarBoleto')
            ->willReturn([
                '_id' => 'boleto-123',
                'status' => 'PROCESSING',
                'url' => 'https://example.test/boleto.pdf',
                'barcode' => '12345678901234567890123456789012345678901234',
                'digitable_line' => '23793.38128 60000.000000 01000.000000 1 98760000002000',
                'amount' => 1810,
            ]);
        $boletoService->expects($this->once())
            ->method('normalizarResposta')
            ->willReturnCallback(function (array $boleto): array {
                $boleto['boleto_url'] = $boleto['url'] ?? null;
                $boleto['boleto_barcode'] = $boleto['barcode'] ?? null;
                $boleto['boleto_digitable_line'] = $boleto['digitable_line'] ?? null;

                return $boleto;
            });

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
                'boleto_url' => 'https://example.test/boleto.pdf',
                'boleto_barcode' => '12345678901234567890123456789012345678901234',
                'boleto_digitable_line' => '23793.38128 60000.000000 01000.000000 1 98760000002000',
                'amount' => 1810,
                'status' => 'PROCESSING',
            ],
        ], $response->getData(true));
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
            ->method('gerarBoleto')
            ->willReturn([
                'error' => 'API indisponível',
                'message' => 'Falha temporária',
            ]);
        $boletoService->expects($this->once())
            ->method('normalizarResposta')
            ->willReturnCallback(function (array $boleto): array {
                $boleto['boleto_url'] = $boleto['url'] ?? null;
                $boleto['boleto_barcode'] = $boleto['barcode'] ?? null;
                $boleto['boleto_digitable_line'] = $boleto['digitable_line'] ?? null;

                return $boleto;
            });

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
                'boleto_url' => null,
                'boleto_barcode' => null,
                'boleto_digitable_line' => null,
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
