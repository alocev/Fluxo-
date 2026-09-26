<?php
/**
 * FLUXO — Sistema de Gestão Doméstica
 * Acompanhamento de Consumo de Água e Energia
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
$mesAnterior = get_previous_month($currentMonth);
$nomeMesAnterior = get_month_name_only($mesAnterior);

// Buscar consumos do mês atual e anterior para Água e Energia
function getConsumoData(PDO $db, int $uid, string $tipo, string $mesAtual, string $mesAnt): array {
    $stmt = $db->prepare("
        SELECT id, mes_referencia, valor_consumo, unidade, valor_fatura, observacao 
        FROM consumos 
        WHERE usuario_id = :uid AND tipo = :tipo AND mes_referencia IN (:mesAtual, :mesAnt)
    ");
    $stmt->execute([':uid' => $uid, ':tipo' => $tipo, ':mesAtual' => $mesAtual, ':mesAnt' => $mesAnt]);
    $rows = $stmt->fetchAll();

    $atual = null;
    $anterior = null;

    foreach ($rows as $r) {
        if ($r['mes_referencia'] === $mesAtual) {
            $atual = $r;
        } elseif ($r['mes_referencia'] === $mesAnt) {
            $anterior = $r;
        }
    }

    $valAtual = $atual ? (float)$atual['valor_consumo'] : null;
    $valAnt = $anterior ? (float)$anterior['valor_consumo'] : null;
    $unidade = $atual['unidade'] ?? ($tipo === 'Água' ? 'L' : 'kWh');
    $variacao = calc_variation((float)($valAtual ?? 0), $valAnt);

    return [
        'atual' => $atual,
        'anterior' => $anterior,
        'val_atual' => $valAtual,
        'val_ant' => $valAnt,
        'unidade' => $unidade,
        'variacao' => $variacao
    ];
}

$dadosAgua = getConsumoData($db, $userId, 'Água', $currentMonth, $mesAnterior);
$dadosEnergia = getConsumoData($db, $userId, 'Energia', $currentMonth, $mesAnterior);

// Buscar histórico dos últimos 6 meses para gráficos de evolução
$stmtChart = $db->prepare("
    SELECT mes_referencia, tipo, valor_consumo, unidade
    FROM consumos
    WHERE usuario_id = :uid
    ORDER BY mes_referencia ASC
");
$stmtChart->execute([':uid' => $userId]);
$chartRows = $stmtChart->fetchAll();

$mesesLabels = [];
$chartAgua = [];
$chartEnergia = [];

foreach ($chartRows as $cr) {
    $m = $cr['mes_referencia'];
    if (!in_array($m, $mesesLabels)) {
        $mesesLabels[] = $m;
    }
}
sort($mesesLabels);
// Manter no máximo os últimos 6 períodos para visualização limpa
if (count($mesesLabels) > 6) {
    $mesesLabels = array_slice($mesesLabels, -6);
}

$mesesLabelsFormatted = array_map(function($m) {
    return format_month_name($m, true);
}, $mesesLabels);

foreach ($mesesLabels as $m) {
    $valA = 0;
    $valE = 0;
    foreach ($chartRows as $cr) {
        if ($cr['mes_referencia'] === $m) {
            if ($cr['tipo'] === 'Água') $valA = (float)$cr['valor_consumo'];
            if ($cr['tipo'] === 'Energia') $valE = (float)$cr['valor_consumo'];
        }
    }
    $chartAgua[] = $valA;
    $chartEnergia[] = $valE;
}

// Histórico de todos os registros de consumo deste usuário
$stmtLista = $db->prepare("
    SELECT * FROM consumos 
    WHERE usuario_id = :uid 
    ORDER BY mes_referencia DESC, tipo ASC
");
$stmtLista->execute([':uid' => $userId]);
$historicoConsumos = $stmtLista->fetchAll();

$pageTitle = 'Consumo | FLUXO';
$pageHeading = 'Água e energia';
$activePage = 'consumo';

include dirname(__DIR__) . '/views/header.php';
?>

<!-- Saudação / Introdução da Seção -->
<div style="margin-bottom: 24px;">
    <h2 style="font-size: 1.45rem; font-weight: 800; color: var(--text-primary); letter-spacing: -0.02em;">
        Acompanhamento de Consumo — <?= format_month_name($currentMonth) ?>
    </h2>
    <p style="font-size: 0.92rem; color: var(--text-secondary); margin-top: 4px;">
        Monitore seus medidores para incentivar a sustentabilidade e evitar surpresas nas faturas.
    </p>
</div>

<!-- Barra de Ações Rápidas de Cadastro -->
<div style="display: flex; gap: 12px; margin-bottom: 24px; flex-wrap: wrap;">
    <a href="consumo-cadastrar.php?tipo=Água" class="btn btn-secondary" style="border-left: 4px solid var(--cat-agua-color);">
        💧 Registrar Consumo de Água
    </a>
    <a href="consumo-cadastrar.php?tipo=Energia" class="btn btn-secondary" style="border-left: 4px solid var(--cat-energia-color);">
        ⚡ Registrar Consumo de Energia
    </a>
</div>

<!-- Cards em Destaque: Água e Energia (Seção 20) -->
<div class="consumption-grid">
    <!-- Card de Água -->
    <div class="consumption-card card-water">
        <div class="consumption-card-header">
            <div class="consumption-type" style="color: var(--cat-agua-color);">
                <span style="font-size: 1.4rem;">💧</span>
                <span>Consumo de Água</span>
            </div>
            <?php if ($dadosAgua['val_atual'] !== null && !empty($dadosAgua['atual']['id'])): ?>
                <a href="consumo-editar.php?id=<?= (int)$dadosAgua['atual']['id'] ?>" class="btn-icon" title="Editar este registro">
                    ✏️
                </a>
            <?php endif; ?>
        </div>

        <?php if ($dadosAgua['val_atual'] !== null): 
            $varA = $dadosAgua['variacao'];
            $varBadgeClass = $varA['direction'] === 'up' ? 'variation-up-warning' : ($varA['direction'] === 'down' ? 'variation-down-good' : 'variation-neutral');
        ?>
            <div class="consumption-value-box">
                <span class="consumption-big-val"><?= format_number($dadosAgua['val_atual']) ?></span>
                <span class="consumption-unit"><?= e($dadosAgua['unidade']) ?></span>
            </div>

            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <?php if ($varA['has_previous']): ?>
                    <span class="consumption-variation-badge <?= $varBadgeClass ?>">
                        <?= $varA['formatted'] ?>
                    </span>
                    <span class="consumption-comparison-text" style="margin: 0;">
                        em relação a <?= e($nomeMesAnterior) ?> (<?= format_number($dadosAgua['val_ant']) ?> <?= e($dadosAgua['unidade']) ?>)
                    </span>
                <?php else: ?>
                    <span class="consumption-variation-badge variation-neutral">
                        Primeiro registro
                    </span>
                    <span class="consumption-comparison-text" style="margin: 0;">
                        Sem histórico no mês anterior para comparar
                    </span>
                <?php endif; ?>
            </div>

            <?php if (!empty($dadosAgua['atual']['valor_fatura'])): ?>
                <div style="margin-top: 16px; padding-top: 12px; border-top: 1px solid var(--border-subtle); font-size: 0.84rem; color: var(--text-secondary);">
                    Fatura correspondente: <strong style="color: var(--text-primary);"><?= format_currency($dadosAgua['atual']['valor_fatura']) ?></strong>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div style="padding: 24px 0; text-align: center;">
                <div style="font-size: 2.2rem; margin-bottom: 8px;">💧</div>
                <div style="font-weight: 700; color: var(--text-primary); margin-bottom: 4px;">Nenhum registro de água</div>
                <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 16px;">
                    Você ainda não informou a leitura do hidrômetro para <?= format_month_name($currentMonth) ?>.
                </p>
                <a href="consumo-cadastrar.php?tipo=Água" class="btn btn-primary" style="padding: 7px 14px; font-size: 0.85rem;">
                    + Registrar Leitura
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Card de Energia -->
    <div class="consumption-card card-energy">
        <div class="consumption-card-header">
            <div class="consumption-type" style="color: var(--cat-energia-color);">
                <span style="font-size: 1.4rem;">⚡</span>
                <span>Consumo de Energia</span>
            </div>
            <?php if ($dadosEnergia['val_atual'] !== null && !empty($dadosEnergia['atual']['id'])): ?>
                <a href="consumo-editar.php?id=<?= (int)$dadosEnergia['atual']['id'] ?>" class="btn-icon" title="Editar este registro">
                    ✏️
                </a>
            <?php endif; ?>
        </div>

        <?php if ($dadosEnergia['val_atual'] !== null): 
            $varE = $dadosEnergia['variacao'];
            $varBadgeClass = $varE['direction'] === 'up' ? 'variation-up-warning' : ($varE['direction'] === 'down' ? 'variation-down-good' : 'variation-neutral');
        ?>
            <div class="consumption-value-box">
                <span class="consumption-big-val"><?= format_number($dadosEnergia['val_atual']) ?></span>
                <span class="consumption-unit"><?= e($dadosEnergia['unidade']) ?></span>
            </div>

            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <?php if ($varE['has_previous']): ?>
                    <span class="consumption-variation-badge <?= $varBadgeClass ?>">
                        <?= $varE['formatted'] ?>
                    </span>
                    <span class="consumption-comparison-text" style="margin: 0;">
                        em relação a <?= e($nomeMesAnterior) ?> (<?= format_number($dadosEnergia['val_ant']) ?> <?= e($dadosEnergia['unidade']) ?>)
                    </span>
                <?php else: ?>
                    <span class="consumption-variation-badge variation-neutral">
                        Primeiro registro
                    </span>
                    <span class="consumption-comparison-text" style="margin: 0;">
                        Sem histórico no mês anterior para comparar
                    </span>
                <?php endif; ?>
            </div>

            <?php if (!empty($dadosEnergia['atual']['valor_fatura'])): ?>
                <div style="margin-top: 16px; padding-top: 12px; border-top: 1px solid var(--border-subtle); font-size: 0.84rem; color: var(--text-secondary);">
                    Fatura correspondente: <strong style="color: var(--text-primary);"><?= format_currency($dadosEnergia['atual']['valor_fatura']) ?></strong>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div style="padding: 24px 0; text-align: center;">
                <div style="font-size: 2.2rem; margin-bottom: 8px;">⚡</div>
                <div style="font-weight: 700; color: var(--text-primary); margin-bottom: 4px;">Nenhum registro de energia</div>
                <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 16px;">
                    Você ainda não informou a leitura do relógio de luz para <?= format_month_name($currentMonth) ?>.
                </p>
                <a href="consumo-cadastrar.php?tipo=Energia" class="btn btn-primary" style="padding: 7px 14px; font-size: 0.85rem;">
                    + Registrar Leitura
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Gráficos de Evolução do Consumo (Seção 22) -->
<?php if (!empty($mesesLabels)): ?>
    <div class="form-card" style="margin-bottom: 32px;">
        <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 6px; color: var(--text-primary);">
            Evolução Histórica do Consumo
        </h3>
        <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 24px;">
            Acompanhe o comportamento das leituras ao longo dos últimos meses.
        </p>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;" class="form-row">
            <div>
                <h4 style="font-size: 0.92rem; font-weight: 700; color: var(--cat-agua-color); margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                    💧 Água (Litros/m³)
                </h4>
                <div style="height: 220px; position: relative;">
                    <canvas id="chartConsumoAgua"></canvas>
                </div>
            </div>

            <div>
                <h4 style="font-size: 0.92rem; font-weight: 700; color: var(--cat-energia-color); margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                    ⚡ Energia (kWh)
                </h4>
                <div style="height: 220px; position: relative;">
                    <canvas id="chartConsumoEnergia"></canvas>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Tabela com Histórico de Leituras Cadastradas -->
<div class="form-card">
    <div class="section-header" style="margin-bottom: 16px;">
        <h3 class="section-title">
            <span>📋</span> Todas as Leituras Registradas
        </h3>
        <a href="consumo-cadastrar.php" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.82rem;">
            + Nova Leitura
        </a>
    </div>

    <?php if (empty($historicoConsumos)): ?>
        <div class="empty-state" style="padding: 24px;">
            <div class="empty-state-icon" style="font-size: 2.2rem;">💧⚡</div>
            <div class="empty-state-title" style="font-size: 1rem;">Nenhum consumo cadastrado</div>
            <p class="empty-state-desc" style="font-size: 0.85rem;">
                Cadastre os dados de consumo de água e energia da sua residência para gerar os comparativos.
            </p>
        </div>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border-color); color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;">
                        <th style="padding: 12px 10px;">Período / Mês</th>
                        <th style="padding: 12px 10px;">Tipo</th>
                        <th style="padding: 12px 10px;">Consumo Registrado</th>
                        <th style="padding: 12px 10px;">Valor Fatura</th>
                        <th style="padding: 12px 10px;">Observação</th>
                        <th style="padding: 12px 10px; text-align: right;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($historicoConsumos as $c): 
                        $isAgua = $c['tipo'] === 'Água';
                        $icon = $isAgua ? '💧' : '⚡';
                        $color = $isAgua ? 'var(--cat-agua-color)' : 'var(--cat-energia-color)';
                        $bg = $isAgua ? 'var(--cat-agua-bg)' : 'var(--cat-energia-bg)';
                    ?>
                        <tr style="border-bottom: 1px solid var(--border-subtle); transition: background-color 0.2s;">
                            <td style="padding: 14px 10px; font-weight: 700; color: var(--text-primary);">
                                <?= format_month_name($c['mes_referencia']) ?>
                            </td>
                            <td style="padding: 14px 10px;">
                                <span style="display: inline-flex; align-items: center; gap: 6px; padding: 3px 10px; border-radius: var(--radius-pill); font-size: 0.8rem; font-weight: 700; background-color: <?= $bg ?>; color: <?= $color ?>;">
                                    <?= $icon ?> <?= e($c['tipo']) ?>
                                </span>
                            </td>
                            <td style="padding: 14px 10px; font-weight: 800; color: var(--text-primary);">
                                <?= format_number($c['valor_consumo']) ?> <span style="font-size: 0.8rem; font-weight: 600; color: var(--text-muted);"><?= e($c['unidade']) ?></span>
                            </td>
                            <td style="padding: 14px 10px; color: var(--text-secondary);">
                                <?= $c['valor_fatura'] ? format_currency($c['valor_fatura']) : '-' ?>
                            </td>
                            <td style="padding: 14px 10px; color: var(--text-muted); font-size: 0.82rem; max-width: 220px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                <?= !empty($c['observacao']) ? e($c['observacao']) : '-' ?>
                            </td>
                            <td style="padding: 14px 10px; text-align: right;">
                                <div style="display: inline-flex; gap: 6px;">
                                    <a href="consumo-editar.php?id=<?= (int)$c['id'] ?>" class="btn-icon" title="Editar">
                                        ✏️
                                    </a>
                                    <form action="consumo-excluir.php" method="POST" style="margin: 0; display: inline;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                                        <button type="submit" class="btn-icon btn-delete-confirm" data-item-name="leitura de <?= e($c['tipo']) ?> de <?= format_month_name($c['mes_referencia']) ?>" title="Excluir" style="color: var(--danger);">
                                            🗑️
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Inicialização dos Gráficos Chart.js com Suporte aos 3 Temas -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const labels = <?= json_encode($mesesLabelsFormatted) ?>;
    const dataAgua = <?= json_encode($chartAgua) ?>;
    const dataEnergia = <?= json_encode($chartEnergia) ?>;

    const isDark = () => document.documentElement.getAttribute('data-theme') === 'escuro';
    const getGridColor = () => isDark() ? 'rgba(255, 255, 255, 0.08)' : '#e6f0ea';
    const getTextColor = () => isDark() ? '#9EB5A8' : '#65756D';

    let chartAguaInst = null;
    let chartEnergiaInst = null;

    const ctxAgua = document.getElementById('chartConsumoAgua');
    if (ctxAgua) {
        chartAguaInst = new Chart(ctxAgua, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Água',
                    data: dataAgua,
                    borderColor: '#5BA7D1',
                    backgroundColor: 'rgba(91, 167, 209, 0.15)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.35,
                    pointBackgroundColor: '#5BA7D1',
                    pointRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: getGridColor() },
                        ticks: { color: getTextColor() }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: getTextColor() }
                    }
                }
            }
        });
    }

    const ctxEnergia = document.getElementById('chartConsumoEnergia');
    if (ctxEnergia) {
        chartEnergiaInst = new Chart(ctxEnergia, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Energia',
                    data: dataEnergia,
                    borderColor: '#D9A928',
                    backgroundColor: 'rgba(217, 169, 40, 0.15)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.35,
                    pointBackgroundColor: '#D9A928',
                    pointRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: getGridColor() },
                        ticks: { color: getTextColor() }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: getTextColor() }
                    }
                }
            }
        });
    }

    // Registra gancho global de alteração de tema para atualizar gráficos dinamicamente
    window.onFluxoThemeChange = function(theme) {
        const gridCol = theme === 'escuro' ? 'rgba(255, 255, 255, 0.08)' : '#e6f0ea';
        const textCol = theme === 'escuro' ? '#9EB5A8' : '#65756D';
        [chartAguaInst, chartEnergiaInst].forEach(ch => {
            if (ch && ch.options && ch.options.scales) {
                if (ch.options.scales.y) {
                    ch.options.scales.y.grid.color = gridCol;
                    ch.options.scales.y.ticks.color = textCol;
                }
                if (ch.options.scales.x) {
                    ch.options.scales.x.ticks.color = textCol;
                }
                ch.update();
            }
        });
    };
});
</script>

<?php include dirname(__DIR__) . '/views/footer.php'; ?>
