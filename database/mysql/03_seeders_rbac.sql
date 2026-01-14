-- =====================================================
-- CITAS EMPRESARIALES - MySQL 8.0+
-- Script de Seeders RBAC
-- Versión: 1.0
-- Fecha: 13 de enero de 2026
-- =====================================================

USE citas_empresariales;

-- =====================================================
-- INSERTAR ROLES DEL SISTEMA
-- =====================================================

INSERT INTO roles (name, description, guard_name) VALUES
('USUARIO_FINAL', 'Usuario final de la aplicación móvil. Solo puede gestionar sus propias citas y perfil.', 'web'),
('NEGOCIO_STAFF', 'Empleado/Proveedor del servicio. Ve su propia agenda y servicios asignados.', 'web'),
('NEGOCIO_MANAGER', 'Gerente de sucursal. Gestiona su sucursal asignada y empleados.', 'web'),
('NEGOCIO_ADMIN', 'Administrador del negocio. CRUD completo de todo el tenant.', 'web'),
('PLATAFORMA_ADMIN', 'Administrador de la plataforma. Acceso total sin restricciones.', 'web');

-- =====================================================
-- INSERTAR PERMISOS DEL SISTEMA (26 permisos)
-- =====================================================

INSERT INTO permissions (name, module, action, description, guard_name) VALUES
-- Módulo: Perfil
('perfil.create', 'perfil', 'create', 'Crear perfil propio', 'web'),
('perfil.read', 'perfil', 'read', 'Leer perfil', 'web'),
('perfil.update', 'perfil', 'update', 'Actualizar perfil', 'web'),

-- Módulo: Negocio
('negocio.create', 'negocio', 'create', 'Crear negocio', 'web'),
('negocio.read', 'negocio', 'read', 'Leer información del negocio', 'web'),
('negocio.update', 'negocio', 'update', 'Actualizar información del negocio', 'web'),
('negocio.delete', 'negocio', 'delete', 'Eliminar negocio', 'web'),

-- Módulo: Sucursal
('sucursal.create', 'sucursal', 'create', 'Crear sucursal', 'web'),
('sucursal.read', 'sucursal', 'read', 'Leer sucursal', 'web'),
('sucursal.update', 'sucursal', 'update', 'Actualizar sucursal', 'web'),
('sucursal.delete', 'sucursal', 'delete', 'Eliminar sucursal', 'web'),

-- Módulo: Servicio
('servicio.create', 'servicio', 'create', 'Crear servicio', 'web'),
('servicio.read', 'servicio', 'read', 'Leer servicio', 'web'),
('servicio.update', 'servicio', 'update', 'Actualizar servicio', 'web'),
('servicio.delete', 'servicio', 'delete', 'Eliminar servicio', 'web'),

-- Módulo: Empleado
('empleado.create', 'empleado', 'create', 'Crear empleado', 'web'),
('empleado.read', 'empleado', 'read', 'Leer empleado', 'web'),
('empleado.update', 'empleado', 'update', 'Actualizar empleado', 'web'),
('empleado.delete', 'empleado', 'delete', 'Eliminar empleado', 'web'),

-- Módulo: Agenda (Disponibilidad/Slots)
('agenda.create', 'agenda', 'create', 'Crear/reservar cita (slot)', 'web'),
('agenda.read', 'agenda', 'read', 'Ver disponibilidad', 'web'),

-- Módulo: Cita
('cita.create', 'cita', 'create', 'Crear cita', 'web'),
('cita.read', 'cita', 'read', 'Leer cita', 'web'),
('cita.update', 'cita', 'update', 'Actualizar cita', 'web'),
('cita.delete', 'cita', 'delete', 'Eliminar cita', 'web'),

-- Módulo: Reportes Financieros
('reportes_financieros.read', 'reportes_financieros', 'read', 'Leer reportes financieros', 'web');

-- =====================================================
-- ASIGNAR PERMISOS A ROLES
-- Basado en la Matriz de Permisos del documento arquitectónico
-- =====================================================

-- USUARIO_FINAL: Perfil propio + leer servicios/empleados públicos + crear/gestionar citas propias
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id 
FROM roles r, permissions p 
WHERE r.name = 'USUARIO_FINAL' 
AND p.name IN (
    'perfil.create', 'perfil.read', 'perfil.update',
    'servicio.read',
    'empleado.read',
    'agenda.read',
    'cita.create', 'cita.read', 'cita.update'
);

-- NEGOCIO_STAFF: Perfil propio + leer servicios asignados + ver/actualizar citas asignadas
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id 
FROM roles r, permissions p 
WHERE r.name = 'NEGOCIO_STAFF' 
AND p.name IN (
    'perfil.create', 'perfil.read', 'perfil.update',
    'servicio.read',
    'agenda.read',
    'cita.read', 'cita.update'
);

-- NEGOCIO_MANAGER: Perfil + leer negocio + gestión de sucursal + empleados + citas + reportes
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id 
FROM roles r, permissions p 
WHERE r.name = 'NEGOCIO_MANAGER' 
AND p.name IN (
    'perfil.create', 'perfil.read', 'perfil.update',
    'negocio.read',
    'sucursal.read', 'sucursal.update',
    'servicio.read',
    'empleado.create', 'empleado.read', 'empleado.update', 'empleado.delete',
    'agenda.read',
    'cita.read', 'cita.update',
    'reportes_financieros.read'
);

-- NEGOCIO_ADMIN: Acceso completo al tenant (excepto crear negocio)
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id 
FROM roles r, permissions p 
WHERE r.name = 'NEGOCIO_ADMIN' 
AND p.name IN (
    'perfil.create', 'perfil.read', 'perfil.update',
    'negocio.read', 'negocio.update', 'negocio.delete',
    'sucursal.create', 'sucursal.read', 'sucursal.update', 'sucursal.delete',
    'servicio.create', 'servicio.read', 'servicio.update', 'servicio.delete',
    'empleado.create', 'empleado.read', 'empleado.update', 'empleado.delete',
    'agenda.read',
    'cita.read', 'cita.update', 'cita.delete',
    'reportes_financieros.read'
);

-- PLATAFORMA_ADMIN: TODOS los permisos
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id 
FROM roles r, permissions p 
WHERE r.name = 'PLATAFORMA_ADMIN';

-- =====================================================
-- INSERTAR CONFIGURACIÓN INICIAL DE PLATAFORMA
-- =====================================================

INSERT INTO platform_settings (clave, valor, descripcion) VALUES
('app_name', '"Citas Empresariales"', 'Nombre de la aplicación'),
('timezone_default', '"America/Mexico_City"', 'Zona horaria por defecto'),
('currency', '"MXN"', 'Moneda por defecto'),
('currency_symbol', '"$"', 'Símbolo de moneda'),
('min_appointment_duration', '15', 'Duración mínima de cita en minutos'),
('max_appointment_duration', '480', 'Duración máxima de cita en minutos'),
('appointment_reminder_24h', 'true', 'Enviar recordatorio 24h antes'),
('appointment_reminder_1h', 'true', 'Enviar recordatorio 1h antes'),
('email_notifications_enabled', 'true', 'Habilitar notificaciones por email'),
('whatsapp_notifications_enabled', 'false', 'Habilitar notificaciones por WhatsApp (Fase 2)'),
('require_email_verification', 'true', 'Requerir verificación de email'),
('max_locations_basic', '1', 'Máximo de sucursales en plan básico'),
('max_locations_standard', '10', 'Máximo de sucursales en plan estándar'),
('max_locations_premium', '999', 'Máximo de sucursales en plan premium'),
('max_employees_basic', '5', 'Máximo de empleados en plan básico'),
('max_employees_standard', '50', 'Máximo de empleados en plan estándar'),
('max_employees_premium', '999', 'Máximo de empleados en plan premium');

-- =====================================================
-- VERIFICAR INSERCIÓN
-- =====================================================

SELECT 'Roles insertados:' AS info, COUNT(*) AS total FROM roles;
SELECT 'Permisos insertados:' AS info, COUNT(*) AS total FROM permissions;
SELECT 'Asignaciones rol-permiso:' AS info, COUNT(*) AS total FROM role_permissions;
SELECT 'Configuraciones:' AS info, COUNT(*) AS total FROM platform_settings;

-- Mostrar resumen de permisos por rol
SELECT 
    r.name AS rol,
    COUNT(rp.permission_id) AS total_permisos
FROM roles r
LEFT JOIN role_permissions rp ON r.id = rp.role_id
GROUP BY r.id, r.name
ORDER BY r.id;
