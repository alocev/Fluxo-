<?php
/**
 * FLUXO — Sistema de Gestão Doméstica
 * Página de Login e Autenticação Real
 */

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Redireciona se já estiver autenticado
require_guest();

$email = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (empty($email) || empty($senha)) {
        $error = 'Por favor, preencha todos os campos.';
    } else {
        try {
            $db = getDBConnection();
            $stmt = $db->prepare("SELECT id, nome, email, senha_hash FROM usuarios WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($senha, $user['senha_hash'])) {
                // Login com sucesso e proteção contra fixação de sessão
                login_user($user);

                $redirect = $_SESSION['intended_url'] ?? 'dashboard.php';
                unset($_SESSION['intended_url']);

                flash_set('success', "Que bom ter você de volta, " . explode(' ', $user['nome'])[0] . "!");
                header("Location: {$redirect}");
                exit;
            } else {
                // Mensagem genérica segura (não revela se o e-mail ou a senha está errada)
                $error = 'E-mail ou senha inválidos. Por favor, confira seus dados e tente novamente.';
            }
        } catch (PDOException $e) {
            error_log("Erro de login: " . $e->getMessage());
            $error = 'Ocorreu uma instabilidade momentânea. Por favor, tente novamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar — FLUXO</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-logo">🌿</div>
            <h1 class="auth-title">Entrar no FLUXO</h1>
            <p class="auth-subtitle">Acompanhe as contas e o consumo da sua casa.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="flash-banner flash-error" style="margin-bottom: 20px;">
                <span>✕ <?= e($error) ?></span>
            </div>
        <?php endif; ?>

        <?php
        $flashMsg = flash_get('error');
        if ($flashMsg): ?>
            <div class="flash-banner flash-error" style="margin-bottom: 20px;">
                <span>✕ <?= e($flashMsg) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" novalidate>
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="email">Seu E-mail</label>
                <input type="email" id="email" name="email" class="form-control" 
                       placeholder="seuemail@exemplo.com" 
                       value="<?= e($email) ?>" required autofocus>
            </div>

            <div class="form-group">
                <label for="senha">Sua Senha</label>
                <input type="password" id="senha" name="senha" class="form-control" 
                       placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 1rem; margin-top: 8px;">
                Entrar no Sistema
            </button>
        </form>

        <!-- Dica amigável para avaliação acadêmica / demonstração -->
        <div style="margin-top: 20px; padding: 12px; border-radius: 10px; background-color: var(--primary-surface); font-size: 0.8rem; color: var(--text-secondary); text-align: left; line-height: 1.4;">
            <strong style="color: var(--primary);">💡 Dica para Testes:</strong><br>
            Você pode acessar com o usuário demonstrativo:<br>
            <strong>E-mail:</strong> alice@fluxo.local | <strong>Senha:</strong> fluxo123<br>
            ou criar um novo usuário no link abaixo.
        </div>

        <div class="auth-footer">
            Ainda não tem uma conta? <a href="cadastro.php" style="font-weight: 700;">Cadastre-se grátis</a>
        </div>
    </div>
</body>
</html>
