### Fase 0 - Decisão técnica

- [x] Definir o modelo da migração: React SPA + API.
- [x] Definir se a migração será total ou gradual: total.
- [ ] Congelar o comportamento atual das rotas críticas antes de trocar a UI.
- [ ] Mapear o que pode permanecer Laravel puro e o que precisa virar componente React.

#### Permanece em Laravel puro

- [ ] Autenticação de sessão, logout, verificação de e-mail, confirmação de senha e troca obrigatória de senha.
- [ ] Autorização por papel e middleware de acesso.
- [ ] Regras de negócio, serviços, jobs, commands e integrações com Paytime.
- [ ] Validações de backend e Form Requests.
- [ ] Persistência de dados, modelos, relacionamentos, factories e seeders.
- [ ] Webhooks e endpoints de integração externa.
- [ ] Testes unitários, feature, integration e browser que validam o domínio.

#### Vai para React

- [ ] Páginas públicas de apresentação e checkout.
- [ ] Tela de pagamento do cliente e retorno de sucesso.
- [ ] Login e demais telas de autenticação.
- [ ] Dashboards por perfil.
- [ ] Módulos de cobrança, links de pagamento, estabelecimentos, vendedores e perfil.
- [ ] Componentes visuais compartilhados: layout, menu, breadcrumb, cards, tabelas, modais e formulários.

#### Pontos de congelamento

- [ ] Manter intactas as rotas críticas de pagamento.
- [ ] Manter intactos os nomes e contratos dos endpoints usados pelos fluxos atuais.
- [ ] Manter as regras de redirecionamento por perfil até o novo roteamento React estar pronto.
- [ ] Manter os fluxos de erro e sucesso existentes durante a transição.

#### Rotas críticas a congelar

- [ ] `GET /` -> checkout público
- [ ] `GET /pagamento/{codigoUnico}` -> tela do cliente
- [ ] `POST /pagamento/{codigoUnico}/cartao`
- [ ] `POST /pagamento/{codigo}/pix`
- [ ] `POST /pagamento/{codigo}/boleto`
- [ ] `GET /pagamento/{codigoUnico}/status`
- [ ] `POST /pagamento/{codigoUnico}/antifraud-auth`
- [ ] `GET /pagamento/efetivado/sucesso`
- [ ] `GET /login`
- [ ] `POST /login`
- [ ] `GET /forgot-password`
- [ ] `POST /forgot-password`
- [ ] `GET /reset-password/{token}`
- [ ] `POST /reset-password`
- [ ] `GET /verify-email`
- [ ] `GET /confirm-password`
- [ ] `POST /confirm-password`
- [ ] `POST /logout`
- [ ] `GET /dashboard`
- [ ] `GET /superadmin/dashboard`
- [ ] `GET /admin/dashboard`
- [ ] `GET /vendedor/dashboard`
- [ ] `GET /cobranca`
- [ ] `GET /cobranca/planos`
- [ ] `GET /cobranca/planos/{id}`
- [ ] `GET /cobranca/saldoextrato`
- [ ] `POST /cobranca/credito-vista`
- [ ] `POST /cobranca/transacao/credito`
- [ ] `POST /cobranca/transacao/pix`
- [ ] `POST /cobranca/boleto`
- [ ] `GET /cobranca/simular`
- [ ] `POST /cobranca/simular`
- [ ] `GET /cobranca/transacao/{id}`
- [ ] `GET /cobranca/boleto/{id}`
- [ ] `GET /cobranca/transacao/{id}/qrcode`
- [ ] `POST /cobranca/transacao/{id}/estornar`
- [ ] `POST /cobranca/transacao/{id}/antifraud-auth`
- [ ] `GET /links-pagamento`
- [ ] `GET /links-pagamento/create`
- [ ] `GET /links-pagamento/{linkPagamento}`
- [ ] `GET /links-pagamento/{linkPagamento}/edit`
- [ ] `GET /links-pagamento-pix`
- [ ] `GET /links-pagamento-pix/create`
- [ ] `GET /links-pagamento-pix/{linkPagamento}`
- [ ] `GET /links-pagamento-pix/{linkPagamento}/edit`
- [ ] `GET /links-pagamento-boleto`
- [ ] `GET /links-pagamento-boleto/create`
- [ ] `GET /links-pagamento-boleto/{linkPagamento}`
- [ ] `GET /links-pagamento-boleto/{linkPagamento}/edit`
- [ ] `GET /estabelecimentos`
- [ ] `GET /estabelecimentos/export`
- [ ] `GET /estabelecimentos/search`
- [ ] `GET /estabelecimentos/{id}`
- [ ] `GET /estabelecimentos/{id}/edit`
- [ ] `GET /vendedores/faturamento`
- [ ] `GET /vendedores/acesso`
- [ ] `GET /vendedores/acesso/search`
- [ ] `GET /profile`
- [ ] `GET /profile/password`
- [ ] `GET /password/change`
- [ ] `POST /password/change`
- [ ] `POST /api/webhook/paytime`

### Fase 1 - Base do frontend

- Instalar React e Ant Design.
- Ajustar o Vite para compilar React.
- Criar a estrutura base do app.
- Criar layout principal com:
  - sidebar/menu
  - header
  - breadcrumb
  - área de conteúdo
  - footer
- Definir tema visual global do Ant Design.
- Definir tokens de cor, espaçamento e tipografia.
- Criar componentes reutilizáveis base:
  - cards
  - tabelas
  - formulários
  - modais
  - alertas
  - botões
  - filtros

### Fase 2 - Infra de navegação e acesso

- Reproduzir a lógica de redirecionamento por perfil:
  - super_admin
  - admin
  - vendedor
- Recriar a navegação por permissão no frontend.
- Garantir compatibilidade com auth, verified, nivel.acesso e must.change.password.
- Validar o fluxo de logout e troca de senha obrigatória.
- Definir estrutura de rotas do frontend.

### Fase 3 - Páginas públicas

Migrar primeiro as telas que não dependem tanto do restante do painel:

- Home pública /
- Checkout público
- Página de pagamento do cliente /pagamento/{codigoUnico}
- Tela de sucesso do pagamento
- Tela de acesso não autorizado

Por que primeiro:

- menor dependência de menu lateral e módulos internos
- valida rapidamente o novo visual
- ajuda a testar responsividade e identidade visual

### Fase 4 - Autenticação

- Login
- Registro
- Esqueci a senha
- Redefinição de senha
- Confirmação de senha
- Verificação de e-mail
- Troca de senha

Por que aqui:

- é o primeiro fluxo dos usuários internos
- prepara o terreno para os dashboards
- costuma expor problemas de sessão, validação e layout

### Fase 5 - Dashboard base

- Criar o esqueleto comum dos dashboards.
- Migrar componentes compartilhados de métricas.
- Migrar breadcrumb e filtro de data.
- Migrar estados de carregamento, vazio e erro.
- Padronizar tabelas e cards de resumo.

### Fase 6 - Dashboards por perfil

Migrar em ordem de impacto e complexidade:

- Dashboard do vendedor
- Dashboard do admin
- Dashboard do super admin
- Dashboard do comprador, se ainda for usado

Sugestão de ordem:

1. vendedor
2. admin
3. super admin
4. comprador

Motivo:

- o vendedor já depende de vários blocos críticos de negócio
- o admin consolida dados maiores
- o super admin tende a ser mais resumido
- o comprador parece ser tela menos central ou até legado

### Fase 7 - Cobrança

Essa é uma das áreas mais importantes e mais sensíveis da aplicação.

Migrar nesta ordem:

- cobranca/index
- cobranca/simular
- cobranca/detalhes
- cobranca/boleto-detalhes
- cobranca/saldoextrato
- cobranca/planos
- cobranca/plano-detalhes

Depois, avaliar as telas legadas:

- cobranca/pix
- cobranca/pagarcontas
- cobranca/recorrente

Observação:

- essas três últimas parecem legadas ou não ativas nas rotas hoje; eu migraria só se ainda fizerem parte do produto.

### Fase 8 - Links de pagamento

Migrar por tipo, reaproveitando o mesmo padrão visual e de formulário:

- Links de pagamento cartão
- Links de pagamento PIX
- Links de pagamento boleto

Em cada módulo:

- listagem
- criação
- edição
- visualização
- ações de status
- exclusão

Motivo:

- os três módulos são quase um mesmo produto com variações
- vale extrair um padrão único de formulário e tabela

### Fase 9 - Estabelecimentos

- Lista de estabelecimentos
- Detalhe do estabelecimento
- Edição do estabelecimento
- Exportação
- Regras de split pré

Motivo:

- depende bastante de estrutura de tabela, filtros e detalhe
- é um módulo bom para consolidar o design system

### Fase 10 - Vendedores

- Faturamento
- Acesso
- Busca de estabelecimentos disponíveis
- Criação de acesso
- Edição de acesso
- Atualização de senha
- Remoção de acesso

Motivo:

- usa formulários e tabelas com estado mais complexo
- deve vir depois do padrão de CRUD estar estável

### Fase 11 - Perfil

- Página principal de perfil
- Edição de dados
- Troca de senha
- Exclusão de conta

Motivo:

- é um fluxo menor, mas importante para completar a experiência do usuário

### Fase 12 - Componentes legados

- Reescrever componentes Blade reutilizáveis em React.
- Substituir navbar, footer e breadcrumb antigos.
- Migrar os componentes de formulário.
- Migrar os componentes de modal, alert e cards utilitários.
- Remover dependência visual de AdminLTE, Bootstrap e jQuery, se ainda estiverem sendo usados.

### Fase 13 - Backend de apoio

- Revisar controllers para retornar dados no formato que o React espera.
- Criar endpoints auxiliares se a tela passar a precisar de carga assíncrona.
- Revisar validações e mensagens de erro.
- Revisar paginação, filtros e ordenação.
- Garantir que os dados necessários não dependam mais de Blade.

### Fase 14 - Testes e validação

- Atualizar testes de browser para os fluxos novos.
- Atualizar testes feature para os endpoints alterados.
- Validar autenticação, permissão, troca de senha e pagamento.
- Validar responsividade desktop e mobile.
- Validar build de produção.
- Validar que páginas críticas continuam acessíveis por perfil.

### Fase 15 - Corte final

- Desligar as views Blade substituídas.
- Remover layouts antigos que não forem mais usados.
- Revisar dependências antigas de frontend.
- Fazer limpeza final de rotas e componentes obsoletos.

### Ordem recomendada de implementação

1. Base técnica React + Ant Design
2. Layout e navegação
3. Autenticação
4. Páginas públicas
5. Dashboards
6. Cobrança
7. Links de pagamento
8. Estabelecimentos
9. Vendedores
10. Perfil
11. Limpeza de legado
12. Testes finais
