<?php
/**
 * FLUXO — Sistema de Gestão Doméstica
 * Cadastro de Nova Conta / Despesa
 */

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/functions.php';

require_auth();

$db = getDBConnection();
$userId = current_user_id();

$currentMonth = $_SESSION['selected_month'] ?? date('Y-m');

$categoriasValidas = ['Água', 'Energia', 'Internet', 'Aluguel', 'Streaming', 'Outras despesas'];

$categoria = $_GET['categoria'] ?? 'Outras despesas';
$nome = '';
$valor = '';
$vencimento = date('Y-m-d');
$mesReferencia = $currentMonth;
$limiteGasto = '';
$status = 'pendente';
$observacao = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $categoria = trim($_POST['categoria'] ?? '');
    $nome = trim($_POST['nome'] ?? '');
    $valorRaw = str_replace(',', '.', trim($_POST['valor'] ?? ''));
    $vencimento = trim($_POST['vencimento'] ?? '');
    $mesReferencia = trim($_POST['mes_referencia'] ?? '');
    $limiteRaw = str_replace(',', '.', trim($_POST['limite_gasto'] ?? ''));
    $status = trim($_POST['status'] ?? 'pendente');
    $observacao = trim($_POST['observacao'] ?? '');

    // Validações no backend
    if (!in_array($categoria, $categoriasValidas)) {
        $error = 'Por favor, selecione uma categoria válida.';
    } elseif (empty($nome) || mb_strlen($nome) < 2) {
        $error = 'Por favor, informe uma descrição/nome para a conta (mínimo 2 caracteres).';
    } elseif (!is_numeric($valorRaw) || (float)$valorRaw <= 0) {
        $error = 'Por favor, informe um valor monetário positivo válido.';
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $vencimento)) {
        $error = 'A data de vencimento informada é inválida.';
    } elseif (!preg_match('/^\d{4}-\d{2}$/', $mesReferencia)) {
        $error = 'O mês de referência informado é inválido.';
    } elseif (!in_array($status, ['pendente', 'paga'])) {
        $status = 'pendente';
    } else {
        $valor = (float)$valorRaw;
        $limiteGasto = ($limiteRaw !== '' && is_numeric($limiteRaw)) ? (float)$limiteRaw : null;
        $dataPagamento = $status === 'paga' ? date('Y-m-d') : null;

        // Se a data de vencimento for anterior a hoje e estiver pendente, ajusta status para vencida
        if ($status === 'pendente' && $vencimento < date('Y-m-d')) {
            $status = 'vencida';
        }

        try {
            $stmt = $db->prepare("
                INSERT INTO contas 
                (usuario_id, categoria, nome, valor, vencimento, limite_gasto, mes_referencia, status, observacao, data_pagamento, data_criacao)
                VALUES 
                (:uid, :categoria, :nome, :valor, :vencimento, :limite, :mes, :status, :obs, :data_pgto, NOW())
            ");
            $stmt->execute([
                ':uid' => $userId,
                ':categoria' => $categoria,
                ':nome' => $nome,
                ':valor' => $valor,
                ':vencimento' => $vencimento,
                ':limite' => $limiteGasto,
                ':mes' => $mesReferencia,
                ':status' => $status,
                ':obs' => !empty($observacao) ? $observacao : null,
                ':data_pgto' => $dataPagamento
            ]);

            // Se for conta de Água ou Energia, sincroniza opcionalmente o valor de fatura na tabela de consumos se houver
            if (in_array($categoria, ['Água', 'Energia'])) {
                $stmtCheckConsumo = $db->prepare("SELECT id FROM consumos WHERE usuario_id = :uid AND tipo = :tipo AND mes_referencia = :mes");
                $stmtCheckConsumo->execute([':uid' => $userId, ':tipo' => $categoria, ':mes' => $mesReferencia]);
                if ($consumoRow = $stmtCheckConsumo->fetch()) {
                    $stmtUpdateFat = $db->prepare("UPDATE consumos SET valor_fatura = :vf WHERE id = :cid");
                    $stmtUpdateFat->execute([':vf' => $valor, ':cid' => $consumoRow['id']]);
                }
            }

            flash_set('success', "A conta \"{$nome}\" foi cadastrada com sucesso!");
            header("Location: contas.php?mes={$mesReferencia}");
            exit;
        } catch (PDOException $e) {
            error_log("Erro ao cadastrar conta: " . $e->getMessage());
            $error = 'Não foi possível salvar a conta. Por favor, tente novamente.';
        }
    }
}

$pageTitle = 'Adicionar Conta — FLUXO';
$pageHeading = 'Nova Conta da Casa';
$activePage = 'contas';

include dirname(__DIR__) . '/views/header.php';
?>

<div style="max-width: 680px; margin: 0 auto;">
    <div style="margin-bottom: 20px;">
        <a href="contas.php" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.85rem;">
            ← Voltar para a lista
        </a>
    </div>

    <div class="form-card">
        <h2 style="font-size: 1.3rem; font-weight: 800; margin-bottom: 6px; color: var(--text-primary);">
            Cadastrar Nova Despesa
        </h2>
        <p style="font-size: 0.88rem; color: var(--text-secondary); margin-bottom: 24px;">
            Preencha os detalhes da conta para acompanhar seus vencimentos e orçamento.
        </p>

        <?php if (!empty($error)): ?>
            <div class="flash-banner flash-error" style="margin-bottom: 20px;">
                <span>✕ <?= e($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="conta-cadastrar.php">
            <?= csrf_field() ?>

            <div class="form-row">
                <div class="form-group">
                    <label for="categoria">Categoria da Despesa *</label>
                    <select name="categoria" id="categoria" class="form-control" required>
                        <?php foreach ($categoriasValidas as $cat): ?>
                            <option value="<?= e($cat) ?>" <?= $categoria === $cat ? 'selected' : '' ?>>
                                <?= get_category_meta($cat)['icon'] ?> <?= e($cat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="mes_referencia">Mês de Referência *</label>
                    <input type="month" name="mes_referencia" id="mes_referencia" class="form-control" 
                           value="<?= e($mesReferencia) ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="nome">Nome / Identificação da Conta *</label>
                <input type="text" name="nome" id="nome" class="form-control" 
                       placeholder="Ex: Conta de Luz (Enel), Fibra Óptica 500MB, Aluguel" 
                       value="<?= e($nome) ?>" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="contaValor">Valor da Conta (R$) *</label>
                    <input type="text" name="valor" id="contaValor" class="form-control" 
                           placeholder="0,00" 
                           value="<?= e($valor) ?>" required>
                    <span class="form-hint">Ex: 142,50 ou 212.00</span>
                </div>

                <div class="form-group">
                    <label for="vencimento">Data de Vencimento *</label>
                    <input type="date" name="vencimento" id="vencimento" class="form-control" 
                           value="<?= e($vencimento) ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="contaLimite">Limite Individual Opcional (R$)</label>
                    <input type="text" name="limite_gasto" id="contaLimite" class="form-control" 
                           placeholder="Ex: 250,00" 
                           value="<?= e($limiteGasto) ?>">
                    <span class="form-hint">O FLUXO avisará se a conta se aproximar deste teto.</span>
                    <div id="contaLimitPreview" style="display: none; margin-top: 6px; font-size: 0.8rem;"></div>
                </div>

                <div class="form-group">
                    <label for="status">Situação Inicial</label>
                    <select name="status" id="status" class="form-control">
                        <option value="pendente" <?= $status === 'pendente' ? 'selected' : '' ?>>⏳ Pendente de pagamento</option>
                        <option value="paga" <?= $status === 'paga' ? 'selected' : '' ?>>✓ Já está paga</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="observacao">Observações (Opcional)</label>
                <textarea name="observacao" id="observacao" rows="3" class="form-control" 
                          placeholder="Ex: Pagamento programado no débito, código de barras, etc."><?= e($observacao) ?></textarea>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px;">
                <a href="contas.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary" style="padding-left: 24px; padding-right: 24px;">
                    Salvar Conta
                </button>
            </div>
        </form>
    </div>
</div>

<?php include dirname(__DIR__) . '/views/footer.php'; ?>
