-- ============================================
-- Script de inicialización MariaDB
-- SOLO DESARROLLO LOCAL
-- ============================================
-- Crea usuarios y permisos adicionales para testing
-- ============================================

USE citas_empresariales;

-- Usuario de solo lectura para análisis
CREATE USER IF NOT EXISTS 'readonly_user'@'%' IDENTIFIED BY 'readonly_pass';
GRANT SELECT ON citas_empresariales.* TO 'readonly_user'@'%';

FLUSH PRIVILEGES;

-- Mensaje de confirmación
SELECT 'Base de datos inicializada correctamente para desarrollo local' AS mensaje;
