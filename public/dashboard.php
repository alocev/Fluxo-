<?php
/**
 * FLUXO — Sistema de Gestão Doméstica
 * Dashboard Principal: Visão Geral Doméstica, Natural e Acolhedora
 */

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/alerts_helper.php';

require_auth();

$db = getDBConnection();
$userId = current_user_id();
$user = current_user();
$userName = $user['nome'] ?? 'Amigo(a)';

// Mês selecionado no topo
if (isset($_GET['mes']) && preg_match('/^\d{4}-\d{2}$/', $_GET['mes'])) {
    $_SESSION['selected_month'] = $_GET['mes'];
}
$currentMonth = $_SESSION['selected_month'] ?? date('Y-m');
$mesAnterior = get_previous_month($currentMonth);
$nomeMesAtual = format_month_name($currentMonth);
$nomeMesAnterior = get_month_name_only($mesAnterior);

// Atualização automática de contas vencidas
$hoje = date('Y-m-d');
$stmtAutoVenc = $db->prepare("
    UPDATE contas 
    SET status = 'vencida' 
    WHERE usuario_id = :uid AND status = 'pendente' AND vencimento < :hoje
");
$stmtAutoVenc->execute([':uid' => $userId, ':hoje' => $hoje]);

// 1. Orçamento do mês
$stmtOrc = $db->prepare("SELECT limite_mensal FROM orcamentos WHERE usuario_id = :uid AND mes_referencia = :mes LIMIT 1");
$stmtOrc->execute([':uid' => $userId, ':mes' => $currentMonth]);
$orcRow = $stmtOrc->fetch();
$limiteMensal = $orcRow ? (float)$orcRow['limite_mensal'] : 0.0;

// 2. Total gasto / comprometido em contas
$stmtTotais = $db->prepare("
    SELECT 
        COUNT(*) as total_contas,
        COALESCE(SUM(valor), 0) as total_gasto,
        COALESCE(SUM(CASE WHEN status = 'paga' THEN valor ELSE 0 END), 0) as total_pago,
        COALESCE(SUM(CASE WHEN status != 'paga' THEN valor ELSE 0 END), 0) as total_pendente,
        COUNT(CASE WHEN status != 'paga' THEN 1 END) as qtd_pendentes
    FROM contas 
    WHERE usuario_id = :uid AND mes_referencia = :mes
");
$stmtTotais->execute([':uid' => $userId, ':mes' => $currentMonth]);
$metricas = $stmtTotais->fetch();

$totalGasto = (float)$metricas['total_gasto'];
$totalPago = (float)$metricas['total_pago'];
$totalPendente = (float)$metricas['total_pendente'];
$qtdPendentes = (int)$metricas['qtd_pendentes'];

$disponivel = $limiteMensal - $totalGasto;
$percentualGasto = $limiteMensal > 0 ? ($totalGasto / $limiteMensal) * 100 : 0;

// Estado do orçamento
if ($limiteMensal <= 0) {
    $estadoBadgeClass = 'status-warning';
    $estadoBadgeTexto = 'Orçamento não definido';
    $barraProgressClass = 'fill-warning';
} elseif ($totalGasto > $limiteMensal) {
    $estadoBadgeClass = 'status-overbudget';
    $estadoBadgeTexto = '🚨 Orçamento Ultrapassado';
    $barraProgressClass = 'fill-overbudget';
} elseif ($percentualGasto >= 80) {
    $estadoBadgeClass = 'status-warning';
    $estadoBadgeTexto = '⚠️ Atenção ao Limite';
    $barraProgressClass = 'fill-warning';
} else {
    $estadoBadgeClass = 'status-comfortable';
    $estadoBadgeTexto = '✓ Sob Controle';
    $barraProgressClass = 'fill-comfortable';
}

// 3. Frase Dinâmica de Saudação (Seção 16)
$saudacaoTexto = get_greeting($userName);
if ($limiteMensal > 0) {
    if ($totalGasto > $limiteMensal) {
        $fraseDinamica = "Atenção: você ultrapassou seu orçamento planejado em " . format_currency(abs($disponivel)) . ".";
    } elseif ($percentualGasto >= 80) {
        $fraseDinamica = "Você já comprometeu " . round($percentualGasto) . "% do orçamento de " . get_month_name_only($currentMonth) . ". Restam " . format_currency($disponivel) . " disponíveis.";
    } else {
        $fraseDinamica = "Seu orçamento está sob controle este mês. Você ainda possui " . format_currency($disponivel) . " disponíveis.";
    }
} else {
    $fraseDinamica = "Você ainda não definiu um teto de orçamento para " . get_month_name_only($currentMonth) . ".";
}

// 4. Alertas Contextuais Inteligentes (Seção 19 & 24)
$systemAlerts = get_system_alerts($db, $userId, $currentMonth);

// 5. Consumos de Água e Energia
function getConsumoResumo(PDO $db, int $uid, string $tipo, string $mesAtual, string $mesAnt): array {
    $stmt = $db->prepare("
        SELECT mes_referencia, valor_consumo, unidade 
        FROM consumos 
        WHERE usuario_id = :uid AND tipo = :tipo AND mes_referencia IN (:mesAtual, :mesAnt)
    ");
    $stmt->execute([':uid' => $uid, ':tipo' => $tipo, ':mesAtual' => $mesAtual, ':mesAnt' => $mesAnt]);
    $rows = $stmt->fetchAll();

    $valAtual = null;
    $valAnt = null;
    $unidade = $tipo === 'Água' ? 'L' : 'kWh';

    foreach ($rows as $r) {
        if ($r['mes_referencia'] === $mesAtual) {
            $valAtual = (float)$r['valor_consumo'];
            $unidade = $r['unidade'];
        } elseif ($r['mes_referencia'] === $mesAnt) {
            $valAnt = (float)$r['valor_consumo'];
        }
    }

    $variacao = calc_variation((float)($valAtual ?? 0), $valAnt);

    return [
        'val_atual' => $valAtual,
        'val_ant' => $valAnt,
        'unidade' => $unidade,
        'variacao' => $variacao
    ];
}

$consumoAgua = getConsumoResumo($db, $userId, 'Água', $currentMonth, $mesAnterior);
$consumoEnergia = getConsumoResumo($db, $userId, 'Energia', $currentMonth, $mesAnterior);

// 6. Contas do mês (últimas 6 ou mais urgentes)
$stmtContas = $db->prepare("
    SELECT * FROM contas 
    WHERE usuario_id = :uid AND mes_referencia = :mes 
    ORDER BY 
        CASE WHEN status != 'paga' THEN 0 ELSE 1 END,
        vencimento ASC 
    LIMIT 6
");
$stmtContas->execute([':uid' => $userId, ':mes' => $currentMonth]);
$contasDestaque = $stmtContas->fetchAll();

$pageTitle = 'Início — FLUXO Gestão Doméstica';
$pageHeading = 'Visão Geral Doméstica';
$activePage = 'dashboard';

include dirname(__DIR__) . '/views/header.php';
?>

<!-- Saudação Natural e Acolhedora (Seção 16) -->
<div class="welcome-hero">
    <h2 class="welcome-title"><?= e($saudacaoTexto) ?></h2>
    <p class="welcome-subtitle">
        <?= e($fraseDinamica) ?>
    </p>
</div>

<!-- Avisos Automáticos e Contextuais do Sistema (Seção 19 & 24) -->
<?php if (!empty($systemAlerts)): ?>
    <div class="alerts-wrapper">
        <?php foreach ($systemAlerts as $alert): ?>
            <div class="smart-alert alert-<?= e($alert['type']) ?>">
                <div class="alert-icon-box"><?= $alert['icon'] ?></div>
                <div class="alert-content">
                    <div class="alert-title"><?= e($alert['title']) ?></div>
                    <div class="alert-desc"><?= e($alert['message']) ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Orçamento como Elemento Principal em Destaque (Seção 17) -->
<div class="budget-hero-card">
    <div class="budget-header">
        <div class="budget-header-title">
            <span style="font-size: 1.5rem;">🎯</span>
            <div>
                <h3>Orçamento de <?= format_month_name($currentMonth) ?></h3>
                <span style="font-size: 0.82rem; font-weight: 500; color: var(--text-muted);">
                    Seu limite de gastos planejado para este mês
                </span>
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 10px;">
            <span class="budget-status-pill <?= $estadoBadgeClass ?>">
                <?= $estadoBadgeTexto ?>
            </span>
            <a href="orcamento.php?mes=<?= e($currentMonth) ?>" class="btn btn-secondary" style="padding: 5px 12px; font-size: 0.8rem;">
                Ajustar Limite
            </a>
        </div>
    </div>

    <div class="budget-numbers-grid">
        <div class="budget-col">
            <span class="budget-label">Total Comprometido</span>
            <div class="budget-val-main <?= $totalGasto > $limiteMensal ? 'budget-val-danger' : '' ?>">
                <?= format_currency($totalGasto) ?>
            </div>
            <span style="font-size: 0.78rem; color: var(--text-muted); margin-top: 4px;">
                de <?= format_currency($limiteMensal) ?> planejados
            </span>
        </div>

        <div class="budget-col">
            <span class="budget-label">Saldo Disponível</span>
            <div class="budget-val-main <?= $disponivel < 0 ? 'budget-val-danger' : 'budget-val-accent' ?>">
                <?= format_currency($disponivel) ?>
            </div>
            <span style="font-size: 0.78rem; color: var(--text-muted); margin-top: 4px;">
                <?= $disponivel < 0 ? 'Teto estourado' : 'Disponível para uso' ?>
            </span>
        </div>

        <div class="budget-col">
            <span class="budget-label">Percentual Utilizado</span>
            <div class="budget-val-main">
                <?= round($percentualGasto) ?>%
            </div>
            <span style="font-size: 0.78rem; color: var(--text-muted); margin-top: 4px;">
                <?= $qtdPendentes ?> conta(s) pendente(s)
            </span>
        </div>
    </div>

    <!-- Barra de Progresso Elegante -->
    <div class="budget-progress-track">
        <div class="budget-progress-fill <?= $barraProgressClass ?>" style="width: <?= min(100, $percentualGasto) ?>%;"></div>
    </div>

    <div class="budget-footer-info">
        <span>0%</span>
        <span><strong><?= round($percentualGasto) ?>%</strong> do orçamento consumido</span>
        <span><?= format_currency($limiteMensal) ?></span>
    </div>
</div>

<!-- Acompanhamento de Consumo: Água e Energia (Seção 20) -->
<div style="margin-bottom: 32px;">
    <div class="section-header">
        <h3 class="section-title">
            <span>💧⚡</span> Consumo Doméstico
        </h3>
        <a href="consumo.php" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.82rem;">
            Ver Histórico Completo →
        </a>
    </div>

    <div class="consumption-grid">
        <!-- Widget Água -->
        <div class="consumption-card card-water">
            <div class="consumption-card-header">
                <div class="consumption-type" style="color: var(--cat-agua-color);">
                    <span>💧</span>
                    <span>Consumo de Água</span>
                </div>
                <a href="consumo-cadastrar.php?tipo=Água" class="btn-icon" title="Adicionar Leitura">
                    +
                </a>
            </div>

            <?php if ($consumoAgua['val_atual'] !== null): 
                $varAgua = $consumoAgua['variacao'];
                $aguaBadgeClass = $varAgua['direction'] === 'up' ? 'variation-up-warning' : ($varAgua['direction'] === 'down' ? 'variation-down-good' : 'variation-neutral');
            ?>
                <div class="consumption-value-box">
                    <span class="consumption-big-val"><?= format_number($consumoAgua['val_atual']) ?></span>
                    <span class="consumption-unit"><?= e($consumoAgua['unidade']) ?></span>
                </div>

                <div style="display: flex; align-items: center; gap: 8px;">
                    <?php if ($varAgua['has_previous']): ?>
                        <span class="consumption-variation-badge <?= $aguaBadgeClass ?>">
                            <?= $varAgua['formatted'] ?>
                        </span>
                        <span class="consumption-comparison-text" style="margin: 0;">
                            em relação a <?= e($nomeMesAnterior) ?>
                        </span>
                    <?php else: ?>
                        <span class="consumption-variation-badge variation-neutral">Primeiro registro</span>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div style="padding: 12px 0;">
                    <p style="font-size: 0.86rem; color: var(--text-muted); margin-bottom: 12px;">
                        Nenhuma leitura de água registrada em <?= format_month_name($currentMonth, true) ?>.
                    </p>
                    <a href="consumo-cadastrar.php?tipo=Água" class="btn btn-outline-primary" style="padding: 6px 12px; font-size: 0.82rem;">
                        + Informar Leitura
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Widget Energia -->
        <div class="consumption-card card-energy">
            <div class="consumption-card-header">
                <div class="consumption-type" style="color: var(--cat-energia-color);">
                    <span>⚡</span>
                    <span>Consumo de Energia</span>
                </div>
                <a href="consumo-cadastrar.php?tipo=Energia" class="btn-icon" title="Adicionar Leitura">
                    +
                </a>
            </div>

            <?php if ($consumoEnergia['val_atual'] !== null): 
                $varEnergia = $consumoEnergia['variacao'];
                $energiaBadgeClass = $varEnergia['direction'] === 'up' ? 'variation-up-warning' : ($varEnergia['direction'] === 'down' ? 'variation-down-good' : 'variation-neutral');
            ?>
                <div class="consumption-value-box">
                    <span class="consumption-big-val"><?= format_number($consumoEnergia['val_atual']) ?></span>
                    <span class="consumption-unit"><?= e($consumoEnergia['unidade']) ?></span>
                </div>

                <div style="display: flex; align-items: center; gap: 8px;">
                    <?php if ($varEnergia['has_previous']): ?>
                        <span class="consumption-variation-badge <?= $energiaBadgeClass ?>">
                            <?= $varEnergia['formatted'] ?>
                        </span>
                        <span class="consumption-comparison-text" style="margin: 0;">
                            em relação a <?= e($nomeMesAnterior) ?>
                        </span>
                    <?php else: ?>
                        <span class="consumption-variation-badge variation-neutral">Primeiro registro</span>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div style="padding: 12px 0;">
                    <p style="font-size: 0.86rem; color: var(--text-muted); margin-bottom: 12px;">
                        Nenhuma leitura de energia registrada em <?= format_month_name($currentMonth, true) ?>.
                    </p>
                    <a href="consumo-cadastrar.php?tipo=Energia" class="btn btn-outline-primary" style="padding: 6px 12px; font-size: 0.82rem;">
                        + Informar Leitura
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Contas da Casa em Destaque (Seção 18) -->
<div>
    <div class="section-header">
        <h3 class="section-title">
            <span>📑</span> Contas do Mês
        </h3>
        <div style="display: flex; gap: 8px;">
            <a href="contas.php" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.82rem;">
                Ver Todas as Contas (<?= (int)$metricas['total_contas'] ?>) →
            </a>
            <a href="conta-cadastrar.php" class="btn btn-primary" style="padding: 6px 14px; font-size: 0.82rem;">
                + Nova Conta
            </a>
        </div>
    </div>

    <?php if (empty($contasDestaque)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">🏡</div>
            <h3 class="empty-state-title">Nenhuma conta cadastrada este mês</h3>
            <p class="empty-state-desc">
                Organize as contas de água, energia, internet, aluguel e outras despesas para manter o controle total da casa.
            </p>
            <a href="conta-cadastrar.php" class="btn btn-primary">
                Adicionar Primeira Conta
            </a>
        </div>
    <?php else: ?>
        <div class="accounts-list-wrapper">
            <?php foreach ($contasDestaque as $conta): 
                $meta = get_category_meta($conta['categoria']);
                $val = (float)$conta['valor'];
                $lim = $conta['limite_gasto'] !== null ? (float)$conta['limite_gasto'] : null;
                $statusClass = 'status-pill-' . $conta['status'];
                $statusLabel = ucfirst($conta['status']);
            ?>
                <div class="account-card-item <?= e($meta['class']) ?>">
                    <div class="account-primary-info">
                        <div class="category-icon-box" title="<?= e($conta['categoria']) ?>">
                            <?= $meta['icon'] ?>
                        </div>
                        <div class="account-title-group">
                            <div class="account-title"><?= e($conta['nome']) ?></div>
                            <div class="account-meta-tags">
                                <span class="account-cat-pill"><?= e($conta['categoria']) ?></span>
                                <span>•</span>
                                <span>Vence dia <?= format_date($conta['vencimento'], 'd/m') ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="account-value-group">
                        <div class="account-value"><?= format_currency($val) ?></div>
                        <?php if ($lim !== null && $lim > 0): 
                            $pctLim = ($val / $lim) * 100;
                            $hintClass = $val > $lim ? 'danger' : ($pctLim >= 80 ? 'warning' : '');
                        ?>
                            <div class="account-limit-hint <?= $hintClass ?>">
                                Limite: <?= format_currency($lim) ?> (<?= round($pctLim) ?>%)
                            </div>
                        <?php else: ?>
                            <div class="account-limit-hint">Sem limite individual</div>
                        <?php endif; ?>
                    </div>

                    <div style="display: flex; align-items: center; gap: 10px; flex-shrink: 0;">
                        <span class="account-status-pill <?= $statusClass ?>">
                            <?= $conta['status'] === 'paga' ? '✓' : ($conta['status'] === 'vencida' ? '✕' : '⏳') ?> 
                            <?= $statusLabel ?>
                        </span>

                        <div class="account-actions-group">
                            <!-- Alternador rápido de status -->
                            <form action="conta-status.php" method="POST" style="margin: 0;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int)$conta['id'] ?>">
                                <input type="hidden" name="novo_status" value="<?= $conta['status'] === 'paga' ? 'pendente' : 'paga' ?>">
                                <button type="submit" class="btn-icon" title="<?= $conta['status'] === 'paga' ? 'Marcar como Pendente' : 'Marcar como Paga' ?>">
                                    <?= $conta['status'] === 'paga' ? '↩️' : '✅' ?>
                                </button>
                            </form>

                            <a href="conta-detalhes.php?id=<?= (int)$conta['id'] ?>" class="btn-icon" title="Ver Detalhes">
                                👁️
                            </a>

                            <a href="conta-editar.php?id=<?= (int)$conta['id'] ?>" class="btn-icon" title="Editar">
                                ✏️
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include dirname(__DIR__) . '/views/footer.php'; ?>
