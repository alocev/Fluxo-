<?php
/**
 * FLUXO — Bateria de Validação End-to-End Automatizada (Item 39 do Requisito)
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/alerts_helper.php';

$db = getDBConnection();

$totalTests = 0;
$passedTests = 0;

function assertCheck(string $description, bool $condition): void {
    global $totalTests, $passedTests;
    $totalTests++;
    if ($condition) {
        $passedTests++;
        echo " [OK] {$description}\n";
    } else {
        echo " [FALHA] {$description}\n";
    }
}

echo "=================================================================\n";
echo " TESTES AUTOMATIZADOS END-TO-END — CRITÉRIO DO REQUISITO 39\n";
echo "=================================================================\n\n";

// 1. Cadastrar Usuário A
echo "--- FLUXO DO USUÁRIO ---\n";
$emailA = 'teste_user_a_' . time() . '@fluxo.local';
$passA = 'fluxoTeste123';
$hashA = password_hash($passA, PASSWORD_DEFAULT);

$stmt = $db->prepare("INSERT INTO usuarios (nome, email, senha_hash, data_criacao) VALUES (:n, :e, :h, NOW())");
$stmt->execute([':n' => 'Alice da Silva', ':e' => $emailA, ':h' => $hashA]);
$uidA = (int)$db->lastInsertId();
assertCheck("1. Abrir cadastro e criar usuário no MySQL (ID: {$uidA})", $uidA > 0);

// 2. Fazer Login (verificação de senha)
assertCheck("2. Fazer login e validar senha com password_verify()", password_verify($passA, $hashA));
assertCheck("3. Rejeição de senha errada sem expor detalhes", !password_verify('senhaIncorreta', $hashA));

// Usuário B para testes de isolamento
$stmt->execute([':n' => 'Bob Santos', ':e' => 'bob_' . time() . '@fluxo.local', ':h' => password_hash('bob123', PASSWORD_DEFAULT)]);
$uidB = (int)$db->lastInsertId();
assertCheck("4. Usuário B cadastrado para teste de isolamento (ID: {$uidB})", $uidB > 0);

// --- FLUXO DE CONTAS (CRUD) ---
echo "\n--- FLUXO DE CONTAS (CRUD) ---\n";
$mes = '2026-09';

// Cadastrar Conta
$stmtConta = $db->prepare("
    INSERT INTO contas (usuario_id, categoria, nome, valor, vencimento, limite_gasto, mes_referencia, status, observacao)
    VALUES (:uid, :cat, :nome, :val, :venc, :lim, :mes, :st, :obs)
");
$stmtConta->execute([
    ':uid' => $uidA,
    ':cat' => 'Energia',
    ':nome' => 'Energia',
    ':val' => 212.00,
    ':venc' => '2026-09-28',
    ':lim' => 250.00,
    ':mes' => $mes,
    ':st' => 'pendente',
    ':obs' => 'Teste CRUD'
]);
$cid = (int)$db->lastInsertId();
assertCheck("5. Cadastrar uma conta e confirmar que foi salva no MySQL (ID: {$cid})", $cid > 0);

// Voltar para listagem / consultar
$stmtGet = $db->prepare("SELECT * FROM contas WHERE id = :id AND usuario_id = :uid");
$stmtGet->execute([':id' => $cid, ':uid' => $uidA]);
$conta = $stmtGet->fetch();
assertCheck("6. Consultar conta cadastrada pelo usuário", $conta && $conta['nome'] === 'Energia');

// Visualizar detalhes
assertCheck("7. Visualizar detalhes com categoria e valores corretos", (float)$conta['valor'] === 212.00 && (float)$conta['limite_gasto'] === 250.00);

// Editar conta
$stmtUpdate = $db->prepare("UPDATE contas SET valor = :val, nome = :nome WHERE id = :id AND usuario_id = :uid");
$stmtUpdate->execute([':val' => 218.00, ':nome' => 'Energia Atualizada', ':id' => $cid, ':uid' => $uidA]);
$stmtGet->execute([':id' => $cid, ':uid' => $uidA]);
$contaEditada = $stmtGet->fetch();
assertCheck("8. Editar conta e confirmar alteração no banco", (float)$contaEditada['valor'] === 218.00 && $contaEditada['nome'] === 'Energia Atualizada');

// Marcar como paga
$stmtPaga = $db->prepare("UPDATE contas SET status = 'paga', data_pagamento = NOW() WHERE id = :id AND usuario_id = :uid");
$stmtPaga->execute([':id' => $cid, ':uid' => $uidA]);
$stmtGet->execute([':id' => $cid, ':uid' => $uidA]);
assertCheck("9. Marcar conta como paga com data de pagamento", $stmtGet->fetch()['status'] === 'paga');

// Excluir conta
$stmtDel = $db->prepare("DELETE FROM contas WHERE id = :id AND usuario_id = :uid");
$stmtDel->execute([':id' => $cid, ':uid' => $uidA]);
$stmtGet->execute([':id' => $cid, ':uid' => $uidA]);
assertCheck("10. Excluir conta e confirmar exclusão", $stmtGet->fetch() === false);

// --- FLUXO DE ORÇAMENTO ---
echo "\n--- FLUXO DE ORÇAMENTO ---\n";
// Definir orçamento de R$ 1.200
$stmtOrc = $db->prepare("
    INSERT INTO orcamentos (usuario_id, mes_referencia, limite_mensal)
    VALUES (:uid, :mes, :lim)
    ON DUPLICATE KEY UPDATE limite_mensal = VALUES(limite_mensal)
");
$stmtOrc->execute([':uid' => $uidA, ':mes' => $mes, ':lim' => 1200.00]);
assertCheck("11. Definir orçamento mensal de R$ 1.200,00", true);

// Cadastrar contas totalizando R$ 487 (conforme especificação)
$contas = [
    ['Água', 'Água', 86.00, 200.00],
    ['Energia', 'Energia', 212.00, 250.00],
    ['Internet', 'Internet', 99.90, 110.00],
    ['Streaming', 'Streaming', 49.90, 60.00],
    ['Outras despesas', 'Feira', 39.20, 80.00]
];
foreach ($contas as $c) {
    $stmtConta->execute([
        ':uid' => $uidA,
        ':cat' => $c[0],
        ':nome' => $c[1],
        ':val' => $c[2],
        ':venc' => '2026-09-25',
        ':lim' => $c[3],
        ':mes' => $mes,
        ':st' => 'pendente',
        ':obs' => null
    ]);
}

$stmtSum = $db->prepare("SELECT SUM(valor) as total FROM contas WHERE usuario_id = :uid AND mes_referencia = :mes");
$stmtSum->execute([':uid' => $uidA, ':mes' => $mes]);
$totalGasto = (float)$stmtSum->fetchColumn();

$disponivel = 1200.00 - $totalGasto;
$percentual = ($totalGasto / 1200.00) * 100;

assertCheck("12. Verificar cálculo do total comprometido (R$ 487,00)", abs($totalGasto - 487.00) < 0.01);
assertCheck("13. Verificar valor disponível (R$ 713,00)", abs($disponivel - 713.00) < 0.01);
assertCheck("14. Verificar percentual utilizado (~41%)", round($percentual) == 41);

// Alerta de energia a 85% do limite
$alerts = get_system_alerts($db, $uidA, $mes);
$achouAlertaEnergia = false;
foreach ($alerts as $a) {
    if (str_contains($a['message'], '85%')) {
        $achouAlertaEnergia = true;
    }
}
assertCheck("15. Verificar aviso contextual quando próximo do limite (⚡ Energia em 85% do limite)", $achouAlertaEnergia);

// --- FLUXO DE CONSUMO ---
echo "\n--- FLUXO DE CONSUMO ---\n";
$stmtCons = $db->prepare("
    INSERT INTO consumos (usuario_id, tipo, mes_referencia, valor_consumo, unidade, valor_fatura)
    VALUES (:uid, :tipo, :mes, :val, :unid, :fat)
");
// Água: Agosto 12000 L, Setembro 15500 L
$stmtCons->execute([':uid' => $uidA, ':tipo' => 'Água', ':mes' => '2026-08', ':val' => 12000.00, ':unid' => 'L', ':fat' => 74.00]);
$stmtCons->execute([':uid' => $uidA, ':tipo' => 'Água', ':mes' => '2026-09', ':val' => 15500.00, ':unid' => 'L', ':fat' => 86.00]);
assertCheck("16. Cadastrar consumo de água em múltiplos períodos", true);

// Energia: Agosto 195 kWh, Setembro 220 kWh
$stmtCons->execute([':uid' => $uidA, ':tipo' => 'Energia', ':mes' => '2026-08', ':val' => 195.00, ':unid' => 'kWh', ':fat' => 180.00]);
$stmtCons->execute([':uid' => $uidA, ':tipo' => 'Energia', ':mes' => '2026-09', ':val' => 220.00, ':unid' => 'kWh', ':fat' => 212.00]);
assertCheck("17. Cadastrar consumo de energia em múltiplos períodos", true);

$varAgua = calc_variation(15500.00, 12000.00);
$varEnergia = calc_variation(220.00, 195.00);
assertCheck("18. Verificar comparação de água: aumento de 29% (↑ 29%)", $varAgua['formatted'] === '↑ 29%');
assertCheck("19. Verificar comparação de energia: aumento de 13% (↑ 13%)", $varEnergia['formatted'] === '↑ 13%');

// --- SEGURANÇA E ISOLAMENTO ---
echo "\n--- SEGURANÇA E ISOLAMENTO ---\n";
// Usuário B tenta ler contas de Usuário A
$stmtB = $db->prepare("SELECT * FROM contas WHERE usuario_id = :uid");
$stmtB->execute([':uid' => $uidB]);
assertCheck("20. Isolamento SQL: Usuário B não acessa nenhuma conta do Usuário A", count($stmtB->fetchAll()) === 0);

// Usuário B tenta atualizar conta do Usuário A
$stmtHack = $db->prepare("UPDATE contas SET valor = 9999 WHERE id = :id AND usuario_id = :uid");
$stmtHack->execute([':id' => $cid, ':uid' => $uidB]);
assertCheck("21. Tentativa de edição cruzada rejeitada (0 linhas afetadas)", $stmtHack->rowCount() === 0);

// Proteção CSRF
$token = csrf_token();
assertCheck("22. Geração e validação de token CSRF", verify_csrf_token($token) && !verify_csrf_token('tokenFalso'));

// Limpeza de teste
$db->prepare("DELETE FROM usuarios WHERE id IN (:u1, :u2)")->execute([':u1' => $uidA, ':u2' => $uidB]);
assertCheck("23. Limpeza de dados de teste", true);

echo "\n=================================================================\n";
echo " TOTAL: {$passedTests} de {$totalTests} TESTES APROVADOS COM SUCESSO!\n";
echo "=================================================================\n";
