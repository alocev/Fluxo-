# FLUXO — Sistema de Gestão de Contas e Acompanhamento de Consumo Doméstico 🌿

O **FLUXO** é uma aplicação web completa desenvolvida para ajudar pessoas e famílias a organizar as contas da residência, acompanhar o orçamento mensal e monitorar as variações no consumo de recursos essenciais como água e energia elétrica.

Com uma identidade visual moderna e acolhedora em verde suave, o FLUXO afasta a frieza dos sistemas bancários tradicionais e proporciona clareza, previsibilidade e sustentabilidade para o dia a dia doméstico.

---

## 🚀 Principais Funcionalidades

- **Visão Geral Acolhedora (Dashboard):** Saudação personalizada com frase diagnóstica em tempo real sobre o teto orçamentário.
- **Destaque do Orçamento Mensal:** Teto de gastos com barra de progresso visual, cálculo de saldo disponível, gasto comprometido e status (Confortável, Atenção ou Ultrapassado).
- **Avisos Automáticos e Contextuais:** Alertas inteligentes para contas próximas ao vencimento, contas vencidas, contas atingindo o teto individual estipulado e aumentos no consumo físico.
- **CRUD Completo de Contas:** Cadastro, listagem com filtros por situação (todas, pendente, paga, vencida) e mês, visualização detalhada, edição, alternador rápido de quitação e exclusão segura.
- **Acompanhamento de Consumo de Água & Energia:** Medição física em Litros/m³ e kWh, com cálculo automático da variação percentual em relação ao mês anterior ($$ \uparrow $$ ou $$ \downarrow $$) e gráficos históricos interativos.
- **Relatórios & Gráficos:** Visualização da distribuição das despesas por categoria em gráfico Donut, evolução mensal dos valores comprometidos e histórico de consumo.
- **Autenticação Real & Isolamento de Dados:** Cadastro e login por nome de usuário (`usuario`) com hash criptográfico seguro (`password_hash` / `password_verify`), alternador de visibilidade de senha (ícone de olho 👁️/🙈), proteção contra fixação de sessão e isolamento rigoroso entre usuários no banco de dados.
- **Três Temas Visuais (Claro ☀️, Verde 🌿, Escuro 🌙):** Seletor rápido de aparência com persistência no navegador, design responsivo e alternância automática entre as duas versões oficiais da marca (logo clara e logo verde).

---

## 🛠️ Tecnologias Utilizadas

- **Backend:** PHP 8.2 (compatível com 8.0+) utilizando a extensão nativa **PDO**.
- **Banco de Dados:** MySQL / MariaDB (com integridade referencial, foreign keys e índices).
- **Frontend:** HTML5 semântico e CSS3 puro (Grid, Flexbox, Custom Properties).
- **Tipografia:** Google Fonts (*Plus Jakarta Sans*).
- **Gráficos:** Chart.js para visualizações dinâmicas e responsivas.
- **JavaScript:** Vanilla JS para interatividades, controle da gaveta mobile e feedback em tempo real.
- **Servidor:** Apache 2.4 (integrado via XAMPP).

---

## 📋 Requisitos do Sistema

- PHP 8.0 ou superior (com extensões `pdo` e `pdo_mysql` habilitadas).
- MySQL 5.7+ ou MariaDB 10.4+.
- Servidor Web Apache (ex.: XAMPP, WAMP ou PHP Built-in Server).
- Navegador moderno (Google Chrome, Firefox, Edge, Safari).

---

## ⚙️ Instalação e Execução Local

### 1. Clonar ou Baixar o Projeto
Coloque o projeto dentro da pasta de documentos web do seu servidor (por exemplo, no XAMPP):
```bash
c:\xampp\htdocs\FLUXO
```

### 2. Configurar Variáveis de Ambiente
Copie o arquivo de exemplo `.env.example` para `.env`:
```bash
copy .env.example .env
```
Abra o arquivo `.env` e confirme os dados da sua conexão local com o MySQL:
```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=fluxo_db
DB_USER=root
DB_PASS=
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost/FLUXO
APP_TIMEZONE=America/Sao_Paulo
```

### 3. Banco de Dados
O sistema conta com **auto-inicialização**! Ao carregar a aplicação pela primeira vez no navegador, o arquivo `config/database.php` cria automaticamente o banco `fluxo_db` e suas tabelas (`usuarios`, `orcamentos`, `contas`, `consumos`).

Caso prefira importar manualmente pelo terminal ou phpMyAdmin:
```bash
mysql -u root -p fluxo_db < database/schema.sql
```

Para carregar os dados demonstrativos de exemplo:
```bash
mysql -u root -p fluxo_db < database/seed.sql
```

### 4. Acessar no Navegador
Certifique-se de que o **Apache** e o **MySQL** estejam rodando e acesse:
```
http://localhost/FLUXO/
```
*(O redirecionador na raiz encaminhará você automaticamente para a área pública `public/index.php`).*

---

## 🔑 Acesso de Teste para Avaliação

Para facilitar a avaliação imediata sem necessidade de cadastrar dados do zero, você pode utilizar o usuário demonstrativo já pré-configurado:
- **Nome:** Alice Silva
- **Usuário:** `alice`
- **Senha:** `fluxo123`

*Ou, se desejar, crie um novo usuário na tela de cadastro para testar o isolamento completo de dados (novas contas começam com estado 100% limpo, sem herança de orçamentos ou contas).*

---

## 🧪 Execução de Testes Automatizados

O projeto inclui uma bateria automatizada de testes end-to-end cobrindo 23 critérios funcionais e de segurança. Para executá-la via terminal:
```bash
php e2e_check.php
```

---

## 📁 Estrutura de Arquivos

```
/FLUXO
│
├── /config
│   └── database.php             # Conexão PDO segura e auto-provisionamento do banco
│
├── /includes
│   ├── alerts_helper.php        # Gerador inteligente de avisos contextuais
│   ├── auth.php                 # Controle de sessão, autenticação e proteção de rotas
│   ├── csrf.php                 # Proteção contra ataques CSRF (Cross-Site Request Forgery)
│   ├── env.php                  # Carregador seguro de variáveis do .env
│   └── functions.php            # Funções utilitárias, formatação e variações
│
├── /public                      # Document root público da aplicação
│   ├── index.php                # Roteamento inicial para autenticados e visitantes
│   ├── login.php                # Tela de autenticação com password_verify
│   ├── cadastro.php             # Cadastro de novos usuários com password_hash
│   ├── logout.php               # Encerramento seguro de sessão
│   ├── dashboard.php            # Painel principal doméstico com orçamento e alertas
│   ├── contas.php               # Listagem filtrável e métricas de despesas
│   ├── conta-cadastrar.php      # Formulário de nova conta com limites opcionais
│   ├── conta-editar.php         # Edição de contas existentes
│   ├── conta-detalhes.php       # Visualização aprofundada dos detalhes da conta
│   ├── conta-status.php         # Ação rápida de alternar situação (paga/pendente)
│   ├── conta-excluir.php        # Exclusão segura com verificação de autorização
│   ├── orcamento.php            # Gestão e histórico do orçamento mensal
│   ├── consumo.php              # Acompanhamento de água e energia com gráficos
│   ├── consumo-cadastrar.php    # Registro de leituras dos medidores
│   ├── consumo-editar.php       # Edição de leituras de consumo
│   ├── consumo-excluir.php      # Exclusão segura de leituras
│   └── relatorios.php           # Relatórios de categorias e evolução mensal
│
├── /views                       # Componentes de interface compartilhados
│   ├── header.php               # Cabeçalho com navegação e seletor de mês
│   ├── sidebar.php              # Menu lateral e perfil do morador
│   ├── footer.php               # Rodapé e fechamento de tags DOM
│   └── alerts.php               # Renderizador de mensagens flash toast
│
├── /assets                      # Recursos estáticos
│   ├── /css/style.css           # Design system em verde suave e responsividade
│   └── /js/main.js              # Interações de tela, drawer mobile e feedback
│
├── /database
│   ├── schema.sql               # Estrutura completa do banco de dados relacional
│   └── seed.sql                 # Dados demonstrativos para teste imediato
│
├── .env.example                 # Modelo de variáveis de ambiente sem segredos
├── .gitignore                   # Ignora .env, logs e arquivos de sistema
├── e2e_check.php                # Validador automatizado de ponta a ponta
├── spec.md                      # Especificação técnica detalhada do sistema
├── CHANGELOG.md                 # Histórico real de alterações do projeto
├── index.php                    # Redirecionador da raiz para a pasta /public
└── README.md                    # Documentação principal do projeto
```

---

## 🔒 Segurança

- **SQL Injection:** 100% prevenido através do uso exclusivo de consultas preparadas com PDO.
- **XSS (Cross-Site Scripting):** Tratamento rigoroso de todas as saídas HTML com `htmlspecialchars`.
- **CSRF (Cross-Site Request Forgery):** Validação de tokens aleatórios em todas as requisições `POST`.
- **Proteção de Senhas:** Armazenamento exclusivamente com `password_hash()` (Bcrypt).
- **Isolamento de Dados:** Cada consulta inclui a restrição `WHERE usuario_id = :uid`, garantindo que nenhum usuário acerte dados de outro.
- **Segredos:** Credenciais do banco não versionadas no repositório Git.

---

## 📄 Licença e Uso Acadêmico

Projeto desenvolvido para fins educacionais e de gestão doméstica cotidiana. Distribuído sob a licença MIT.
