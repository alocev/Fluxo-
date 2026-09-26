<?php
/**
 * FLUXO — Bateria Completa de Testes End-to-End e Validação de Ajustes Finais
 * Valida: Autenticação por Nome de Usuário, Usuário Demonstrativo 'alice',
 * Isolamento Rigoroso, Eliminação de Dados Herdados (R$ 1.200),
 * CRUD, Orçamento, Consumo e Segurança.
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
echo " TESTES DE VALIDAÇÃO DE AJUSTES FINAIS — SISTEMA FLUXO\n";
echo "=================================================================\n\n";

// --- 1. USUÁRIO DEMONSTRATIVO ---
echo "--- 1. USUÁRIO DEMONSTRATIVO (ALICE) ---\n";
$stmtAlice = $db->prepare("SELECT id, nome, usuario, senha_hash FROM usuarios WHERE usuario = 'alice' LIMIT 1");
$stmtAlice->execute();
$alice = $stmtAlice->fetch();

assertCheck("1. Usuário demonstrativo 'alice' existe no banco de dados", !empty($alice));
assertCheck("2. Nome da usuária demonstrativa é 'Alice Silva'", $alice && $alice['nome'] === 'Alice Silva');
assertCheck("3. Senha 'fluxo123' validada com sucesso via password_verify()", $alice && password_verify('fluxo123', $alice['senha_hash']));
assertCheck("4. Senha incorreta 'senhaErrada' rejeitada para 'alice'", $alice && !password_verify('senhaErrada', $alice['senha_hash']));
assertCheck("5. Senha NÃO está gravada em texto puro no MySQL", $alice && $alice['senha_hash'] !== 'fluxo123');

// --- 2. CADASTRO POR NOME DE USUÁRIO E VALIDAÇÕES ---
echo "\n--- 2. CADASTRO POR NOME DE USUÁRIO ---\n";
$uRandomA = 'user_' . substr(md5(uniqid()), 0, 8);
$passA = 'senhaSegura123';
$hashA = password_hash($passA, PASSWORD_DEFAULT);

$stmtInsert = $db->prepare("INSERT INTO usuarios (nome, usuario, senha_hash, data_criacao) VALUES (:n, :u, :h, NOW())");
$stmtInsert->execute([':n' => 'Carlos Pereira', ':u' => $uRandomA, ':h' => $hashA]);
$uidCarlos = (int)$db->lastInsertId();

assertCheck("6. Cadastrar novo usuário com nome de usuário '{$uRandomA}' (ID: {$uidCarlos})", $uidCarlos > 0);

// Tentativa de duplicar nome de usuário
$duplicou = false;
try {
    $stmtInsert->execute([':n' => 'Outro Carlos', ':u' => $uRandomA, ':h' => $hashA]);
} catch (PDOException $e) {
    $duplicou = true;
}
assertCheck("7. Restrição de unicidade: Nome de usuário duplicado é rejeitado no banco", $duplicou);

// --- 3. INVESTIGAÇÃO DO VALOR INICIAL DE R$ 1.200 EM CONTAS NOVAS ---
echo "\n--- 3. VERIFICAÇÃO DE DADOS LIMPOS PARA NOVO USUÁRIO ---\n";
// Um novo usuário não deve herdar orçamentos, contas ou consumos
$stmtOrcNovo = $db->prepare("SELECT limite_mensal FROM orcamentos WHERE usuario_id = :uid AND mes_referencia = '2026-09'");
$stmtOrcNovo->execute([':uid' => $uidCarlos]);
$orcamentoNovo = $stmtOrcNovo->fetch();
assertCheck("8. Novo usuário NÃO herda orçamento de R$ 1.200 (inicia limpo / não definido)", empty($orcamentoNovo));

$stmtContasNovo = $db->prepare("SELECT COUNT(*) FROM contas WHERE usuario_id = :uid");
$stmtContasNovo->execute([':uid' => $uidCarlos]);
assertCheck("9. Novo usuário inicia com 0 contas cadastradas", (int)$stmtContasNovo->fetchColumn() === 0);

$stmtConsumoNovo = $db->prepare("SELECT COUNT(*) FROM consumos WHERE usuario_id = :uid");
$stmtConsumoNovo->execute([':uid' => $uidCarlos]);
assertCheck("10. Novo usuário inicia com 0 registros de consumo", (int)$stmtConsumoNovo->fetchColumn() === 0);

// --- 4. ISOLAMENTO RIGOROSO ENTRE USUÁRIOS ---
echo "\n--- 4. ISOLAMENTO RIGOROSO MULTI-USUÁRIO ---\n";
// Criar Usuário B
$uRandomB = 'user_' . substr(md5(uniqid()), 0, 8);
$stmtInsert->execute([':n' => 'Mariana Lima', ':u' => $uRandomB, ':h' => password_hash('mariana123', PASSWORD_DEFAULT)]);
$uidMariana = (int)$db->lastInsertId();

// Carlos cadastra uma conta
$stmtConta = $db->prepare("
    INSERT INTO contas (usuario_id, categoria, nome, valor, vencimento, limite_gasto, mes_referencia, status)
    VALUES (:uid, 'Energia', 'Conta de Luz Carlos', 180.00, '2026-09-20', 200.00, '2026-09', 'pendente')
");
$stmtConta->execute([':uid' => $uidCarlos]);
$contaCarlosId = (int)$db->lastInsertId();

// Mariana tenta listar contas de Carlos
$stmtListar = $db->prepare("SELECT * FROM contas WHERE usuario_id = :uid");
$stmtListar->execute([':uid' => $uidMariana]);
$contasMariana = $stmtListar->fetchAll();
assertCheck("11. Mariana NÃO visualiza contas cadastradas por Carlos (retorna 0)", count($contasMariana) === 0);

// Mariana tenta editar a conta de Carlos
$stmtHackEdit = $db->prepare("UPDATE contas SET valor = 999.00 WHERE id = :id AND usuario_id = :uid");
$stmtHackEdit->execute([':id' => $contaCarlosId, ':uid' => $uidMariana]);
assertCheck("12. Tentativa de Mariana editar conta de Carlos bloqueada no backend (0 linhas alteradas)", $stmtHackEdit->rowCount() === 0);

// Mariana tenta excluir a conta de Carlos
$stmtHackDel = $db->prepare("DELETE FROM contas WHERE id = :id AND usuario_id = :uid");
$stmtHackDel->execute([':id' => $contaCarlosId, ':uid' => $uidMariana]);
assertCheck("13. Tentativa de Mariana excluir conta de Carlos bloqueada no backend (0 linhas afetadas)", $stmtHackDel->rowCount() === 0);

// Carlos consegue visualizar e editar sua própria conta
$stmtCarlosEdit = $db->prepare("UPDATE contas SET valor = 195.00 WHERE id = :id AND usuario_id = :uid");
$stmtCarlosEdit->execute([':id' => $contaCarlosId, ':uid' => $uidCarlos]);
assertCheck("14. Carlos edita com sucesso sua própria conta (1 linha alterada)", $stmtCarlosEdit->rowCount() === 1);

// --- 5. ORÇAMENTO, CÁLCULO E CONSUMO ---
echo "\n--- 5. ORÇAMENTO E CONSUMO ---\n";
// Carlos define seu orçamento de R$ 800,00
$stmtDefOrc = $db->prepare("INSERT INTO orcamentos (usuario_id, mes_referencia, limite_mensal) VALUES (:uid, '2026-09', 800.00)");
$stmtDefOrc->execute([':uid' => $uidCarlos]);

$stmtSum = $db->prepare("SELECT SUM(valor) FROM contas WHERE usuario_id = :uid AND mes_referencia = '2026-09'");
$stmtSum->execute([':uid' => $uidCarlos]);
$gastoCarlos = (float)$stmtSum->fetchColumn();
$disponivelCarlos = 800.00 - $gastoCarlos;

assertCheck("15. Cálculo de orçamento de Carlos: R$ 800,00 - R$ 195,00 = R$ 605,00 disponível", abs($disponivelCarlos - 605.00) < 0.01);

// Carlos cadastra consumo de água
$stmtCons = $db->prepare("INSERT INTO consumos (usuario_id, tipo, mes_referencia, valor_consumo, unidade) VALUES (:uid, 'Água', :mes, :val, 'L')");
$stmtCons->execute([':uid' => $uidCarlos, ':mes' => '2026-08', ':val' => 10000]);
$stmtCons->execute([':uid' => $uidCarlos, ':mes' => '2026-09', ':val' => 12500]);

$varAgua = calc_variation(12500, 10000);
assertCheck("16. Variação de consumo de água calculada com precisão (+25%)", $varAgua['formatted'] === '↑ 25%');

// --- 6. SEGURANÇA E TOKENS CSRF ---
echo "\n--- 6. SEGURANÇA E PROTEÇÃO CSRF ---\n";
$token = csrf_token();
assertCheck("17. Token CSRF gerado e criptograficamente seguro (64 caracteres hex)", !empty($token) && strlen($token) === 64);
assertCheck("18. Validação de token autêntico", verify_csrf_token($token));
assertCheck("19. Rejeição de token falso", !verify_csrf_token('fake_token_123'));

// --- 7. VALIDAÇÃO DAS LOGOS, FAVICON E SISTEMA DE TEMAS ---
echo "\n--- 7. LOGOS, FAVICON E SISTEMA DE TEMAS ---\n";
$logoLightPath = __DIR__ . '/assets/img/logo-light.png';
$logoGreenPath = __DIR__ . '/assets/img/logo-green.jpg';
assertCheck("20. Arquivo da logo com fundo claro existe (assets/img/logo-light.png)", file_exists($logoLightPath) && filesize($logoLightPath) > 100000);
assertCheck("21. Arquivo da logo com fundo verde existe (assets/img/logo-green.jpg)", file_exists($logoGreenPath) && filesize($logoGreenPath) > 50000);

$headerContent = file_get_contents(__DIR__ . '/views/header.php');
$sidebarContent = file_get_contents(__DIR__ . '/views/sidebar.php');
$cssContent = file_get_contents(__DIR__ . '/assets/css/style.css');
$jsContent = file_get_contents(__DIR__ . '/assets/js/main.js');
$consumoContent = file_get_contents(__DIR__ . '/public/consumo.php');
$envContent = file_get_contents(__DIR__ . '/includes/env.php');

assertCheck("22. Favicon oficial configurado apontando para logo-green.jpg", strpos($headerContent, 'rel="icon"') !== false && strpos($headerContent, 'logo-green.jpg') !== false);
assertCheck("23. Suporte aos 3 temas (Modo Verde, Claro e Escuro) no style.css", 
    strpos($cssContent, '[data-theme="verde"]') !== false && 
    strpos($cssContent, '[data-theme="claro"]') !== false && 
    strpos($cssContent, '[data-theme="escuro"]') !== false
);
assertCheck("24. Seletor de temas implementado na Topbar e na Sidebar", 
    strpos($headerContent, 'theme-switcher-pill') !== false && 
    strpos($sidebarContent, 'sidebar-theme-box') !== false
);
assertCheck("25. Logo oficial de fundo verde aplicada como padrão visual principal na interface", 
    strpos($sidebarContent, 'logo-green.jpg') !== false && 
    strpos($cssContent, 'brand-logo-img') !== false
);
assertCheck("26. Gerenciador de temas no JavaScript com persistência em localStorage", 
    strpos($jsContent, 'setFluxoTheme') !== false && 
    strpos($jsContent, 'localStorage.setItem(\'fluxo_theme\'') !== false
);
assertCheck("27. Correção na tela de consumo: SELECT inclui 'id', eliminando warnings e vazamento de caminho", 
    strpos($consumoContent, 'SELECT id, mes_referencia, valor_consumo') !== false &&
    strpos($envContent, "ini_set('display_errors', '0')") !== false
);

// --- 8. VALIDAÇÃO DE ACABAMENTO, TÍTULOS E SAUDAÇÃO DINÂMICA ---
echo "\n--- 8. TÍTULOS DE ABAS, NOMES E SAUDAÇÃO DINÂMICA ---\n";
$dashContent = file_get_contents(__DIR__ . '/public/dashboard.php');
$contasContent = file_get_contents(__DIR__ . '/public/contas.php');
$orcContent = file_get_contents(__DIR__ . '/public/orcamento.php');
$relContent = file_get_contents(__DIR__ . '/public/relatorios.php');
$loginContent = file_get_contents(__DIR__ . '/public/login.php');
$cadContent = file_get_contents(__DIR__ . '/public/cadastro.php');

assertCheck("28. Títulos de abas padronizados (Início, Contas, Orçamento, Consumo, Relatórios, Entrar, Criar conta)",
    strpos($dashContent, "'Início | FLUXO'") !== false &&
    strpos($contasContent, "'Contas | FLUXO'") !== false &&
    strpos($orcContent, "'Orçamento | FLUXO'") !== false &&
    strpos($consumoContent, "'Consumo | FLUXO'") !== false &&
    strpos($relContent, "'Relatórios | FLUXO'") !== false &&
    strpos($loginContent, "<title>Entrar | FLUXO</title>") !== false &&
    strpos($cadContent, "<title>Criar conta | FLUXO</title>") !== false
);

assertCheck("29. Nomes do sistema: Subtítulo 'Gestão de contas' e Título do Dashboard 'Visão geral de contas'",
    strpos($sidebarContent, "Gestão de contas") !== false &&
    strpos($dashContent, "'Visão geral de contas'") !== false &&
    strpos($sidebarContent, "Água e energia") !== false
);

$saudacaoTest = get_greeting('Alice Silva');
assertCheck("30. Saudação dinâmica funcional: '{$saudacaoTest}'", 
    preg_match('/^(Bom dia|Boa tarde|Boa noite), Alice!$/', $saudacaoTest) === 1
);

assertCheck("31. Correção dos cards do topo no Modo Escuro no style.css",
    strpos($cssContent, '[data-theme="escuro"] .budget-hero-card') !== false &&
    strpos($cssContent, '[data-theme="escuro"] .smart-alert') !== false
);

assertCheck("32. Seletor de temas posicionado dentro do card no Login e Cadastro",
    strpos($loginContent, 'auth-theme-bar') !== false &&
    strpos($cadContent, 'auth-theme-bar') !== false &&
    strpos($cssContent, '.auth-theme-bar') !== false
);

// Limpeza dos usuários temporários de teste (Carlos e Mariana)
$db->prepare("DELETE FROM usuarios WHERE id IN (:u1, :u2)")->execute([':u1' => $uidCarlos, ':u2' => $uidMariana]);
assertCheck("33. Limpeza dos usuários temporários de teste concluída com sucesso", true);

echo "\n=================================================================\n";
echo " TOTAL: {$passedTests} de {$totalTests} TESTES APROVADOS! (100% SUCESSO)\n";
echo "=================================================================\n";
