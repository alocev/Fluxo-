# Especificação Técnica do Sistema FLUXO (spec.md)

> **Versão:** 1.3.0  
> **Status:** Sistema Totalmente Implementado, Polido e Funcional  
> **Ambiente de Referência:** PHP 8.2+ / MySQL 10.4+ (MariaDB) / XAMPP / Apache  

---

## 1. Visão Geral

O **FLUXO** é uma aplicação web completa desenvolvida para apoiar pessoas e famílias no gerenciamento doméstico de despesas e no acompanhamento consciente do consumo de recursos essenciais como água e energia elétrica.

A proposta de valor do sistema reside em eliminar o atrito de softwares corporativos e planilhas complexas, oferecendo uma experiência acolhedora, visualmente equilibrada em atmosfera verde suave, com títulos curtos e objetivos, que responde instantaneamente à pergunta fundamental do morador: *"Como estão minhas contas e o consumo da minha casa este mês?"*.

---

## 2. Problema

Em muitas residências, as contas só são notadas no dia do vencimento ou do pagamento. Os moradores enfrentam dificuldades frequentes como:
- Falta de percepção sobre quanto do orçamento mensal já foi comprometido;
- Desconhecimento de quais contas ainda estão pendentes ou próximas de vencer;
- Insegurança sobre quanto ainda é possível gastar até o fim do mês;
- Dificuldade para identificar anomalias, como um aumento repentino no valor de uma conta variável;
- Falta de histórico e visão percentual sobre o aumento ou redução do consumo físico de água (litros/m³) e energia (kWh);
- Sensação de sobrecarga gerada por sistemas com estética bancária fria e rígida.

---

## 3. Solução

O **FLUXO** reúne em uma interface orgânica e acolhedora:
- **Painel Inicial Doméstico:** Saudação humanizada com diagnóstico em tempo real do estado financeiro do mês;
- **Hero de Orçamento Mensal:** Visualizador proeminente do teto de gastos familiar, valor comprometido, valor livre e barra de progresso com indicativo de estado (Confortável, Atenção ou Ultrapassado);
- **Avisos Contextuais Automáticos:** Notificações inteligentes que alertam sobre limites atingidos (ex.: conta de luz próxima do limite individual), vencimentos nos próximos dias e variações anormais de consumo;
- **Gestão de Contas (CRUD Completo):** Controle por categorias contextuais (Água, Energia, Internet, Aluguel, Streaming, Outras despesas), status (pendente, paga, vencida) e limites individuais opcionais;
- **Acompanhamento de Consumo:** Registro e cálculo automático da variação percentual de água e energia em relação ao mês anterior, com gráficos de linha interativos;
- **Relatórios & Gráficos:** Gráfico de distribuição por categorias (Donut), evolução mensal dos gastos e comparativo físico de recursos.

---

## 4. Funcionalidades Implementadas

### 4.1 Autenticação e Gestão de Usuários
- Cadastro de novos usuários com validação de dados no servidor e unicidade de nome de usuário (`usuario`);
- Login seguro utilizando nome de usuário (`usuario`) e verificação de hash criptográfico (`password_verify`);
- Alternância de visibilidade da senha (mostrar/ocultar senha com ícone de olho) no login e cadastro;
- Proteção contra fixação de sessão com `session_regenerate_id(true)`;
- Logout seguro com destruição de sessão e expiração de cookies;
- Isolamento estrito de dados entre diferentes usuários (`usuario_id`), garantindo que novas contas iniciem com saldo e contas limpas (sem herança de valores).

### 4.2 Gestão de Contas e Despesas (CRUD)
- **Criar:** Cadastro informando categoria, nome, valor, data de vencimento, mês de referência, limite de gasto individual opcional e observação;
- **Consultar / Listar:** Visualização filtrável por mês e por situação (todas, pendente, paga, vencida) com contadores de resumo financeiro;
- **Visualizar:** Página de detalhes individuais exibindo histórico, observações e termômetro do limite individual;
- **Editar:** Alteração completa dos dados com validação rigorosa de campos;
- **Excluir:** Remoção com confirmação em interface e validação de propriedade no backend;
- **Alternar Status:** Ação rápida para marcar conta como paga (com gravação da data de pagamento) ou retornar para pendente.

### 4.3 Orçamento Mensal
- Definição e ajuste do teto de gastos (`limite_mensal`) para qualquer mês;
- Cálculo em tempo real do valor comprometido (soma das contas do mês) e do saldo disponível;
- Percentual de uso do orçamento com transições de cor (verde para confortável, amarelo para alerta a partir de 80%, vermelho para estouro);
- Histórico comparativo dos últimos 6 orçamentos cadastrados.

### 4.4 Consumo de Água e Energia
- Registro da medição em unidades apropriadas (`L`, `m³`, `kWh`) e valor faturado opcional;
- Cálculo automático da variação percentual contra o mês anterior:  
  $$\text{Variação} = \left(\frac{\text{Consumo Atual} - \text{Consumo Anterior}}{\text{Consumo Anterior}}\right) \times 100$$
- Indicadores visuais claros de subida ($$ \uparrow $$) ou descida ($$ \downarrow $$);
- Tratamento robusto para ausência de histórico anterior ou divisão por zero;
- Gráficos interativos com curvas de tendência históricas.

### 4.5 Alertas Inteligentes do Sistema
- Alerta de orçamento quando atingir $$ \ge 80\% $$ ou quando ultrapassado;
- Alerta de limite individual de conta quando $$ \ge 80\% $$ ou quando estourado;
- Alerta de contas a vencer (hoje, amanhã ou nos próximos 3 dias) e contas vencidas;
- Alerta de aumento físico de consumo de água ou energia $$ \ge 10\% $$ em relação ao mês anterior.

### 4.6 Relatórios
- Distribuição de despesas por categoria com gráfico Donut e tabela detalhada de percentuais;
- Evolução cronológica mensal de gastos comprometidos versus valores quitados;
- Tabela de histórico das medições de consumo físico.

---

## 5. Tecnologias Utilizadas

- **Linguagem Backend:** PHP 8.2 (compatível com PHP 8.0+)
- **Banco de Dados:** MySQL / MariaDB via extensão nativa **PDO**
- **Frontend:** HTML5 semântico, CSS3 moderno (Custom Properties, Flexbox, CSS Grid)
- **Tipografia:** Google Fonts (*Plus Jakarta Sans*)
- **Biblioteca de Gráficos:** Chart.js (via CDN oficial)
- **JavaScript:** Vanilla JS moderno para interatividade, gaveta mobile, cálculos em tempo real e modais
- **Servidor Web:** Apache 2.4 (integrado ao XAMPP)

### 5.1 Identidade Visual, Logotipos e Sistema de Temas

O sistema dispõe de três identidades visuais selecionáveis pelo usuário, com persistência via `localStorage`:
- **Modo Verde (Principal Identidade do FLUXO):** Atmosfera acolhedora em camadas verdes:
  - Fundo geral: `#EEF7F1`
  - Sidebar: `#1B4533` (verde floresta escuro que confere sofisticação e contraste)
  - Cards e superfícies: `#FFFFFF` com bordas suaves `#DCEBE2`
  - Ações e botões primários: `#3A8F6B` (hover `#236B4F`)
  - Textos: principal `#18352A`, secundário `#65756D`
- **Modo Claro (☀️ Claro):** Visual limpo, leve e moderno com foco em legibilidade diurna:
  - Fundo geral: `#F7FAF8`
  - Cards e Sidebar: `#FFFFFF` com bordas `#E2ECE6`
  - Cor primária: `#2F855A` (hover `#226745`)
  - Textos: principal `#1A2E26`, secundário `#5C7066`
- **Modo Escuro (🌙 Escuro):** Dark mode autêntico com baixo cansaço visual:
  - Fundo geral: `#111A15`
  - Cards e superfícies: `#1B2922` (mais claros que o fundo)
  - Sidebar: `#15221B` com bordas `#24372D`
  - Cor primária: `#45A67B` (hover `#56BA8D`)
  - Textos: principal `#E7F3EC`, secundário `#9EB5A8`

#### Logotipos Oficiais e Favicon
- **Logo de Fundo Claro (`assets/img/logo-light.png`):** Utilizada no Modo Claro e sobre fundos claros onde a versão escura perderia contraste.
- **Logo de Fundo Verde (`assets/img/logo-green.jpg`):** Utilizada no Modo Verde e Modo Escuro, proporcionando alto contraste e destaque da marca.
- **Favicon Oficial:** Definido como `assets/img/logo-green.jpg` devido à sua máxima legibilidade em abas de navegadores tanto em temas claros quanto escuros.

#### Cores das Categorias de Despesas
- **Água:** `#5BA7D1` (Ícone: 💧)
- **Energia:** `#D9A928` (Ícone: ⚡)
- **Internet:** `#7C83D1` (Ícone: 🌐)
- **Aluguel:** `#C98568` (Ícone: 🏠)
- **Streaming:** `#A66BB5` (Ícone: 📺)
- **Outras despesas:** `#82928A` (Ícone: 📦)

---

## 6. Arquitetura do Banco de Dados

O banco de dados oficial é o `fluxo_db`, com codificação `utf8mb4` e collation `utf8mb4_unicode_ci`.

### 6.1 Tabela `usuarios`
| Campo | Tipo | Nulo | Descrição |
|---|---|---|---|
| `id` | INT AUTO_INCREMENT | NÃO | Chave primária |
| `nome` | VARCHAR(120) | NÃO | Nome completo do usuário |
| `usuario` | VARCHAR(60) | NÃO | Nome de usuário único para login (Unique Key) |
| `senha_hash` | VARCHAR(255) | NÃO | Hash seguro da senha (`password_hash`) |
| `data_criacao` | TIMESTAMP | NÃO | Data e hora do cadastro |

### 6.2 Tabela `orcamentos`
| Campo | Tipo | Nulo | Descrição |
|---|---|---|---|
| `id` | INT AUTO_INCREMENT | NÃO | Chave primária |
| `usuario_id` | INT | NÃO | Chave estrangeira referenciando `usuarios(id)` ON DELETE CASCADE |
| `mes_referencia` | VARCHAR(7) | NÃO | Mês no formato `YYYY-MM` (ex.: `2026-09`) |
| `limite_mensal` | DECIMAL(10,2) | NÃO | Teto de orçamento planejado |
| `data_criacao` | TIMESTAMP | NÃO | Data de cadastro |
| `data_atualizacao`| TIMESTAMP | NÃO | Data da última alteração |

> **Restrição:** `UNIQUE KEY (usuario_id, mes_referencia)` para assegurar um único teto por mês por usuário.

### 6.3 Tabela `contas`
| Campo | Tipo | Nulo | Descrição |
|---|---|---|---|
| `id` | INT AUTO_INCREMENT | NÃO | Chave primária |
| `usuario_id` | INT | NÃO | Chave estrangeira para `usuarios(id)` ON DELETE CASCADE |
| `categoria` | ENUM | NÃO | 'Água', 'Energia', 'Internet', 'Aluguel', 'Streaming', 'Outras despesas' |
| `nome` | VARCHAR(150) | NÃO | Identificação da conta (ex.: Conta de Luz CPFL) |
| `valor` | DECIMAL(10,2) | NÃO | Valor monetário em reais |
| `vencimento` | DATE | NÃO | Data de vencimento |
| `limite_gasto` | DECIMAL(10,2) | SIM | Limite individual opcional para a conta |
| `mes_referencia`| VARCHAR(7) | NÃO | Mês de competência (`YYYY-MM`) |
| `status` | ENUM | NÃO | 'pendente', 'paga', 'vencida' |
| `observacao` | TEXT | SIM | Observações ou anotações |
| `data_pagamento`| DATE | SIM | Data da quitação |
| `data_criacao` | TIMESTAMP | NÃO | Data do registro |

### 6.4 Tabela `consumos`
| Campo | Tipo | Nulo | Descrição |
|---|---|---|---|
| `id` | INT AUTO_INCREMENT | NÃO | Chave primária |
| `usuario_id` | INT | NÃO | Chave estrangeira para `usuarios(id)` ON DELETE CASCADE |
| `tipo` | ENUM | NÃO | 'Água' ou 'Energia' |
| `mes_referencia`| VARCHAR(7) | NÃO | Mês de leitura (`YYYY-MM`) |
| `valor_consumo`| DECIMAL(10,2) | NÃO | Valor numérico (ex.: 15500.00 ou 220.00) |
| `unidade` | VARCHAR(20) | NÃO | 'L', 'm³', 'kWh' |
| `valor_fatura` | DECIMAL(10,2) | SIM | Valor em R$ cobrado na fatura |
| `observacao` | VARCHAR(255) | SIM | Observações |
| `data_registro` | TIMESTAMP | NÃO | Data do registro |

> **Restrição:** `UNIQUE KEY (usuario_id, tipo, mes_referencia)` para evitar medições duplicadas do mesmo recurso no mesmo mês.

---

## 7. Regras de Negócio Implementadas

1. **Cálculo de Orçamento Mensal:**  
   $$\text{Gasto Comprometido} = \sum \text{contas.valor do mês}$$  
   $$\text{Saldo Disponível} = \text{limite\_mensal} - \text{Gasto Comprometido}$$  
   $$\text{Percentual Utilizado} = \left(\frac{\text{Gasto Comprometido}}{\text{limite\_mensal}}\right) \times 100$$

2. **Estados do Orçamento:**
   - $\text{Percentual} < 80\%$: *Confortável* (Verde suave).
   - $80\% \le \text{Percentual} \le 100\%$: *Atenção ao Limite* (Amarelo).
   - $\text{Percentual} > 100\%$: *Orçamento Ultrapassado* (Vermelho).

3. **Limites Individuais de Despesas:**  
   Caso uma conta possua `limite_gasto > 0`, é calculado $\frac{\text{valor}}{\text{limite\_gasto}} \times 100$. Se atingir $\ge 80\%$, um aviso contextual é emitido no dashboard. Se for superior a 100%, é disparado alerta de teto estourado.

4. **Cálculo de Variação de Consumo:**  
   Compara o valor do mês corrente com o mês imediatamente anterior ($M-1$). Se houver aumento $\ge 10\%$, emite aviso de atenção; se houver redução $\ge 10\%$, emite mensagem de incentivo à economia.

5. **Atualização Automática de Status de Contas:**  
   Contas com status `pendente` cuja data de vencimento seja anterior à data atual do sistema (`vencimento < CURDATE()`) são automaticamente promovidas a `vencida`.

---

## 8. Segurança e Boas Práticas

- **Prepared Statements (PDO):** Todas as instruções SQL utilizam queries preparadas com parâmetros vinculados (`:param`). Não há concatenação direta de dados fornecidos pelo usuário.
- **Hash de Senhas:** As senhas dos usuários são processadas exclusivamente através de `password_hash($senha, PASSWORD_DEFAULT)` e checadas via `password_verify()`. Senhas nunca são salvas em texto puro.
- **Prevenção contra XSS:** Toda saída de dados em tela é tratada pela função global `e($string)` que encapsula `htmlspecialchars($string, ENT_QUOTES, 'UTF-8')`.
- **Proteção CSRF:** Formulários POST contêm um campo oculto com token criptograficamente seguro gerado por `bin2hex(random_bytes(32))`, validado por `hash_equals()`.
- **Sessões Seguras:** Configuração de cookies com `httponly = true`, `samesite = 'Lax'`, além de `session_regenerate_id(true)` no login para mitigar ataques de fixação de sessão.
- **Isolamento de Dados no Backend:** Todas as consultas `SELECT`, `UPDATE` e `DELETE` incluem a cláusula `WHERE usuario_id = :uid`. Um usuário não consegue visualizar, alterar ou excluir registros alheios mesmo forjando IDs no formulário.
- **Credenciais e Ambiente:** O arquivo `.env` está registrado no `.gitignore`. As configurações são fornecidas através de `.env.example`.

---

## 9. Instalação e Execução Local

### Pré-requisitos
- XAMPP (ou ambiente com PHP 8.0+ e MySQL 5.7+ / MariaDB)
- Git

### Passo a Passo
1. Clonar ou mover a pasta do projeto para `c:\xampp\htdocs\FLUXO`;
2. Iniciar os módulos **Apache** e **MySQL** no XAMPP Control Panel;
3. Criar o arquivo `.env` na raiz do projeto copiando o modelo:
   ```bash
   copy .env.example .env
   ```
4. Ajustar as credenciais no `.env` se necessário (por padrão, no XAMPP, `DB_USER=root` e `DB_PASS=` vazio);
5. O banco de dados e as tabelas são criados automaticamente no primeiro acesso ao sistema via `config/database.php`.
   - Se desejar popular os dados demonstrativos de teste, importe o arquivo `database/seed.sql` pelo phpMyAdmin ou terminal.
6. Acessar no navegador:
   `http://localhost/FLUXO/`

### Credenciais Demonstrativas de Teste
- **Nome:** Alice Silva
- **Usuário:** `alice`
- **Senha:** `fluxo123`
*(Ou crie um novo usuário na tela de cadastro).*

---

## 10. Limitações Conhecidas

- O envio de e-mails para recuperação de senha não foi acoplado a um servidor SMTP externo, dependendo de redefinição pelo administrador caso necessário.
- A leitura dos dados de consumo é feita por apontamento manual do usuário (ou conferência da fatura), não havendo integração via IoT/smart meters residenciais.

---

## 11. Futuras Melhorias

- Integração com leitor de código de barras ou linha digitável de boletos (PIX / Código de Barras);
- Notificações de vencimento via Telegram Bot ou WhatsApp Webhook;
- Upload e anexo de comprovantes de pagamento em PDF ou imagem;
- Exportação de relatórios em formato PDF e planilha Excel (CSV).
