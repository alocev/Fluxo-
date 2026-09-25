<?php
/**
 * FLUXO — Sistema de Gestão Doméstica
 * Conexão com o Banco de Dados MySQL via PDO
 */

require_once dirname(__DIR__) . '/includes/env.php';

if (!function_exists('getDBConnection')) {
    function getDBConnection(): PDO {
        static $pdo = null;

        if ($pdo !== null) {
            return $pdo;
        }

        $host = env('DB_HOST', '127.0.0.1');
        $port = env('DB_PORT', '3306');
        $dbname = env('DB_NAME', 'fluxo_db');
        $user = env('DB_USER', 'root');
        $pass = env('DB_PASS', '');

        $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            // Conecta inicialmente ao servidor MySQL
            $tempPdo = new PDO($dsn, $user, $pass, $options);
            
            // Garante que o banco de dados exista
            $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
            $tempPdo->exec("USE `{$dbname}`;");

            // Verifica se a tabela principal 'usuarios' existe; se não, executa o schema.sql
            $checkTable = $tempPdo->query("SHOW TABLES LIKE 'usuarios'")->fetch();
            if (!$checkTable) {
                $schemaFile = dirname(__DIR__) . '/database/schema.sql';
                if (file_exists($schemaFile)) {
                    $sql = file_get_contents($schemaFile);
                    $tempPdo->exec($sql);
                }
            }

            // Define a conexão definitiva com o banco selecionado
            $pdo = new PDO("mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4", $user, $pass, $options);
            return $pdo;
        } catch (PDOException $e) {
            error_log("Erro de Conexão com o Banco de Dados FLUXO: " . $e->getMessage());

            if (env('APP_DEBUG', true)) {
                die("Erro ao conectar com o banco de dados: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
            } else {
                die("Desculpe, ocorreu uma instabilidade ao conectar ao banco de dados. Por favor, tente novamente em alguns instantes.");
            }
        }
    }
}
