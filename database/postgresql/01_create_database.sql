-- =====================================================
-- CITAS EMPRESARIALES - PostgreSQL 14+
-- Script de Creación de Base de Datos
-- Versión: 1.0
-- Fecha: 13 de enero de 2026
-- =====================================================

-- Crear base de datos con configuración para español
CREATE DATABASE citas_empresariales
    WITH 
    ENCODING = 'UTF8'
    LC_COLLATE = 'es_MX.UTF-8'
    LC_CTYPE = 'es_MX.UTF-8'
    TEMPLATE = template0;

-- Conectar a la base de datos
\c citas_empresariales;

-- Crear extensiones necesarias
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";  -- Para UUIDs
CREATE EXTENSION IF NOT EXISTS "pg_trgm";    -- Para búsquedas de texto similares
CREATE EXTENSION IF NOT EXISTS "btree_gin";  -- Para índices GIN en columnas escalares

-- Verificar versión de PostgreSQL
SELECT version();
