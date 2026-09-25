<?php
/**
 * FLUXO — Sistema de Gestão Doméstica
 * Edição de Leitura de Consumo
 */

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/functions.php';

require_auth();

$db = getDBConnection();
$userId = current_user_id();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    flash_set('error', 'Registro de consumo inválido.');
    header('Location: consumo.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM consumos WHERE id = :id AND usuario_id = :uid LIMIT 1");
$stmt->execute([':id' => $id, ':uid' => $userId]);
$consumo = $stmt->fetch();

if (!$consumo) {
    flash_set('error', 'Registro não encontrado ou você não possui permissão para editá-lo.');
    header('Location: consumo.php');
    exit;
}

$tipo = $consumo['tipo'];
$mesReferencia = $consumo['mes_referencia'];
$valorConsumo = number_format((float)$consumo['valor_consumo'], 2, ',', '');
$unidade = $consumo['unidade'];
$valorFatura = $consumo['valor_fatura'] !== null ? number_format((float)$consumo['valor_fatura'], 2, ',', '') : '';
$observacao = $consumo['observacao'] ?? '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $tipo = trim($_POST['tipo'] ?? 'Água');
    $mesReferencia = trim($_POST['mes_referencia'] ?? '');
    $valorRaw = str_replace(',', '.', trim($_POST['valor_consumo'] ?? ''));
    $unidade = trim($_POST['unidade'] ?? 'L');
    $faturaRaw = str_replace(',', '.', trim($_POST['valor_fatura'] ?? ''));
    $observacao = trim($_POST['observacao'] ?? '');

    if (!in_array($tipo, ['Água', 'Energia'])) {
        $error = 'Tipo inválido.';
    } elseif (!preg_match('/^\d{4}-\d{2}$/', $mesReferencia)) {
        $error = 'Mês inválido.';
    } elseif (!is_numeric($valorRaw) || (float)$valorRaw < 0) {
        $error = 'Valor de consumo inválido.';
    } else {
        $valorNum = (float)$valorRaw;
        $faturaNum = ($faturaRaw !== '' && is_numeric($faturaRaw)) ? (float)$faturaRaw : null;

        try {
            $stmtUpdate = $db->prepare("
                UPDATE consumos 
                SET tipo = :tipo,
                    mes_referencia = :mes,
                    valor_consumo = :val,
                    unidade = :unidade,
                    valor_fatura = :fatura,
                    observacao = :obs
                WHERE id = :id AND usuario_id = :uid
            ");
            $stmtUpdate->execute([
                ':tipo' => $tipo,
                ':mes' => $mesReferencia,
                ':val' => $valorNum,
                ':unidade' => $unidade,
                ':fatura' => $faturaNum,
                ':obs' => !empty($observacao) ? $observacao : null,
                ':id' => $id,
                ':uid' => $userId
            ]);

            flash_set('success', "Leitura atualizada com sucesso!");
            header("Location: consumo.php?mes={$mesReferencia}");
            exit;
        } catch (PDOException $e) {
            error_log("Erro ao atualizar consumo: " . $e->getMessage());
            $error = 'Não foi possível atualizar o consumo. Tente novamente.';
        }
    }
}

$pageTitle = 'Editar Leitura de Consumo — FLUXO';
$pageHeading = 'Editar Consumo';
$activePage = 'consumo';

include dirname(__DIR__) . '/views/header.php';
?>

<div style="max-width: 640px; margin: 0 auto;">
    <div style="margin-bottom: 20px;">
        <a href="consumo.php" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.85rem;">
            ← Voltar para consumo
        </a>
    </div>

    <div class="form-card">
        <h2 style="font-size: 1.3rem; font-weight: 800; margin-bottom: 6px; color: var(--text-primary);">
            Editar Leitura: <?= e($consumo['tipo']) ?> (<?= format_month_name($consumo['mes_referencia']) ?>)
        </h2>

        <?php if (!empty($error)): ?>
            <div class="flash-banner flash-error" style="margin-bottom: 20px;">
                <span>✕ <?= e($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="consumo-editar.php?id=<?= (int)$id ?>">
            <?= csrf_field() ?>

            <div class="form-row">
                <div class="form-group">
                    <label for="tipo">Tipo de Medição *</label>
                    <select name="tipo" id="tipo" class="form-control" required>
                        <option value="Água" <?= $tipo === 'Água' ? 'selected' : '' ?>>💧 Água</option>
                        <option value="Energia" <?= $tipo === 'Energia' ? 'selected' : '' ?>>⚡ Energia</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="mes_referencia">Mês de Referência *</label>
                    <input type="month" name="mes_referencia" id="mes_referencia" class="form-control" 
                           value="<?= e($mesReferencia) ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="valor_consumo">Valor da Leitura / Consumo *</label>
                    <input type="text" name="valor_consumo" id="valor_consumo" class="form-control" 
                           value="<?= e($valorConsumo) ?>" required>
                </div>

                <div class="form-group">
                    <label for="unidade">Unidade de Medida *</label>
                    <select name="unidade" id="unidade" class="form-control" required>
                        <option value="L" <?= $unidade === 'L' ? 'selected' : '' ?>>L (Litros)</option>
                        <option value="m³" <?= $unidade === 'm³' ? 'selected' : '' ?>>m³ (Metros Cúbicos)</option>
                        <option value="kWh" <?= $unidade === 'kWh' ? 'selected' : '' ?>>kWh (Quilowatt-hora)</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="valor_fatura">Valor Cobrado na Fatura (R$ opcional)</label>
                <input type="text" name="valor_fatura" id="valor_fatura" class="form-control" 
                       value="<?= e($valorFatura) ?>">
            </div>

            <div class="form-group">
                <label for="observacao">Observações</label>
                <textarea name="observacao" id="observacao" rows="3" class="form-control"><?= e($observacao) ?></textarea>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px;">
                <a href="consumo.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary" style="padding-left: 24px; padding-right: 24px;">
                    Salvar Alterações
                </button>
            </div>
        </form>
    </div>
</div>

<?php include dirname(__DIR__) . '/views/footer.php'; ?>
