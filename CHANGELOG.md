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
