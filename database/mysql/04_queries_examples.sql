-- =====================================================
-- CITAS EMPRESARIALES - MySQL 8.0+
-- Queries de Validación y Ejemplos
-- Versión: 1.0
-- Fecha: 13 de enero de 2026
-- =====================================================

USE citas_empresariales;

-- =====================================================
-- QUERIES DE VALIDACIÓN RBAC
-- =====================================================

-- -----------------------------------------------------
-- 1. Verificar si usuario tiene permiso específico en un negocio
-- -----------------------------------------------------
-- Ejemplo: ¿Usuario 1 tiene permiso 'servicio.update' en negocio 5?
SELECT EXISTS (
    SELECT 1 
    FROM business_user_roles bur
    JOIN role_permissions rp ON bur.role_id = rp.role_id
    JOIN permissions p ON rp.permission_id = p.id
    WHERE bur.user_id = 1 
      AND (bur.business_id = 5 OR bur.business_id IS NULL)
      AND p.name = 'servicio.update'
) AS has_permission;

-- -----------------------------------------------------
-- 2. Obtener todos los permisos de un usuario en un negocio
-- -----------------------------------------------------
SELECT DISTINCT
    p.name AS permission_name,
    p.module,
    p.action,
    r.name AS role_name
FROM business_user_roles bur
JOIN roles r ON bur.role_id = r.id
JOIN role_permissions rp ON r.id = rp.role_id
JOIN permissions p ON rp.permission_id = p.id
WHERE bur.user_id = 1 
  AND (bur.business_id = 5 OR bur.business_id IS NULL)
ORDER BY p.module, p.action;

-- -----------------------------------------------------
-- 3. Listar usuarios con rol específico en un negocio
-- -----------------------------------------------------
SELECT 
    u.id AS user_id,
    u.nombre,
    u.email,
    r.name AS role_name,
    bur.created_at AS assigned_at
FROM business_user_roles bur
JOIN users u ON bur.user_id = u.id
JOIN roles r ON bur.role_id = r.id
WHERE bur.business_id = 5 
  AND r.name = 'NEGOCIO_ADMIN'
ORDER BY bur.created_at DESC;

-- -----------------------------------------------------
-- 4. Verificar si usuario puede acceder a una sucursal específica
-- -----------------------------------------------------
SELECT EXISTS (
    SELECT 1 
    FROM business_user_roles bur
    JOIN roles r ON bur.role_id = r.id
    WHERE bur.user_id = 1 
      AND bur.business_id = 5
      AND (
          r.name IN ('NEGOCIO_ADMIN', 'PLATAFORMA_ADMIN')
          OR (r.name = 'NEGOCIO_MANAGER' AND bur.location_id = 10)
          OR (r.name = 'NEGOCIO_STAFF' AND bur.location_id = 10)
      )
) AS has_access;

-- -----------------------------------------------------
-- 5. Obtener todos los roles de un usuario en todos los negocios
-- -----------------------------------------------------
SELECT 
    b.id AS business_id,
    b.nombre AS business_name,
    r.name AS role_name,
    bl.nombre AS location_name,
    bur.created_at AS assigned_at
FROM business_user_roles bur
JOIN roles r ON bur.role_id = r.id
LEFT JOIN businesses b ON bur.business_id = b.id
LEFT JOIN business_locations bl ON bur.location_id = bl.id
WHERE bur.user_id = 1
ORDER BY b.nombre, r.name;

-- =====================================================
-- QUERIES DEL MOTOR DE DISPONIBILIDAD
-- =====================================================

-- -----------------------------------------------------
-- 6. Obtener slots ocupados de un empleado en un rango de fechas
-- -----------------------------------------------------
SELECT 
    a.id,
    a.fecha_hora_inicio,
    a.fecha_hora_fin,
    a.estado,
    s.nombre AS servicio,
    s.buffer_pre_minutos,
    s.buffer_post_minutos
FROM appointments a
JOIN services s ON a.service_id = s.id
WHERE a.business_id = 1
  AND a.employee_id = 5
  AND a.fecha_hora_inicio >= '2026-01-15 00:00:00'
  AND a.fecha_hora_inicio < '2026-01-16 00:00:00'
  AND a.estado != 'cancelled'
  AND a.deleted_at IS NULL
ORDER BY a.fecha_hora_inicio;

-- -----------------------------------------------------
-- 7. Verificar si un slot está disponible (para prevención de doble booking)
-- -----------------------------------------------------
SELECT NOT EXISTS (
    SELECT 1 
    FROM appointments a
    WHERE a.employee_id = 5
      AND a.estado != 'cancelled'
      AND a.deleted_at IS NULL
      AND (
          '2026-01-15 10:00:00' < a.fecha_hora_fin 
          AND '2026-01-15 11:00:00' > a.fecha_hora_inicio
      )
) AS is_available;

-- -----------------------------------------------------
-- 8. Obtener horarios de una sucursal para un día específico
-- -----------------------------------------------------
SELECT 
    st.dia_semana,
    st.hora_apertura,
    st.hora_cierre,
    st.activo
FROM schedule_templates st
WHERE st.business_location_id = 10
  AND st.dia_semana = 1;  -- 0=Domingo, 1=Lunes

-- -----------------------------------------------------
-- 9. Verificar excepciones de horario para una fecha
-- -----------------------------------------------------
SELECT 
    se.tipo,
    se.todo_el_dia,
    se.hora_inicio,
    se.hora_fin,
    se.motivo
FROM schedule_exceptions se
WHERE se.business_location_id = 10
  AND se.fecha = '2026-01-15';

-- =====================================================
-- QUERIES DE REPORTES
-- =====================================================

-- -----------------------------------------------------
-- 10. Dashboard de citas por negocio (métricas del día)
-- -----------------------------------------------------
SELECT 
    SUM(CASE WHEN estado = 'pending' THEN 1 ELSE 0 END) AS citas_pendientes,
    SUM(CASE WHEN estado = 'confirmed' THEN 1 ELSE 0 END) AS citas_confirmadas,
    SUM(CASE WHEN estado = 'completed' THEN 1 ELSE 0 END) AS citas_completadas,
    SUM(CASE WHEN estado = 'cancelled' THEN 1 ELSE 0 END) AS citas_canceladas,
    SUM(CASE WHEN estado = 'no_show' THEN 1 ELSE 0 END) AS no_shows,
    COALESCE(SUM(CASE WHEN estado = 'completed' THEN precio_final ELSE 0 END), 0) AS ingresos_dia
FROM appointments
WHERE business_id = 1
  AND DATE(fecha_hora_inicio) = '2026-01-15'
  AND deleted_at IS NULL;

-- -----------------------------------------------------
-- 11. Top servicios más solicitados (último mes)
-- -----------------------------------------------------
SELECT 
    s.id,
    s.nombre,
    COUNT(a.id) AS total_reservas,
    COALESCE(SUM(a.precio_final), 0) AS ingresos_total
FROM services s
LEFT JOIN appointments a ON s.id = a.service_id 
    AND a.fecha_hora_inicio >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
    AND a.estado = 'completed'
    AND a.deleted_at IS NULL
WHERE s.business_id = 1
  AND s.deleted_at IS NULL
GROUP BY s.id, s.nombre
ORDER BY total_reservas DESC
LIMIT 5;

-- -----------------------------------------------------
-- 12. Empleados con más citas (último mes)
-- -----------------------------------------------------
SELECT 
    e.id,
    e.nombre,
    COUNT(a.id) AS total_citas,
    SUM(CASE WHEN a.estado = 'completed' THEN 1 ELSE 0 END) AS completadas,
    SUM(CASE WHEN a.estado = 'no_show' THEN 1 ELSE 0 END) AS no_shows
FROM employees e
LEFT JOIN appointments a ON e.id = a.employee_id 
    AND a.fecha_hora_inicio >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
    AND a.deleted_at IS NULL
WHERE e.business_id = 1
  AND e.deleted_at IS NULL
GROUP BY e.id, e.nombre
ORDER BY total_citas DESC
LIMIT 5;

-- =====================================================
-- QUERIES MULTI-TENANT SEGURAS
-- =====================================================

-- -----------------------------------------------------
-- 13. Listar citas de un usuario en un negocio específico
-- -----------------------------------------------------
SELECT 
    a.id,
    a.fecha_hora_inicio,
    a.fecha_hora_fin,
    a.estado,
    a.codigo_confirmacion,
    s.nombre AS servicio,
    s.precio,
    e.nombre AS empleado,
    bl.nombre AS sucursal
FROM appointments a
JOIN services s ON a.service_id = s.id
LEFT JOIN employees e ON a.employee_id = e.id
JOIN business_locations bl ON a.business_location_id = bl.id
WHERE a.user_id = 1
  AND a.business_id = 5
  AND a.deleted_at IS NULL
ORDER BY a.fecha_hora_inicio DESC;

-- -----------------------------------------------------
-- 14. Buscar negocios con filtros (para app usuario final)
-- -----------------------------------------------------
SELECT 
    b.id,
    b.nombre,
    b.logo_url,
    b.categoria,
    (
        SELECT COUNT(*) 
        FROM appointments a 
        WHERE a.business_id = b.id AND a.estado = 'completed'
    ) AS total_citas
FROM businesses b
WHERE b.estado = 'approved'
  AND b.deleted_at IS NULL
  AND (
      b.nombre LIKE '%peluqueria%'
      OR b.categoria = 'peluqueria'
  )
ORDER BY total_citas DESC
LIMIT 10;

-- =====================================================
-- QUERIES DE AUDITORÍA
-- =====================================================

-- -----------------------------------------------------
-- 15. Historial de cambios de estado de una cita
-- -----------------------------------------------------
SELECT 
    ash.id,
    ash.estado_anterior,
    ash.estado_nuevo,
    u.nombre AS cambiado_por,
    ash.motivo,
    ash.fecha_cambio
FROM appointment_status_histories ash
LEFT JOIN users u ON ash.cambiado_por = u.id
WHERE ash.appointment_id = 100
ORDER BY ash.fecha_cambio DESC;

-- -----------------------------------------------------
-- 16. Notificaciones enviadas para una cita
-- -----------------------------------------------------
SELECT 
    nl.id,
    nl.tipo,
    nl.evento,
    nl.estado,
    nl.destinatario,
    nl.intentos,
    nl.created_at
FROM notification_logs nl
WHERE nl.appointment_id = 100
ORDER BY nl.created_at DESC;
