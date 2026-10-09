-- ========================================================
-- SISTEMA FLUXO — Dados Demonstrativos (Conta Oficial: alice)
-- Cobertura: 6 Meses Completos (2026-05 a 2026-10)
-- Totalmente Idempotente (Pode ser re-executado sem duplicação)
-- ========================================================

USE `fluxo_db`;

-- 1. Usuária de demonstração: Alice Silva
-- Usuário: alice
-- Senha de teste: fluxo123
-- Hash gerado via password_hash('fluxo123', PASSWORD_DEFAULT)
INSERT INTO `usuarios` (`id`, `nome`, `usuario`, `senha_hash`, `data_criacao`) 
VALUES (
    1, 
    'Alice Silva', 
    'alice', 
    '$2y$10$hhjgu/MKV/JMCT47XJyOKetpgW0lbNVqfXnw6sIW.6YXCZsJvNjIu',
    NOW()
) ON DUPLICATE KEY UPDATE `nome` = VALUES(`nome`), `usuario` = VALUES(`usuario`), `senha_hash` = VALUES(`senha_hash`);

-- 2. Orçamentos Mensais para os 6 meses (2026-05 até 2026-10)
INSERT INTO `orcamentos` (`usuario_id`, `mes_referencia`, `limite_mensal`)
VALUES 
    (1, '2026-05', 1250.00),
    (1, '2026-06', 1250.00),
    (1, '2026-07', 1300.00),
    (1, '2026-08', 1300.00),
    (1, '2026-09', 1200.00),
    (1, '2026-10', 1350.00)
ON DUPLICATE KEY UPDATE `limite_mensal` = VALUES(`limite_mensal`);

-- 3. Contas da Casa distribuídas nos 6 meses (Chaves primárias fixas para idempotência estrita)
INSERT INTO `contas` (`id`, `usuario_id`, `categoria`, `nome`, `valor`, `vencimento`, `limite_gasto`, `mes_referencia`, `status`, `observacao`, `data_pagamento`)
VALUES
-- --- 2026-05 (Maio) ---
(101, 1, 'Aluguel', 'Aluguel & Condomínio', 650.00, '2026-05-05', 700.00, '2026-05', 'paga', 'Boleto bancário pago', '2026-05-04'),
(102, 1, 'Streaming', 'Netflix & Spotify', 49.90, '2026-05-10', 60.00, '2026-05', 'paga', 'Cartão de crédito mensal', '2026-05-10'),
(103, 1, 'Água', 'Saneamento Básico (Água)', 72.40, '2026-05-15', 150.00, '2026-05', 'paga', 'Consumo regular do mês', '2026-05-14'),
(104, 1, 'Internet', 'Fibra Óptica 500MB', 99.90, '2026-05-20', 110.00, '2026-05', 'paga', 'Plano fibra residencial', '2026-05-19'),
(105, 1, 'Energia', 'Conta de Luz (CPFL/Enel)', 176.50, '2026-05-25', 220.00, '2026-05', 'paga', 'Consumo padrão de outono', '2026-05-24'),

-- --- 2026-06 (Junho) ---
(106, 1, 'Aluguel', 'Aluguel & Condomínio', 650.00, '2026-06-05', 700.00, '2026-06', 'paga', 'Boleto bancário pago', '2026-06-04'),
(107, 1, 'Streaming', 'Netflix & Spotify', 49.90, '2026-06-10', 60.00, '2026-06', 'paga', 'Cobrança recorrente', '2026-06-10'),
(108, 1, 'Água', 'Saneamento Básico (Água)', 76.80, '2026-06-15', 150.00, '2026-06', 'paga', 'Débito em conta', '2026-06-15'),
(109, 1, 'Internet', 'Fibra Óptica 500MB', 99.90, '2026-06-20', 110.00, '2026-06', 'paga', 'Pago pontualmente', '2026-06-18'),
(110, 1, 'Energia', 'Conta de Luz (CPFL/Enel)', 184.20, '2026-06-25', 220.00, '2026-06', 'paga', 'Temperatura amena', '2026-06-24'),
(111, 1, 'Outras despesas', 'Feira e Hortifruti', 58.50, '2026-06-28', 100.00, '2026-06', 'paga', 'Compras de frutas e legumes', '2026-06-28'),

-- --- 2026-07 (Julho) ---
(112, 1, 'Aluguel', 'Aluguel & Condomínio', 650.00, '2026-07-05', 700.00, '2026-07', 'paga', 'Boleto quitado', '2026-07-05'),
(113, 1, 'Streaming', 'Netflix & Spotify', 49.90, '2026-07-10', 60.00, '2026-07', 'paga', 'Recorrente', '2026-07-10'),
(114, 1, 'Água', 'Saneamento Básico (Água)', 79.50, '2026-07-15', 160.00, '2026-07', 'paga', 'Fatura quitada no vencimento', '2026-07-15'),
(115, 1, 'Internet', 'Fibra Óptica 500MB', 99.90, '2026-07-20', 110.00, '2026-07', 'paga', 'Internet fibra', '2026-07-19'),
(116, 1, 'Energia', 'Conta de Luz (CPFL/Enel)', 198.80, '2026-07-25', 240.00, '2026-07', 'paga', 'Inverno - maior uso de chuveiro elétrico', '2026-07-24'),
(117, 1, 'Outras despesas', 'Manutenção Residencial', 85.00, '2026-07-29', 120.00, '2026-07', 'paga', 'Reparo da torneira e filtro', '2026-07-29'),

-- --- 2026-08 (Agosto) ---
(118, 1, 'Aluguel', 'Aluguel & Condomínio', 650.00, '2026-08-05', 700.00, '2026-08', 'paga', 'Boleto quitado', '2026-08-04'),
(119, 1, 'Streaming', 'Netflix & Spotify', 49.90, '2026-08-10', 60.00, '2026-08', 'paga', 'Cartão de crédito', '2026-08-10'),
(120, 1, 'Água', 'Saneamento Básico (Água)', 74.50, '2026-08-15', 160.00, '2026-08', 'paga', 'Mês com consumo regular', '2026-08-14'),
(121, 1, 'Internet', 'Fibra Óptica 500MB', 99.90, '2026-08-20', 110.00, '2026-08', 'paga', 'Plano residencial', '2026-08-20'),
(122, 1, 'Energia', 'Conta de Luz (CPFL/Enel)', 188.00, '2026-08-25', 240.00, '2026-08', 'paga', 'Consumo padrão de inverno', '2026-08-24'),

-- --- 2026-09 (Setembro) — Preservando IDs oficiais 1..5 ---
(1, 1, 'Água', 'Saneamento Básico (Água)', 86.00, '2026-09-15', 200.00, '2026-09', 'paga', 'Fatura quitada no débito automático', '2026-09-15'),
(2, 1, 'Energia', 'Conta de Luz (CPFL/Enel)', 212.00, '2026-09-28', 250.00, '2026-09', 'paga', 'Uso maior de aquecedor no início do mês', '2026-09-28'),
(3, 1, 'Internet', 'Fibra Óptica 500MB', 99.90, '2026-09-26', 110.00, '2026-09', 'paga', 'Vence em breve - pago pontual', '2026-09-25'),
(4, 1, 'Streaming', 'Assinatura Música e Séries', 49.90, '2026-09-10', 60.00, '2026-09', 'paga', 'Cartão de crédito mensal', '2026-09-10'),
(5, 1, 'Outras despesas', 'Feira e Hortifruti Semanal', 39.20, '2026-09-24', 80.00, '2026-09', 'paga', 'Compras no mercado do bairro', '2026-09-24'),

-- --- 2026-10 (Outubro — Mês Atual) ---
(123, 1, 'Aluguel', 'Aluguel & Condomínio', 650.00, '2026-10-05', 700.00, '2026-10', 'paga', 'Boleto quitado no quinto dia útil', '2026-10-05'),
(124, 1, 'Streaming', 'Netflix & Spotify', 49.90, '2026-10-10', 60.00, '2026-10', 'paga', 'Débito automático no cartão', '2026-10-09'),
(125, 1, 'Água', 'Saneamento Básico (Água)', 81.20, '2026-10-15', 160.00, '2026-10', 'pendente', 'Vence no meio do mês', NULL),
(126, 1, 'Internet', 'Fibra Óptica 500MB', 99.90, '2026-10-20', 110.00, '2026-10', 'pendente', 'Fatura gerada aguardando pagamento', NULL),
(127, 1, 'Energia', 'Conta de Luz (CPFL/Enel)', 205.40, '2026-10-25', 230.00, '2026-10', 'pendente', 'Leitura realizada pela concessionária', NULL),
(128, 1, 'Outras despesas', 'Supermercado e Feira', 75.80, '2026-10-28', 100.00, '2026-10', 'pendente', 'Previsão de compras semanais', NULL)
ON DUPLICATE KEY UPDATE 
    `categoria` = VALUES(`categoria`),
    `nome` = VALUES(`nome`),
    `valor` = VALUES(`valor`),
    `vencimento` = VALUES(`vencimento`),
    `limite_gasto` = VALUES(`limite_gasto`),
    `mes_referencia` = VALUES(`mes_referencia`),
    `status` = VALUES(`status`),
    `observacao` = VALUES(`observacao`),
    `data_pagamento` = VALUES(`data_pagamento`);

-- 4. Consumos de Água e Energia (12 Registros nos 6 Meses)
INSERT INTO `consumos` (`usuario_id`, `tipo`, `mes_referencia`, `valor_consumo`, `unidade`, `valor_fatura`, `observacao`)
VALUES
-- Maio (2026-05)
(1, 'Água', '2026-05', 11200.00, 'L', 72.40, 'Consumo moderado de outono'),
(1, 'Energia', '2026-05', 175.00, 'kWh', 176.50, 'Uso normal de eletrodomésticos'),
-- Junho (2026-06)
(1, 'Água', '2026-06', 11800.00, 'L', 76.80, 'Leve variação sazonal'),
(1, 'Energia', '2026-06', 185.00, 'kWh', 184.20, 'Uso regular'),
-- Julho (2026-07)
(1, 'Água', '2026-07', 12500.00, 'L', 79.50, 'Consumo estável'),
(1, 'Energia', '2026-07', 205.00, 'kWh', 198.80, 'Mais banhos quentes no frio'),
-- Agosto (2026-08)
(1, 'Água', '2026-08', 12000.00, 'L', 74.50, 'Mês com consumo regular'),
(1, 'Energia', '2026-08', 195.00, 'kWh', 188.00, 'Consumo padrão de inverno'),
-- Setembro (2026-09)
(1, 'Água', '2026-09', 15500.00, 'L', 86.00, 'Consumo maior devido a manutenção do jardim'),
(1, 'Energia', '2026-09', 220.00, 'kWh', 212.00, 'Consumo mais elevado no período'),
-- Outubro (2026-10)
(1, 'Água', '2026-10', 13400.00, 'L', 81.20, 'Consumo voltando à média após fim da manutenção'),
(1, 'Energia', '2026-10', 210.00, 'kWh', 205.40, 'Consumo dentro do planejado para a primavera')
ON DUPLICATE KEY UPDATE 
    `valor_consumo` = VALUES(`valor_consumo`), 
    `unidade` = VALUES(`unidade`),
    `valor_fatura` = VALUES(`valor_fatura`),
    `observacao` = VALUES(`observacao`);
