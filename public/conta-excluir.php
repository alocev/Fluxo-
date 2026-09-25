<?php
/**
 * FLUXO — Sistema de Gestão Doméstica
 * Exclusão Segura de Conta
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
if (!$id) {
    flash_set('error', 'Identificador de conta inválido.');
    header('Location: contas.php');
    exit;
}

$db = getDBConnection();
$userId = current_user_id();

// Busca o nome antes da exclusão para mensagem amigável e valida autorização
$stmt = $db->prepare("SELECT nome, mes_referencia FROM contas WHERE id = :id AND usuario_id = :uid LIMIT 1");
$stmt->execute([':id' => $id, ':uid' => $userId]);
$conta = $stmt->fetch();

if (!$conta) {
    flash_set('error', 'A conta que você tentou excluir não existe ou você não possui permissão para excluí-la.');
    header('Location: contas.php');
    exit;
}

try {
    $stmtDelete = $db->prepare("DELETE FROM contas WHERE id = :id AND usuario_id = :uid");
    $stmtDelete->execute([':id' => $id, ':uid' => $userId]);

    flash_set('success', "A conta \"{$conta['nome']}\" foi excluída com sucesso.");
} catch (PDOException $e) {
    error_log("Erro ao excluir conta: " . $e->getMessage());
    flash_set('error', 'Não foi possível excluir a conta no momento. Tente novamente.');
}

header("Location: contas.php?mes={$conta['mes_referencia']}");
exit;
