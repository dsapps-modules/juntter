# Chamadas à API Paytime

Esta é uma lista consolidada das chamadas à API da Paytime que a aplicação Juntter realiza, organizada por categoria.

## Core / Autenticação
- **Login**: `POST /auth/login`
  - Realizado pelo `ApiClientService` para obter o token de acesso.

## Estabelecimentos (`EstabelecimentoService`, `BalanceService`)
- **Listar Estabelecimentos**: `GET marketplace/establishments`
- **Detalhes do Estabelecimento**: `GET marketplace/establishments/{id}`
- **Criar Estabelecimento**: `POST marketplace/establishments`
- **Atualizar Estabelecimento**: `PUT marketplace/establishments/{id}`
- **Consultar Saldo**: `GET marketplace/establishments/balance`
- **Consultar Extrato**: `GET marketplace/establishments/extract`

## Transações (`TransacaoService`, `PixService`, `CreditoService`)
- **Listar Transações**: `GET marketplace/transactions`
- **Detalhes da Transação**: `GET marketplace/transactions/{codigo}`
- **Simular Transação**: `POST marketplace/transactions/simulate`
- **Criar Transação (Cartão/PIX)**: `POST marketplace/transactions`
- **Obter QR Code PIX**: `GET marketplace/transactions/{id}/qrcode`
- **Estornar Transação**: `POST marketplace/transactions/{id}/reversal`
- **Autenticação Antifraude (3DS)**: `POST marketplace/transactions/{id}/antifraud-auth`
- **Lançamentos Futuros**: `GET marketplace/transactions/future_releases`
- **Lançamentos Futuros Diários**: `GET marketplace/transactions/future_releases_daily`

## Split de Pagamento (`TransacaoService`, `SplitPreService`)
- **Aplicar Split**: `POST marketplace/transactions/{id}/split`
- **Consultar Split**: `GET marketplace/transactions/{id}/split`
- **Cancelar Split**: `DELETE marketplace/transactions/{id}/split`
- **Criar Regra Split Pré**: `POST marketplace/establishments/{estId}/split-pre`
- **Listar Regras Split Pré**: `GET marketplace/establishments/{estId}/split-pre`
- **Consultar Regra Split Pré**: `GET marketplace/establishments/{estId}/split-pre/{splitId}`
- **Atualizar Regra Split Pré**: `PUT marketplace/establishments/{estId}/split-pre/{splitId}`
- **Deletar Regra Split Pré**: `DELETE marketplace/establishments/{estId}/split-pre/{splitId}`

## Boletos (`BoletoService`)
- **Gerar Boleto**: `POST marketplace/billets`
- **Listar Boletos**: `GET marketplace/billets`
- **Consultar Boleto**: `GET marketplace/billets/{id}`
- **Recarga via Boleto**: `POST marketplace/billets/recharge`
- **Deletar Boleto**: `DELETE marketplace/billets/{id}`

## Liquidações (`LiquidacaoService`)
- **Listar Liquidações**: `GET marketplace/liquidations`
- **Extrato de Liquidações**: `GET marketplace/liquidations/extract`
- **Detalhes de Transferência**: `GET marketplace/liquidations/{liqId}/payments/{payId}/transfer`

## Planos Comerciais (`TransacaoService`)
- **Listar Planos**: `GET marketplace/plans`
- **Detalhes do Plano**: `GET marketplace/plans/{id}`

## Outros Serviços (API v1)
- **Relatórios**: `POST /v1/report` (`RelatorioService`)
- **Reembolsos**: `POST /v1/refund` (`ReembolsoService`)
- **Checkout**: `POST /v1/checkout` (`CheckoutService`)
- **Webhooks (Listar)**: `GET /v1/webhook` (`WebhookService`)
- **Webhooks (Cadastrar)**: `POST /v1/webhook` (`WebhookService`)
- **Notificações**: `POST /v1/notification` (`NotificacaoService`)
- **Pagamentos (Criar)**: `POST /v1/payment` (`PagamentoService`)
- **Pagamentos (Consultar)**: `GET /v1/payment/{codigo}` (`PagamentoService`)
