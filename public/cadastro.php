<?php
/**
 * FLUXO — Sistema de Gestão Doméstica
 * Página de Cadastro de Novo Usuário por Nome de Usuário
 */

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Redireciona se já estiver autenticado
require_guest();

$nome = '';
$usuario = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $nome = trim($_POST['nome'] ?? '');
    $usuario = strtolower(trim($_POST['usuario'] ?? ''));
    $senha = $_POST['senha'] ?? '';
    $senhaConfirma = $_POST['senha_confirma'] ?? '';

    // Validações no backend
    if (empty($nome) || mb_strlen($nome) < 3) {
        $error = 'Por favor, informe seu nome completo (mínimo de 3 caracteres).';
    } elseif (empty($usuario) || !preg_match('/^[a-z0-9_.-]{3,30}$/', $usuario)) {
        $error = 'O nome de usuário deve conter entre 3 e 30 caracteres (letras, números, pontos ou sublinhados).';
    } elseif (mb_strlen($senha) < 6) {
        $error = 'A senha deve conter no mínimo 6 caracteres.';
    } elseif ($senha !== $senhaConfirma) {
        $error = 'A confirmação de senha não confere com a senha digitada.';
    } else {
        try {
            $db = getDBConnection();

            // Verifica se o nome de usuário já existe
            $stmtCheck = $db->prepare("SELECT id FROM usuarios WHERE usuario = :u LIMIT 1");
            $stmtCheck->execute([':u' => $usuario]);
            if ($stmtCheck->fetch()) {
                $error = 'Este nome de usuário já está cadastrado no FLUXO. Por favor, escolha outro.';
            } else {
                // Criação do hash seguro da senha
                $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

                $stmtInsert = $db->prepare("
                    INSERT INTO usuarios (nome, usuario, senha_hash, data_criacao)
                    VALUES (:nome, :usuario, :senha_hash, NOW())
                ");
                $stmtInsert->execute([
                    ':nome' => $nome,
                    ':usuario' => $usuario,
                    ':senha_hash' => $senhaHash
                ]);

                $userId = (int)$db->lastInsertId();

                // Novo usuário inicia com dados limpos (sem valores herdados de outros usuários)

                // Faz login automático seguro
                login_user([
                    'id'      => $userId,
                    'nome'    => $nome,
                    'usuario' => $usuario
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
    <title>Criar conta | FLUXO</title>

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
        <!-- Seletor de Tema Centralizado no Topo do Card -->
        <div class="auth-theme-bar">
            <div class="theme-switcher-pill" role="group" aria-label="Seletor de Aparência">
                <button type="button" class="theme-btn" data-theme-val="claro" title="Modo Claro">
                    <span class="theme-icon">☀️</span>
                    <span class="theme-text">Claro</span>
                </button>
                <button type="button" class="theme-btn" data-theme-val="verde" title="Modo Verde (Principal)">
                    <span class="theme-icon">🌿</span>
                    <span class="theme-text">Verde</span>
                </button>
                <button type="button" class="theme-btn" data-theme-val="escuro" title="Modo Escuro">
                    <span class="theme-icon">🌙</span>
                    <span class="theme-text">Escuro</span>
                </button>
            </div>
        </div>

        <div class="auth-header">
            <div class="auth-logo-wrap">
                <img src="../assets/img/logo-green.jpg" alt="FLUXO" class="auth-logo-img">
            </div>
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
                <label for="nome">Nome Completo</label>
                <input type="text" id="nome" name="nome" class="form-control" 
                       placeholder="Ex: Alice Silva" 
                       value="<?= e($nome) ?>" required autofocus>
            </div>

            <div class="form-group">
                <label for="usuario">Nome de Usuário</label>
                <input type="text" id="usuario" name="usuario" class="form-control" 
                       placeholder="Ex: alice" 
                       value="<?= e($usuario) ?>" required autocomplete="username">
                <span class="form-hint">Apenas letras, números, ponto ou traço (ex: alice, carlos.souza).</span>
            </div>

            <div class="form-group">
                <label for="senha">Senha</label>
                <div class="password-toggle-wrapper">
                    <input type="password" id="senha" name="senha" class="form-control" 
                           placeholder="Mínimo 6 caracteres" required autocomplete="new-password">
                    <button type="button" class="btn-toggle-password" data-target="senha" title="Mostrar senha" aria-label="Mostrar senha">
                        👁️
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label for="senha_confirma">Confirmar Senha</label>
                <div class="password-toggle-wrapper">
                    <input type="password" id="senha_confirma" name="senha_confirma" class="form-control" 
                           placeholder="Repita sua senha" required autocomplete="new-password">
                    <button type="button" class="btn-toggle-password" data-target="senha_confirma" title="Mostrar senha" aria-label="Mostrar senha">
                        👁️
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 1rem; margin-top: 8px;">
                Começar agora no FLUXO
            </button>
        </form>

        <div class="auth-footer">
            Já possui uma conta? <a href="login.php" style="font-weight: 700;">Acesse aqui</a>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>
