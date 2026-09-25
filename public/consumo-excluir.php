<?php
/**
 * FLUXO — Sistema de Gestão Doméstica
 * Exclusão de Registro de Consumo
 */

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/functions.php';

require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: consumo.php');
    exit;
}

require_csrf();

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    flash_set('error', 'Registro inválido.');
    header('Location: consumo.php');
    exit;
}

$db = getDBConnection();
$userId = current_user_id();

$stmt = $db->prepare("SELECT tipo, mes_referencia FROM consumos WHERE id = :id AND usuario_id = :uid LIMIT 1");
$stmt->execute([':id' => $id, ':uid' => $userId]);
$consumo = $stmt->fetch();

if (!$consumo) {
    flash_set('error', 'Registro não encontrado ou você não tem permissão para excluí-lo.');
    header('Location: consumo.php');
    exit;
}

try {
    $stmtDelete = $db->prepare("DELETE FROM consumos WHERE id = :id AND usuario_id = :uid");
    $stmtDelete->execute([':id' => $id, ':uid' => $userId]);

    flash_set('success', "Leitura de {$consumo['tipo']} de " . format_month_name($consumo['mes_referencia']) . " excluída com sucesso.");
} catch (PDOException $e) {
    error_log("Erro ao excluir consumo: " . $e->getMessage());
    flash_set('error', 'Não foi possível excluir o registro. Tente novamente.');
}

header("Location: consumo.php?mes={$consumo['mes_referencia']}");
exit;
