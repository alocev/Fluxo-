<?php
/**
 * FLUXO — Sistema de Gestão Doméstica
 * Gerenciamento do Orçamento Mensal
 */

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/functions.php';

require_auth();

$db = getDBConnection();
$userId = current_user_id();

// Mês selecionado
if (isset($_GET['mes']) && preg_match('/^\d{4}-\d{2}$/', $_GET['mes'])) {
    $_SESSION['selected_month'] = $_GET['mes'];
}
$currentMonth = $_SESSION['selected_month'] ?? date('Y-m');

$error = '';

// Processa salvamento/atualização do orçamento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $mesForm = trim($_POST['mes_referencia'] ?? '');
    $limiteRaw = str_replace(',', '.', trim($_POST['limite_mensal'] ?? ''));

    if (!preg_match('/^\d{4}-\d{2}$/', $mesForm)) {
        $error = 'Mês de referência inválido.';
    } elseif (!is_numeric($limiteRaw) || (float)$limiteRaw < 0) {
        $error = 'Por favor, informe um valor de orçamento válido e positivo.';
    } else {
        $limite = (float)$limiteRaw;

        try {
            $stmtUpsert = $db->prepare("
                INSERT INTO orcamentos (usuario_id, mes_referencia, limite_mensal, data_criacao)
                VALUES (:uid, :mes, :limite, NOW())
                ON DUPLICATE KEY UPDATE limite_mensal = :limite_update, data_atualizacao = NOW()
            ");
            $stmtUpsert->execute([
                ':uid' => $userId,
                ':mes' => $mesForm,
                ':limite' => $limite,
                ':limite_update' => $limite
            ]);

            flash_set('success', 'Orçamento de ' . format_month_name($mesForm) . ' definido com sucesso!');
            header("Location: orcamento.php?mes={$mesForm}");
            exit;
        } catch (PDOException $e) {
            error_log("Erro ao salvar orçamento: " . $e->getMessage());
            $error = 'Não foi possível salvar o orçamento. Tente novamente.';
        }
    }
}

// Busca orçamento do mês selecionado
$stmtOrc = $db->prepare("SELECT * FROM orcamentos WHERE usuario_id = :uid AND mes_referencia = :mes LIMIT 1");
$stmtOrc->execute([':uid' => $userId, ':mes' => $currentMonth]);
$orcamento = $stmtOrc->fetch();

$limiteAtual = $orcamento ? (float)$orcamento['limite_mensal'] : 0.0;

// Soma total comprometido nas contas cadastradas do mês
$stmtGasto = $db->prepare("SELECT COALESCE(SUM(valor), 0) as total FROM contas WHERE usuario_id = :uid AND mes_referencia = :mes");
$stmtGasto->execute([':uid' => $userId, ':mes' => $currentMonth]);
$totalComprometido = (float)$stmtGasto->fetchColumn();

// Cálculos de orçamento
$disponivel = $limiteAtual - $totalComprometido;
$percentual = $limiteAtual > 0 ? ($totalComprometido / $limiteAtual) * 100 : 0;

// Determinação do estado do orçamento
if ($limiteAtual <= 0) {
    $estadoClasse = 'status-warning';
    $barraClasse = 'fill-warning';
    $estadoTexto = 'Orçamento não definido';
} elseif ($totalComprometido > $limiteAtual) {
    $estadoClasse = 'status-overbudget';
    $barraClasse = 'fill-overbudget';
    $estadoTexto = '🚨 Orçamento Ultrapassado';
} elseif ($percentual >= 80) {
    $estadoClasse = 'status-warning';
    $barraClasse = 'fill-warning';
    $estadoTexto = '⚠️ Próximo do Limite';
} else {
    $estadoClasse = 'status-comfortable';
    $barraClasse = 'fill-comfortable';
    $estadoTexto = '✓ Confortável';
}

// Histórico de orçamentos dos outros meses
$stmtHistorico = $db->prepare("
    SELECT o.mes_referencia, o.limite_mensal, COALESCE(SUM(c.valor), 0) as total_gasto
    FROM orcamentos o
    LEFT JOIN contas c ON c.usuario_id = o.usuario_id AND c.mes_referencia = o.mes_referencia
    WHERE o.usuario_id = :uid
    GROUP BY o.mes_referencia, o.limite_mensal
    ORDER BY o.mes_referencia DESC
    LIMIT 6
");
$stmtHistorico->execute([':uid' => $userId]);
$historicoOrcamentos = $stmtHistorico->fetchAll();

$pageTitle = 'Orçamento Mensal — FLUXO';
$pageHeading = 'Orçamento Doméstico';
$activePage = 'orcamento';

include dirname(__DIR__) . '/views/header.php';
?>

<!-- Card Principal em Destaque do Orçamento -->
<div class="budget-hero-card">
    <div class="budget-header">
        <div class="budget-header-title">
            <span style="font-size: 1.5rem;">🎯</span>
            <div>
                <h3>Orçamento de <?= format_month_name($currentMonth) ?></h3>
                <span style="font-size: 0.82rem; font-weight: 500; color: var(--text-muted);">
                    Controle de teto de gastos familiar
                </span>
            </div>
        </div>

        <span class="budget-status-pill <?= $estadoClasse ?>">
            <?= $estadoTexto ?>
        </span>
    </div>

    <div class="budget-numbers-grid">
        <div class="budget-col">
            <span class="budget-label">Orçamento Definido</span>
            <div class="budget-val-main">
                <?= format_currency($limiteAtual) ?>
            </div>
            <span style="font-size: 0.78rem; color: var(--text-muted); margin-top: 4px;">
                Teto máximo planejado
            </span>
        </div>

        <div class="budget-col">
            <span class="budget-label">Comprometido / Gasto</span>
            <div class="budget-val-main <?= $totalComprometido > $limiteAtual ? 'budget-val-danger' : '' ?>">
                <?= format_currency($totalComprometido) ?>
            </div>
            <span style="font-size: 0.78rem; color: var(--text-muted); margin-top: 4px;">
                <?= round($percentual) ?>% do orçamento total
            </span>
        </div>

        <div class="budget-col">
            <span class="budget-label">Saldo Disponível</span>
            <div class="budget-val-main <?= $disponivel < 0 ? 'budget-val-danger' : 'budget-val-accent' ?>">
                <?= format_currency($disponivel) ?>
            </div>
            <span style="font-size: 0.78rem; color: var(--text-muted); margin-top: 4px;">
                <?= $disponivel < 0 ? 'Excedente a conter' : 'Livre para novas despesas' ?>
            </span>
        </div>
    </div>

    <!-- Barra de Progresso do Orçamento -->
    <div class="budget-progress-track" style="height: 16px;">
        <div class="budget-progress-fill <?= $barraClasse ?>" style="width: <?= min(100, $percentual) ?>%;"></div>
    </div>

    <div class="budget-footer-info">
        <span>0%</span>
        <span><strong><?= round($percentual) ?>%</strong> consumido</span>
        <span>100% (<?= format_currency($limiteAtual) ?>)</span>
    </div>
</div>

<!-- Layout em Grid: Formulário de Ajuste e Histórico -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 32px;" class="form-row">
    <!-- Formulário para Definir/Alterar Orçamento -->
    <div class="form-card">
        <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 6px; color: var(--text-primary);">
            Definir ou Ajustar Teto Mensal
        </h3>
        <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 20px;">
            Escolha o mês e estabeleça quanto sua casa pretende gastar ao todo.
        </p>

        <?php if (!empty($error)): ?>
            <div class="flash-banner flash-error" style="margin-bottom: 16px;">
                <span>✕ <?= e($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="orcamento.php">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="mes_referencia">Mês Desejado</label>
                <input type="month" name="mes_referencia" id="mes_referencia" class="form-control" 
                       value="<?= e($currentMonth) ?>" required>
            </div>

            <div class="form-group">
                <label for="limite_mensal">Limite Geral de Gastos (R$)</label>
                <input type="text" name="limite_mensal" id="limite_mensal" class="form-control" 
                       placeholder="Ex: 1200,00" 
                       value="<?= $limiteAtual > 0 ? number_format($limiteAtual, 2, ',', '') : '' ?>" required>
                <span class="form-hint">Dica: Um valor realista previne surpresas financeiras no fim do mês.</span>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 8px;">
                Salvar Orçamento do Mês
            </button>
        </form>
    </div>

    <!-- Histórico de Orçamentos Recentes -->
    <div class="form-card">
        <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 6px; color: var(--text-primary);">
            Histórico de Orçamentos
        </h3>
        <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 20px;">
            Acompanhe o planejado versus realizado nos últimos períodos.
        </p>

        <?php if (empty($historicoOrcamentos)): ?>
            <div class="empty-state" style="padding: 24px;">
                <div class="empty-state-icon" style="font-size: 2rem;">📊</div>
                <div class="empty-state-title" style="font-size: 1rem;">Nenhum histórico registrado</div>
                <p class="empty-state-desc" style="font-size: 0.82rem;">Defina o orçamento deste mês no formulário ao lado.</p>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <?php foreach ($historicoOrcamentos as $item): 
                    $hLimite = (float)$item['limite_mensal'];
                    $hGasto = (float)$item['total_gasto'];
                    $hPct = $hLimite > 0 ? ($hGasto / $hLimite) * 100 : 0;
                    $isExceeded = $hGasto > $hLimite;
                ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 14px; background: var(--bg-page); border: 1px solid var(--border-color); border-radius: var(--radius-md);">
                        <div>
                            <div style="font-weight: 700; font-size: 0.92rem; color: var(--text-primary);">
                                <?= format_month_name($item['mes_referencia']) ?>
                            </div>
                            <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 2px;">
                                Gasto: <?= format_currency($hGasto) ?> de <?= format_currency($hLimite) ?>
                            </div>
                        </div>

                        <div style="text-align: right;">
                            <span class="account-status-pill <?= $isExceeded ? 'status-pill-vencida' : ($hPct >= 80 ? 'status-pill-pendente' : 'status-pill-paga') ?>">
                                <?= round($hPct) ?>%
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include dirname(__DIR__) . '/views/footer.php'; ?>
