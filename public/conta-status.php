<?php
/**
 * FLUXO — Sistema de Gestão Doméstica
 * Alteração Rápida de Status da Conta (Paga / Pendente)
 */

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/functions.php';

require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: contas.php');
    exit;
}

require_csrf();

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$novoStatus = trim($_POST['novo_status'] ?? '');

if (!$id || !in_array($novoStatus, ['paga', 'pendente'])) {
    flash_set('error', 'Ação inválida.');
    header('Location: contas.php');
    exit;
}

$db = getDBConnection();
$userId = current_user_id();

// Busca conta verificando propriedade
$stmt = $db->prepare("SELECT nome, vencimento FROM contas WHERE id = :id AND usuario_id = :uid LIMIT 1");
$stmt->execute([':id' => $id, ':uid' => $userId]);
$conta = $stmt->fetch();

if (!$conta) {
    flash_set('error', 'Conta não encontrada ou sem autorização.');
    header('Location: contas.php');
    exit;
}

$hoje = date('Y-m-d');
$dataPagamento = null;
$statusFinal = $novoStatus;

if ($novoStatus === 'paga') {
    $dataPagamento = $hoje;
} else {
    // Se voltar para pendente e já venceu, ajusta para 'vencida'
    if ($conta['vencimento'] < $hoje) {
        $statusFinal = 'vencida';
    }
}

$stmtUpdate = $db->prepare("
    UPDATE contas 
    SET status = :status, data_pagamento = :data_pgto 
    WHERE id = :id AND usuario_id = :uid
");
$stmtUpdate->execute([
    ':status' => $statusFinal,
    ':data_pgto' => $dataPagamento,
    ':id' => $id,
    ':uid' => $userId
]);

if ($novoStatus === 'paga') {
    flash_set('success', "A conta \"{$conta['nome']}\" foi marcada como paga com sucesso! 🎉");
} else {
    flash_set('info', "A conta \"{$conta['nome']}\" foi reaberta como pendente.");
}

$referer = $_SERVER['HTTP_REFERER'] ?? 'contas.php';
header("Location: {$referer}");
exit;
