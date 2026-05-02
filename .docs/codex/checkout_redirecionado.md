# Skill Codex — Checkout Juntter: Checkout Padrão Redirecionado

## 1. Objetivo

Implementar o **checkout padrão redirecionado** do Checkout Juntter no projeto atual **Laravel + React/Vite**.

O vendedor, já cadastrado e autenticado no sistema, poderá criar links públicos de checkout para produtos específicos cadastrados no Checkout Juntter. O vendedor acessará esses links em uma área protegida chamada, por exemplo, **Meus Links de Checkout**. Ao copiar o link e inserir em um botão de sua página externa, o cliente final será redirecionado para uma página pública do Checkout Juntter, onde preencherá identificação, endereço, escolherá forma de pagamento e concluirá a transação.

O cliente final **não cria conta** e **não acessa Paytime diretamente**. Toda a experiência de pagamento ocorre dentro do Checkout Juntter. A integração com o gateway Paytime deve ser feita pelo backend Laravel.

---

## 2. Premissas funcionais confirmadas

1. Cada link é associado a **um produto específico**.
2. O vendedor pode criar **vários links**.
3. O link é público.
4. A quantidade do produto vem **travada** pelo link/oferta.
5. Os produtos são cadastrados dentro do Checkout Juntter.
6. Meios de pagamento: **Pix, Boleto e Cartão de Crédito**.
7. O pagamento ocorre em tela do próprio Checkout Juntter; o usuário não vê Paytime.
8. Gateway inicial: **Paytime**.
9. O checkout consulta CEP apenas para preencher endereço, não para calcular frete.
10. O vendedor pode configurar: logo, cor principal, nome da loja, depoimentos, rodapé, mensagem de oferta e timer de escassez.
11. Timer é apenas visual; não bloqueia desconto nem expira a compra.
12. O link carrega uma **oferta salva no banco**. Preço nunca vem pela URL.
13. Cliente final não precisa criar conta.
14. Após pagamento aprovado, redirecionar para página personalizada do vendedor; fallback: página de obrigado padrão da Juntter.
15. Pix deve ser acompanhado por webhook e tela atualizada automaticamente.
16. O vendedor terá página protegida para copiar link, ativar/desativar e ver vendas.
17. O link pode ser desativado manualmente.
18. O sistema deve registrar abandono por etapa.
19. Deve haver recuperação de carrinho abandonado por e-mail/WhatsApp.
20. Implementação no projeto atual Laravel + React/Vite.

---

## 3. Arquitetura geral

### Fluxo do vendedor

```text
Vendedor logado
  → Produtos
  → Criar/editar produto
  → Links de checkout
  → Criar link para produto/oferta
  → Configurar identidade visual e regras do link
  → Copiar URL pública
  → Inserir botão no site externo
```

### Fluxo do comprador

```text
Cliente clica no botão externo
  → /checkout/{public_token}
  → Identificação
  → Entrega/endereço
  → Pagamento
  → Backend cria pedido
  → Backend cria transação na Paytime
  → Pix: exibe QR Code e monitora status
  → Boleto: exibe linha digitável/link PDF
  → Cartão: processa pagamento via Paytime pelo backend
  → Webhook confirma pagamento
  → Página de obrigado personalizada ou fallback Juntter
```

---

## 4. Regras de segurança do link

### 4.1. Formato recomendado

```text
https://checkout.juntter.com.br/checkout/{public_token}
```

Exemplo:

```text
https://checkout.juntter.com.br/checkout/chk_9Lx7bR4YzQp2AaM
```

### 4.2. Regras obrigatórias

- O token público deve ser gerado com entropia alta.
- Nunca usar ID incremental na URL pública.
- Nunca receber preço, quantidade, ID do vendedor ou ID do produto pela URL.
- O backend deve buscar a oferta pelo `public_token`.
- O link só pode abrir se:
  - existir;
  - estiver ativo;
  - o vendedor estiver ativo;
  - o produto estiver ativo;
  - a oferta estiver válida no banco.
- Se o link estiver desativado, retornar tela amigável: “Este checkout não está disponível no momento.”

### 4.3. Token

Gerar token com prefixo legível:

```php
'chk_' . Str::random(32)
```

Criar índice único para `public_token`.

---

## 5. Modelagem de dados

Ajuste nomes conforme a estrutura real do projeto, mas implemente este desenho lógico.

### 5.1. Tabela `products`

Responsável pelos produtos cadastrados pelo vendedor.

Campos sugeridos:

```php
id
seller_id
name
slug
description
short_description
sku nullable
image_path nullable
price decimal(12,2)
status enum: active, inactive
created_at
updated_at
```

Regras:

- Produto pertence a um vendedor.
- Produto inativo não pode gerar venda.
- O preço base pode existir no produto, mas o checkout deve usar o preço congelado na oferta/link.

---

### 5.2. Tabela `checkout_links`

Representa o link público/oferta.

Campos sugeridos:

```php
id
seller_id
product_id
public_token unique
name
status enum: active, inactive, archived
quantity integer default 1
unit_price decimal(12,2)
total_price decimal(12,2)
allow_pix boolean default true
allow_boleto boolean default true
allow_credit_card boolean default true
pix_discount_type enum: none, fixed, percentage default none
pix_discount_value decimal(12,2) default 0
boleto_discount_type enum: none, fixed, percentage default none
boleto_discount_value decimal(12,2) default 0
free_shipping boolean default true
success_url nullable
failure_url nullable
expires_at nullable
visual_config json nullable
created_at
updated_at
```

Observação: mesmo que o timer seja apenas visual, `expires_at` pode existir para uso futuro. Nesta versão, não deve bloquear a compra salvo se `status != active`.

`visual_config` pode conter:

```json
{
  "store_name": "Juntter Shop",
  "logo_path": "...",
  "primary_color": "#FFC800",
  "offer_message": "OFERTA DE CONSULTOR: 5% EXTRA NO PIX OU NO BOLETO",
  "countdown_minutes": 10,
  "footer_text": "Juntter Shop: juntter.com.br",
  "testimonials": [
    {
      "name": "Cristina",
      "text": "A Juntter ajudou muito a decolar minha loja de roupas.",
      "rating": 5,
      "avatar_path": null
    }
  ]
}
```

---

### 5.3. Tabela `checkout_sessions`

Representa a jornada do cliente no checkout antes do pagamento.

Campos sugeridos:

```php
id
checkout_link_id
seller_id
product_id
session_token unique
status enum: started, identification_completed, delivery_completed, payment_started, payment_pending, paid, abandoned, cancelled, failed
current_step enum: identification, delivery, payment, confirmation
customer_name nullable
customer_email nullable
customer_document nullable
customer_document_type enum: cpf, cnpj nullable
customer_phone nullable
customer_birth_date nullable
customer_company_name nullable
customer_state_registration nullable
customer_is_state_registration_exempt boolean default false
zipcode nullable
street nullable
number nullable
complement nullable
neighborhood nullable
city nullable
state nullable
recipient_name nullable
payment_method enum: pix, boleto, credit_card nullable
subtotal decimal(12,2)
discount_total decimal(12,2) default 0
shipping_total decimal(12,2) default 0
total decimal(12,2)
metadata json nullable
last_activity_at nullable
created_at
updated_at
```

Regras:

- Criar uma sessão ao abrir o checkout.
- Salvar progresso por etapa.
- Atualizar `last_activity_at` em cada interação relevante.
- Usar `session_token` em cookie seguro ou localStorage, sem expor dados sensíveis.

---

### 5.4. Tabela `orders`

Pedido comercial final.

Campos sugeridos:

```php
id
seller_id
checkout_link_id
checkout_session_id
product_id
order_number unique
status enum: pending, paid, cancelled, failed, expired, refunded
customer_name
customer_email
customer_document
customer_phone nullable
quantity
unit_price decimal(12,2)
subtotal decimal(12,2)
discount_total decimal(12,2)
shipping_total decimal(12,2)
total decimal(12,2)
payment_method enum: pix, boleto, credit_card
success_url_used nullable
created_at
updated_at
```

Regras:

- Criar pedido ao iniciar pagamento.
- Não criar múltiplos pedidos duplicados para a mesma sessão, salvo nova tentativa controlada.
- `order_number` deve ser amigável, por exemplo `JNT-2026-000001`.

---

### 5.5. Tabela `payment_transactions`

Representa a transação enviada ao Paytime.

Campos sugeridos:

```php
id
order_id
seller_id
gateway enum: paytime
gateway_transaction_id nullable
gateway_status nullable
internal_status enum: pending, authorized, paid, failed, cancelled, expired, refunded
payment_method enum: pix, boleto, credit_card
amount decimal(12,2)
pix_qr_code nullable
pix_copy_paste nullable
pix_expires_at nullable
boleto_url nullable
boleto_barcode nullable
boleto_digitable_line nullable
card_last_four nullable
card_brand nullable
installments nullable
request_payload json nullable
response_payload json nullable
webhook_payload json nullable
created_at
updated_at
```

Regras:

- Nunca armazenar número completo de cartão.
- Nunca armazenar CVV.
- Pode armazenar bandeira, últimos quatro dígitos e parcelas.
- Toda resposta do gateway deve ser registrada com cuidado, removendo dados sensíveis caso existam.

---

### 5.6. Tabela `checkout_events`

Eventos de rastreamento e abandono.

Campos sugeridos:

```php
id
checkout_session_id nullable
checkout_link_id
seller_id
event_type
step nullable
ip_address nullable
user_agent nullable
metadata json nullable
created_at
```

Eventos mínimos:

```text
checkout_opened
identification_started
identification_completed
delivery_started
delivery_completed
payment_started
payment_method_selected
payment_submitted
pix_generated
boleto_generated
card_payment_submitted
payment_approved
payment_failed
checkout_abandoned
recovery_email_sent
recovery_whatsapp_sent
```

---

### 5.7. Tabela `abandoned_checkout_recoveries`

Campos sugeridos:

```php
id
checkout_session_id
seller_id
channel enum: email, whatsapp
status enum: pending, sent, failed, skipped
scheduled_at
sent_at nullable
error_message nullable
created_at
updated_at
```

---

## 6. Rotas Laravel

### 6.1. Rotas protegidas do vendedor

Aplicar middleware de autenticação e autorização.

```php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/seller/products', ...);
    Route::post('/seller/products', ...);
    Route::put('/seller/products/{product}', ...);

    Route::get('/seller/checkout-links', ...);
    Route::post('/seller/checkout-links', ...);
    Route::get('/seller/checkout-links/{checkoutLink}', ...);
    Route::put('/seller/checkout-links/{checkoutLink}', ...);
    Route::post('/seller/checkout-links/{checkoutLink}/activate', ...);
    Route::post('/seller/checkout-links/{checkoutLink}/deactivate', ...);
    Route::get('/seller/checkout-links/{checkoutLink}/sales', ...);
});
```

Garantir policy:

```php
$user->id === $checkoutLink->seller_id
```

ou relação equivalente conforme modelo de vendedores do sistema.

---

### 6.2. Rotas públicas do checkout

```php
Route::get('/checkout/{publicToken}', [PublicCheckoutController::class, 'show']);
Route::post('/checkout/{publicToken}/session', [PublicCheckoutSessionController::class, 'createOrResume']);
Route::post('/checkout/session/{sessionToken}/identification', [PublicCheckoutSessionController::class, 'saveIdentification']);
Route::post('/checkout/session/{sessionToken}/delivery', [PublicCheckoutSessionController::class, 'saveDelivery']);
Route::post('/checkout/session/{sessionToken}/payment', [PublicCheckoutPaymentController::class, 'startPayment']);
Route::get('/checkout/session/{sessionToken}/status', [PublicCheckoutPaymentController::class, 'status']);
Route::get('/checkout/session/{sessionToken}/thank-you', [PublicCheckoutController::class, 'thankYou']);
```

### 6.3. Webhook Paytime

```php
Route::post('/webhooks/paytime', [PaytimeWebhookController::class, 'handle']);
```

Regras:

- Validar assinatura/autenticidade conforme documentação Paytime.
- Registrar todo webhook recebido.
- Processar de forma idempotente.
- Se possível, responder rápido e despachar job para processamento.

---

## 7. Frontend React/Vite

Implementar páginas/componentes dentro do projeto atual.

### 7.1. Área protegida do vendedor

Páginas necessárias:

```text
/seller/products
/seller/products/create
/seller/products/{id}/edit
/seller/checkout-links
/seller/checkout-links/create
/seller/checkout-links/{id}/edit
/seller/checkout-links/{id}/sales
```

Funcionalidades da tela “Meus Links de Checkout”:

- listar links;
- exibir produto, preço, status, vendas, total vendido;
- botão copiar link;
- botão ativar/desativar;
- botão editar;
- botão ver vendas;
- botão criar novo link.

---

### 7.2. Página pública do checkout

URL:

```text
/checkout/{publicToken}
```

Componentes principais:

```text
CheckoutLayout
CheckoutHeader
OfferBar
CheckoutStepIdentification
CheckoutStepDelivery
CheckoutStepPayment
CheckoutSummary
CheckoutTestimonials
CheckoutFooter
PixWaitingPage
ThankYouPage
```

### 7.3. Etapa 1 — Identificação

Suportar pessoa física e jurídica.

Pessoa física:

- nome completo;
- e-mail;
- data de nascimento;
- CPF;
- celular/WhatsApp.

Pessoa jurídica:

- razão social;
- e-mail;
- data de abertura;
- CNPJ;
- inscrição estadual;
- isento de inscrição estadual;
- celular/WhatsApp.

Validações:

- e-mail válido;
- CPF/CNPJ válido;
- telefone obrigatório;
- documento obrigatório;
- nome/razão social obrigatório.

---

### 7.4. Etapa 2 — Entrega

Campos:

- CEP;
- endereço;
- número;
- bairro;
- complemento;
- cidade;
- UF;
- destinatário.

Ao digitar CEP:

- consultar API de CEP;
- preencher endereço, bairro, cidade e UF;
- permitir edição manual;
- não calcular frete.

Frete:

- nesta versão, usar frete grátis ou valor fixo vindo da oferta, se existir no futuro.

---

### 7.5. Etapa 3 — Pagamento

Opções:

- Cartão de crédito;
- Pix;
- Boleto.

Regras:

- Mostrar apenas métodos habilitados no link.
- Aplicar desconto de Pix/Boleto conforme configuração do link.
- Resumo deve refletir subtotal, desconto, frete e total.
- Ao submeter pagamento, criar pedido e transação.

#### Pix

- Backend cria transação Paytime Pix.
- Frontend exibe QR Code e código copia-e-cola.
- Frontend consulta `/status` a cada 3–5 segundos.
- Quando status mudar para pago, redirecionar para página de obrigado.

#### Boleto

- Backend cria transação Paytime Boleto.
- Frontend exibe linha digitável e link do boleto.
- Pedido fica pendente até confirmação por webhook.

#### Cartão

- O formulário pode estar na tela do Checkout Juntter.
- Backend envia dados ao Paytime.
- Não armazenar PAN nem CVV.
- Registrar apenas últimos 4 dígitos, bandeira e parcelas, se retornados pelo gateway.

Observação de segurança: futuramente migrar para tokenização/hosted fields se Paytime oferecer esse recurso. Nesta versão, aplicar o mínimo necessário para não persistir dados sensíveis.

---

## 8. Integração Paytime

Criar service dedicado:

```php
App\Services\Payments\Paytime\PaytimeClient
App\Services\Payments\Paytime\PaytimePaymentService
App\Services\Payments\PaymentGatewayInterface
```

Interface sugerida:

```php
interface PaymentGatewayInterface
{
    public function createPixPayment(Order $order): PaymentGatewayResponse;
    public function createBoletoPayment(Order $order): PaymentGatewayResponse;
    public function createCreditCardPayment(Order $order, array $cardData): PaymentGatewayResponse;
    public function parseWebhook(array $payload, array $headers): GatewayWebhookDTO;
}
```

Variáveis `.env`:

```env
PAYTIME_BASE_URL=
PAYTIME_PUBLIC_KEY=
PAYTIME_SECRET_KEY=
PAYTIME_WEBHOOK_SECRET=
PAYTIME_TIMEOUT=30
```

Regras:

- Todas as chamadas devem ter timeout.
- Tratar erro de rede.
- Logar erro sem dados sensíveis.
- Usar jobs para operações que possam demorar.
- Implementar idempotência por `order_id` e `gateway_transaction_id`.

---

## 9. Webhooks e atualização de status

### 9.1. Recebimento

Ao receber webhook Paytime:

1. Validar assinatura/autenticidade.
2. Registrar payload bruto em tabela/log seguro.
3. Identificar transação por `gateway_transaction_id` ou referência enviada na criação.
4. Atualizar `payment_transactions`.
5. Atualizar `orders`.
6. Atualizar `checkout_sessions`.
7. Disparar eventos internos, e-mails e notificações.

### 9.2. Idempotência

O mesmo webhook pode chegar mais de uma vez. Não duplicar:

- baixa de pedido;
- envio de e-mail;
- registro financeiro;
- evento de venda.

Criar chave única ou registrar hash do webhook se necessário.

---

## 10. Abandono de checkout

### 10.1. Critério de abandono

Uma sessão pode ser marcada como abandonada quando:

- status não é `paid`, `cancelled` ou `failed`;
- `last_activity_at` é anterior a X minutos;
- etapa atual é `identification`, `delivery` ou `payment`.

Configuração inicial sugerida:

```text
abandonar após 30 minutos sem atividade
```

### 10.2. Job agendado

Criar command:

```bash
php artisan checkout:mark-abandoned
```

Scheduler:

```php
$schedule->command('checkout:mark-abandoned')->everyFiveMinutes();
```

### 10.3. Recuperação

Criar command/job:

```bash
php artisan checkout:send-recovery-messages
```

Regras iniciais:

- Enviar primeiro e-mail/WhatsApp após 30–60 minutos do abandono.
- Não enviar se pedido já foi pago.
- Não enviar mais de uma recuperação por canal para a mesma sessão na primeira versão.
- Link de recuperação deve levar para a mesma sessão ou recriar sessão com dados já preenchidos.

Canais:

- E-mail: implementar com Mailable.
- WhatsApp: preparar interface `WhatsAppSenderInterface`; se integração ainda não existir, deixar implementação fake/log para futura conexão.

---

## 11. Personalização visual do vendedor

Na criação/edição do link, permitir configurar:

- nome da loja;
- logo;
- cor principal;
- mensagem de oferta;
- tempo inicial do timer visual;
- texto de rodapé;
- depoimentos.

Aplicação no checkout:

- Logo aparece no header.
- Cor principal altera botões, badges e destaques.
- Mensagem de oferta aparece na barra superior.
- Timer inicia no valor configurado, mas não bloqueia nada ao zerar.
- Rodapé usa texto configurado; se ausente, usar Juntter.
- Depoimentos aparecem no bloco lateral; se ausentes, ocultar ou usar fallback Juntter.

---

## 12. Página de obrigado

Após pagamento aprovado:

1. Se `success_url` existir no link, redirecionar para ela.
2. Caso contrário, exibir `/checkout/session/{sessionToken}/thank-you`.

Página fallback deve mostrar:

- confirmação da compra;
- número do pedido;
- produto;
- valor pago;
- meio de pagamento;
- mensagem institucional da Juntter.

---

## 13. Policies e autorização

Implementar policies para:

- ProductPolicy;
- CheckoutLinkPolicy;
- OrderPolicy.

Regra padrão:

```php
seller_id do recurso deve ser igual ao seller_id do usuário autenticado
```

Superadmin pode visualizar tudo, se o sistema já tiver esse papel.

---

## 14. Validações importantes

### Backend

- Validar CPF/CNPJ.
- Validar e-mail.
- Validar telefone.
- Validar UF.
- Validar CEP.
- Validar método de pagamento habilitado no link.
- Validar se link/produto/vendedor estão ativos.
- Recalcular totais sempre no backend.
- Ignorar totais enviados pelo frontend.

### Frontend

- Máscaras para CPF, CNPJ, telefone, CEP, cartão e validade.
- Mensagens claras de erro.
- Estados de loading.
- Prevenção de duplo clique no botão “Finalizar compra”.

---

## 15. Cálculo de totais

Criar service:

```php
App\Services\Checkout\CheckoutPricingService
```

Entrada:

- checkout_link;
- payment_method.

Saída:

```php
[
  'quantity' => 1,
  'unit_price' => 358.80,
  'subtotal' => 358.80,
  'discount_total' => 17.94,
  'shipping_total' => 0.00,
  'total' => 340.86,
]
```

Regras:

- Quantidade vem do link.
- Preço vem do link.
- Desconto depende do método.
- Frete nesta versão é grátis ou fixo zero.
- Frontend apenas exibe; backend é autoridade.

---

## 16. UX esperada conforme imagens

### Layout desktop

- Cabeçalho amarelo com logo do vendedor/Juntter.
- Selo “Pagamento 100% seguro”.
- Barra preta de oferta.
- Timer visual abaixo da mensagem.
- Coluna esquerda/central com etapas:
  - Identificação;
  - Entrega;
  - Pagamento.
- Coluna direita com resumo:
  - cupom;
  - observação;
  - produtos;
  - descontos;
  - frete;
  - total;
  - item comprado;
  - depoimentos.
- Rodapé com formas de pagamento, dados da loja e selo de segurança.

### Layout mobile

- Priorizar fluxo em uma coluna.
- Resumo pode virar accordion.
- Botão principal sempre visível ao final da etapa.
- Pix QR Code responsivo.

---

## 17. Testes obrigatórios

### Feature tests Laravel

Criar testes para:

1. vendedor autenticado cria produto;
2. vendedor cria link de checkout;
3. vendedor não acessa link de outro vendedor;
4. link público ativo abre checkout;
5. link público inativo não abre checkout;
6. preço enviado pelo frontend é ignorado;
7. identificação é salva;
8. entrega é salva;
9. pagamento Pix cria pedido e transação;
10. webhook Paytime aprova pedido;
11. webhook duplicado não duplica baixa;
12. checkout abandonado é marcado;
13. recuperação não é enviada para pedido pago.

### Testes frontend

Se o projeto tiver estrutura de testes:

- renderização das etapas;
- validação de campos obrigatórios;
- troca PF/PJ;
- aplicação de desconto Pix/Boleto;
- tela de QR Code Pix;
- redirecionamento para obrigado.

---

## 18. Ordem recomendada de implementação

1. Criar migrations e models.
2. Criar policies.
3. Criar CRUD básico de produtos do vendedor.
4. Criar CRUD de links de checkout.
5. Criar endpoint público para carregar checkout por token.
6. Criar sessão de checkout.
7. Implementar etapa de identificação.
8. Implementar etapa de entrega com consulta CEP.
9. Implementar pricing service.
10. Implementar criação de pedido.
11. Implementar Paytime service.
12. Implementar Pix.
13. Implementar Boleto.
14. Implementar Cartão.
15. Implementar webhook.
16. Implementar tela de status Pix.
17. Implementar página de obrigado.
18. Implementar eventos de rastreamento.
19. Implementar abandono.
20. Implementar recuperação por e-mail/WhatsApp.
21. Implementar testes.
22. Refinar UI conforme imagens.

---

## 19. Critérios de aceite

A funcionalidade estará pronta quando:

- Vendedor logado conseguir cadastrar produto.
- Vendedor logado conseguir criar múltiplos links para o produto.
- Vendedor conseguir copiar link público.
- Link público abrir checkout sem exigir login.
- Link inativo não permitir compra.
- Cliente conseguir preencher identificação.
- Cliente conseguir preencher endereço com CEP automático.
- Cliente conseguir escolher Pix, Boleto ou Cartão, conforme habilitado.
- Backend recalcular todos os valores sem confiar no frontend.
- Pix exibir QR Code e atualizar status após webhook.
- Boleto exibir dados de pagamento.
- Cartão criar transação sem armazenar dados sensíveis.
- Pedido aprovado redirecionar para URL personalizada ou fallback Juntter.
- Vendedor conseguir ver vendas do link.
- Abandono por etapa ser registrado.
- Recuperação ser agendada/enviada sem duplicidade.
- Testes principais passarem.

---

## 20. Observações finais para o Codex

- Não implemente preço, quantidade ou vendedor vindo pela URL.
- Não use IDs incrementais em links públicos.
- Não armazene dados completos de cartão.
- Não confie em totais calculados no frontend.
- Use services para regras de negócio, evitando controllers gordos.
- Use jobs para integração externa e recuperação.
- Use policies para isolamento entre vendedores.
- Priorize uma primeira versão funcional e segura antes de refinamentos visuais avançados.
