# Chamadas à API no CobrancaController

O `CobrancaController` é um dos principais pontos de interação com a Paytime, gerenciando desde a listagem de transações até a criação de cobranças por Cartão, PIX e Boleto.

## Lista de Chamadas à API

| Método no Controller | Serviço Utilizado | Endpoint Paytime | Finalidade |
| :--- | :--- | :--- | :--- |
| `index` | `EstabelecimentoService` | `GET marketplace/establishments/{id}` | Busca dados do estabelecimento logado. |
| `index` | `TransacaoService` | `GET marketplace/transactions` | Lista transações filtradas por estabelecimento/data. |
| `index` | `BoletoService` | `GET marketplace/billets` | Lista boletos emitidos (mesclados na listagem geral). |
| `criarTransacaoCredito`| `CreditoService` | `POST marketplace/transactions` | Cria pagamento via cartão de crédito. |
| `criarTransacaoPix` | `PixService` | `POST marketplace/transactions` | Gera transação PIX. |
| `criarTransacaoPix` | `PixService` | `GET marketplace/transactions/{id}/qrcode` | Busca o QR Code e código EMV para o PIX criado. |
| `criarBoleto` | `BoletoService` | `POST marketplace/billets` | Emite boleto bancário. |
| `simularTransacao` | `TransacaoService` | `POST marketplace/transactions/simulate` | Simula taxas e parcelamento para uma venda. |
| `obterQrCodePix` | `PixService` | `GET marketplace/transactions/{id}/qrcode` | Recupera QR Code de uma transação existente. |
| `detalhesTransacao` | `TransacaoService` | `GET marketplace/transactions/{id}` | Busca informações detalhadas de uma transação. |
| `detalhesBoleto` | `BoletoService` | `GET marketplace/billets/{id}` | Busca detalhes específicos de um boleto. |
| `estornarTransacao` | `TransacaoService` | `POST marketplace/transactions/{id}/reversal` | Realiza o estorno ou cancelamento de uma venda. |
| `saldoExtrato` | `TransacaoService` | `GET marketplace/establishments/balance` | Consulta saldo atual do estabelecimento. |
| `saldoExtrato` | `TransacaoService` | `GET marketplace/establishments/extract` | Consulta extrato de liquidações. |
| `saldoExtrato` | `TransacaoService` | `GET marketplace/transactions/future_releases`| Consulta lançamentos futuros (saldo a liberar). |
| `saldoExtrato` | `TransacaoService` | `GET marketplace/transactions/future_releases_daily`| Extrato detalhado de lançamentos futuros por dia. |
| `listarPlanos` | `TransacaoService` | `GET marketplace/plans/{id}` | Detalhes do plano comercial contratado. |
| `autenticarAntifraude` | `TransacaoService` | `POST marketplace/transactions/{id}/antifraud-auth`| Finaliza autenticação 3DS (cartão de crédito). |

---

## Como Testar (Verificação no "Mundo Real")

Para garantir que essas chamadas estão funcionando corretamente sem realizar transações reais de alto valor, seguimos estas estratégias:

### 1. Ambiente de Sandbox (Se disponível)
A Paytime geralmente fornece um ambiente de **Sandbox/Homologação**. 
- Verifique no seu arquivo `.env` se a `PAYTIME_BASE_URL` aponta para um endereço de "homolog", "sandbox" ou "staging".
- Nestes ambientes, você pode usar cartões de teste (disponíveis na documentação da Paytime) para simular sucessos e falhas (ex: falta de saldo, erro de 3DS).

### 2. Logs de Integração (Auditoria)
A aplicação utiliza intensivamente o `Log::info` e `Log::error`. Para monitorar em tempo real:
- No terminal: `tail -f storage/logs/laravel.log`
- Procure por tags como `ApiClientService`, `CobrancaController.criarTransacaoCredito`, ou `Resposta recebida`.
- Isso permite ver exatamente o JSON enviado e a resposta da API Paytime.

### 3. Testes Unitários/Feature (Mocks)
Você pode rodar testes automatizados que simulam a resposta da API (Mocks) para garantir que o Controller trata corretamente o retorno:
```bash
php artisan test --filter CobrancaController
```
> [!NOTE]
> Estes testes validam a lógica interna, mas não a conectividade real com a Paytime.

### 4. Teste de Sanidade (Smoke Tests)
Realize operações de baixo risco em produção (se necessário):
- **Simulação**: O método `simularTransacao` não cria débitos reais, apenas consulta tabelas de taxas. É seguro para testar conectividade.
- **Consulta de Detalhes**: Abrir a página de detalhes de uma transação antiga valida o `GET`.
- **Saldo/Extrato**: Acessar a tela de Saldo valida a comunicação com múltiplos endpoints de relatórios.

### 5. Monitoramento de Webhooks
Muitas chamadas da API Paytime são assíncronas (ex: status de boleto pago).
- Verifique as rotas em `api.php`.
- Você pode usar ferramentas como **Ngrok** localmente para receber notificações reais da Paytime e ver se o sistema processa corretamente a mudança de status.
