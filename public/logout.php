<?php
/**
 * FLUXO — Sistema de Gestão Doméstica
 * Encerramento Seguro de Sessão (Logout)
 */

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Se for requisição POST, verifica CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
}

logout_user();

flash_set('info', 'Você saiu com segurança do FLUXO. Até breve!');
header('Location: login.php');
exit;
