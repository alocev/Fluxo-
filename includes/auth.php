<?php
/**
 * FLUXO — Sistema de Gestão Doméstica
 * Gerenciamento de Autenticação e Sessão de Usuário
 */

require_once __DIR__ . '/env.php';

if (!function_exists('init_session')) {
    function init_session(): void {
        if (session_status() === PHP_SESSION_NONE) {
            $lifetime = 60 * 60 * 24 * 7; // 7 dias
            
            $cookieParams = [
                'lifetime' => $lifetime,
                'path'     => '/',
                'domain'   => '',
                'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                'httponly' => true,
                'samesite' => 'Lax'
            ];
            
            session_set_cookie_params($cookieParams);
            session_start();
        }
    }
}

// Inicia sessão segura por padrão ao carregar
init_session();

if (!function_exists('is_authenticated')) {
    function is_authenticated(): bool {
        return !empty($_SESSION['user']) && !empty($_SESSION['user']['id']);
    }
}

if (!function_exists('current_user')) {
    function current_user(): ?array {
        return $_SESSION['user'] ?? null;
    }
}

if (!function_exists('current_user_id')) {
    function current_user_id(): ?int {
        return isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;
    }
}

if (!function_exists('login_user')) {
    function login_user(array $user): void {
        // Prevenção contra fixação de sessão
        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id'    => (int)$user['id'],
            'nome'  => $user['nome'],
            'email' => $user['email']
        ];
    }
}

if (!function_exists('logout_user')) {
    function logout_user(): void {
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();
    }
}

if (!function_exists('require_auth')) {
    function require_auth(string $redirectUrl = 'login.php'): void {
        if (!is_authenticated()) {
            $_SESSION['flash_error'] = 'Por favor, realize login para acessar esta página.';
            $target = $_SERVER['REQUEST_URI'] ?? '';
            if (!empty($target)) {
                $_SESSION['intended_url'] = $target;
            }
            header("Location: {$redirectUrl}");
            exit;
        }
    }
}

if (!function_exists('require_guest')) {
    function require_guest(string $redirectUrl = 'dashboard.php'): void {
        if (is_authenticated()) {
            header("Location: {$redirectUrl}");
            exit;
        }
    }
}
