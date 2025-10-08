# 🧾 Pré-cheques

---

### 🔧 Alinhamento do provedor
- O backend usa **Paytime**, mas o front (`public/js/checkout-scripts.js`) chama **PagSeguro.authenticate3DS**.  
  → **Escolha um só provedor** e alinhe SDK/fluxo.  
- Se optar por **Paytime**:
  - Troque o SDK.
  - Ajuste os campos esperados (`session`, `resultado`, etc.).

### 🧪 Sandbox habilitado
- Use **chaves sandbox**.
- Se disponível, **ative 3DS** no painel do provedor (Paytime).

### 🌐 URLs de retorno / callback
- Confirme que `redirect`, `callback` e `webhook` estão configurados e acessíveis.  
- Verifique as rotas:
  - `pagamento.*`
  - Webhooks definidos em `routes/api.php:1`.

---

# 🧠 Cenários de Teste (Manuais)

---

### ✅ Frictionless (sem desafio)
- Use cartão de teste que force 3DS **aprovado sem desafio**.  
- Verifique:
  - Transação autorizada.
  - Retorno de **ECI/CAVV válidos**.

### ⚔️ Challenge (com desafio)
- Use cartão de teste que **force o desafio**.
- Confirme:
  - Exibição do **iframe/modal**.
  - Conclusão do desafio.
  - Autorização segue com **ECI adequado**.

### ❌ Falha / Abortado
- Force **falha no desafio ou cancelamento**.
- Verifique:
  - Mensagens de erro no **frontend**.
  - **Status de recusa** no **backend**.

### 🔁 Repetição / Timeout
- Simule **refresh** ou **timeout** durante o desafio.
- Confirme **idempotência** (sem duplicar transações).

---

# 👀 O que Observar (Evidências)

---

### 🖥️ Front-end
- Resposta do `POST` de início de pagamento deve indicar:
  - `requires_3ds`
  - `session_id` (ou equivalente)
  - `transaction_id`
- O **SDK 3DS** deve:
  - Renderizar o desafio quando necessário.
  - Resolver a `Promise` com um objeto contendo:
    - `CAVV`, `ECI`, `transactionId`, `paresStatus`, etc.
- Após o desafio:
  - Deve haver `POST` com o resultado para o backend.  
    → No script, há `enviarResultado3DS(...)` — **confirme rota/handler**.

### ⚙️ Backend
- Logue e persista os **campos 3DS**:
  - `versão`, `ECI`, `CAVV`, `ds_transaction_id`, `eci`, `paresStatus`.
- ⚠️ **Não logue PAN/CVV.**
- Autorização no gateway deve usar os **dados 3DS recebidos** (evita *soft decline*).
- Atualização de status da transação ocorre via **webhook**:
  - Verifique as rotas em `routes/api.php:1`.
  - Valide a cadeia de eventos.

---

# 🧭 Diagnóstico Rápido

---

### 🧰 DevTools → Network
1. Iniciar pagamento  
2. Resposta com `requires_3ds`  
3. Chamada ao SDK  
4. Envio do resultado 3DS  
5. Autorização / captura

### 🪵 Logs do Laravel
- Monitore `storage/logs/laravel.log` nos seguintes pontos:
  - `processarCartao`
  - `autenticarAntifraude`
  - Chamadas que consomem o resultado 3DS  
    (ver `PagamentoClienteController` / `CobrancaController`)

### 📡 Webhooks
- Simule ou force **webhook Paytime**.
- Verifique o processamento:
  - Rotas `api/webhook/paytime/*`
  - Preferencialmente com **validação de assinatura/HMAC**, se suportado.

---

# ✅ Critérios de Aceite

---

| Cenário | Resultado Esperado |
|----------|--------------------|
| **Frictionless** | Autorização OK e campos 3DS salvos (`ECI 05/06` comum em 3DS 2.x). |
| **Challenge** | Desafio exibido, sucesso propaga e status final **aprovado**. |
| **Falha** | UX clara e status coerente (**sem pendência**). |
| **Sem duplicação** | Reentrância/refresh **não cria transações duplicadas**. |

---

# 🧩 Observações Específicas do Código

- Verifique se o **endpoint que recebe o resultado 3DS** existe.  
  O JS referencia `enviarResultado3DS`, mas é preciso confirmar a rota no Laravel.
- Rotas já mapeadas para **antifraude/3DS**:
  - `routes/web.php:1` → `PagamentoClienteController@autenticarAntifraude`
  - `CobrancaController@autenticarAntifraude`
- Garanta:
  - Que o front chama a **rota correta**.
  - Que o backend devolve exatamente o que o **SDK espera**.
- Se seguir com **Paytime**:
  - Substitua o uso de **PagSeguro** no JS pelo SDK/fluxo **Paytime**.  
  - Ou adapte o backend para gerar `session_id` compatível com o SDK escolhido.
