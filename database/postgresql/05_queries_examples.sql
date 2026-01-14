-- =====================================================
-- CITAS EMPRESARIALES - PostgreSQL 14+
-- Queries de Validación y Ejemplos
-- Versión: 1.0
-- Fecha: 13 de enero de 2026
-- =====================================================

-- =====================================================
-- QUERIES DE VALIDACIÓN RBAC
-- =====================================================

-- -----------------------------------------------------
-- 1. Verificar si usuario tiene permiso específico en un negocio
-- -----------------------------------------------------
-- Parámetros: $1 = user_id, $2 = business_id, $3 = permission_name

-- Query preparada para verificación de permisos
PREPARE check_permission(BIGINT, BIGINT, VARCHAR) AS
SELECT EXISTS (
    SELECT 1 
    FROM business_user_roles bur
    JOIN role_permissions rp ON bur.role_id = rp.role_id
    JOIN permissions p ON rp.permission_id = p.id
    WHERE bur.user_id = $1 
      AND (bur.business_id = $2 OR bur.business_id IS NULL)
      AND p.name = $3
) AS has_permission;

-- Ejemplo de uso:
-- EXECUTE check_permission(1, 5, 'servicio.update');

-- -----------------------------------------------------
-- 2. Obtener todos los permisos de un usuario en un negocio
-- -----------------------------------------------------
PREPARE get_user_permissions(BIGINT, BIGINT) AS
SELECT DISTINCT
    p.name AS permission_name,
    p.module,
    p.action,
    r.name AS role_name
FROM business_user_roles bur
JOIN roles r ON bur.role_id = r.id
JOIN role_permissions rp ON r.id = rp.role_id
JOIN permissions p ON rp.permission_id = p.id
WHERE bur.user_id = $1 
  AND (bur.business_id = $2 OR bur.business_id IS NULL)
ORDER BY p.module, p.action;

-- Ejemplo de uso:
-- EXECUTE get_user_permissions(1, 5);

-- -----------------------------------------------------
-- 3. Listar usuarios con rol específico en un negocio
-- -----------------------------------------------------
PREPARE get_users_by_role(BIGINT, VARCHAR) AS
SELECT 
    u.id AS user_id,
    u.nombre,
    u.email,
    r.name AS role_name,
    bur.created_at AS assigned_at
FROM business_user_roles bur
JOIN users u ON bur.user_id = u.id
JOIN roles r ON bur.role_id = r.id
WHERE bur.business_id = $1 
  AND r.name = $2
ORDER BY bur.created_at DESC;

-- Ejemplo de uso:
-- EXECUTE get_users_by_role(5, 'NEGOCIO_ADMIN');

-- -----------------------------------------------------
-- 4. Verificar si usuario puede acceder a una sucursal específica
-- -----------------------------------------------------
PREPARE check_location_access(BIGINT, BIGINT, BIGINT) AS
SELECT EXISTS (
    SELECT 1 
    FROM business_user_roles bur
    JOIN roles r ON bur.role_id = r.id
    WHERE bur.user_id = $1 
      AND bur.business_id = $2
      AND (
          -- NEGOCIO_ADMIN o PLATAFORMA_ADMIN tienen acceso a todas las sucursales
          r.name IN ('NEGOCIO_ADMIN', 'PLATAFORMA_ADMIN')
          -- NEGOCIO_MANAGER solo a su sucursal asignada
          OR (r.name = 'NEGOCIO_MANAGER' AND bur.location_id = $3)
          -- NEGOCIO_STAFF también puede tener sucursal asignada
          OR (r.name = 'NEGOCIO_STAFF' AND bur.location_id = $3)
      )
) AS has_access;

-- Ejemplo de uso:
-- EXECUTE check_location_access(1, 5, 10);

-- -----------------------------------------------------
-- 5. Obtener todos los roles de un usuario en todos los negocios
-- -----------------------------------------------------
PREPARE get_user_all_roles(BIGINT) AS
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
WHERE bur.user_id = $1
ORDER BY b.nombre, r.name;

-- Ejemplo de uso:
-- EXECUTE get_user_all_roles(1);

-- =====================================================
-- QUERIES DEL MOTOR DE DISPONIBILIDAD
-- =====================================================

-- -----------------------------------------------------
-- 6. Obtener slots ocupados de un empleado en un rango de fechas
-- -----------------------------------------------------
PREPARE get_employee_appointments(BIGINT, BIGINT, TIMESTAMP, TIMESTAMP) AS
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
WHERE a.business_id = $1
  AND a.employee_id = $2
  AND a.fecha_hora_inicio >= $3
  AND a.fecha_hora_inicio < $4
  AND a.estado NOT IN ('cancelled')
  AND a.deleted_at IS NULL
ORDER BY a.fecha_hora_inicio;

-- Ejemplo de uso:
-- EXECUTE get_employee_appointments(1, 5, '2026-01-15', '2026-01-16');

-- -----------------------------------------------------
-- 7. Verificar si un slot está disponible (para prevención de doble booking)
-- -----------------------------------------------------
PREPARE check_slot_availability(BIGINT, TIMESTAMP, TIMESTAMP) AS
SELECT NOT EXISTS (
    SELECT 1 
    FROM appointments a
    WHERE a.employee_id = $1
      AND a.estado NOT IN ('cancelled')
      AND a.deleted_at IS NULL
      AND (
          -- El nuevo slot se solapa con una cita existente
          ($2 < a.fecha_hora_fin AND $3 > a.fecha_hora_inicio)
      )
) AS is_available;

-- Ejemplo de uso:
-- EXECUTE check_slot_availability(5, '2026-01-15 10:00:00', '2026-01-15 11:00:00');

-- -----------------------------------------------------
-- 8. Obtener horarios de una sucursal para un día específico
-- -----------------------------------------------------
PREPARE get_schedule_for_day(BIGINT, INT) AS
SELECT 
    st.dia_semana,
    st.hora_apertura,
    st.hora_cierre,
    st.activo
FROM schedule_templates st
WHERE st.business_location_id = $1
  AND st.dia_semana = $2;

-- Ejemplo de uso (0=Domingo, 1=Lunes, etc.):
-- EXECUTE get_schedule_for_day(10, 1);

-- -----------------------------------------------------
-- 9. Verificar excepciones de horario para una fecha
-- -----------------------------------------------------
PREPARE check_schedule_exceptions(BIGINT, DATE) AS
SELECT 
    se.tipo,
    se.todo_el_dia,
    se.hora_inicio,
    se.hora_fin,
    se.motivo
FROM schedule_exceptions se
WHERE se.business_location_id = $1
  AND se.fecha = $2;

-- Ejemplo de uso:
-- EXECUTE check_schedule_exceptions(10, '2026-01-15');

-- =====================================================
-- QUERIES DE REPORTES
-- =====================================================

-- -----------------------------------------------------
-- 10. Dashboard de citas por negocio (métricas del día)
-- -----------------------------------------------------
PREPARE get_business_daily_metrics(BIGINT, DATE) AS
SELECT 
    COUNT(*) FILTER (WHERE estado = 'pending') AS citas_pendientes,
    COUNT(*) FILTER (WHERE estado = 'confirmed') AS citas_confirmadas,
    COUNT(*) FILTER (WHERE estado = 'completed') AS citas_completadas,
    COUNT(*) FILTER (WHERE estado = 'cancelled') AS citas_canceladas,
    COUNT(*) FILTER (WHERE estado = 'no_show') AS no_shows,
    COALESCE(SUM(precio_final) FILTER (WHERE estado = 'completed'), 0) AS ingresos_dia
FROM appointments
WHERE business_id = $1
  AND DATE(fecha_hora_inicio) = $2
  AND deleted_at IS NULL;

-- Ejemplo de uso:
-- EXECUTE get_business_daily_metrics(1, '2026-01-15');

-- -----------------------------------------------------
-- 11. Top servicios más solicitados (último mes)
-- -----------------------------------------------------
PREPARE get_top_services(BIGINT, INT) AS
SELECT 
    s.id,
    s.nombre,
    COUNT(a.id) AS total_reservas,
    COALESCE(SUM(a.precio_final), 0) AS ingresos_total
FROM services s
LEFT JOIN appointments a ON s.id = a.service_id 
    AND a.fecha_hora_inicio >= CURRENT_DATE - INTERVAL '30 days'
    AND a.estado = 'completed'
    AND a.deleted_at IS NULL
WHERE s.business_id = $1
  AND s.deleted_at IS NULL
GROUP BY s.id, s.nombre
ORDER BY total_reservas DESC
LIMIT $2;

-- Ejemplo de uso:
-- EXECUTE get_top_services(1, 5);

-- -----------------------------------------------------
-- 12. Empleados con más citas (último mes)
-- -----------------------------------------------------
PREPARE get_top_employees(BIGINT, INT) AS
SELECT 
    e.id,
    e.nombre,
    COUNT(a.id) AS total_citas,
    COUNT(*) FILTER (WHERE a.estado = 'completed') AS completadas,
    COUNT(*) FILTER (WHERE a.estado = 'no_show') AS no_shows
FROM employees e
LEFT JOIN appointments a ON e.id = a.employee_id 
    AND a.fecha_hora_inicio >= CURRENT_DATE - INTERVAL '30 days'
    AND a.deleted_at IS NULL
WHERE e.business_id = $1
  AND e.deleted_at IS NULL
GROUP BY e.id, e.nombre
ORDER BY total_citas DESC
LIMIT $2;

-- Ejemplo de uso:
-- EXECUTE get_top_employees(1, 5);

-- =====================================================
-- QUERIES MULTI-TENANT SEGURAS
-- =====================================================

-- -----------------------------------------------------
-- 13. Listar citas de un usuario en un negocio específico
-- (Respeta multi-tenancy)
-- -----------------------------------------------------
PREPARE get_user_appointments_in_business(BIGINT, BIGINT) AS
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
WHERE a.user_id = $1
  AND a.business_id = $2
  AND a.deleted_at IS NULL
ORDER BY a.fecha_hora_inicio DESC;

-- Ejemplo de uso:
-- EXECUTE get_user_appointments_in_business(1, 5);

-- -----------------------------------------------------
-- 14. Buscar negocios con filtros (para app usuario final)
-- -----------------------------------------------------
PREPARE search_businesses(VARCHAR, VARCHAR, INT) AS
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
      $1 IS NULL 
      OR b.nombre ILIKE '%' || $1 || '%'
  )
  AND (
      $2 IS NULL 
      OR b.categoria = $2
  )
ORDER BY total_citas DESC
LIMIT $3;

-- Ejemplo de uso:
-- EXECUTE search_businesses('peluqueria', NULL, 10);

-- =====================================================
-- LIMPIEZA DE QUERIES PREPARADAS (opcional)
-- =====================================================
-- DEALLOCATE ALL;
