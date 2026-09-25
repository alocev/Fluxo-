<?php
/**
 * FLUXO — Sistema de Gestão Doméstica
 * Relatórios e Análise de Gastos e Consumo
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

// 1. Gastos por Categoria no mês selecionado
$stmtCat = $db->prepare("
    SELECT 
        categoria, 
        COUNT(*) as total_contas,
        SUM(valor) as total_valor
    FROM contas 
    WHERE usuario_id = :uid AND mes_referencia = :mes
    GROUP BY categoria
    ORDER BY total_valor DESC
");
$stmtCat->execute([':uid' => $userId, ':mes' => $currentMonth]);
$gastosPorCategoria = $stmtCat->fetchAll();

$totalGastoMes = 0;
foreach ($gastosPorCategoria as $g) {
    $totalGastoMes += (float)$g['total_valor'];
}

$chartCatLabels = [];
$chartCatData = [];
$chartCatColors = [];

foreach ($gastosPorCategoria as $cat) {
    $meta = get_category_meta($cat['categoria']);
    $chartCatLabels[] = $cat['categoria'];
    $chartCatData[] = (float)$cat['total_valor'];
    $chartCatColors[] = $meta['color'];
}

// 2. Evolução Mensal dos Gastos nos últimos 6 meses
$stmtEvolucao = $db->prepare("
    SELECT 
        mes_referencia,
        COUNT(*) as qtd_contas,
        SUM(valor) as total_gasto,
        SUM(CASE WHEN status = 'paga' THEN valor ELSE 0 END) as total_pago,
        SUM(CASE WHEN status != 'paga' THEN valor ELSE 0 END) as total_pendente
    FROM contas
    WHERE usuario_id = :uid
    GROUP BY mes_referencia
    ORDER BY mes_referencia ASC
");
$stmtEvolucao->execute([':uid' => $userId]);
$evolucaoRows = $stmtEvolucao->fetchAll();

// Limitar aos últimos 6 meses
if (count($evolucaoRows) > 6) {
    $evolucaoRows = array_slice($evolucaoRows, -6);
}

$chartEvolLabels = [];
$chartEvolGastos = [];
$chartEvolPagos = [];

foreach ($evolucaoRows as $er) {
    $chartEvolLabels[] = format_month_name($er['mes_referencia'], true);
    $chartEvolGastos[] = (float)$er['total_gasto'];
    $chartEvolPagos[] = (float)$er['total_pago'];
}

// 3. Resumo de Contas Pagas vs Pendentes
$stmtStatus = $db->prepare("
    SELECT 
        status, 
        COUNT(*) as qtd,
        SUM(valor) as total
    FROM contas 
    WHERE usuario_id = :uid AND mes_referencia = :mes
    GROUP BY status
");
$stmtStatus->execute([':uid' => $userId, ':mes' => $currentMonth]);
$statusResumo = $stmtStatus->fetchAll();

// 4. Comparativo de Consumo nos últimos períodos
$stmtConsumoRel = $db->prepare("
    SELECT mes_referencia, tipo, valor_consumo, unidade, valor_fatura
    FROM consumos
    WHERE usuario_id = :uid
    ORDER BY mes_referencia DESC, tipo ASC
    LIMIT 10
");
$stmtConsumoRel->execute([':uid' => $userId]);
$consumoRelatorio = $stmtConsumoRel->fetchAll();

$pageTitle = 'Relatórios — FLUXO';
$pageHeading = 'Relatórios e Análises';
$activePage = 'relatorios';

include dirname(__DIR__) . '/views/header.php';
?>

<!-- Cabeçalho do Relatório -->
<div style="margin-bottom: 24px;">
    <h2 style="font-size: 1.45rem; font-weight: 800; color: var(--text-primary); letter-spacing: -0.02em;">
        Relatório Doméstico — <?= format_month_name($currentMonth) ?>
    </h2>
    <p style="font-size: 0.92rem; color: var(--text-secondary); margin-top: 4px;">
        Entenda a distribuição do seu dinheiro e identifique oportunidades de economia para sua casa.
    </p>
</div>

<!-- Seção 1: Distribuição por Categorias -->
<div class="form-card" style="margin-bottom: 28px;">
    <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 6px; color: var(--text-primary);">
        Gastos por Categoria (<?= format_month_name($currentMonth) ?>)
    </h3>
    <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 20px;">
        Total apurado no período: <strong><?= format_currency($totalGastoMes) ?></strong>
    </p>

    <?php if (empty($gastosPorCategoria)): ?>
        <div class="empty-state" style="padding: 24px;">
            <div class="empty-state-icon" style="font-size: 2rem;">📊</div>
            <div class="empty-state-title" style="font-size: 1rem;">Nenhuma despesa para exibir neste mês</div>
            <p class="empty-state-desc" style="font-size: 0.82rem;">Adicione contas na aba "Contas da Casa" para gerar o gráfico.</p>
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: 320px 1fr; gap: 32px; align-items: center;" class="form-row">
            <!-- Gráfico Donut -->
            <div style="height: 260px; position: relative;">
                <canvas id="chartCategorias"></canvas>
            </div>

            <!-- Tabela Detalhada por Categoria -->
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color); color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;">
                            <th style="padding: 8px 10px; text-align: left;">Categoria</th>
                            <th style="padding: 8px 10px; text-align: center;">Contas</th>
                            <th style="padding: 8px 10px; text-align: right;">Total (R$)</th>
                            <th style="padding: 8px 10px; text-align: right;">% do Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($gastosPorCategoria as $cat): 
                            $meta = get_category_meta($cat['categoria']);
                            $val = (float)$cat['total_valor'];
                            $pct = $totalGastoMes > 0 ? ($val / $totalGastoMes) * 100 : 0;
                        ?>
                            <tr style="border-bottom: 1px solid var(--border-subtle);">
                                <td style="padding: 10px; font-weight: 700;">
                                    <span style="display: inline-flex; align-items: center; gap: 6px; padding: 2px 8px; border-radius: 6px; background-color: <?= $meta['bg'] ?>; color: <?= $meta['color'] ?>; font-size: 0.82rem;">
                                        <?= $meta['icon'] ?> <?= e($cat['categoria']) ?>
                                    </span>
                                </td>
                                <td style="padding: 10px; text-align: center; color: var(--text-muted);">
                                    <?= (int)$cat['total_contas'] ?>
                                </td>
                                <td style="padding: 10px; text-align: right; font-weight: 800; color: var(--text-primary);">
                                    <?= format_currency($val) ?>
                                </td>
                                <td style="padding: 10px; text-align: right; font-weight: 700; color: var(--primary);">
                                    <?= round($pct) ?>%
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Seção 2: Evolução dos Gastos Mensais -->
<div class="form-card" style="margin-bottom: 28px;">
    <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 6px; color: var(--text-primary);">
        Evolução dos Gastos da Casa ao Longo dos Meses
    </h3>
    <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 24px;">
        Histórico comparativo dos valores comprometidos e quitados nos períodos recentes.
    </p>

    <?php if (empty($evolucaoRows)): ?>
        <div class="empty-state" style="padding: 24px;">
            <div class="empty-state-icon" style="font-size: 2rem;">📈</div>
            <div class="empty-state-title" style="font-size: 1rem;">Histórico insuficiente</div>
            <p class="empty-state-desc" style="font-size: 0.82rem;">Cadastre despesas ao longo dos meses para visualizar a linha de tendência.</p>
        </div>
    <?php else: ?>
        <div style="height: 280px; position: relative; margin-bottom: 20px;">
            <canvas id="chartEvolucaoGastos"></canvas>
        </div>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.88rem;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border-color); color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;">
                        <th style="padding: 8px 10px; text-align: left;">Mês</th>
                        <th style="padding: 8px 10px; text-align: center;">Qtd Contas</th>
                        <th style="padding: 8px 10px; text-align: right;">Total Comprometido</th>
                        <th style="padding: 8px 10px; text-align: right;">Total Quitado</th>
                        <th style="padding: 8px 10px; text-align: right;">Pendente</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($evolucaoRows as $er): ?>
                        <tr style="border-bottom: 1px solid var(--border-subtle);">
                            <td style="padding: 10px; font-weight: 700; color: var(--text-primary);">
                                <?= format_month_name($er['mes_referencia']) ?>
                            </td>
                            <td style="padding: 10px; text-align: center; color: var(--text-muted);">
                                <?= (int)$er['qtd_contas'] ?>
                            </td>
                            <td style="padding: 10px; text-align: right; font-weight: 800; color: var(--text-primary);">
                                <?= format_currency($er['total_gasto']) ?>
                            </td>
                            <td style="padding: 10px; text-align: right; font-weight: 700; color: var(--success);">
                                <?= format_currency($er['total_pago']) ?>
                            </td>
                            <td style="padding: 10px; text-align: right; font-weight: 700; color: var(--warning);">
                                <?= format_currency($er['total_pendente']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Seção 3: Comparativo de Consumo de Recursos -->
<div class="form-card">
    <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 6px; color: var(--text-primary);">
        Histórico e Variações de Consumo Doméstico
    </h3>
    <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 20px;">
        Monitore aumentos ou reduções físicas nos consumos de água e energia da residência.
    </p>

    <?php if (empty($consumoRelatorio)): ?>
        <div class="empty-state" style="padding: 24px;">
            <div class="empty-state-icon" style="font-size: 2rem;">💧⚡</div>
            <div class="empty-state-title" style="font-size: 1rem;">Nenhuma leitura registrada</div>
            <p class="empty-state-desc" style="font-size: 0.82rem;">Adicione leituras no módulo de Consumo para alimentar este relatório.</p>
        </div>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border-color); color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;">
                        <th style="padding: 10px; text-align: left;">Mês</th>
                        <th style="padding: 10px; text-align: left;">Tipo</th>
                        <th style="padding: 10px; text-align: right;">Medição</th>
                        <th style="padding: 10px; text-align: right;">Valor Fatura</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($consumoRelatorio as $cr): 
                        $isAgua = $cr['tipo'] === 'Água';
                        $color = $isAgua ? 'var(--cat-agua-color)' : 'var(--cat-energia-color)';
                        $bg = $isAgua ? 'var(--cat-agua-bg)' : 'var(--cat-energia-bg)';
                        $icon = $isAgua ? '💧' : '⚡';
                    ?>
                        <tr style="border-bottom: 1px solid var(--border-subtle);">
                            <td style="padding: 10px; font-weight: 700; color: var(--text-primary);">
                                <?= format_month_name($cr['mes_referencia']) ?>
                            </td>
                            <td style="padding: 10px;">
                                <span style="display: inline-flex; align-items: center; gap: 6px; padding: 2px 8px; border-radius: 6px; background-color: <?= $bg ?>; color: <?= $color ?>; font-size: 0.82rem; font-weight: 700;">
                                    <?= $icon ?> <?= e($cr['tipo']) ?>
                                </span>
                            </td>
                            <td style="padding: 10px; text-align: right; font-weight: 800; color: var(--text-primary);">
                                <?= format_number($cr['valor_consumo']) ?> <?= e($cr['unidade']) ?>
                            </td>
                            <td style="padding: 10px; text-align: right; color: var(--text-secondary);">
                                <?= $cr['valor_fatura'] ? format_currency($cr['valor_fatura']) : '-' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Scripts dos Gráficos com Chart.js -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Gráfico de Categorias (Donut)
    const catLabels = <?= json_encode($chartCatLabels) ?>;
    const catData = <?= json_encode($chartCatData) ?>;
    const catColors = <?= json_encode($chartCatColors) ?>;

    const ctxCat = document.getElementById('chartCategorias');
    if (ctxCat && catData.length > 0) {
        new Chart(ctxCat, {
            type: 'doughnut',
            data: {
                labels: catLabels,
                datasets: [{
                    data: catData,
                    backgroundColor: catColors,
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: { family: 'Plus Jakarta Sans', size: 12 },
                            padding: 14
                        }
                    }
                },
                cutout: '65%'
            }
        });
    }

    // 2. Gráfico de Evolução Mensal (Barras / Linha)
    const evolLabels = <?= json_encode($chartEvolLabels) ?>;
    const evolGastos = <?= json_encode($chartEvolGastos) ?>;
    const evolPagos = <?= json_encode($chartEvolPagos) ?>;

    const ctxEvol = document.getElementById('chartEvolucaoGastos');
    if (ctxEvol && evolLabels.length > 0) {
        new Chart(ctxEvol, {
            type: 'bar',
            data: {
                labels: evolLabels,
                datasets: [
                    {
                        label: 'Total Comprometido',
                        data: evolGastos,
                        backgroundColor: 'rgba(45, 122, 88, 0.75)',
                        borderColor: '#2d7a58',
                        borderWidth: 1,
                        borderRadius: 6
                    },
                    {
                        label: 'Total Pago',
                        data: evolPagos,
                        backgroundColor: 'rgba(52, 211, 153, 0.5)',
                        borderColor: '#34d399',
                        borderWidth: 1,
                        borderRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { font: { family: 'Plus Jakarta Sans', size: 12 } }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f3' },
                        ticks: {
                            callback: function(value) { return 'R$ ' + value; }
                        }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    }
});
</script>

<?php include dirname(__DIR__) . '/views/footer.php'; ?>
