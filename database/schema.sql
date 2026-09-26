-- ========================================================
-- SISTEMA FLUXO — Sistema de Gestão Doméstica
-- Script de Criação do Banco de Dados e Tabelas
-- ========================================================

CREATE DATABASE IF NOT EXISTS `fluxo_db` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `fluxo_db`;

-- 1. TABELA DE USUÁRIOS
CREATE TABLE IF NOT EXISTS `usuarios` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nome` VARCHAR(120) NOT NULL,
    `usuario` VARCHAR(60) NOT NULL UNIQUE,
    `senha_hash` VARCHAR(255) NOT NULL,
    `data_criacao` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. TABELA DE ORÇAMENTOS MENSAIS
CREATE TABLE IF NOT EXISTS `orcamentos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `usuario_id` INT NOT NULL,
    `mes_referencia` VARCHAR(7) NOT NULL COMMENT 'Formato YYYY-MM, ex: 2026-09',
    `limite_mensal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `data_criacao` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `data_atualizacao` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_usuario_mes` (`usuario_id`, `mes_referencia`),
    CONSTRAINT `fk_orcamentos_usuario` 
        FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. TABELA DE CONTAS E DESPESAS
CREATE TABLE IF NOT EXISTS `contas` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `usuario_id` INT NOT NULL,
    `categoria` ENUM('Água', 'Energia', 'Internet', 'Aluguel', 'Streaming', 'Outras despesas') NOT NULL,
    `nome` VARCHAR(150) NOT NULL,
    `valor` DECIMAL(10,2) NOT NULL,
    `vencimento` DATE NOT NULL,
    `limite_gasto` DECIMAL(10,2) NULL DEFAULT NULL COMMENT 'Limite individual opcional',
    `mes_referencia` VARCHAR(7) NOT NULL COMMENT 'Formato YYYY-MM',
    `status` ENUM('pendente', 'paga', 'vencida') NOT NULL DEFAULT 'pendente',
    `observacao` TEXT NULL,
    `data_pagamento` DATE NULL,
    `data_criacao` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `data_atualizacao` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_contas_user_mes` (`usuario_id`, `mes_referencia`),
    INDEX `idx_contas_user_status` (`usuario_id`, `status`),
    INDEX `idx_contas_user_vencimento` (`usuario_id`, `vencimento`),
    CONSTRAINT `fk_contas_usuario` 
        FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. TABELA DE CONSUMO (ÁGUA E ENERGIA)
CREATE TABLE IF NOT EXISTS `consumos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `usuario_id` INT NOT NULL,
    `tipo` ENUM('Água', 'Energia') NOT NULL,
    `mes_referencia` VARCHAR(7) NOT NULL COMMENT 'Formato YYYY-MM',
    `valor_consumo` DECIMAL(10,2) NOT NULL COMMENT 'Ex: 15500.00 para Litros ou 220.00 para kWh',
    `unidade` VARCHAR(20) NOT NULL COMMENT 'L, m³, kWh',
    `valor_fatura` DECIMAL(10,2) NULL DEFAULT NULL COMMENT 'Valor em R$ da fatura correspondente',
    `observacao` VARCHAR(255) NULL,
    `data_registro` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_usuario_tipo_mes` (`usuario_id`, `tipo`, `mes_referencia`),
    INDEX `idx_consumos_user_tipo` (`usuario_id`, `tipo`),
    CONSTRAINT `fk_consumos_usuario` 
        FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
