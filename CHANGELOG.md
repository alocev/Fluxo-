# Registro de Alterações (CHANGELOG) — FLUXO

Todas as alterações notáveis, decisões de arquitetura e correções realizadas durante o desenvolvimento do sistema FLUXO estão documentadas neste arquivo.

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/).

---

## [1.0.0] — 2026-09-25

### Adicionado
- **Estrutura Base do Projeto:** Criação da hierarquia de diretórios `/config`, `/includes`, `/views`, `/assets`, `/database` e `/public`.
- **Banco de Dados Real:** Script `database/schema.sql` definindo as tabelas `usuarios`, `orcamentos`, `contas` e `consumos` com integridade referencial (`FOREIGN KEY ... ON DELETE CASCADE`), índices e constraints de unicidade.
- **Auto-Provisionamento do Banco:** Mecanismo em `config/database.php` que verifica e cria o banco `fluxo_db` e suas tabelas automaticamente caso não existam.
- **Dados Demonstrativos:** Script `database/seed.sql` com o cenário de teste completo (Usuário Alice Silva, orçamento de R$ 1.200, despesas somando R$ 487 e consumos de água e energia com variações de +29% e +13%).
- **Autenticação Real de Usuários:**
  - Cadastro de novos usuários em `public/cadastro.php` com validação de campos no backend e verificação de e-mail duplicado.
  - Login seguro em `public/login.php` com verificação de credenciais criptografadas via `password_verify()`.
  - Logout seguro em `public/logout.php` com destruição completa de sessão e cookies.
- **Segurança & Sessão:**
  - Carregador seguro de variáveis de ambiente sem dependências externas em `includes/env.php`.
  - Módulo de proteção CSRF em `includes/csrf.php` com geração e validação de tokens em formulários POST.
  - Configuração de cookies de sessão com atributos `httponly: true` e `samesite: 'Lax'`.
  - Regeneração do ID de sessão (`session_regenerate_id(true)`) pós-login.
  - Função global de escapamento `e()` para proteção contra Cross-Site Scripting (XSS).
- **CRUD Completo de Contas e Despesas:**
  - Listagem com filtros por situação e mês em `public/contas.php`.
  - Formulário de cadastro em `public/conta-cadastrar.php` com suporte a categorias contextuais, valores, datas, limite individual opcional e observações.
  - Edição de contas existentes em `public/conta-editar.php`.
  - Visualização detalhada da conta em `public/conta-detalhes.php` com termômetro do limite individual.
  - Alternador rápido de status (marcar como paga/pendente) em `public/conta-status.php`.
  - Exclusão com confirmação e validação estrita de propriedade em `public/conta-excluir.php`.
- **Módulo de Orçamento Mensal:**
  - Interface em `public/orcamento.php` para definir e ajustar o teto familiar por mês.
  - Cálculo automático de valor comprometido, saldo disponível e percentual utilizado.
  - Histórico dos últimos 6 meses de orçamentos e despesas.
- **Módulo de Consumo de Água e Energia:**
  - Interface em `public/consumo.php` com cartões dedicados para Água (azul) e Energia (amarelo).
  - Cálculo automático de variação percentual: `((atual - anterior) / anterior) * 100`.
  - Cadastro e edição de medições com suporte a unidades `L`, `m³` e `kWh` em `public/consumo-cadastrar.php` e `public/consumo-editar.php`.
  - Exclusão segura de leituras em `public/consumo-excluir.php`.
  - Gráficos históricos de linha via Chart.js.
- **Dashboard Doméstico Humanizado:**
  - Página inicial em `public/dashboard.php` com saudação personalizada e frase contextual dinâmica baseada no estado do orçamento.
  - Componente Hero para o orçamento mensal com barra de progresso colorida por estado.
  - Cards de resumo de consumo de água e energia com badges de variação.
  - Lista orgânica das contas mais urgentes do mês.
- **Avisos Contextuais Automáticos:**
  - Módulo `includes/alerts_helper.php` gerando notificações para orçamentos próximos do limite ($$ \ge 80\% $$) ou estourados, contas próximas do teto individual ($$ \ge 80\% $$), vencimentos (hoje, amanhã ou em 3 dias), contas vencidas e picos de consumo ($$ \ge 10\% $$).
- **Relatórios & Gráficos:**
  - Página `public/relatorios.php` com gráfico de rosca (Donut) para gastos por categoria, evolução mensal em barras/linhas e histórico detalhado.
- **Design System Natural:**
  - Folha de estilos `assets/css/style.css` baseada em verde suave (`#2D7A58`), superfícies brancas, sombras sutis, fontes modernas e identidade acolhedora.
  - Menu responsivo com gaveta lateral e overlay para dispositivos móveis em `assets/js/main.js`.
- **Testes Automatizados:**
  - Script `e2e_check.php` cobrindo 23 verificações de ponta a ponta para CRUD, autenticação, isolamento SQL, cálculos matemáticos e segurança.

### Alterado
- **Ajuste na Query de Upsert de Orçamento:** Substituído o reuso do mesmo parâmetro nomeado `:lim` em cláusula `ON DUPLICATE KEY UPDATE` por `VALUES(limite_mensal)`, garantindo total compatibilidade com PDO em modo nativo (`PDO::ATTR_EMULATE_PREPARES => false`).
- **Navegação do Topbar:** Adicionado seletor de mês dinâmico em `views/header.php` para navegação fluida entre os meses.

### Corrigido
- **Detecção de Nome de Conta no Alerta de Limite:** Ajustada a regra de notificação em `includes/alerts_helper.php` para identificar limites contextuais tanto pela categoria quanto pelo nome descritivo cadastrado pelo usuário.
- **Validação de Divisão por Zero:** Implementado tratamento em `calc_variation()` para casos onde o mês anterior não possuía medições cadastradas ou possuía valor zero.

### Segurança
- **Isolamento de Dados Multi-usuário:** Garantido que 100% das operações SQL de leitura, escrita e exclusão filtrem estritamente por `usuario_id = current_user_id()`.
- **Proteção CSRF Universal:** Tokens de segurança adicionados em todas as operações `POST`.
- **Proteção de Credenciais:** Arquivo `.env` adicionado ao `.gitignore` e modelo seguro fornecido via `.env.example`.

---

## [1.1.0] — 2026-09-26

### Adicionado
- **Autenticação por Nome de Usuário (`usuario`):**
  - Migração completa do identificador de login de e-mail para nome de usuário (`usuario VARCHAR(60) NOT NULL UNIQUE`) na tabela `usuarios`.
  - Formulário de login (`public/login.php`) e cadastro (`public/cadastro.php`) adaptados com validação de formato (`/^[a-zA-Z0-9_.-]{3,60}$/`) e mensagens de erro específicas.
  - Exibição do identificador de usuário (`@usuario`) no cabeçalho/menu lateral (`views/sidebar.php`).
- **Alternador de Visibilidade de Senha (Mostrar/Ocultar Senha):**
  - Implementação de botão com ícone de olho (`👁️` / `🙈`) nas telas de Login e Cadastro.
  - Interação via JavaScript puro (`assets/js/main.js`) com acessibilidade (`aria-label`) e estilo integrado (`assets/css/style.css`).
- **Identidade Visual Acolhedora em Tons Sálvia:**
  - Aplicação de nova paleta de cores no CSS (`assets/css/style.css`): fundo da página sálvia suave (`#EEF7F1`), verde primário (`#3A8F6B`), verde escuro/hover (`#236B4F`), verde realce (`#BFE3CE`), cards e superfícies (`#FFFFFF`), texto escuro (`#18352A`), texto secundário (`#65756D`) e bordas suaves (`#DCEBE2`).
  - Atualização dos padrões de cores e ícones das categorias em `includes/functions.php`:
    - Água: `#5BA7D1` (Ícone: 💧)
    - Energia: `#D9A928` (Ícone: ⚡)
    - Internet: `#7C83D1` (Ícone: 🌐)
    - Aluguel: `#C98568` (Ícone: 🏠)
    - Streaming: `#A66BB5` (Ícone: 📺)
    - Outras despesas: `#82928A` (Ícone: 📦)
- **Bateria de Testes Automatizados E2E (20/20 Verificações):**
  - Validação de ponta a ponta cobrindo cadastro, unicidade de usuário, login de demonstração (`alice`), estado inicial limpo (sem herança de valores), isolamento estrito de dados entre usuários concorrentes e persistência de dados.

### Alterado
- **Estrutura da Tabela `usuarios`:** Substituída a coluna `email` por `usuario VARCHAR(60) NOT NULL UNIQUE` nos scripts de criação (`database/schema.sql`) e na base ativa.
- **Usuário Demonstrativo no Seed:** Atualizado em `database/seed.sql` e no banco de dados para `nome = 'Alice Silva'`, `usuario = 'alice'`, com hash Bcrypt válido correspondente à senha `fluxo123`.
- **Dica de Acesso na Tela de Login:** Atualizada para exibir o login rápido com `alice` e `fluxo123`.
- **Controle de Sessão:** Atualizada a rotina `auth.php` para armazenar `usuario` na sessão (`$_SESSION['user_usuario']`) e disponibilizar a função `current_user_usuario()`.

### Corrigido
- **Eliminação da Herança de Orçamento Inicial de R$ 1.200:** Identificada a causa raiz em `public/cadastro.php` onde um orçamento padrão de R$ 1.200 era inserido compulsoriamente no ato do cadastro. O trecho foi removido, assegurando que todo novo usuário inicie com estado 100% limpo (R$ 0,00 de orçamento e sem contas preexistentes).
- **Consistência do Hash da Senha de Demonstração:** Corrigido o hash do seed demonstrativo que impedia a autenticação do usuário `alice` com `fluxo123`.

### Segurança
- **Garantia de Isolamento Multi-usuário Estrito:** Verificado e testado que consultas em `orcamentos`, `contas` e `consumos` utilizam restrição `WHERE usuario_id = :uid`, prevenindo vazamento de dados ou visibilidade cruzada entre contas distintas.
- **Higienização de Inputs:** Validação estrita de formato de nome de usuário para evitar injeções ou caracteres de controle indesejados.
