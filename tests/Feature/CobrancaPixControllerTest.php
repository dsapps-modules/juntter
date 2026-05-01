<?php

namespace Tests\Feature;

use App\Http\Controllers\CobrancaController;
use App\Http\Requests\CobrancaPixRequest;
use App\Models\User;
use App\Models\Vendedor;
use App\Services\BoletoService;
use App\Services\CreditoService;
use App\Services\EstabelecimentoService;
use App\Services\PixService;
use App\Services\TransacaoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Tests\TestCase;

class CobrancaPixControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('session.driver', 'array');
    }

    public function test_criar_transacao_pix_retorna_json_quando_requisicao_espera_json(): void
    {
        $request = $this->makeRequest(true, [
            'payment_type' => 'PIX',
            'amount' => '125,50',
            'interest' => 'CLIENT',
            'client' => [
                'first_name' => 'Maria',
                'last_name' => 'Silva',
                'document' => '123.456.789-09',
                'phone' => '(11) 99999-9999',
                'email' => 'maria@example.com',
            ],
            'info_additional' => 'Cobrança de teste',
        ]);

        $user = $this->makeVendorUser('127700');

        $pixService = $this->createMock(PixService::class);
        $pixService->expects($this->once())
            ->method('criarTransacaoPix')
            ->with($this->callback(function (array $dados): bool {
                return $dados['amount'] === 12550
                    && $dados['extra_headers']['establishment_id'] === '127700'
                    && $dados['info_additional'][0]['value'] === 'Cobrança de teste';
            }))
            ->willReturn([
                '_id' => 'pix-123',
                'emv' => '00020126580014br.gov.bcb.pix...',
                'amount' => 12550,
                'status' => 'PENDING',
            ]);
        $pixService->expects($this->once())
            ->method('obterQrCodePix')
            ->with('pix-123')
            ->willReturn([
                'qrcode' => 'data:image/png;base64,ZmFrZQ==',
                'emv' => '00020126580014br.gov.bcb.pix...',
            ]);

        $controller = $this->makeController($pixService);

        $this->be($user);
        $this->app['session']->start();

        $response = $controller->criarTransacaoPix($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'message' => 'Transação PIX criada com sucesso!',
            'pix_data' => [
                'qr_code' => [
                    'qrcode' => 'data:image/png;base64,ZmFrZQ==',
                    'emv' => '00020126580014br.gov.bcb.pix...',
                ],
                'pix_code' => '00020126580014br.gov.bcb.pix...',
                'amount' => 12550,
                'status' => 'PENDING',
            ],
        ], $response->getData(true));
    }

    public function test_criar_transacao_pix_mantem_redirecionamento_legado_quando_nao_espera_json(): void
    {
        $request = $this->makeRequest(false, [
            'payment_type' => 'PIX',
            'amount' => '20,00',
            'interest' => 'ESTABLISHMENT',
            'client' => [
                'first_name' => null,
                'last_name' => null,
                'document' => null,
                'phone' => null,
                'email' => null,
            ],
            'info_additional' => null,
        ]);

        $user = $this->makeVendorUser('998877');

        $pixService = $this->createMock(PixService::class);
        $pixService->expects($this->once())
            ->method('criarTransacaoPix')
            ->willReturn([
                '_id' => 'pix-456',
                'emv' => '00020126580014br.gov.bcb.pix...',
                'amount' => 2000,
                'status' => 'PENDING',
            ]);
        $pixService->expects($this->once())
            ->method('obterQrCodePix')
            ->with('pix-456')
            ->willReturn([
                'qrcode' => 'data:image/png;base64,ZmFrZQ==',
                'emv' => '00020126580014br.gov.bcb.pix...',
            ]);

        $controller = $this->makeController($pixService);

        $this->be($user);
        $this->app['session']->start();

        $response = $controller->criarTransacaoPix($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(route('cobranca.index'), $response->getTargetUrl());
        $this->assertSame('Transação PIX criada com sucesso!', session('success'));
        $this->assertSame([
            'qr_code' => [
                'qrcode' => 'data:image/png;base64,ZmFrZQ==',
                'emv' => '00020126580014br.gov.bcb.pix...',
            ],
            'pix_code' => '00020126580014br.gov.bcb.pix...',
            'amount' => 2000,
            'status' => 'PENDING',
        ], session('pix_data'));
    }

    public function test_criar_transacao_pix_retorna_erro_json_quando_api_nao_entrega_id(): void
    {
        $request = $this->makeRequest(true, [
            'payment_type' => 'PIX',
            'amount' => '15,00',
            'interest' => 'CLIENT',
            'client' => [
                'first_name' => 'Ana',
                'last_name' => 'Costa',
                'document' => '123.456.789-09',
                'phone' => '(11) 99999-9999',
                'email' => 'ana@example.com',
            ],
            'info_additional' => 'Observação',
        ]);

        $user = $this->makeVendorUser('554433');

        $pixService = $this->createMock(PixService::class);
        $pixService->expects($this->once())
            ->method('criarTransacaoPix')
            ->willReturn([
                'error' => 'API indisponível',
                'message' => 'Falha temporária',
            ]);
        $pixService->expects($this->never())
            ->method('obterQrCodePix');

        $controller = $this->makeController($pixService);

        $this->be($user);
        $this->app['session']->start();

        $response = $controller->criarTransacaoPix($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Erro ao criar transação PIX: Falha temporária',
            'paytime_error' => [
                'error' => 'API indisponível',
                'message' => 'Falha temporária',
            ],
        ], $response->getData(true));
    }

    public function test_estornar_transacao_pix_retorna_json_quando_requisicao_espera_json(): void
    {
        $request = Request::create('/cobranca/transacao/pix-789/estornar', 'POST', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ]);

        $transacaoService = $this->createMock(TransacaoService::class);
        $transacaoService->expects($this->once())
            ->method('detalhesTransacao')
            ->with('pix-789')
            ->willReturn([
                'status' => 'PENDING',
                'amount' => 300,
            ]);
        $transacaoService->expects($this->once())
            ->method('estornarTransacao')
            ->with('pix-789')
            ->willReturn([
                'status' => 'REFUNDED',
            ]);

        $controller = new CobrancaController(
            $transacaoService,
            $this->createMock(CreditoService::class),
            $this->createMock(PixService::class),
            $this->createMock(BoletoService::class),
            $this->createMock(EstabelecimentoService::class),
        );

        $response = $controller->estornarTransacao($request, 'pix-789');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'message' => 'Transação de R$ 3,00 cancelada com sucesso!',
            'status' => 'REFUNDED',
            'transaction_id' => 'pix-789',
        ], $response->getData(true));
    }

    private function makeController(PixService $pixService): CobrancaController
    {
        return new CobrancaController(
            $this->createMock(TransacaoService::class),
            $this->createMock(CreditoService::class),
            $pixService,
            $this->createMock(BoletoService::class),
            $this->createMock(EstabelecimentoService::class),
        );
    }

    private function makeVendorUser(string $establishmentId): User
    {
        $user = User::factory()->make([
            'nivel_acesso' => 'vendedor',
        ]);

        $user->setRelation('vendedor', new Vendedor([
            'user_id' => $user->id,
            'estabelecimento_id' => $establishmentId,
            'sub_nivel' => 'admin_loja',
            'status' => 'ativo',
            'must_change_password' => false,
        ]));

        return $user;
    }

    private function makeRequest(bool $expectsJson, array $dadosValidados): CobrancaPixRequest
    {
        return new class($expectsJson, $dadosValidados) extends CobrancaPixRequest
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
        };
    }
}
