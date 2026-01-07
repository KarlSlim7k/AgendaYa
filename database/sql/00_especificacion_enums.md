# Especificación de Tipos ENUM - PostgreSQL 14+

**Versión**: 1.0  
**Fecha**: 01 de enero de 2026  
**Ambiente**: Producción (PostgreSQL 14+)  
**Estado**: Aprobado para implementación

---

## Introducción

Este documento especifica todos los tipos enumerados (ENUM) que serán utilizados en la plataforma. Los ENUM types en PostgreSQL proporcionan validación a nivel de base de datos, mejorando integridad de datos y performance en comparación con VARCHAR con CHECK constraints.

---

## ENUM 1: Estatus de Cita

**Nombre**: `appointment_status`

**Descripción**: Define los estados posibles de una cita a lo largo de su ciclo de vida.

**Valores**:

| Valor | Descripción | Transiciones Permitidas |
|-------|-------------|------------------------|
| `pending` | Cita creada pero no confirmada | → confirmed, cancelled |
| `confirmed` | Cita confirmada y activa | → completed, cancelled, no_show |
| `completed` | Cita finalizada exitosamente | (terminal) |
| `cancelled` | Cita cancelada por usuario o negocio | (terminal) |
| `no_show` | Usuario no asistió a la cita | (terminal) |

**Justificación**: Estados discretos que representan el ciclo completo de una cita. Todas las transiciones se validan a nivel de API.

**SQL**:
```sql
CREATE TYPE appointment_status AS ENUM (
    'pending',      -- Cita pendiente de confirmación
    'confirmed',    -- Cita confirmada
    'completed',    -- Cita completada exitosamente
    'cancelled',    -- Cita cancelada
    'no_show'       -- Usuario no asistió
);
```

---

## ENUM 2: Tipo de Excepción de Horario

**Nombre**: `schedule_exception_type`

**Descripción**: Clasifica las excepciones al horario base de una sucursal.

**Valores**:

| Valor | Descripción | Impacto |
|-------|-------------|---------|
| `feriado` | Día feriado nacional | Sucursal cerrada todo el día |
| `vacaciones` | Período de vacaciones del negocio | Sucursal cerrada todo el período |
| `cierre` | Cierre temporal por mantenimiento/capacitación | Sucursal cerrada en horario especificado |

**Justificación**: Diferencia el tipo de excepción para reportes y filtros específicos.

**SQL**:
```sql
CREATE TYPE schedule_exception_type AS ENUM (
    'feriado',      -- Día feriado nacional
    'vacaciones',   -- Período de vacaciones
    'cierre'        -- Cierre temporal
);
```

---

## ENUM 3: Tipo de Recurso

**Nombre**: `resource_type`

**Descripción**: Clasifica los recursos compartidos que pueden ser asignados a citas (Fase 2).

**Valores**:

| Valor | Descripción | Ejemplo |
|-------|-------------|---------|
| `fisico` | Recurso tangible, ocupado durante cita | Sala, camilla, silla de corte |
| `virtual` | Recurso digital o virtual | Enlace Zoom, plataforma de video |

**Justificación**: Diferencia el tipo de recurso para validaciones específicas de capacidad y disponibilidad.

**SQL**:
```sql
CREATE TYPE resource_type AS ENUM (
    'fisico',       -- Recurso físico
    'virtual'       -- Recurso virtual
);
```

---

## ENUM 4: Estado de Negocio

**Nombre**: `business_status`

**Descripción**: Define el estado de registro y aprobación de un negocio en la plataforma.

**Valores**:

| Valor | Descripción | Restricciones |
|-------|-------------|---|
| `pending` | Solicitud pendiente de aprobación | No visible en app, no acepta citas |
| `approved` | Negocio aprobado y activo | Totalmente operacional |
| `suspended` | Negocio suspendido por incumplimiento | No acepta nuevas citas |
| `inactive` | Negocio desactivado voluntariamente | Datos archivados |

**Justificación**: Control granular del ciclo de vida del negocio en la plataforma.

**SQL**:
```sql
CREATE TYPE business_status AS ENUM (
    'pending',      -- Pendiente de aprobación
    'approved',     -- Aprobado y activo
    'suspended',    -- Suspendido
    'inactive'      -- Inactivo/Archivado
);
```

---

## ENUM 5: Plan de Suscripción

**Nombre**: `subscription_plan`

**Descripción**: Niveles de suscripción disponibles para negocios (implementado como VARCHAR en tabla para flexibilidad futura, pero tipado aquí).

**Valores**:

| Valor | Descripción | Límites |
|-------|-------------|---------|
| `basic` | Plan básico gratuito | 1 sucursal, 5 empleados, sin integraciones |
| `standard` | Plan estándar | Ilimitadas sucursales, 50 empleados, email |
| `premium` | Plan premium | Ilimitado, WhatsApp, API, reportes avanzados |

**Justificación**: Estructura de precios en diferentes niveles.

**SQL**:
```sql
CREATE TYPE subscription_plan AS ENUM (
    'basic',        -- Plan básico
    'standard',     -- Plan estándar
    'premium'       -- Plan premium
);
```

---

## ENUM 6: Tipo de Notificación

**Nombre**: `notification_type`

**Descripción**: Canales de notificación disponibles (Tabla: `notification_logs`).

**Valores**:

| Valor | Descripción | Proveedor |
|-------|-------------|-----------|
| `email` | Notificación por correo electrónico | SMTP nativo Laravel |
| `whatsapp` | Notificación por WhatsApp | Twilio/WhatsApp Business |
| `sms` | Notificación por SMS | Twilio |

**Justificación**: Múltiples canales de comunicación para máxima cobertura.

**SQL**:
```sql
CREATE TYPE notification_type AS ENUM (
    'email',        -- Correo electrónico
    'whatsapp',     -- WhatsApp (Fase 2)
    'sms'           -- SMS (Fase 2)
);
```

---

## ENUM 7: Estado de Notificación

**Nombre**: `notification_status`

**Descripción**: Estado actual de una notificación enviada (Tabla: `notification_logs`).

**Valores**:

| Valor | Descripción | Acción Siguiente |
|-------|-------------|------------------|
| `enviado` | Notificación enviada exitosamente | Completada |
| `fallido` | Falló el envío | Reintentar |
| `reintentando` | En proceso de reintento | Esperar |
| `descartado` | Rechazado después de múltiples intentos | Registrar |

**Justificación**: Seguimiento detallado de estado de notificaciones para debugging.

**SQL**:
```sql
CREATE TYPE notification_status AS ENUM (
    'enviado',      -- Enviado exitosamente
    'fallido',      -- Falló el envío
    'reintentando', -- En proceso de reintento
    'descartado'    -- Descartado después de intentos
);
```

---

## ENUM 8: Rol de Usuario (Conceptual)

**Nombre**: NO es ENUM (se almacena en tabla `roles`), pero se documenta para referencia.

**Descripción**: Los 5 roles del sistema RBAC.

**Valores**:

| Valor | Descripción | Contexto |
|-------|-------------|---------|
| `USUARIO_FINAL` | Consumidor de servicios | Global o por negocio |
| `NEGOCIO_STAFF` | Empleado/Proveedor | Por negocio específico |
| `NEGOCIO_MANAGER` | Gerente de sucursal | Por sucursal |
| `NEGOCIO_ADMIN` | Administrador del negocio | Por negocio completo |
| `PLATAFORMA_ADMIN` | Administrador de plataforma | Global |

**Nota**: Se almacena en tabla `roles` para permitir extensiones futuras sin migración de schema.

---

## Especificaciones Técnicas PostgreSQL

### Sintaxis de Creación

```sql
-- Crear todos los ENUM types
CREATE TYPE appointment_status AS ENUM (...);
CREATE TYPE schedule_exception_type AS ENUM (...);
CREATE TYPE resource_type AS ENUM (...);
CREATE TYPE business_status AS ENUM (...);
CREATE TYPE subscription_plan AS ENUM (...);
CREATE TYPE notification_type AS ENUM (...);
CREATE TYPE notification_status AS ENUM (...);
```

### Ventajas de Usar ENUM

1. **Validación a nivel BD**: Imposible insertar valores no válidos
2. **Storage eficiente**: 4 bytes por valor (vs VARCHAR)
3. **Performance**: Comparación más rápida que strings
4. **Documentación automática**: Schema auto-documenta valores válidos

### Limitaciones y Mitigaciones

| Limitación | Impacto | Mitigación |
|------------|---------|-----------|
| Agregar valores requiere ALTER TYPE | Downtime o complejidad | Planificar enumerados desde inicio |
| No se pueden remover valores directamente | Dificultad en refactor | Usar estrategia de deprecación |
| El orden importa para casting | Confusión en código | Documentar claramente |

---

## Uso en Tablas

### Ejemplo: Appointments
```sql
CREATE TABLE appointments (
    id BIGSERIAL PRIMARY KEY,
    estado appointment_status NOT NULL DEFAULT 'pending',
    -- ...
);

-- Validación automática en inserción
INSERT INTO appointments (estado) VALUES ('invalid'); 
-- ERROR: invalid input value for enum appointment_status: "invalid"
```

### Ejemplo: Schedule Exceptions
```sql
CREATE TABLE schedule_exceptions (
    id BIGSERIAL PRIMARY KEY,
    tipo schedule_exception_type NOT NULL,
    -- ...
);

-- Casteo desde string
UPDATE schedule_exceptions 
SET tipo = 'feriado'::schedule_exception_type 
WHERE id = 1;
```

---

## Migraciones Laravel

En migraciones Laravel, se usa el método `enum()` o se ejecuta SQL directo:

```php
// Opción 1: Directo SQL en migraciones
DB::statement("CREATE TYPE appointment_status AS ENUM ('pending', 'confirmed', 'completed', 'cancelled', 'no_show')");

// Opción 2: Usando macro personalizado (ver documentación FASE 1)
$table->enum('estado', ['pending', 'confirmed', 'completed', 'cancelled', 'no_show']);
```

---

## Changelog de ENUM Types

| Versión | Fecha | Cambio | Justificación |
|---------|-------|--------|---|
| 1.0 | 01/01/2026 | Creación inicial | Especificación base |

---

## Siguiente Paso

Los ENUM types serán incluidos en el script SQL consolidado de FASE 0, previo a la implementación en FASE 1.

---

## Referencias

- [PostgreSQL Enum Types Documentation](https://www.postgresql.org/docs/current/datatype-enum.html)
- [Laravel Enum Migrations](https://laravel.com/docs/migrations#creating-enums)
