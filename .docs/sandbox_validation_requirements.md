# Requisitos para Validação em Sandbox

Para que possamos validar cada uma das chamadas à API da Paytime de forma segura e completa no ambiente de Sandbox, precisaremos dos seguintes elementos:

## 1. Credenciais de Sandbox
Devemos atualizar o arquivo `.env` com as chaves específicas do ambiente de testes da Paytime:
- `PAYTIME_BASE_URL`: (Geralmente `https://homolog-api.paytime.com.br` ou similar)
- `PAYTIME_INTEGRATION_KEY`
- `PAYTIME_AUTHENTICATION_KEY`
- `PAYTIME_X_TOKEN`

## 2. Massa de Dados de Teste (Mock Assets)
A Paytime fornece uma lista de dados que funcionam apenas no sandbox para simular diferentes cenários:
- **Cartões de Teste**: Números de cartão para simular:
  - **Sucesso**: Transação aprovada.
  - **Saldo Insuficiente**: Para testar o tratamento de erros.
  - **Requer 3DS**: Para testar o fluxo de autenticação antifraude.
- **CPFs/CNPJs de Teste**: Documentos válidos para o ambiente de homologação.

## 3. Contexto de Estabelecimento
Precisamos de um `establishment_id` criado dentro do Sandbox. 
- Idealmente, criaríamos um registro na nossa tabela local `paytime_establishments` (ou vinculando ao usuário vendedor atual) que aponte para este ID real do Sandbox.

## 4. Túnel para Webhooks (Crucial para PIX e Boleto)
Como o PIX e o Boleto dependem de notificações externas para mudar de status (`PAID`, `CANCELLED`), precisaremos de:
- **Ngrok ou Expose**: Para criar uma URL pública que aponte para o seu ambiente local.
- **Configuração no Dashboard Paytime**: A URL gerada (ex: `https://meu-tunel.ngrok.io/api/webhook/paytime/...`) deve ser cadastrada no painel da Paytime para que as notificações cheguem até nós.

## 5. Ferramenta de disparo (Sanity Check)
Para não depender apenas de cliques na interface, posso criar um **Artisan Command** simples (ex: `php artisan paytime:test-connection`) que:
1. Tenta fazer um `GET` no saldo.
2. Tenta uma simulação de transação.
3. Reporta o status da conexão.

---

### O que eu faria com isso?
Com esses dados em mãos, eu executaria um ciclo de validação:
1. **Teste de Conectividade**: Validar se o Token é gerado e o Saldo é retornado.
2. **Ciclo de Venda Pix**: Criar PIX -> Simular pagamento via Webhook -> Verificar alteração no banco de dados.
3. **Ciclo de Cartão com 3DS**: Iniciar transação -> Simular resposta de autenticação -> Validar finalização.
4. **Ciclo de Boleto**: Gerar -> Consultar detalhes -> Simular expiração.
