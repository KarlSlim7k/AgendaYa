# Especificación de Índices - Optimización de Performance

**Versión**: 1.0  
**Fecha**: 01 de enero de 2026  
**Ambiente**: PostgreSQL 14+  
**Estado**: Aprobado para implementación en FASE 1-4

---

## Introducción

Este documento especifica todos los índices necesarios para la plataforma, agrupados por tabla y priorizados por criticidad. Los índices son fundamentales para:

1. Validación de disponibilidad (motor de slots)
2. Queries multi-tenant rápidas
3. Búsquedas de usuarios y negocios
4. Prevención de doble booking

**Criterio de incluir un índice**:
- Consulta critical (< 200ms requerido)
- Consulta ejecutada frecuentemente (> 100 veces/día)
- Tabla con > 100 registros
- Filtro usado en WHERE, JOIN, GROUP BY

---

## Índices por Tabla

### TABLA: `users`

**Descripción**: Tabla global de usuarios. Tamaño estimado: 100k - 1M registros en producción.

| # | Índice | Columnas | Tipo | Criticidad | Justificación |
|---|--------|----------|------|-----------|---|
| U1 | `idx_users_email` | `email` | UNIQUE | 🔴 Critical | Login/recuperación por email |
| U2 | `idx_users_email_verified` | `email_verified_at` | Regular | 🟡 Alta | Filtrar usuarios verificados |
| U3 | `idx_users_created` | `created_at DESC` | Regular | 🟢 Media | Reportes de crecimiento |

**SQL**:
```sql
CREATE UNIQUE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_email_verified ON users(email_verified_at) 
  WHERE email_verified_at IS NOT NULL;
CREATE INDEX idx_users_created ON users(created_at DESC);
```

---

### TABLA: `businesses`

**Descripción**: Negocios registrados. Tamaño estimado: 1k - 100k registros.

| # | Índice | Columnas | Tipo | Criticidad | Justificación |
|---|--------|----------|------|-----------|---|
| B1 | `idx_businesses_nombre` | `nombre` | Regular | 🔴 Critical | Búsqueda por nombre en app |
| B2 | `idx_businesses_estado` | `estado` | Regular | 🟡 Alta | Filtrar por aprobados/suspendidos |
| B3 | `idx_businesses_categoria` | `categoria` | Regular | 🟡 Alta | Filtrar por categoría en app |
| B4 | `idx_businesses_created` | `created_at DESC` | Regular | 🟢 Media | Dashboard admin |

**SQL**:
```sql
CREATE INDEX idx_businesses_nombre ON businesses(nombre);
CREATE INDEX idx_businesses_estado ON businesses(estado);
CREATE INDEX idx_businesses_categoria ON businesses(categoria);
CREATE INDEX idx_businesses_created ON businesses(created_at DESC);
```

---

### TABLA: `business_locations`

**Descripción**: Sucursales. Tamaño estimado: 5k - 50k registros (5-10 por negocio).

| # | Índice | Columnas | Tipo | Criticidad | Justificación |
|---|--------|----------|------|-----------|---|
| BL1 | `idx_locations_business` | `business_id` | Regular | 🔴 Critical | Listar sucursales de un negocio |
| BL2 | `idx_locations_business_id_nombre` | `(business_id, nombre)` | UNIQUE | 🟡 Alta | Evitar sucursales duplicadas |
| BL3 | `idx_locations_created` | `created_at DESC` | Regular | 🟢 Media | Ordenamiento |

**SQL**:
```sql
CREATE INDEX idx_locations_business ON business_locations(business_id);
CREATE UNIQUE INDEX idx_locations_business_nombre 
  ON business_locations(business_id, nombre);
CREATE INDEX idx_locations_created 
  ON business_locations(created_at DESC);
```

---

### TABLA: `services`

**Descripción**: Servicios ofrecidos. Tamaño estimado: 10k - 200k registros (10-50 por negocio).

| # | Índice | Columnas | Tipo | Criticidad | Justificación |
|---|--------|----------|------|-----------|---|
| S1 | `idx_services_business` | `business_id` | Regular | 🔴 Critical | Listar servicios de un negocio |
| S2 | `idx_services_business_nombre` | `(business_id, nombre)` | UNIQUE | 🟡 Alta | Evitar duplicados |
| S3 | `idx_services_created` | `created_at DESC` | Regular | 🟢 Media | Ordenamiento |

**SQL**:
```sql
CREATE INDEX idx_services_business ON services(business_id);
CREATE UNIQUE INDEX idx_services_business_nombre 
  ON services(business_id, nombre);
CREATE INDEX idx_services_created ON services(created_at DESC);
```

---

### TABLA: `employees`

**Descripción**: Empleados. Tamaño estimado: 50k - 500k registros (5-20 por negocio).

| # | Índice | Columnas | Tipo | Criticidad | Justificación |
|---|--------|----------|------|-----------|---|
| E1 | `idx_employees_business` | `business_id` | Regular | 🔴 Critical | Listar empleados de un negocio |
| E2 | `idx_employees_email` | `(business_id, email)` | UNIQUE | 🟡 Alta | Email único por negocio |
| E3 | `idx_employees_user_account` | `user_account_id` | Regular | 🟡 Alta | Relacionar con usuario sistema |
| E4 | `idx_employees_deleted` | `(business_id, deleted_at DESC)` | Regular | 🟢 Media | Filtrar soft-deleted |

**SQL**:
```sql
CREATE INDEX idx_employees_business ON employees(business_id);
CREATE UNIQUE INDEX idx_employees_business_email 
  ON employees(business_id, email);
CREATE INDEX idx_employees_user_account ON employees(user_account_id) 
  WHERE user_account_id IS NOT NULL;
CREATE INDEX idx_employees_deleted 
  ON employees(business_id, deleted_at DESC)
  WHERE deleted_at IS NULL;
```

---

### TABLA: `schedule_templates`

**Descripción**: Horarios base por día de semana. Tamaño estimado: 50k registros (7 por sucursal).

| # | Índice | Columnas | Tipo | Criticidad | Justificación |
|---|--------|----------|------|-----------|---|
| ST1 | `idx_schedule_templates_location` | `business_location_id` | Regular | 🟡 Alta | Obtener horarios de sucursal |

**SQL**:
```sql
CREATE INDEX idx_schedule_templates_location 
  ON schedule_templates(business_location_id);
```

---

### TABLA: `schedule_exceptions`

**Descripción**: Excepciones de horario (vacaciones, festivos). Tamaño estimado: 100k registros.

| # | Índice | Columnas | Tipo | Criticidad | Justificación |
|---|--------|----------|------|-----------|---|
| SE1 | `idx_schedule_exceptions_location_fecha` | `(business_location_id, fecha)` | Regular | 🔴 Critical | Validar disponibilidad por fecha |
| SE2 | `idx_schedule_exceptions_fecha` | `fecha` | Regular | 🟡 Alta | Reportes de excepciones |

**SQL**:
```sql
CREATE INDEX idx_schedule_exceptions_location_fecha 
  ON schedule_exceptions(business_location_id, fecha);
CREATE INDEX idx_schedule_exceptions_fecha ON schedule_exceptions(fecha);
```

---

### TABLA: `appointments` ⭐ CRÍTICA

**Descripción**: Citas. Tamaño estimado: 1M - 10M registros. **TABLA MÁS CRÍTICA PARA PERFORMANCE**.

| # | Índice | Columnas | Tipo | Criticidad | Justificación |
|---|--------|----------|------|-----------|---|
| A1 | `idx_appointments_business` | `business_id` | Regular | 🔴 Critical | Filtrar por tenant |
| A2 | `idx_appointments_employee_fecha` | `(employee_id, fecha_hora_inicio, estado)` | Composite | 🔴 Critical | Motor de disponibilidad |
| A3 | `idx_appointments_location_fecha` | `(business_location_id, fecha_hora_inicio)` | Composite | 🔴 Critical | Buscar citas por sucursal/fecha |
| A4 | `idx_appointments_user` | `user_id` | Regular | 🟡 Alta | Mis citas |
| A5 | `idx_appointments_estado` | `(business_id, estado)` | Composite | 🟡 Alta | Filtrar por estado |
| A6 | `idx_appointments_codigo_confirmacion` | `codigo_confirmacion` | UNIQUE | 🟢 Media | Check-in por código |
| A7 | `idx_appointments_soft_delete` | `(business_id, deleted_at DESC)` | Regular | 🟢 Media | Excluir canceladas |

**SQL**:
```sql
-- CRÍTICOS para motor de disponibilidad
CREATE INDEX idx_appointments_employee_fecha 
  ON appointments(employee_id, fecha_hora_inicio, estado)
  WHERE estado != 'cancelled';
  
CREATE INDEX idx_appointments_location_fecha 
  ON appointments(business_location_id, fecha_hora_inicio)
  WHERE estado != 'cancelled';

-- Multi-tenant
CREATE INDEX idx_appointments_business ON appointments(business_id);

-- Usuarios
CREATE INDEX idx_appointments_user ON appointments(user_id);

-- Estado
CREATE INDEX idx_appointments_estado 
  ON appointments(business_id, estado);

-- Código de confirmación
CREATE UNIQUE INDEX idx_appointments_codigo_confirmacion 
  ON appointments(codigo_confirmacion);

-- Soft deletes
CREATE INDEX idx_appointments_soft_delete 
  ON appointments(business_id, deleted_at DESC)
  WHERE deleted_at IS NULL;
```

**Notas**:
- Incluir `WHERE estado != 'cancelled'` en índices de disponibilidad (no contar citas canceladas)
- Índices compuestos en orden de selectividad: employee_id es más selectivo que fecha
- La clave para performance del motor

---

### TABLA: `appointment_status_histories`

**Descripción**: Historial de cambios de estado. Tamaño estimado: 2-3M registros.

| # | Índice | Columnas | Tipo | Criticidad | Justificación |
|---|--------|----------|------|-----------|---|
| ASH1 | `idx_appointment_histories_appointment` | `appointment_id` | Regular | 🟡 Alta | Ver historial de una cita |
| ASH2 | `idx_appointment_histories_fecha` | `fecha_cambio DESC` | Regular | 🟢 Media | Auditoría por fecha |

**SQL**:
```sql
CREATE INDEX idx_appointment_histories_appointment 
  ON appointment_status_histories(appointment_id);
CREATE INDEX idx_appointment_histories_fecha 
  ON appointment_status_histories(fecha_cambio DESC);
```

---

### TABLA: `employee_services` (Pivote)

**Descripción**: Relación N:M empleado-servicio. Tamaño estimado: 100k registros.

| # | Índice | Columnas | Tipo | Criticidad | Justificación |
|---|--------|----------|------|-----------|---|
| ES1 | `idx_employee_services_employee` | `employee_id` | Regular | 🟡 Alta | Servicios de un empleado |
| ES2 | `idx_employee_services_service` | `service_id` | Regular | 🟡 Alta | Empleados de un servicio |

**SQL**:
```sql
CREATE INDEX idx_employee_services_employee 
  ON employee_services(employee_id);
CREATE INDEX idx_employee_services_service 
  ON employee_services(service_id);
```

---

### TABLA: `business_user_roles` (RBAC)

**Descripción**: Asignación de roles a usuarios por negocio. Tamaño estimado: 10k - 100k registros.

| # | Índice | Columnas | Tipo | Criticidad | Justificación |
|---|--------|----------|------|-----------|---|
| BUR1 | `idx_business_user_roles_user` | `user_id` | Regular | 🟡 Alta | Roles de un usuario |
| BUR2 | `idx_business_user_roles_business` | `business_id` | Regular | 🟡 Alta | Usuarios de un negocio |
| BUR3 | `idx_business_user_roles_lookup` | `(user_id, business_id)` | Composite | 🔴 Critical | Verificar permisos en middleware |

**SQL**:
```sql
CREATE INDEX idx_business_user_roles_user 
  ON business_user_roles(user_id);
CREATE INDEX idx_business_user_roles_business 
  ON business_user_roles(business_id);
CREATE INDEX idx_business_user_roles_lookup 
  ON business_user_roles(user_id, business_id);
```

---

### TABLA: `role_permissions` (Pivote - RBAC)

**Descripción**: Asignación de permisos a roles. Tamaño estimado: 150 registros.

| # | Índice | Columnas | Tipo | Criticidad | Justificación |
|---|--------|----------|------|-----------|---|
| RP1 | `idx_role_permissions_role` | `role_id` | Regular | 🟢 Media | Permisos de un rol (pequeña tabla) |

**SQL**:
```sql
CREATE INDEX idx_role_permissions_role ON role_permissions(role_id);
```

---

### TABLA: `notification_logs` (Fase 5)

**Descripción**: Logging de notificaciones. Tamaño estimado: 100k - 1M registros.

| # | Índice | Columnas | Tipo | Criticidad | Justificación |
|---|--------|----------|------|-----------|---|
| NL1 | `idx_notification_logs_user` | `user_id` | Regular | 🟡 Alta | Historial de usuario |
| NL2 | `idx_notification_logs_appointment` | `appointment_id` | Regular | 🟡 Alta | Notificaciones de una cita |
| NL3 | `idx_notification_logs_estado_fecha` | `(estado, created_at DESC)` | Composite | 🟡 Alta | Reportes de envíos |
| NL4 | `idx_notification_logs_created` | `created_at DESC` | Regular | 🟢 Media | Auditoría reciente |

**SQL**:
```sql
CREATE INDEX idx_notification_logs_user ON notification_logs(user_id);
CREATE INDEX idx_notification_logs_appointment 
  ON notification_logs(appointment_id);
CREATE INDEX idx_notification_logs_estado_fecha 
  ON notification_logs(estado, created_at DESC);
CREATE INDEX idx_notification_logs_created 
  ON notification_logs(created_at DESC);
```

---

### TABLA: `resources` (Fase 2)

**Descripción**: Recursos compartidos. Tamaño estimado: 5k registros.

| # | Índice | Columnas | Tipo | Criticidad | Justificación |
|---|--------|----------|------|-----------|---|
| R1 | `idx_resources_business` | `business_id` | Regular | 🟡 Alta | Recursos de un negocio |
| R2 | `idx_resources_location` | `business_location_id` | Regular | 🟡 Alta | Recursos de una sucursal |

**SQL**:
```sql
CREATE INDEX idx_resources_business ON resources(business_id);
CREATE INDEX idx_resources_location ON resources(business_location_id);
```

---

### TABLA: `appointment_resources` (Pivote - Fase 2)

**Descripción**: Asignación de recursos a citas. Tamaño estimado: 2-3M registros.

| # | Índice | Columnas | Tipo | Criticidad | Justificación |
|---|--------|----------|------|-----------|---|
| AR1 | `idx_appointment_resources_appointment` | `appointment_id` | Regular | 🟡 Alta | Recursos de una cita |
| AR2 | `idx_appointment_resources_resource` | `resource_id` | Regular | 🟡 Alta | Citas de un recurso |

**SQL**:
```sql
CREATE INDEX idx_appointment_resources_appointment 
  ON appointment_resources(appointment_id);
CREATE INDEX idx_appointment_resources_resource 
  ON appointment_resources(resource_id);
```

---

## Índices JSON/Full-Text (Fase 2+)

Para búsquedas avanzadas y metadatos:

```sql
-- Búsqueda en campos JSON
CREATE INDEX idx_services_meta_gin ON services USING GIN(meta);
CREATE INDEX idx_appointments_custom_data_gin 
  ON appointments USING GIN(custom_data);

-- Full-text search en nombres/descripciones (futuro)
CREATE INDEX idx_services_search 
  ON services USING GIN(to_tsvector('spanish', nombre || ' ' || descripcion));
```

---

## Estrategia de Particionamiento (Futuro)

Cuando `appointments` supere 10M registros, particionar por:

```sql
-- Partitionamiento por business_id (después de Fase 5)
CREATE TABLE appointments_large (
    -- mismos campos
) PARTITION BY HASH (business_id) PARTITIONS 10;

-- O particionamiento por fecha si es muy grande
CREATE TABLE appointments_temporal (
    -- mismos campos
) PARTITION BY RANGE (fecha_hora_inicio);
```

---

## Resumen de Índices Críticos

**Prioridad 1 - Implementar en FASE 1**:
- `idx_users_email` (login)
- `idx_businesses_estado` (aprobación)
- `idx_locations_business` (sucursales)
- `idx_services_business` (servicios)
- `idx_employees_business` (empleados)

**Prioridad 2 - Implementar en FASE 3**:
- `idx_appointments_employee_fecha` (motor disponibilidad)
- `idx_appointments_location_fecha` (motor disponibilidad)
- `idx_business_user_roles_lookup` (RBAC middleware)

**Prioridad 3 - Implementar en FASE 4**:
- `idx_appointments_user` (mis citas)
- `idx_appointments_estado` (filtros)
- Resto de índices secundarios

---

## Performance Baseline Esperado

| Query | Sin índice | Con índice | Objetivo |
|-------|-----------|-----------|----------|
| Listar citas de empleado/día | 800ms | 50ms | < 200ms |
| Validar disponibilidad | 2000ms | 100ms | < 200ms |
| Buscar negocio por estado | 500ms | 20ms | < 100ms |
| Verificar permisos usuario | 300ms | 30ms | < 50ms |

---

## Mantenimiento de Índices

```sql
-- Monitoreo (ejecutar mensualmente)
SELECT 
    schemaname, tablename, indexname, idx_scan, idx_tup_read
FROM pg_stat_user_indexes
ORDER BY idx_scan DESC;

-- Reindex si es necesario
REINDEX INDEX idx_appointments_employee_fecha;

-- Analizar tablas para estadísticas
ANALYZE appointments;
```

---

## Referencias

- PostgreSQL Documentation: Index Types
- PostgreSQL Performance Tips
- Documento motor_disponibilidad_pseudocodigo.md (Sección "Performance y Optimizaciones")
