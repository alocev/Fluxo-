<?php
require_once __DIR__ . '/config/database.php';
$db = getDBConnection();

echo "--- RESUMO DE ORÇAMENTOS (ALICE) ---\n";
$orcs = $db->query("SELECT mes_referencia, limite_mensal FROM orcamentos WHERE usuario_id = 1 ORDER BY mes_referencia")->fetchAll();
foreach ($orcs as $o) {
    echo "Mês: {$o['mes_referencia']} | Limite: R$ " . number_format($o['limite_mensal'], 2, ',', '.') . "\n";
}

echo "\n--- RESUMO DE CONTAS POR MÊS (ALICE) ---\n";
$contas = $db->query("SELECT mes_referencia, COUNT(id) as total_contas, SUM(valor) as total_valor, SUM(CASE WHEN status = 'paga' THEN valor ELSE 0 END) as total_pago, SUM(CASE WHEN status != 'paga' THEN valor ELSE 0 END) as total_pendente FROM contas WHERE usuario_id = 1 GROUP BY mes_referencia ORDER BY mes_referencia")->fetchAll();
foreach ($contas as $c) {
    echo "Mês: {$c['mes_referencia']} | Contas: {$c['total_contas']} | Total: R$ " . number_format($c['total_valor'], 2, ',', '.') . " (Pago: R$ " . number_format($c['total_pago'], 2, ',', '.') . " | Pendente: R$ " . number_format($c['total_pendente'], 2, ',', '.') . ")\n";
}

echo "\n--- RESUMO DE CONSUMO DE ÁGUA E ENERGIA (ALICE) ---\n";
$consumos = $db->query("SELECT mes_referencia, tipo, valor_consumo, unidade, valor_fatura FROM consumos WHERE usuario_id = 1 ORDER BY mes_referencia, tipo")->fetchAll();
foreach ($consumos as $co) {
    echo "Mês: {$co['mes_referencia']} | {$co['tipo']}: " . number_format($co['valor_consumo'], 2, ',', '.') . " {$co['unidade']} | Fatura: R$ " . number_format($co['valor_fatura'], 2, ',', '.') . "\n";
}
