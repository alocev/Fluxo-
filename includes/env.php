<?php
/**
 * FLUXO — Sistema de Gestão Doméstica
 * Carregador seguro de variáveis de ambiente (.env)
 */

if (!function_exists('loadEnv')) {
    function loadEnv(?string $filePath = null): void {
        if ($filePath === null) {
            $filePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
        }

        if (!file_exists($filePath)) {
            $examplePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env.example';
            if (file_exists($examplePath)) {
                $filePath = $examplePath;
            } else {
                return;
            }
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            // Ignorar comentários
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            // Separar Chave e Valor
            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $key = trim($parts[0]);
                $val = trim($parts[1]);

                // Remover aspas se existirem
                if (
                    (str_starts_with($val, '"') && str_ends_with($val, '"')) ||
                    (str_starts_with($val, "'") && str_ends_with($val, "'"))
                ) {
                    $val = substr($val, 1, -1);
                }

                if (!array_key_exists($key, $_ENV)) {
                    $_ENV[$key] = $val;
                    putenv("{$key}={$val}");
                }
            }
        }
    }
}

// Carregar variáveis automaticamente
loadEnv();

if (!function_exists('env')) {
    function env(string $key, $default = null) {
        $val = $_ENV[$key] ?? getenv($key);
        if ($val === false || $val === null) {
            return $default;
        }
        if (strtolower($val) === 'true') return true;
        if (strtolower($val) === 'false') return false;
        if (strtolower($val) === 'null') return null;
        return $val;
    }
}

// Configuração segura de tratamento de erros — impede vazamento de caminhos locais e dados sensíveis na interface
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL & ~E_DEPRECATED);
