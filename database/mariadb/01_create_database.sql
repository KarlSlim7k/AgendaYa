-- =====================================================
-- CITAS EMPRESARIALES - MariaDB 10.6+
-- Script de Creación de Base de Datos
-- Versión: 1.0
-- Fecha: 13 de enero de 2026
-- =====================================================

-- Crear base de datos con charset adecuado para español
CREATE DATABASE IF NOT EXISTS citas_empresariales
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE citas_empresariales;

-- Verificar versión de MariaDB
SELECT VERSION() AS 'MariaDB Version';

-- =====================================================
-- NOTA: MariaDB es altamente compatible con MySQL 8.0
-- pero incluye algunas optimizaciones específicas:
-- - CHECK constraints son completamente soportados
-- - Mejor soporte para JSON con funciones nativas
-- - InnoDB como motor por defecto
-- =====================================================
