<?php
/**
 * Script de Alimentação e Atualização de Dados Demonstrativos Idempotente
 * Conta de Teste: alice (Alice Silva, ID = 1)
 * Período: 6 meses (2026-05, 2026-06, 2026-07, 2026-08, 2026-09, 2026-10)
 */

require_once __DIR__ . '/../config/database.php';

$db = getDBConnection();

echo "==> Iniciando povoamento de dados demonstrativos do FLUXO...\n";

// 1. Garantir que a usuária demonstrativa Alice Silva existe
$hashAlice = '$2y$10$hhjgu/MKV/JMCT47XJyOKetpgW0lbNVqfXnw6sIW.6YXCZsJvNjIu'; // fluxo123
$stmtUser = $db->prepare("
    INSERT INTO usuarios (id, nome, usuario, senha_hash, data_criacao)
    VALUES (1, 'Alice Silva', 'alice', :hash, NOW())
    ON DUPLICATE KEY UPDATE 
        nome = 'Alice Silva',
        usuario = 'alice',
        senha_hash = :hash_up
");
$stmtUser->execute([':hash' => $hashAlice, ':hash_up' => $hashAlice]);
echo " [OK] Usuária demonstrativa 'alice' verificada/atualizada.\n";

// 2. Orçamentos Mensais para os 6 meses
$orcamentos = [
    ['mes' => '2026-05', 'limite' => 1250.00],
    ['mes' => '2026-06', 'limite' => 1250.00],
    ['mes' => '2026-07', 'limite' => 1300.00],
    ['mes' => '2026-08', 'limite' => 1300.00],
    ['mes' => '2026-09', 'limite' => 1200.00],
    ['mes' => '2026-10', 'limite' => 1350.00],
];

$stmtOrc = $db->prepare("
    INSERT INTO orcamentos (usuario_id, mes_referencia, limite_mensal)
    VALUES (1, :mes, :limite)
    ON DUPLICATE KEY UPDATE limite_mensal = VALUES(limite_mensal)
");

foreach ($orcamentos as $orc) {
    $stmtOrc->execute([':mes' => $orc['mes'], ':limite' => $orc['limite']]);
}
echo " [OK] Orçamentos de 6 meses inseridos/atualizados com sucesso.\n";

// 3. Contas da Casa para os 6 meses (32 contas com IDs fixos 1..32 para idempotência total)
$contas = [
    // --- 2026-05 (Maio) ---
    [
        'id' => 101, 'cat' => 'Aluguel', 'nome' => 'Aluguel & Condomínio', 'val' => 650.00,
        'venc' => '2026-05-05', 'lim' => 700.00, 'mes' => '2026-05', 'status' => 'paga',
        'obs' => 'Boleto bancário pago', 'pag' => '2026-05-04'
    ],
    [
        'id' => 102, 'cat' => 'Streaming', 'nome' => 'Netflix & Spotify', 'val' => 49.90,
        'venc' => '2026-05-10', 'lim' => 60.00, 'mes' => '2026-05', 'status' => 'paga',
        'obs' => 'Cartão de crédito mensal', 'pag' => '2026-05-10'
    ],
    [
        'id' => 103, 'cat' => 'Água', 'nome' => 'Saneamento Básico (Água)', 'val' => 72.40,
        'venc' => '2026-05-15', 'lim' => 150.00, 'mes' => '2026-05', 'status' => 'paga',
        'obs' => 'Consumo regular do mês', 'pag' => '2026-05-14'
    ],
    [
        'id' => 104, 'cat' => 'Internet', 'nome' => 'Fibra Óptica 500MB', 'val' => 99.90,
        'venc' => '2026-05-20', 'lim' => 110.00, 'mes' => '2026-05', 'status' => 'paga',
        'obs' => 'Plano fibra residencial', 'pag' => '2026-05-19'
    ],
    [
        'id' => 105, 'cat' => 'Energia', 'nome' => 'Conta de Luz (CPFL/Enel)', 'val' => 176.50,
        'venc' => '2026-05-25', 'lim' => 220.00, 'mes' => '2026-05', 'status' => 'paga',
        'obs' => 'Consumo padrão de outono', 'pag' => '2026-05-24'
    ],

    // --- 2026-06 (Junho) ---
    [
        'id' => 106, 'cat' => 'Aluguel', 'nome' => 'Aluguel & Condomínio', 'val' => 650.00,
        'venc' => '2026-06-05', 'lim' => 700.00, 'mes' => '2026-06', 'status' => 'paga',
        'obs' => 'Boleto bancário pago', 'pag' => '2026-06-04'
    ],
    [
        'id' => 107, 'cat' => 'Streaming', 'nome' => 'Netflix & Spotify', 'val' => 49.90,
        'venc' => '2026-06-10', 'lim' => 60.00, 'mes' => '2026-06', 'status' => 'paga',
        'obs' => 'Cobrança recorrente', 'pag' => '2026-06-10'
    ],
    [
        'id' => 108, 'cat' => 'Água', 'nome' => 'Saneamento Básico (Água)', 'val' => 76.80,
        'venc' => '2026-06-15', 'lim' => 150.00, 'mes' => '2026-06', 'status' => 'paga',
        'obs' => 'Débito em conta', 'pag' => '2026-06-15'
    ],
    [
        'id' => 109, 'cat' => 'Internet', 'nome' => 'Fibra Óptica 500MB', 'val' => 99.90,
        'venc' => '2026-06-20', 'lim' => 110.00, 'mes' => '2026-06', 'status' => 'paga',
        'obs' => 'Pago pontualmente', 'pag' => '2026-06-18'
    ],
    [
        'id' => 110, 'cat' => 'Energia', 'nome' => 'Conta de Luz (CPFL/Enel)', 'val' => 184.20,
        'venc' => '2026-06-25', 'lim' => 220.00, 'mes' => '2026-06', 'status' => 'paga',
        'obs' => 'Temperatura amena', 'pag' => '2026-06-24'
    ],
    [
        'id' => 111, 'cat' => 'Outras despesas', 'nome' => 'Feira e Hortifruti', 'val' => 58.50,
        'venc' => '2026-06-28', 'lim' => 100.00, 'mes' => '2026-06', 'status' => 'paga',
        'obs' => 'Compras de frutas e legumes', 'pag' => '2026-06-28'
    ],

    // --- 2026-07 (Julho) ---
    [
        'id' => 112, 'cat' => 'Aluguel', 'nome' => 'Aluguel & Condomínio', 'val' => 650.00,
        'venc' => '2026-07-05', 'lim' => 700.00, 'mes' => '2026-07', 'status' => 'paga',
        'obs' => 'Boleto quitado', 'pag' => '2026-07-05'
    ],
    [
        'id' => 113, 'cat' => 'Streaming', 'nome' => 'Netflix & Spotify', 'val' => 49.90,
        'venc' => '2026-07-10', 'lim' => 60.00, 'mes' => '2026-07', 'status' => 'paga',
        'obs' => 'Recorrente', 'pag' => '2026-07-10'
    ],
    [
        'id' => 114, 'cat' => 'Água', 'nome' => 'Saneamento Básico (Água)', 'val' => 79.50,
        'venc' => '2026-07-15', 'lim' => 160.00, 'mes' => '2026-07', 'status' => 'paga',
        'obs' => 'Fatura quitada no vencimento', 'pag' => '2026-07-15'
    ],
    [
        'id' => 115, 'cat' => 'Internet', 'nome' => 'Fibra Óptica 500MB', 'val' => 99.90,
        'venc' => '2026-07-20', 'lim' => 110.00, 'mes' => '2026-07', 'status' => 'paga',
        'obs' => 'Internet fibra', 'pag' => '2026-07-19'
    ],
    [
        'id' => 116, 'cat' => 'Energia', 'nome' => 'Conta de Luz (CPFL/Enel)', 'val' => 198.80,
        'venc' => '2026-07-25', 'lim' => 240.00, 'mes' => '2026-07', 'status' => 'paga',
        'obs' => 'Inverno - maior uso de chuveiro elétrico', 'pag' => '2026-07-24'
    ],
    [
        'id' => 117, 'cat' => 'Outras despesas', 'nome' => 'Manutenção Residencial', 'val' => 85.00,
        'venc' => '2026-07-29', 'lim' => 120.00, 'mes' => '2026-07', 'status' => 'paga',
        'obs' => 'Reparo da torneira e filtro', 'pag' => '2026-07-29'
    ],

    // --- 2026-08 (Agosto) ---
    [
        'id' => 118, 'cat' => 'Aluguel', 'nome' => 'Aluguel & Condomínio', 'val' => 650.00,
        'venc' => '2026-08-05', 'lim' => 700.00, 'mes' => '2026-08', 'status' => 'paga',
        'obs' => 'Boleto quitado', 'pag' => '2026-08-04'
    ],
    [
        'id' => 119, 'cat' => 'Streaming', 'nome' => 'Netflix & Spotify', 'val' => 49.90,
        'venc' => '2026-08-10', 'lim' => 60.00, 'mes' => '2026-08', 'status' => 'paga',
        'obs' => 'Cartão de crédito', 'pag' => '2026-08-10'
    ],
    [
        'id' => 120, 'cat' => 'Água', 'nome' => 'Saneamento Básico (Água)', 'val' => 74.50,
        'venc' => '2026-08-15', 'lim' => 160.00, 'mes' => '2026-08', 'status' => 'paga',
        'obs' => 'Mês com consumo regular', 'pag' => '2026-08-14'
    ],
    [
        'id' => 121, 'cat' => 'Internet', 'nome' => 'Fibra Óptica 500MB', 'val' => 99.90,
        'venc' => '2026-08-20', 'lim' => 110.00, 'mes' => '2026-08', 'status' => 'paga',
        'obs' => 'Plano residencial', 'pag' => '2026-08-20'
    ],
    [
        'id' => 122, 'cat' => 'Energia', 'nome' => 'Conta de Luz (CPFL/Enel)', 'val' => 188.00,
        'venc' => '2026-08-25', 'lim' => 240.00, 'mes' => '2026-08', 'status' => 'paga',
        'obs' => 'Consumo padrão de inverno', 'pag' => '2026-08-24'
    ],

    // --- 2026-09 (Setembro) — Preservando exatamente os dados oficiais de Setembro ---
    [
        'id' => 1, 'cat' => 'Água', 'nome' => 'Saneamento Básico (Água)', 'val' => 86.00,
        'venc' => '2026-09-15', 'lim' => 200.00, 'mes' => '2026-09', 'status' => 'paga',
        'obs' => 'Fatura quitada no débito automático', 'pag' => '2026-09-15'
    ],
    [
        'id' => 2, 'cat' => 'Energia', 'nome' => 'Conta de Luz (CPFL/Enel)', 'val' => 212.00,
        'venc' => '2026-09-28', 'lim' => 250.00, 'mes' => '2026-09', 'status' => 'paga',
        'obs' => 'Uso maior de aquecedor no início do mês', 'pag' => '2026-09-28'
    ],
    [
        'id' => 3, 'cat' => 'Internet', 'nome' => 'Fibra Óptica 500MB', 'val' => 99.90,
        'venc' => '2026-09-26', 'lim' => 110.00, 'mes' => '2026-09', 'status' => 'paga',
        'obs' => 'Vence em breve - pago pontual', 'pag' => '2026-09-25'
    ],
    [
        'id' => 4, 'cat' => 'Streaming', 'nome' => 'Assinatura Música e Séries', 'val' => 49.90,
        'venc' => '2026-09-10', 'lim' => 60.00, 'mes' => '2026-09', 'status' => 'paga',
        'obs' => 'Cartão de crédito mensal', 'pag' => '2026-09-10'
    ],
    [
        'id' => 5, 'cat' => 'Outras despesas', 'nome' => 'Feira e Hortifruti Semanal', 'val' => 39.20,
        'venc' => '2026-09-24', 'lim' => 80.00, 'mes' => '2026-09', 'status' => 'paga',
        'obs' => 'Compras no mercado do bairro', 'pag' => '2026-09-24'
    ],

    // --- 2026-10 (Outubro — Mês Atual) — Mix realista de contas pagas e pendentes ---
    [
        'id' => 123, 'cat' => 'Aluguel', 'nome' => 'Aluguel & Condomínio', 'val' => 650.00,
        'venc' => '2026-10-05', 'lim' => 700.00, 'mes' => '2026-10', 'status' => 'paga',
        'obs' => 'Boleto quitado no quinto dia útil', 'pag' => '2026-10-05'
    ],
    [
        'id' => 124, 'cat' => 'Streaming', 'nome' => 'Netflix & Spotify', 'val' => 49.90,
        'venc' => '2026-10-10', 'lim' => 60.00, 'mes' => '2026-10', 'status' => 'paga',
        'obs' => 'Débito automático no cartão', 'pag' => '2026-10-09'
    ],
    [
        'id' => 125, 'cat' => 'Água', 'nome' => 'Saneamento Básico (Água)', 'val' => 81.20,
        'venc' => '2026-10-15', 'lim' => 160.00, 'mes' => '2026-10', 'status' => 'pendente',
        'obs' => 'Vence no meio do mês', 'pag' => null
    ],
    [
        'id' => 126, 'cat' => 'Internet', 'nome' => 'Fibra Óptica 500MB', 'val' => 99.90,
        'venc' => '2026-10-20', 'lim' => 110.00, 'mes' => '2026-10', 'status' => 'pendente',
        'obs' => 'Fatura gerada aguardando pagamento', 'pag' => null
    ],
    [
        'id' => 127, 'cat' => 'Energia', 'nome' => 'Conta de Luz (CPFL/Enel)', 'val' => 205.40,
        'venc' => '2026-10-25', 'lim' => 230.00, 'mes' => '2026-10', 'status' => 'pendente',
        'obs' => 'Leitura realizada pela concessionária', 'pag' => null
    ],
    [
        'id' => 128, 'cat' => 'Outras despesas', 'nome' => 'Supermercado e Feira', 'val' => 75.80,
        'venc' => '2026-10-28', 'lim' => 100.00, 'mes' => '2026-10', 'status' => 'pendente',
        'obs' => 'Previsão de compras semanais', 'pag' => null
    ],
];

$stmtConta = $db->prepare("
    INSERT INTO contas (id, usuario_id, categoria, nome, valor, vencimento, limite_gasto, mes_referencia, status, observacao, data_pagamento)
    VALUES (:id, 1, :cat, :nome, :val, :venc, :lim, :mes, :status, :obs, :pag)
    ON DUPLICATE KEY UPDATE
        categoria = VALUES(categoria),
        nome = VALUES(nome),
        valor = VALUES(valor),
        vencimento = VALUES(vencimento),
        limite_gasto = VALUES(limite_gasto),
        mes_referencia = VALUES(mes_referencia),
        status = VALUES(status),
        observacao = VALUES(observacao),
        data_pagamento = VALUES(data_pagamento)
");

foreach ($contas as $c) {
    $stmtConta->execute([
        ':id'     => $c['id'],
        ':cat'    => $c['cat'],
        ':nome'   => $c['nome'],
        ':val'    => $c['val'],
        ':venc'   => $c['venc'],
        ':lim'    => $c['lim'],
        ':mes'    => $c['mes'],
        ':status' => $c['status'],
        ':obs'    => $c['obs'],
        ':pag'    => $c['pag']
    ]);
}
echo " [OK] " . count($contas) . " contas distribuídas nos 6 meses inseridas/atualizadas.\n";

// 4. Consumos de Água e Energia nos 6 meses (12 registros no total)
$consumos = [
    // 2026-05
    ['tipo' => 'Água', 'mes' => '2026-05', 'val' => 11200.00, 'un' => 'L', 'fat' => 72.40, 'obs' => 'Consumo moderado de outono'],
    ['tipo' => 'Energia', 'mes' => '2026-05', 'val' => 175.00, 'un' => 'kWh', 'fat' => 176.50, 'obs' => 'Uso normal de eletrodomésticos'],
    // 2026-06
    ['tipo' => 'Água', 'mes' => '2026-06', 'val' => 11800.00, 'un' => 'L', 'fat' => 76.80, 'obs' => 'Leve variação sazonal'],
    ['tipo' => 'Energia', 'mes' => '2026-06', 'val' => 185.00, 'un' => 'kWh', 'fat' => 184.20, 'obs' => 'Uso regular'],
    // 2026-07
    ['tipo' => 'Água', 'mes' => '2026-07', 'val' => 12500.00, 'un' => 'L', 'fat' => 79.50, 'obs' => 'Consumo estável'],
    ['tipo' => 'Energia', 'mes' => '2026-07', 'val' => 205.00, 'un' => 'kWh', 'fat' => 198.80, 'obs' => 'Mais banhos quentes no frio'],
    // 2026-08 (Valores oficiais de referência)
    ['tipo' => 'Água', 'mes' => '2026-08', 'val' => 12000.00, 'un' => 'L', 'fat' => 74.50, 'obs' => 'Mês com consumo regular'],
    ['tipo' => 'Energia', 'mes' => '2026-08', 'val' => 195.00, 'un' => 'kWh', 'fat' => 188.00, 'obs' => 'Consumo padrão de inverno'],
    // 2026-09 (Valores oficiais de referência com aumento visível para alertas)
    ['tipo' => 'Água', 'mes' => '2026-09', 'val' => 15500.00, 'un' => 'L', 'fat' => 86.00, 'obs' => 'Consumo maior devido a manutenção do jardim'],
    ['tipo' => 'Energia', 'mes' => '2026-09', 'val' => 220.00, 'un' => 'kWh', 'fat' => 212.00, 'obs' => 'Consumo mais elevado no período'],
    // 2026-10 (Mês atual com redução positiva após ajustes)
    ['tipo' => 'Água', 'mes' => '2026-10', 'val' => 13400.00, 'un' => 'L', 'fat' => 81.20, 'obs' => 'Consumo voltando à média após fim da manutenção'],
    ['tipo' => 'Energia', 'mes' => '2026-10', 'val' => 210.00, 'un' => 'kWh', 'fat' => 205.40, 'obs' => 'Consumo dentro do planejado para a primavera'],
];

$stmtConsumo = $db->prepare("
    INSERT INTO consumos (usuario_id, tipo, mes_referencia, valor_consumo, unidade, valor_fatura, observacao)
    VALUES (1, :tipo, :mes, :val, :un, :fat, :obs)
    ON DUPLICATE KEY UPDATE
        valor_consumo = VALUES(valor_consumo),
        unidade = VALUES(unidade),
        valor_fatura = VALUES(valor_fatura),
        observacao = VALUES(observacao)
");

foreach ($consumos as $co) {
    $stmtConsumo->execute([
        ':tipo' => $co['tipo'],
        ':mes'  => $co['mes'],
        ':val'  => $co['val'],
        ':un'   => $co['un'],
        ':fat'  => $co['fat'],
        ':obs'  => $co['obs']
    ]);
}
echo " [OK] 12 registros de consumo de água e energia inseridos/atualizados com sucesso.\n";

echo "==> Povoamento de dados demonstrativos concluído com 100% de sucesso!\n";
