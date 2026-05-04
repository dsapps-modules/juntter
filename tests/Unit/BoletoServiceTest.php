<?php

namespace Tests\Unit;

use App\Services\ApiClientService;
use App\Services\BoletoService;
use PHPUnit\Framework\TestCase;

class BoletoServiceTest extends TestCase
{
    public function test_gerar_boleto_com_consulta_busca_detalhes_quando_resposta_inicial_esta_incompleta(): void
    {
        $dados = [
            'amount' => 1000,
            'expiration' => '2026-05-10',
        ];

        $apiClient = $this->createMock(ApiClientService::class);
        $apiClient->expects($this->once())
            ->method('post')
            ->with('marketplace/billets', $dados)
            ->willReturn([
                '_id' => 'boleto-123',
                'status' => 'PROCESSING',
                'amount' => 810,
                'url' => null,
                'barcode' => null,
                'digitable_line' => null,
            ]);
        $apiClient->expects($this->once())
            ->method('get')
            ->with('marketplace/billets/boleto-123')
            ->willReturn([
                '_id' => 'boleto-123',
                'status' => 'PROCESSING',
                'url' => 'https://example.test/boleto.pdf',
                'barcode' => '12345678901234567890123456789012345678901234',
                'digitable_line' => '23793.38128 60000.000000 01000.000000 1 98760000002000',
            ]);

        $service = new BoletoService($apiClient);
        $boleto = $service->gerarBoletoComConsulta($dados, 1, 0);

        $this->assertSame('boleto-123', $boleto['_id']);
        $this->assertSame('https://example.test/boleto.pdf', $boleto['boleto_url']);
        $this->assertSame('12345678901234567890123456789012345678901234', $boleto['boleto_barcode']);
        $this->assertSame('23793.38128 60000.000000 01000.000000 1 98760000002000', $boleto['boleto_digitable_line']);
    }

    public function test_gerar_boleto_com_consulta_nao_consulta_novamente_quando_resposta_ja_tem_dados(): void
    {
        $dados = [
            'amount' => 1000,
            'expiration' => '2026-05-10',
        ];

        $apiClient = $this->createMock(ApiClientService::class);
        $apiClient->expects($this->once())
            ->method('post')
            ->with('marketplace/billets', $dados)
            ->willReturn([
                '_id' => 'boleto-123',
                'status' => 'PROCESSING',
                'url' => 'https://example.test/boleto.pdf',
                'barcode' => '12345678901234567890123456789012345678901234',
                'digitable_line' => '23793.38128 60000.000000 01000.000000 1 98760000002000',
            ]);
        $apiClient->expects($this->never())->method('get');

        $service = new BoletoService($apiClient);
        $boleto = $service->gerarBoletoComConsulta($dados, 1, 0);

        $this->assertSame('https://example.test/boleto.pdf', $boleto['boleto_url']);
        $this->assertSame('12345678901234567890123456789012345678901234', $boleto['boleto_barcode']);
        $this->assertSame('23793.38128 60000.000000 01000.000000 1 98760000002000', $boleto['boleto_digitable_line']);
    }
}
