<?php
/**
 * FLUXO — Sistema de Gestão Doméstica
 * Página de Cadastro de Novo Usuário
 */

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Redireciona se já estiver autenticado
require_guest();

$nome = '';
$email = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $senhaConfirma = $_POST['senha_confirma'] ?? '';

    // Validações no backend
    if (empty($nome) || mb_strlen($nome) < 3) {
        $error = 'Por favor, informe seu nome completo (mínimo de 3 caracteres).';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Por favor, informe um endereço de e-mail válido.';
    } elseif (mb_strlen($senha) < 6) {
        $error = 'A senha deve conter no mínimo 6 caracteres.';
    } elseif ($senha !== $senhaConfirma) {
        $error = 'A confirmação de senha não confere com a senha digitada.';
    } else {
        try {
            $db = getDBConnection();

            // Verifica se o e-mail já existe
            $stmtCheck = $db->prepare("SELECT id FROM usuarios WHERE email = :email LIMIT 1");
            $stmtCheck->execute([':email' => $email]);
            if ($stmtCheck->fetch()) {
                $error = 'Este e-mail já está cadastrado no FLUXO. Tente fazer login.';
            } else {
                // Criação do hash seguro da senha
                $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

                $stmtInsert = $db->prepare("
                    INSERT INTO usuarios (nome, email, senha_hash, data_criacao)
                    VALUES (:nome, :email, :senha_hash, NOW())
                ");
                $stmtInsert->execute([
                    ':nome' => $nome,
                    ':email' => $email,
                    ':senha_hash' => $senhaHash
                ]);

                $userId = (int)$db->lastInsertId();

                // Cria automaticamente um orçamento inicial sugerido para o mês atual
                $mesAtual = date('Y-m');
                $stmtOrcamento = $db->prepare("
                    INSERT INTO orcamentos (usuario_id, mes_referencia, limite_mensal)
                    VALUES (:uid, :mes, 1200.00)
                ");
                $stmtOrcamento->execute([':uid' => $userId, ':mes' => $mesAtual]);

                // Faz login automático
                login_user([
                    'id' => $userId,
                    'nome' => $nome,
                    'email' => $email
                ]);

                flash_set('success', "Bem-vindo ao FLUXO, {$nome}! Sua conta foi criada com sucesso.");
                header('Location: dashboard.php');
                exit;
            }
        } catch (PDOException $e) {
            error_log("Erro no cadastro de usuário: " . $e->getMessage());
            $error = 'Não foi possível concluir seu cadastro no momento. Por favor, tente novamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Conta — FLUXO</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-logo">🌿</div>
            <h1 class="auth-title">Crie sua conta</h1>
            <p class="auth-subtitle">Organize as contas da sua casa com leveza e clareza.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="flash-banner flash-error" style="margin-bottom: 20px;">
                <span>✕ <?= e($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="cadastro.php" novalidate>
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="nome">Seu Nome Completo</label>
                <input type="text" id="nome" name="nome" class="form-control" 
                       placeholder="Ex: Alice Silva" 
                       value="<?= e($nome) ?>" required autofocus>
            </div>

            <div class="form-group">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" class="form-control" 
                       placeholder="seuemail@exemplo.com" 
                       value="<?= e($email) ?>" required>
            </div>

            <div class="form-group">
                <label for="senha">Senha de Acesso</label>
                <input type="password" id="senha" name="senha" class="form-control" 
                       placeholder="Mínimo 6 caracteres" required>
            </div>

            <div class="form-group">
                <label for="senha_confirma">Confirmação da Senha</label>
                <input type="password" id="senha_confirma" name="senha_confirma" class="form-control" 
                       placeholder="Repita sua senha" required>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 1rem; margin-top: 8px;">
                Começar agora no FLUXO
            </button>
        </form>

        <div class="auth-footer">
            Já possui uma conta? <a href="login.php" style="font-weight: 700;">Acesse aqui</a>
        </div>
    </div>
</body>
</html>
