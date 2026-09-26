<?php
/**
 * FLUXO — Sistema de Gestão Doméstica
 * Visualização Detalhada da Conta
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
    flash_set('error', 'Conta inválida.');
    header('Location: contas.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM contas WHERE id = :id AND usuario_id = :uid LIMIT 1");
$stmt->execute([':id' => $id, ':uid' => $userId]);
$conta = $stmt->fetch();

if (!$conta) {
    flash_set('error', 'Conta não encontrada ou você não possui permissão para visualizá-la.');
    header('Location: contas.php');
    exit;
}

$meta = get_category_meta($conta['categoria']);
$valor = (float)$conta['valor'];
$limite = $conta['limite_gasto'] !== null ? (float)$conta['limite_gasto'] : null;

$pageTitle = 'Detalhes | FLUXO';
$pageHeading = 'Detalhes da conta';
$activePage = 'contas';

include dirname(__DIR__) . '/views/header.php';
?>

<div style="max-width: 680px; margin: 0 auto;">
    <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
        <a href="contas.php" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.85rem;">
            ← Voltar para as contas
        </a>

        <div style="display: flex; gap: 8px;">
            <a href="conta-editar.php?id=<?= (int)$conta['id'] ?>" class="btn btn-secondary" style="padding: 6px 14px; font-size: 0.85rem;">
                ✏️ Editar
            </a>

            <form action="conta-excluir.php" method="POST" style="margin: 0;">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$conta['id'] ?>">
                <button type="submit" class="btn btn-danger btn-delete-confirm" data-item-name="<?= e($conta['nome']) ?>" style="padding: 6px 14px; font-size: 0.85rem;">
                    🗑️ Excluir
                </button>
            </form>
        </div>
    </div>

    <div class="form-card" style="border-top: 5px solid <?= e($meta['color']) ?>;">
        <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 24px;">
            <div class="category-icon-box" style="width: 56px; height: 56px; font-size: 1.8rem; background-color: <?= e($meta['bg']) ?>; color: <?= e($meta['color']) ?>;">
                <?= $meta['icon'] ?>
            </div>
            <div>
                <span class="account-cat-pill" style="background-color: <?= e($meta['bg']) ?>; color: <?= e($meta['color']) ?>;">
                    <?= e($conta['categoria']) ?>
                </span>
                <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--text-primary); margin-top: 4px;">
                    <?= e($conta['nome']) ?>
                </h2>
            </div>
        </div>

        <div style="background-color: var(--bg-page); border-radius: var(--radius-md); padding: 20px; margin-bottom: 24px;">
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                <div>
                    <div style="font-size: 0.8rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase;">Valor da Fatura</div>
                    <div style="font-size: 1.8rem; font-weight: 800; color: var(--text-primary); margin-top: 2px;">
                        <?= format_currency($valor) ?>
                    </div>
                </div>

                <div>
                    <div style="font-size: 0.8rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase;">Situação Atual</div>
                    <div style="margin-top: 6px;">
                        <span class="account-status-pill status-pill-<?= e($conta['status']) ?>">
                            <?= $conta['status'] === 'paga' ? '✓ Paga' : ($conta['status'] === 'vencida' ? '✕ Vencida' : '⏳ Pendente') ?>
                        </span>
                    </div>
                </div>
            </div>

            <?php if ($limite !== null && $limite > 0): 
                $pct = ($valor / $limite) * 100;
                $barClass = $valor > $limite ? 'fill-overbudget' : ($pct >= 80 ? 'fill-warning' : 'fill-comfortable');
            ?>
                <div style="margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--border-color);">
                    <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 6px;">
                        <span style="font-weight: 600; color: var(--text-secondary);">Limite Individual Estabelecido:</span>
                        <strong style="color: var(--text-primary);"><?= format_currency($limite) ?> (<?= round($pct) ?>% consumido)</strong>
                    </div>
                    <div class="budget-progress-track">
                        <div class="budget-progress-fill <?= $barClass ?>" style="width: <?= min(100, $pct) ?>%;"></div>
                    </div>
                    <?php if ($valor > $limite): ?>
                        <p style="font-size: 0.8rem; color: var(--danger); font-weight: 700; margin-top: 6px;">
                            ⚠️ Esta conta ultrapassou o teto estipulado em <?= format_currency($valor - $limite) ?>.
                        </p>
                    <?php elseif ($pct >= 80): ?>
                        <p style="font-size: 0.8rem; color: var(--warning); font-weight: 600; margin-top: 6px;">
                            ⚡ Esta conta está bem próxima do teto limite definido.
                        </p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 18px; margin-bottom: 24px; font-size: 0.9rem;">
            <div>
                <strong style="color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;">Mês de Referência:</strong>
                <div style="color: var(--text-primary); font-weight: 600; margin-top: 2px;">
                    <?= format_month_name($conta['mes_referencia']) ?>
                </div>
            </div>

            <div>
                <strong style="color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;">Data de Vencimento:</strong>
                <div style="color: var(--text-primary); font-weight: 600; margin-top: 2px;">
                    <?= format_date($conta['vencimento']) ?>
                </div>
            </div>

            <div>
                <strong style="color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;">Data de Pagamento:</strong>
                <div style="color: var(--text-primary); font-weight: 600; margin-top: 2px;">
                    <?= $conta['data_pagamento'] ? format_date($conta['data_pagamento']) : 'Não quitada' ?>
                </div>
            </div>

            <div>
                <strong style="color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;">Cadastrada em:</strong>
                <div style="color: var(--text-primary); font-weight: 600; margin-top: 2px;">
                    <?= format_date($conta['data_criacao'], 'd/m/Y H:i') ?>
                </div>
            </div>
        </div>

        <?php if (!empty($conta['observacao'])): ?>
            <div style="border-top: 1px solid var(--border-subtle); padding-top: 16px;">
                <strong style="color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;">Observações:</strong>
                <p style="color: var(--text-secondary); margin-top: 6px; white-space: pre-line; line-height: 1.6;">
                    <?= e($conta['observacao']) ?>
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include dirname(__DIR__) . '/views/footer.php'; ?>
