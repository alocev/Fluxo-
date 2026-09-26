<?php
/**
 * FLUXO — Sistema de Gestão Doméstica
 * Funções Auxiliares, Formatação e Lógica Compartilhada
 */

// Garante o alinhamento de fuso horário ao Brasil (Horário de Brasília)
date_default_timezone_set('America/Sao_Paulo');

if (!function_exists('e')) {
    function e(?string $value): string {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('format_currency')) {
    function format_currency($value): string {
        $num = (float)$value;
        return 'R$ ' . number_format($num, 2, ',', '.');
    }
}

if (!function_exists('format_number')) {
    function format_number($value, int $decimals = 2): string {
        $num = (float)$value;
        // Se for inteiro exato, pode omitir casas decimais se preferir, ou manter padrão brasileiro
        if (floor($num) == $num) {
            return number_format($num, 0, ',', '.');
        }
        return number_format($num, $decimals, ',', '.');
    }
}

if (!function_exists('format_date')) {
    function format_date(?string $date, string $format = 'd/m/Y'): string {
        if (!$date) return '-';
        $timestamp = strtotime($date);
        return $timestamp ? date($format, $timestamp) : $date;
    }
}

if (!function_exists('format_month_name')) {
    function format_month_name(string $yearMonth, bool $short = false): string {
        $meses = [
            '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março',
            '04' => 'Abril',   '05' => 'Maio',      '06' => 'Junho',
            '07' => 'Julho',   '08' => 'Agosto',    '09' => 'Setembro',
            '10' => 'Outubro', '11' => 'Novembro',  '12' => 'Dezembro'
        ];
        $mesesAbrev = [
            '01' => 'Jan', '02' => 'Fev', '03' => 'Mar',
            '04' => 'Abr', '05' => 'Mai', '06' => 'Jun',
            '07' => 'Jul', '08' => 'Ago', '09' => 'Set',
            '10' => 'Out', '11' => 'Nov', '12' => 'Dez'
        ];

        $parts = explode('-', $yearMonth);
        if (count($parts) === 2) {
            $ano = $parts[0];
            $mes = str_pad($parts[1], 2, '0', STR_PAD_LEFT);
            if ($short && isset($mesesAbrev[$mes])) {
                return $mesesAbrev[$mes] . '/' . $ano;
            }
            if (isset($meses[$mes])) {
                return $meses[$mes] . ' de ' . $ano;
            }
        }
        return $yearMonth;
    }
}

if (!function_exists('get_month_name_only')) {
    function get_month_name_only(string $yearMonth): string {
        $parts = explode('-', $yearMonth);
        if (count($parts) === 2) {
            $mes = str_pad($parts[1], 2, '0', STR_PAD_LEFT);
            $meses = [
                '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março',
                '04' => 'Abril',   '05' => 'Maio',      '06' => 'Junho',
                '07' => 'Julho',   '08' => 'Agosto',    '09' => 'Setembro',
                '10' => 'Outubro', '11' => 'Novembro',  '12' => 'Dezembro'
            ];
            return $meses[$mes] ?? $yearMonth;
        }
        return $yearMonth;
    }
}

if (!function_exists('get_previous_month')) {
    function get_previous_month(string $yearMonth): string {
        $date = DateTime::createFromFormat('Y-m-d', $yearMonth . '-01');
        if ($date) {
            $date->modify('-1 month');
            return $date->format('Y-m');
        }
        return date('Y-m', strtotime('-1 month'));
    }
}

if (!function_exists('get_greeting')) {
    function get_greeting(string $name): string {
        $hora = (int)date('H');
        $primeiroNome = explode(' ', trim($name))[0] ?? 'usuário';
        
        if ($hora >= 5 && $hora < 12) {
            $saudacao = 'Bom dia';
        } elseif ($hora >= 12 && $hora < 18) {
            $saudacao = 'Boa tarde';
        } else {
            $saudacao = 'Boa noite';
        }
        return "{$saudacao}, {$primeiroNome}!";
    }
}

if (!function_exists('get_category_meta')) {
    function get_category_meta(string $categoria): array {
        switch ($categoria) {
            case 'Água':
                return [
                    'icon' => '💧',
                    'class' => 'cat-agua',
                    'color' => '#5BA7D1',
                    'bg' => '#E5F3FA',
                    'label' => 'Água'
                ];
            case 'Energia':
                return [
                    'icon' => '⚡',
                    'class' => 'cat-energia',
                    'color' => '#D9A928',
                    'bg' => '#FFF5D9',
                    'label' => 'Energia'
                ];
            case 'Internet':
                return [
                    'icon' => '📱',
                    'class' => 'cat-internet',
                    'color' => '#7C83D1',
                    'bg' => '#EEEEFC',
                    'label' => 'Internet'
                ];
            case 'Aluguel':
                return [
                    'icon' => '🏠',
                    'class' => 'cat-aluguel',
                    'color' => '#C98568',
                    'bg' => '#FAEDE8',
                    'label' => 'Aluguel'
                ];
            case 'Streaming':
                return [
                    'icon' => '📺',
                    'class' => 'cat-streaming',
                    'color' => '#A66BB5',
                    'bg' => '#F5EAF7',
                    'label' => 'Streaming'
                ];
            default:
                return [
                    'icon' => '💰',
                    'class' => 'cat-outras',
                    'color' => '#82928A',
                    'bg' => '#EEF2F0',
                    'label' => 'Outras despesas'
                ];
        }
    }
}

if (!function_exists('calc_variation')) {
    /**
     * Calcula variação percentual: ((atual - anterior) / anterior) * 100
     * Trata divisão por zero e ausência de histórico
     */
    function calc_variation(float $atual, ?float $anterior): array {
        if ($anterior === null || $anterior <= 0) {
            if ($atual > 0 && ($anterior === 0.0 || $anterior === null)) {
                return [
                    'percent' => 100.0,
                    'formatted' => 'Novo registro',
                    'direction' => 'up',
                    'is_increase' => true,
                    'has_previous' => false
                ];
            }
            return [
                'percent' => 0.0,
                'formatted' => 'Sem histórico anterior',
                'direction' => 'equal',
                'is_increase' => false,
                'has_previous' => false
            ];
        }

        $diff = $atual - $anterior;
        $varPercent = ($diff / $anterior) * 100;
        $rounded = round(abs($varPercent));

        if (abs($varPercent) < 0.5) {
            return [
                'percent' => 0.0,
                'formatted' => 'Estável (0%)',
                'direction' => 'equal',
                'is_increase' => false,
                'has_previous' => true
            ];
        }

        if ($varPercent > 0) {
            return [
                'percent' => $varPercent,
                'formatted' => '↑ ' . $rounded . '%',
                'direction' => 'up',
                'is_increase' => true,
                'has_previous' => true
            ];
        } else {
            return [
                'percent' => $varPercent,
                'formatted' => '↓ ' . $rounded . '%',
                'direction' => 'down',
                'is_increase' => false,
                'has_previous' => true
            ];
        }
    }
}

if (!function_exists('flash_set')) {
    function flash_set(string $type, string $message): void {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['flash_' . $type] = $message;
    }
}

if (!function_exists('flash_get')) {
    function flash_get(string $type): ?string {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $key = 'flash_' . $type;
        if (!empty($_SESSION[$key])) {
            $msg = $_SESSION[$key];
            unset($_SESSION[$key]);
            return $msg;
        }
        return null;
    }
}

if (!function_exists('redirect')) {
    function redirect(string $url): void {
        header("Location: {$url}");
        exit;
    }
}
