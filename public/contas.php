<?php
/**
 * FLUXO — Sistema de Gestão Doméstica
 * Listagem e Gerenciamento de Contas e Despesas
 */

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/functions.php';

require_auth();

$db = getDBConnection();
$userId = current_user_id();

// Mês de referência selecionado
if (isset($_GET['mes']) && preg_match('/^\d{4}-\d{2}$/', $_GET['mes'])) {
    $_SESSION['selected_month'] = $_GET['mes'];
}
$currentMonth = $_SESSION['selected_month'] ?? date('Y-m');

// Filtro de status
$filtroStatus = $_GET['status'] ?? 'todos';
$allowedStatus = ['todos', 'pendente', 'paga', 'vencida'];
if (!in_array($filtroStatus, $allowedStatus)) {
    $filtroStatus = 'todos';
}

// Atualizar automaticamente contas vencidas que ainda estão como 'pendente'
$hoje = date('Y-m-d');
$stmtAutoVenc = $db->prepare("
    UPDATE contas 
    SET status = 'vencida' 
    WHERE usuario_id = :uid AND status = 'pendente' AND vencimento < :hoje
");
$stmtAutoVenc->execute([':uid' => $userId, ':hoje' => $hoje]);

// Consulta das contas com isolamento estrito por usuário e filtros
$sql = "SELECT * FROM contas WHERE usuario_id = :uid AND mes_referencia = :mes";
$params = [':uid' => $userId, ':mes' => $currentMonth];

if ($filtroStatus !== 'todos') {
    $sql .= " AND status = :status";
    $params[':status'] = $filtroStatus;
}
$sql .= " ORDER BY vencimento ASC, id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$contas = $stmt->fetchAll();

// Métricas do mês para os cartões de resumo
$stmtTotais = $db->prepare("
    SELECT 
        COUNT(*) as total_qtd,
        COALESCE(SUM(valor), 0) as total_valor,
        COALESCE(SUM(CASE WHEN status = 'paga' THEN valor ELSE 0 END), 0) as total_pago,
        COALESCE(SUM(CASE WHEN status != 'paga' THEN valor ELSE 0 END), 0) as total_pendente
    FROM contas 
    WHERE usuario_id = :uid AND mes_referencia = :mes
");
$stmtTotais->execute([':uid' => $userId, ':mes' => $currentMonth]);
$metricas = $stmtTotais->fetch();

$pageTitle = 'Contas | FLUXO';
$pageHeading = 'Contas da casa';
$activePage = 'contas';

include dirname(__DIR__) . '/views/header.php';
?>

<!-- Resumo do Mês das Contas -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
    <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 18px; box-shadow: var(--shadow-sm);">
        <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Total de Despesas</div>
        <div style="font-size: 1.55rem; font-weight: 800; color: var(--text-primary); margin-top: 4px;">
            <?= format_currency($metricas['total_valor']) ?>
        </div>
        <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 4px;">
            <?= (int)$metricas['total_qtd'] ?> conta(s) em <?= format_month_name($currentMonth, true) ?>
        </div>
    </div>

    <div style="background: var(--bg-card); border: 1px solid var(--success-border); border-radius: var(--radius-md); padding: 18px; box-shadow: var(--shadow-sm);">
        <div style="font-size: 0.8rem; font-weight: 700; color: var(--success); text-transform: uppercase;">Total Já Pago</div>
        <div style="font-size: 1.55rem; font-weight: 800; color: var(--success); margin-top: 4px;">
            <?= format_currency($metricas['total_pago']) ?>
        </div>
        <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 4px;">
            Contas quitadas com sucesso
        </div>
    </div>

    <div style="background: var(--bg-card); border: 1px solid var(--warning-border); border-radius: var(--radius-md); padding: 18px; box-shadow: var(--shadow-sm);">
        <div style="font-size: 0.8rem; font-weight: 700; color: var(--warning); text-transform: uppercase;">Pendente ou a Vencer</div>
        <div style="font-size: 1.55rem; font-weight: 800; color: var(--warning); margin-top: 4px;">
            <?= format_currency($metricas['total_pendente']) ?>
        </div>
        <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 4px;">
            Aguardando pagamento
        </div>
    </div>
</div>

<!-- Barra de Ações & Filtros -->
<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; margin-bottom: 20px;">
    <!-- Filtros de Status em Pills -->
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <a href="contas.php?status=todos" class="btn <?= $filtroStatus === 'todos' ? 'btn-primary' : 'btn-secondary' ?>" style="padding: 7px 14px; font-size: 0.85rem;">
            Todas
        </a>
        <a href="contas.php?status=pendente" class="btn <?= $filtroStatus === 'pendente' ? 'btn-primary' : 'btn-secondary' ?>" style="padding: 7px 14px; font-size: 0.85rem;">
            Pendentes
        </a>
        <a href="contas.php?status=vencida" class="btn <?= $filtroStatus === 'vencida' ? 'btn-primary' : 'btn-secondary' ?>" style="padding: 7px 14px; font-size: 0.85rem;">
            Vencidas
        </a>
        <a href="contas.php?status=paga" class="btn <?= $filtroStatus === 'paga' ? 'btn-primary' : 'btn-secondary' ?>" style="padding: 7px 14px; font-size: 0.85rem;">
            Pagas
        </a>
    </div>

    <a href="conta-cadastrar.php" class="btn btn-primary">
        + Cadastrar Nova Conta
    </a>
</div>

<!-- Lista de Contas -->
<?php if (empty($contas)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">📝</div>
        <h3 class="empty-state-title">Nenhuma conta encontrada</h3>
        <p class="empty-state-desc">
            <?php if ($filtroStatus !== 'todos'): ?>
                Não há contas com o status "<strong><?= e($filtroStatus) ?></strong>" para <?= format_month_name($currentMonth) ?>.
            <?php else: ?>
                Você ainda não cadastrou nenhuma conta para o mês de <?= format_month_name($currentMonth) ?>.
            <?php endif; ?>
        </p>
        <a href="conta-cadastrar.php" class="btn btn-primary">
            Adicionar Primeira Conta
        </a>
    </div>
<?php else: ?>
    <div class="accounts-list-wrapper">
        <?php foreach ($contas as $conta): 
            $meta = get_category_meta($conta['categoria']);
            $valor = (float)$conta['valor'];
            $limite = $conta['limite_gasto'] !== null ? (float)$conta['limite_gasto'] : null;
            $statusClass = 'status-pill-' . $conta['status'];
            $statusLabel = ucfirst($conta['status']);
        ?>
            <div class="account-card-item <?= e($meta['class']) ?>">
                <div class="account-primary-info">
                    <div class="category-icon-box" title="<?= e($conta['categoria']) ?>">
                        <?= $meta['icon'] ?>
                    </div>
                    <div class="account-title-group">
                        <div class="account-title" title="<?= e($conta['nome']) ?>">
                            <?= e($conta['nome']) ?>
                        </div>
                        <div class="account-meta-tags">
                            <span class="account-cat-pill"><?= e($conta['categoria']) ?></span>
                            <span>•</span>
                            <span>Vence dia <?= format_date($conta['vencimento'], 'd/m') ?></span>
                            <?php if (!empty($conta['observacao'])): ?>
                                <span>•</span>
                                <span title="<?= e($conta['observacao']) ?>">💬 Observação</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="account-value-group">
                    <div class="account-value"><?= format_currency($valor) ?></div>
                    <?php if ($limite !== null && $limite > 0): 
                        $pctLimite = ($valor / $limite) * 100;
                        $hintClass = $valor > $limite ? 'danger' : ($pctLimite >= 80 ? 'warning' : '');
                    ?>
                        <div class="account-limit-hint <?= $hintClass ?>">
                            Limite: <?= format_currency($limite) ?> (<?= round($pctLimite) ?>%)
                        </div>
                    <?php else: ?>
                        <div class="account-limit-hint">Sem limite individual</div>
                    <?php endif; ?>
                </div>

                <div style="display: flex; align-items: center; gap: 12px; flex-shrink: 0;">
                    <!-- Status Pill -->
                    <span class="account-status-pill <?= $statusClass ?>">
                        <?= $conta['status'] === 'paga' ? '✓' : ($conta['status'] === 'vencida' ? '✕' : '⏳') ?> 
                        <?= $statusLabel ?>
                    </span>

                    <!-- Ações -->
                    <div class="account-actions-group">
                        <!-- Alternador Rápido de Status (Pendente / Paga) -->
                        <form action="conta-status.php" method="POST" style="margin: 0;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int)$conta['id'] ?>">
                            <input type="hidden" name="novo_status" value="<?= $conta['status'] === 'paga' ? 'pendente' : 'paga' ?>">
                            <button type="submit" class="btn-icon" title="<?= $conta['status'] === 'paga' ? 'Marcar como Pendente' : 'Marcar como Paga' ?>">
                                <?= $conta['status'] === 'paga' ? '↩️' : '✅' ?>
                            </button>
                        </form>

                        <!-- Visualizar Detalhes -->
                        <a href="conta-detalhes.php?id=<?= (int)$conta['id'] ?>" class="btn-icon" title="Ver Detalhes">
                            👁️
                        </a>

                        <!-- Editar Conta -->
                        <a href="conta-editar.php?id=<?= (int)$conta['id'] ?>" class="btn-icon" title="Editar Conta">
                            ✏️
                        </a>

                        <!-- Excluir Conta -->
                        <form action="conta-excluir.php" method="POST" style="margin: 0;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int)$conta['id'] ?>">
                            <button type="submit" class="btn-icon btn-delete-confirm" data-item-name="<?= e($conta['nome']) ?>" title="Excluir Conta" style="color: var(--danger);">
                                🗑️
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include dirname(__DIR__) . '/views/footer.php'; ?>
