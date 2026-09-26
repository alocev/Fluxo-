<?php
/**
 * FLUXO — Sistema de Gestão Doméstica
 * Cadastro de Leitura de Consumo (Água ou Energia)
 */

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/functions.php';

require_auth();

$db = getDBConnection();
$userId = current_user_id();

$currentMonth = $_SESSION['selected_month'] ?? date('Y-m');

$tipo = $_GET['tipo'] ?? 'Água';
if (!in_array($tipo, ['Água', 'Energia'])) {
    $tipo = 'Água';
}

$mesReferencia = $currentMonth;
$valorConsumo = '';
$unidade = $tipo === 'Água' ? 'L' : 'kWh';
$valorFatura = '';
$observacao = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $tipo = trim($_POST['tipo'] ?? 'Água');
    $mesReferencia = trim($_POST['mes_referencia'] ?? '');
    $valorRaw = str_replace(',', '.', trim($_POST['valor_consumo'] ?? ''));
    $unidade = trim($_POST['unidade'] ?? ($tipo === 'Água' ? 'L' : 'kWh'));
    $faturaRaw = str_replace(',', '.', trim($_POST['valor_fatura'] ?? ''));
    $observacao = trim($_POST['observacao'] ?? '');

    // Validações no backend
    if (!in_array($tipo, ['Água', 'Energia'])) {
        $error = 'Por favor, selecione um tipo de consumo válido (Água ou Energia).';
    } elseif (!preg_match('/^\d{4}-\d{2}$/', $mesReferencia)) {
        $error = 'O mês de referência informado é inválido.';
    } elseif (!is_numeric($valorRaw) || (float)$valorRaw < 0) {
        $error = 'Por favor, informe uma leitura de consumo positiva válida.';
    } else {
        $valorConsumoNum = (float)$valorRaw;
        $valorFaturaNum = ($faturaRaw !== '' && is_numeric($faturaRaw)) ? (float)$faturaRaw : null;

        try {
            // Inserir ou atualizar caso já exista leitura deste tipo no mesmo mês
            $stmtUpsert = $db->prepare("
                INSERT INTO consumos 
                (usuario_id, tipo, mes_referencia, valor_consumo, unidade, valor_fatura, observacao, data_registro)
                VALUES 
                (:uid, :tipo, :mes, :val, :unidade, :fatura, :obs, NOW())
                ON DUPLICATE KEY UPDATE 
                    valor_consumo = VALUES(valor_consumo),
                    unidade = VALUES(unidade),
                    valor_fatura = VALUES(valor_fatura),
                    observacao = VALUES(observacao)
            ");
            $stmtUpsert->execute([
                ':uid' => $userId,
                ':tipo' => $tipo,
                ':mes' => $mesReferencia,
                ':val' => $valorConsumoNum,
                ':unidade' => $unidade,
                ':fatura' => $valorFaturaNum,
                ':obs' => !empty($observacao) ? $observacao : null
            ]);

            flash_set('success', "Leitura de {$tipo} de " . format_month_name($mesReferencia) . " registrada com sucesso!");
            header("Location: consumo.php?mes={$mesReferencia}");
            exit;
        } catch (PDOException $e) {
            error_log("Erro ao salvar consumo: " . $e->getMessage());
            $error = 'Não foi possível salvar o consumo. Por favor, tente novamente.';
        }
    }
}

$pageTitle = 'Novo consumo | FLUXO';
$pageHeading = 'Registrar consumo';
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
            Nova Leitura de Consumo
        </h2>
        <p style="font-size: 0.88rem; color: var(--text-secondary); margin-bottom: 24px;">
            Insira o valor apontado no relógio ou na conta para acompanhar o gráfico e as variações.
        </p>

        <?php if (!empty($error)): ?>
            <div class="flash-banner flash-error" style="margin-bottom: 20px;">
                <span>✕ <?= e($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="consumo-cadastrar.php">
            <?= csrf_field() ?>

            <div class="form-row">
                <div class="form-group">
                    <label for="tipo">Tipo de Medição *</label>
                    <select name="tipo" id="tipo" class="form-control" required onchange="ajustarUnidade(this.value)">
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
                           placeholder="Ex: 15500 ou 220" 
                           value="<?= e($valorConsumo) ?>" required>
                    <span class="form-hint" id="dicaConsumo">
                        <?= $tipo === 'Água' ? 'Ex: 15500 Litros ou 15.5 m³' : 'Ex: 220 kWh' ?>
                    </span>
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
                       placeholder="Ex: 86,00" 
                       value="<?= e($valorFatura) ?>">
                <span class="form-hint">Ajuda a correlacionar o consumo físico com o valor em reais.</span>
            </div>

            <div class="form-group">
                <label for="observacao">Observações (Opcional)</label>
                <textarea name="observacao" id="observacao" rows="3" class="form-control" 
                          placeholder="Ex: Vazamento identificado, ar-condicionado mais ligado, etc."><?= e($observacao) ?></textarea>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px;">
                <a href="consumo.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary" style="padding-left: 24px; padding-right: 24px;">
                    Salvar Leitura
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function ajustarUnidade(tipo) {
    const sel = document.getElementById('unidade');
    const dica = document.getElementById('dicaConsumo');
    if (tipo === 'Água') {
        sel.value = 'L';
        dica.innerText = 'Ex: 15500 Litros ou 15.5 m³';
    } else {
        sel.value = 'kWh';
        dica.innerText = 'Ex: 220 kWh';
    }
}
</script>

<?php include dirname(__DIR__) . '/views/footer.php'; ?>
