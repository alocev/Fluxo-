<?php
/**
 * FLUXO — Sistema de Gestão Doméstica
 * Header & Topbar Comum
 */

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$currentUser = current_user();
$pageTitle = $pageTitle ?? 'FLUXO — Gestão Doméstica';
$activePage = $activePage ?? 'dashboard';

// Determina mês de referência atual (ou selecionado pelo usuário)
if (isset($_GET['mes']) && preg_match('/^\d{4}-\d{2}$/', $_GET['mes'])) {
    $_SESSION['selected_month'] = $_GET['mes'];
}
$currentMonth = $_SESSION['selected_month'] ?? date('Y-m');

// Gerar lista dos últimos 12 meses e próximos 3 meses para o seletor
$availableMonths = [];
for ($i = -8; $i <= 3; $i++) {
    $m = date('Y-m', strtotime("{$i} month"));
    $availableMonths[$m] = format_month_name($m);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    
    <!-- Google Fonts & Stylesheet -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <!-- Chart.js para visualização leve e elegante de gráficos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <div class="app-container">
        <?php include __DIR__ . '/sidebar.php'; ?>
        
        <main class="app-main">
            <!-- Topbar Header -->
            <header class="app-topbar">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <button type="button" class="mobile-nav-toggle" id="mobileNavToggle" aria-label="Abrir Menu">
                        ☰
                    </button>
                    <span class="topbar-title"><?= e($pageHeading ?? 'Visão Geral') ?></span>
                </div>

                <div class="topbar-actions">
                    <!-- Seletor de Mês de Referência -->
                    <form method="GET" class="month-selector-form">
                        <label for="topbarMonthSelect">📅 Mês:</label>
                        <select name="mes" id="topbarMonthSelect">
                            <?php foreach ($availableMonths as $val => $label): ?>
                                <option value="<?= $val ?>" <?= $val === $currentMonth ? 'selected' : '' ?>>
                                    <?= e($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>

                    <!-- Botão de Ação Rápida -->
                    <a href="conta-cadastrar.php" class="btn btn-primary" style="padding: 7px 14px; font-size: 0.85rem;">
                        + Nova Conta
                    </a>
                </div>
            </header>

            <!-- Conteúdo da Página -->
            <div class="content-body">
                <?php include __DIR__ . '/alerts.php'; ?>
