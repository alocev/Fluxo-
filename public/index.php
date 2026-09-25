<?php
/**
 * FLUXO — Sistema de Gestão Doméstica
 * Ponto de Entrada (Index)
 */

require_once dirname(__DIR__) . '/includes/auth.php';

if (is_authenticated()) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;
