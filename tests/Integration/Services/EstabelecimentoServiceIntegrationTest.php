<?php

namespace Tests\Integration\Services;

use Tests\TestCase;
use App\Services\EstabelecimentoService;
use App\Services\ApiClientService;

class EstabelecimentoServiceIntegrationTest extends TestCase
{
    protected $apiClient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiClient = app(ApiClientService::class);
    }

    /**
     * Teste real de integração - cria um estabelecimento no ambiente sandbox.
     *
     * Campos obrigatórios:
     * - type: Tipo do estabelecimento ('BUSINESS' ou 'INDIVIDUAL')
     * - activity_id: ID do tipo de atividade (ex: 30)
     * - notes: Observações gerais
     * - visited: booleano (true/false)
     * - responsible: objeto com:
     *     - email, document (CPF), first_name, phone, birthdate
     * - address: objeto com:
     *     - zip_code, street, neighborhood, city, state, number
     * - first_name / last_name: nome ou razão social
     * - cnae: código CNAE
     * - document: CNPJ
     * - phone_number: telefone do estabelecimento
     * - email: e-mail do estabelecimento
     * - birthdate: data de abertura (YYYY-MM-DD)
     * - revenue: faturamento
     * - format: SS, SC, SPE, LTDA, etc.
     * - gmv: meta de faturamento
     *
     * Códigos de resposta esperados:
     * - 200: Requisição processada com sucesso
     * - 201: Recurso criado com sucesso
     * - 400: Requisição inválida
     * - 401: Token inválido ou ausente
     * - 404: Recurso não encontrado
     * - 500: Erro interno no servidor
     */
    public function criacao_real_de_estabelecimento() // acesso negado
    {
        $service = new EstabelecimentoService($this->apiClient);

        $dados = [
            "type" => "BUSINESS",
            "activity_id" => 30,
            "notes" => "Observação sobre o EC",
            "visited" => false,
            "responsible" => [
                "email" => "emaildoresponsavel@email.com",
                "document" => "400.752.010-03",
                "first_name" => "João Desenvolvedor",
                "phone" => "27998765431",
                "birthdate" => "2000-10-12"
            ],
            "address" => [
                "zip_code" => "29000000",
                "street" => "Rua Dos desenvolvedores",
                "neighborhood" => "Bairro da Programação",
                "city" => "Vitória",
                "state" => "ES",
                "number" => "01"
            ],
            "first_name" => "DV",
            "last_name" => "Solucoes",
            "cnae" => "0111302",
            "document" => "11.299.221/0001-29",
            "phone_number" => "27992836213",
            "email" => "emaildodoecl@email.com",
            "birthdate" => "2022-01-01",
            "revenue" => 10000,
            "format" => "LTDA",
            "gmv" => 13000
        ];

        $response = $service->criarEstabelecimento($dados);
        echo json_encode($response, JSON_PRETTY_PRINT);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('id', $response); // esperando retorno com id
    }

    public function test_recupera_dados_do_estabelecimento_pelo_id()
    {
        $service = new EstabelecimentoService($this->apiClient);
        $id = '155102';

        $response = $service->buscarEstabelecimento($id);
        echo json_encode($response, JSON_PRETTY_PRINT);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('id', $response);
        $this->assertEquals($id, $response['id']);
    }

    public function test_lista_estabelecimentos()
    {
        $service = new EstabelecimentoService($this->apiClient);

        $response = $service->listarEstabelecimentos();
        echo json_encode($response, JSON_PRETTY_PRINT);

        $this->assertIsArray($response);
        $this->assertNotEmpty($response);
    }

    public function test_atualiza_estabelecimento()
    {
        $service = new EstabelecimentoService($this->apiClient);

        $id = '155102';
        $data = [
            "access_type" => "ACQUIRER",
            "first_name" => "Desenvolvedor",
            "last_name" => "Solucoes",
            "phone_number" => "27992830000",
            "revenue" => 9999,
            "format" => "SC",
            "email" => "emaildocliente@gmail.com",
            "gmv" => 0,
            "birthdate" => "2022-01-01"
        ];
        $response = $service->atualizarEstabelecimento($id, $data);
        echo json_encode($response, JSON_PRETTY_PRINT);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('id', $response);
        $this->assertEquals($id, $response['id']);
    }
}
