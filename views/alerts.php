<?php
/**
 * FLUXO — Sistema de Gestão Doméstica
 * Exibição de Mensagens Flash (Sucesso, Erro, Alerta, Info)
 */

$flashSuccess = flash_get('success');
$flashError = flash_get('error');
$flashWarning = flash_get('warning');
$flashInfo = flash_get('info');
?>

<?php if ($flashSuccess): ?>
    <div class="flash-banner flash-success">
        <span>✓ <?= e($flashSuccess) ?></span>
        <button type="button" class="btn-close-flash" aria-label="Fechar">&times;</button>
    </div>
<?php endif; ?>

<?php if ($flashError): ?>
    <div class="flash-banner flash-error">
        <span>✕ <?= e($flashError) ?></span>
        <button type="button" class="btn-close-flash" aria-label="Fechar">&times;</button>
    </div>
<?php endif; ?>

<?php if ($flashWarning): ?>
    <div class="flash-banner flash-warning">
        <span>⚠️ <?= e($flashWarning) ?></span>
        <button type="button" class="btn-close-flash" aria-label="Fechar">&times;</button>
    </div>
<?php endif; ?>

<?php if ($flashInfo): ?>
    <div class="flash-banner flash-info">
        <span>ℹ️ <?= e($flashInfo) ?></span>
        <button type="button" class="btn-close-flash" aria-label="Fechar">&times;</button>
    </div>
<?php endif; ?>
