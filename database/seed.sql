-- ========================================================
-- SISTEMA FLUXO — Dados Demonstrativos (Opcional / Demonstração)
-- ========================================================

USE `fluxo_db`;

-- Usuário de demonstração: Alice Silva
-- Senha de teste: fluxo123
-- Hash gerado via password_hash('fluxo123', PASSWORD_DEFAULT)
INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha_hash`, `data_criacao`) 
VALUES (
    1, 
    'Alice Silva', 
    'alice@fluxo.local', 
    '$2y$10$v7mN3xI25Y29/xLekVwXkOG7Y1Q0fVZZ4o3bFwBw3Z7yI/HlM5q/e',
    NOW()
) ON DUPLICATE KEY UPDATE `nome` = VALUES(`nome`);

-- Orçamento para 2026-09: R$ 1.200,00
INSERT INTO `orcamentos` (`usuario_id`, `mes_referencia`, `limite_mensal`)
VALUES (1, '2026-09', 1200.00)
ON DUPLICATE KEY UPDATE `limite_mensal` = VALUES(`limite_mensal`);

-- Contas do mês de Setembro (Totalizando R$ 487,00)
-- 1) Água: R$ 86,00 (Limite 200, Vence dia 15)
-- 2) Energia: R$ 212,00 (Limite 250 -> 84.8% ~85% do limite)
-- 3) Internet: R$ 99,90 (Vence amanhã/próximo)
-- 4) Streaming: R$ 49,90 (Paga)
-- 5) Outras despesas: Feira/Hortifruti R$ 39,20
INSERT INTO `contas` (`id`, `usuario_id`, `categoria`, `nome`, `valor`, `vencimento`, `limite_gasto`, `mes_referencia`, `status`, `observacao`)
VALUES
(1, 1, 'Água', 'Saneamento Básico (Água)', 86.00, '2026-09-15', 200.00, '2026-09', 'paga', 'Fatura quitada no débito automático'),
(2, 1, 'Energia', 'Conta de Luz (CPFL/Enel)', 212.00, '2026-09-28', 250.00, '2026-09', 'pendente', 'Uso maior de aquecedor no início do mês'),
(3, 1, 'Internet', 'Fibra Óptica 500MB', 99.90, '2026-09-26', 110.00, '2026-09', 'pendente', 'Vence em breve'),
(4, 1, 'Streaming', 'Assinatura Música e Séries', 49.90, '2026-09-10', 60.00, '2026-09', 'paga', 'Cartão de crédito mensal'),
(5, 1, 'Outras despesas', 'Feira e Hortifruti Semanal', 39.20, '2026-09-24', 80.00, '2026-09', 'paga', 'Compras no mercado do bairro')
ON DUPLICATE KEY UPDATE `valor` = VALUES(`valor`), `status` = VALUES(`status`);

-- Consumos de Água e Energia (Histórico Agosto e Setembro para demonstrar cálculo de variação real)
-- Água: Agosto = 12.000 L, Setembro = 15.500 L (+29,17% aumento)
-- Energia: Agosto = 195 kWh, Setembro = 220 kWh (+12,82% aumento)
INSERT INTO `consumos` (`usuario_id`, `tipo`, `mes_referencia`, `valor_consumo`, `unidade`, `valor_fatura`, `observacao`)
VALUES
(1, 'Água', '2026-08', 12000.00, 'L', 74.50, 'Mês com consumo regular'),
(1, 'Água', '2026-09', 15500.00, 'L', 86.00, 'Consumo maior devido a manutenção do jardim'),
(1, 'Energia', '2026-08', 195.00, 'kWh', 188.00, 'Consumo padrão de inverno'),
(1, 'Energia', '2026-09', 220.00, 'kWh', 212.00, 'Consumo mais elevado no período')
ON DUPLICATE KEY UPDATE `valor_consumo` = VALUES(`valor_consumo`), `valor_fatura` = VALUES(`valor_fatura`);
