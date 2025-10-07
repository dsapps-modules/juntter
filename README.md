# Juntter Checkout

Aplicação Laravel 10 para criação de links de pagamento e processamento de cobranças (PIX, cartão e boleto), com controle de acesso por papéis e integração com Paytime. Front‑end com Vite, TailwindCSS e Alpine.js.

## Visão Geral
- Back-end: Laravel 10 (PHP 8.1+), Breeze, Sanctum, Spatie Permission.
- Front-end: Vite 5, TailwindCSS 3, Alpine.js, Axios.
- Pagamentos: Integração Paytime (sandbox por padrão).
- Acesso: Dashboards por papel (`super_admin`, `admin`, `vendedor`) com middlewares `nivel.acesso` e `must.change.password`.

## Recursos
- Criação e gerenciamento de links de pagamento (cartão, PIX e boleto).
- Fluxos de cobrança (crédito à vista/parcelado, PIX, boleto), estorno e autenticação antifraude.
- Webhooks Paytime para atualização de status e criação de estabelecimentos.
- Páginas públicas de checkout e pagamento por link.
- Painéis separados por papel com limpeza de cache consolidado.

## Requisitos
- PHP 8.1+
- Composer 2.x
- Node.js 18+ e npm 9+
- MySQL/MariaDB (o `.env` de exemplo usa porta `3307`)
- Opcional: Redis, Mailpit para e-mails de desenvolvimento

## Configuração Rápida
1. Instalar dependências PHP e JS:
   - `composer install`
   - `npm install`
2. Configurar ambiente:
   - Copie um arquivo de exemplo de variáveis: `copy sample_env.txt .env` (ou ajuste manualmente).
   - Gere a chave da aplicação: `php artisan key:generate`.
3. Criar banco e credenciais conforme `.env` e rodar migrações (se aplicável):
   - `php artisan migrate`
4. Rodar ambiente de desenvolvimento:
   - Backend: `php artisan serve` (ou via Apache/Nginx apontando para `public/`).
   - Front-end (Vite): `npm run dev`.

## Variáveis de Ambiente
Principais variáveis utilizadas (veja `sample_env.txt` para referência):
- App: `APP_NAME`, `APP_ENV`, `APP_URL`, `APP_DEBUG`, `APP_KEY`.
- Banco: `DB_CONNECTION`, `DB_HOST`, `DB_PORT` (ex.: `3307`), `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`.
- Cache/Queue: `CACHE_DRIVER`, `SESSION_DRIVER`, `QUEUE_CONNECTION`.
- E-mail: `MAIL_MAILER`, `MAIL_HOST` (ex.: `mailpit`), `MAIL_PORT`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`.
- Redis (opcional): `REDIS_HOST`, `REDIS_PASSWORD`, `REDIS_PORT`.
- Paytime: `PAYTIME_BASE_URL`, `PAYTIME_INTEGRATION_KEY`, `PAYTIME_AUTHENTICATION_KEY`, `PAYTIME_X_TOKEN`.

Importante: não versione segredos. Use `sample_env.txt`/`sample_env_teste.txt` como base e mantenha `.env` fora do controle de versão. Caso algum segredo tenha sido exposto, providencie rotação imediata.

## Como Rodar
- Servidor Laravel: `php artisan serve` (ou configure Apache/Nginx com `DocumentRoot` em `public/`).
- Vite dev server: `npm run dev`.
- Build de produção: `npm run build` (gera assets versionados consumidos pelo plugin Laravel‑Vite).

## Estrutura
- Rotas
  - `routes/web.php`: páginas públicas (`/` → `resources/views/checkout.blade.php`), fluxo de pagamento por link, dashboards por papel, perfis e troca de senha.
  - `routes/api.php`: webhooks Paytime.
- Controllers
  - `app/Http/Controllers`: cobrança, links de pagamento (cartão/PIX/boleto), pagamento do cliente, dashboards, estabelecimentos, webhooks Paytime, autenticação e perfil.
- Middleware
  - `app/Http/Middleware`: `NivelAcessoMiddleware`, `MustChangePassword`, entre outros. Aliases em `app/Http/Kernel.php`.
- Views
  - `resources/views`: `checkout.blade.php`, pastas `cobranca`, `links-pagamento*`, `pagamento`, `dashboard`, `templates`, etc.
- Front-end
  - `resources/js` (`app.js`, `bootstrap.js`), `resources/css` (`app.css`), `vite.config.js`, `tailwind.config.js`, `postcss.config.js`.

## Controle de Acesso
- Papéis (via Spatie Permission): `super_admin`, `admin`, `vendedor`.
- Middlewares:
  - `nivel.acesso:{papel}`: restringe rotas por papel.
  - `must.change.password`: força troca de senha quando necessário.

## Endpoints de Pagamento (públicos)
- Exibir pagamento por link: `GET /pagamento/{codigoUnico}`
- Cartão: `POST /pagamento/{codigoUnico}/cartao`
- PIX: `POST /pagamento/{codigo}/pix`
- Boleto: `POST /pagamento/{codigo}/boleto`
- Status: `GET /pagamento/{codigoUnico}/status`
- Antifraude: `POST /pagamento/{codigo}/antifraud-auth`

## Webhooks Paytime (`routes/api.php`)
- `POST /api/webhook/paytime/update-establishment-status`
- `POST /api/webhook/paytime/update-billet-status`
- `POST /api/webhook/paytime/create-establishment`
- `POST /api/webhook/paytime/test`

Configure o endpoint no painel do Paytime (use `PAYTIME_BASE_URL` e chaves sandbox/produção adequadas). Trate assinaturas/validação conforme requisitos do provedor, se aplicável.

## Testes
- Unitários: `phpunit` via `php artisan test` ou `vendor/bin/phpunit`.
- Dusk (browser): requer Chrome/Chromedriver compatíveis. Use `php artisan dusk` (configure ambiente de teste antes de rodar).

## Deploy
- Servidor web apontando para `public/`.
- `APP_ENV=production`, `APP_DEBUG=false`.
- Cache de config/rotas/views:
  - `php artisan config:cache`
  - `php artisan route:cache`
  - `php artisan view:cache`
- Build de assets: `npm run build` e publicação dos arquivos gerados.

## Segurança
- Nunca commitar `.env` e segredos; use arquivos de exemplo.
- Rotacione credenciais se houver exposição.
- Restrinja `APP_DEBUG` a ambientes não‑produtivos.

## Suporte
Para dúvidas sobre o projeto (estrutura, rotas, integrações ou ajustes), abra uma issue interna ou entre em contato com a equipe responsável pelo Checkout Juntter.

