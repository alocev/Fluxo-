<?php
/**
 * FLUXO — Sistema de Gestão Doméstica
 * Barra Lateral de Navegação
 */

$user = current_user();
$userName = $user['nome'] ?? 'Usuário';
$userUsuario = $user['usuario'] ?? '';
$initial = strtoupper(mb_substr($userName, 0, 1, 'UTF-8'));
$active = $activePage ?? 'dashboard';
?>
<aside class="app-sidebar" id="appSidebar">
    <!-- Brand / Logotipo Oficial de Fundo Verde -->
    <a href="dashboard.php" class="brand">
        <div class="brand-logo-wrap">
            <img src="../assets/img/logo-green.jpg" alt="FLUXO" class="brand-logo-img">
        </div>
        <div class="brand-text">
            <h1>FLUXO</h1>
            <span>Gestão de contas</span>
        </div>
    </a>

    <!-- Menu Principal -->
    <ul class="nav-menu">
        <li class="nav-item <?= $active === 'dashboard' ? 'active' : '' ?>">
            <a href="dashboard.php">
                <span class="nav-icon">📊</span>
                <span>Início</span>
            </a>
        </li>
        <li class="nav-item <?= $active === 'contas' ? 'active' : '' ?>">
            <a href="contas.php">
                <span class="nav-icon">📑</span>
                <span>Contas da casa</span>
            </a>
        </li>
        <li class="nav-item <?= $active === 'orcamento' ? 'active' : '' ?>">
            <a href="orcamento.php">
                <span class="nav-icon">🎯</span>
                <span>Orçamento mensal</span>
            </a>
        </li>
        <li class="nav-item <?= $active === 'consumo' ? 'active' : '' ?>">
            <a href="consumo.php">
                <span class="nav-icon">💧</span>
                <span>Água e energia</span>
            </a>
        </li>
        <li class="nav-item <?= $active === 'relatorios' ? 'active' : '' ?>">
            <a href="relatorios.php">
                <span class="nav-icon">📈</span>
                <span>Relatórios</span>
            </a>
        </li>
    </ul>

    <!-- Rodapé da Sidebar: Usuário Autenticado -->
    <div class="sidebar-user">
        <div class="user-profile-badge">
            <div class="user-avatar"><?= e($initial) ?></div>
            <div class="user-info">
                <div class="user-name" title="<?= e($userName) ?>"><?= e($userName) ?></div>
                <div class="user-handle" title="@<?= e($userUsuario) ?>">@<?= e($userUsuario) ?></div>
            </div>
        </div>

        <div class="sidebar-theme-box">
            <div class="sidebar-theme-header">
                <span>Tema Visual</span>
            </div>
            <div class="theme-switcher-pill" role="group" aria-label="Tema Visual">
                <button type="button" class="theme-btn" data-theme-val="claro" title="Modo Claro">
                    ☀️ Claro
                </button>
                <button type="button" class="theme-btn" data-theme-val="verde" title="Modo Verde">
                    🌿 Verde
                </button>
                <button type="button" class="theme-btn" data-theme-val="escuro" title="Modo Escuro">
                    🌙 Escuro
                </button>
            </div>
        </div>

        <form action="logout.php" method="POST" style="margin: 0;">
            <?= csrf_field() ?>
            <button type="submit" class="btn-logout">
                🚪 Sair da conta
            </button>
        </form>
    </div>
</aside>
