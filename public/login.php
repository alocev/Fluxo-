<?php
/**
 * FLUXO — Sistema de Gestão Doméstica
 * Página de Login e Autenticação Real por Nome de Usuário
 */

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Redireciona se já estiver autenticado
require_guest();

$usuario = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $usuario = trim($_POST['usuario'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (empty($usuario) || empty($senha)) {
        $error = 'Por favor, preencha todos os campos.';
    } else {
        try {
            $db = getDBConnection();
            $stmt = $db->prepare("SELECT id, nome, usuario, senha_hash FROM usuarios WHERE usuario = :u LIMIT 1");
            $stmt->execute([':u' => $usuario]);
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
                // Mensagem genérica segura (não revela se o usuário ou a senha está errada)
                $error = 'Usuário ou senha inválidos. Por favor, confira seus dados e tente novamente.';
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

    <!-- Favicon Oficial -->
    <link rel="icon" type="image/jpeg" href="../assets/img/logo-green.jpg">

    <!-- Inicialização Imediata do Tema -->
    <script>
    (function() {
        var t = localStorage.getItem('fluxo_theme') || 'verde';
        document.documentElement.setAttribute('data-theme', t);
    })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="auth-wrapper">
    <div class="auth-card">
        <!-- Barra Superior de Seleção de Tema -->
        <div class="auth-theme-bar">
            <div class="theme-switcher-pill" role="group" aria-label="Aparência">
                <button type="button" class="theme-btn" data-theme-val="claro" title="Modo Claro">
                    <span>☀️</span>
                </button>
                <button type="button" class="theme-btn" data-theme-val="verde" title="Modo Verde (Principal)">
                    <span>🌿</span>
                </button>
                <button type="button" class="theme-btn" data-theme-val="escuro" title="Modo Escuro">
                    <span>🌙</span>
                </button>
            </div>
        </div>

        <div class="auth-header">
            <div class="auth-logo-wrap">
                <img src="../assets/img/logo-light.png" alt="FLUXO" class="auth-logo-img logo-claro">
                <img src="../assets/img/logo-green.jpg" alt="FLUXO" class="auth-logo-img logo-verde">
                <img src="../assets/img/logo-green.jpg" alt="FLUXO" class="auth-logo-img logo-escuro">
            </div>
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
                <label for="usuario">Usuário</label>
                <input type="text" id="usuario" name="usuario" class="form-control" 
                       placeholder="Seu nome de usuário (ex: alice)" 
                       value="<?= e($usuario) ?>" required autofocus autocomplete="username">
            </div>

            <div class="form-group">
                <label for="senha">Senha</label>
                <div class="password-toggle-wrapper">
                    <input type="password" id="senha" name="senha" class="form-control" 
                           placeholder="••••••••" required autocomplete="current-password">
                    <button type="button" class="btn-toggle-password" data-target="senha" title="Mostrar senha" aria-label="Mostrar senha">
                        👁️
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 1rem; margin-top: 8px;">
                Entrar no Sistema
            </button>
        </form>

        <!-- Dica amigável para avaliação acadêmica / demonstração -->
        <div style="margin-top: 20px; padding: 14px; border-radius: 12px; background-color: var(--primary-surface); border: 1px solid var(--border-color); font-size: 0.85rem; color: var(--text-secondary); text-align: left; line-height: 1.5;">
            <strong style="color: var(--primary-dark); font-size: 0.9rem;">💡 Dica para testes:</strong><br>
            <strong>Usuário:</strong> alice<br>
            <strong>Senha:</strong> fluxo123
        </div>

        <div class="auth-footer">
            Ainda não tem uma conta? <a href="cadastro.php" style="font-weight: 700;">Cadastre-se grátis</a>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>
