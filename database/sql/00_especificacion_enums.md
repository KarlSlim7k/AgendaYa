# Especificación de Tipos ENUM - MariaDB 11.4.9+

**Versión**: 2.0  
**Fecha**: 16 de enero de 2026  
**Ambiente**: Producción (MariaDB 11.4.9+)  
**Estado**: Aprobado para implementación

---

## Introducción

Este documento especifica todos los tipos enumerados (ENUM) que serán utilizados en la plataforma. Los ENUM types en MariaDB se definen inline directamente en las columnas, proporcionando validación a nivel de base de datos y mejorando la integridad de datos.

**Cambio importante**: A diferencia de PostgreSQL que usa `CREATE TYPE`, en MariaDB los ENUMs se definen directamente en la definición de columna usando la sintaxis `ENUM('valor1', 'valor2', ...)`.

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

**SQL (MariaDB)**:
```sql
-- Definición inline en columna de tabla appointments
CREATE TABLE appointments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    -- ... otros campos
    estado ENUM('pending', 'confirmed', 'completed', 'cancelled', 'no_show') 
        NOT NULL DEFAULT 'pending' 
        COMMENT 'Estado actual de la cita',
    -- ... otros campos
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Laravel Migration**:
```php
$table->enum('estado', ['pending', 'confirmed', 'completed', 'cancelled', 'no_show'])
      ->default('pending')
      ->comment('Estado actual de la cita');
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

**SQL (MariaDB)**:
```sql
-- Definición inline en columna de tabla schedule_exceptions
CREATE TABLE schedule_exceptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    -- ... otros campos
    tipo ENUM('feriado', 'vacaciones', 'cierre') 
        NOT NULL 
        COMMENT 'Tipo de excepción de horario',
    -- ... otros campos
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Laravel Migration**:
```php
$table->enum('tipo', ['feriado', 'vacaciones', 'cierre'])
      ->comment('Tipo de excepción de horario');
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

**SQL (MariaDB)**:
```sql
-- Definición inline en columna de tabla resources
CREATE TABLE resources (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    -- ... otros campos
    tipo ENUM('fisico', 'virtual') 
        NOT NULL 
        COMMENT 'Tipo de recurso (físico o virtual)',
    -- ... otros campos
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Laravel Migration**:
```php
$table->enum('tipo', ['fisico', 'virtual'])
      ->comment('Tipo de recurso (físico o virtual)');
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

**SQL (MariaDB)**:
```sql
-- Definición inline en columna de tabla businesses
CREATE TABLE businesses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    -- ... otros campos
    estado ENUM('pending', 'approved', 'suspended', 'inactive') 
        NOT NULL DEFAULT 'pending' 
        COMMENT 'Estado del negocio en la plataforma',
    -- ... otros campos
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Laravel Migration**:
```php
$table->enum('estado', ['pending', 'approved', 'suspended', 'inactive'])
      ->default('pending')
      ->comment('Estado del negocio en la plataforma');
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

**Nota**: Este campo se implementa como VARCHAR(50) en lugar de ENUM para mayor flexibilidad en adición de nuevos planes sin alterar estructura. Los valores listados son los iniciales.

**SQL (MariaDB)**:
```sql
-- Implementado como VARCHAR con valores sugeridos
CREATE TABLE businesses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    -- ... otros campos
    plan VARCHAR(50) NOT NULL DEFAULT 'basic' 
        COMMENT 'Plan de suscripción: basic, standard, premium',
    -- ... otros campos
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Laravel Migration**:
```php
$table->string('plan', 50)->default('basic')
      ->comment('Plan de suscripción: basic, standard, premium');
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

**SQL (MariaDB)**:
```sql
-- Definición inline en columna de tabla notification_logs
CREATE TABLE notification_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    -- ... otros campos
    tipo ENUM('email', 'whatsapp', 'sms') 
        NOT NULL 
        COMMENT 'Canal de notificación utilizado',
    -- ... otros campos
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Laravel Migration**:
```php
$table->enum('tipo', ['email', 'whatsapp', 'sms'])
      ->comment('Canal de notificación utilizado');
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

**SQL (MariaDB)**:
```sql
-- Definición inline en columna de tabla notification_logs
CREATE TABLE notification_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    -- ... otros campos
    estado ENUM('enviado', 'fallido', 'reintentando', 'descartado') 
        NOT NULL 
        COMMENT 'Estado actual de la notificación',
    -- ... otros campos
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Laravel Migration**:
```php
$table->enum('estado', ['enviado', 'fallido', 'reintentando', 'descartado'])
      ->comment('Estado actual de la notificación');
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

## Especificaciones Técnicas MariaDB

### Sintaxis de Creación

```sql
-- En MariaDB, los ENUMs se definen inline en cada columna
-- No hay necesidad de CREATE TYPE previo

-- Ejemplo en tabla appointments
CREATE TABLE appointments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    estado ENUM('pending', 'confirmed', 'completed', 'cancelled', 'no_show') 
        NOT NULL DEFAULT 'pending',
    -- ... otros campos
);

-- Ejemplo en tabla schedule_exceptions
CREATE TABLE schedule_exceptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('feriado', 'vacaciones', 'cierre') NOT NULL,
    -- ... otros campos
);
```

### Ventajas de Usar ENUM en MariaDB

1. **Validación a nivel BD**: Imposible insertar valores no válidos
2. **Storage eficiente**: 1-2 bytes por valor (dependiendo del número de opciones)
3. **Performance**: Comparación más rápida que strings
4. **Simplicidad**: Definición inline, no requiere tipos separados
5. **Documentación automática**: Schema auto-documenta valores válidos

### Limitaciones y Mitigaciones

| Limitación | Impacto | Mitigación |
|------------|---------|-----------|
| Agregar valores requiere ALTER TABLE | Operación que puede bloquear tabla | Usar migrations en horarios de bajo tráfico |
| El orden de valores importa para índices | Puede afectar performance | Planificar valores desde inicio |
| Remover valores requiere recrear columna | Downtime potencial | Usar estrategia de deprecación + datos históricos |
| Máximo 65,535 elementos | Limitación teórica | No aplica (nuestros ENUMs tienen 3-5 valores) |

### Comparación: ENUM vs VARCHAR

| Aspecto | ENUM | VARCHAR(50) |
|---------|------|-------------|
| Storage | 1-2 bytes | 50+ bytes |
| Validación | Automática en BD | Requiere validación en app |
| Modificación | Requiere ALTER TABLE | Solo cambio en validación app |
| Performance | Excelente (índices numéricos) | Buena (índices string) |
| Flexibilidad | Baja | Alta |

**Decisión para CitasEmpresariales**: Usar ENUM para estados fijos con 3-7 valores que raramente cambiarán (estados de cita, tipos de excepción). Usar VARCHAR para campos que pueden expandirse frecuentemente (planes de suscripción).

---

## Uso en Tablas

### Ejemplo: Appointments con MariaDB
```sql
CREATE TABLE appointments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    employee_id BIGINT UNSIGNED,
    location_id BIGINT UNSIGNED NOT NULL,
    service_id BIGINT UNSIGNED NOT NULL,
    fecha_hora_inicio DATETIME NOT NULL,
    fecha_hora_fin DATETIME NOT NULL,
    estado ENUM('pending', 'confirmed', 'completed', 'cancelled', 'no_show') 
        NOT NULL DEFAULT 'pending'
        COMMENT 'Estado actual de la cita',
    codigo_confirmacion VARCHAR(20) UNIQUE NOT NULL,
    custom_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (business_id) REFERENCES businesses(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (employee_id) REFERENCES employees(id),
    FOREIGN KEY (location_id) REFERENCES business_locations(id),
    FOREIGN KEY (service_id) REFERENCES services(id),
    CHECK (fecha_hora_fin > fecha_hora_inicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Validación automática en inserción
INSERT INTO appointments (estado) VALUES ('invalid'); 
-- ERROR: Data truncated for column 'estado' at row 1
```

### Ejemplo: Schedule Exceptions con MariaDB
```sql
CREATE TABLE schedule_exceptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_location_id BIGINT UNSIGNED NOT NULL,
    fecha DATE NOT NULL,
    tipo ENUM('feriado', 'vacaciones', 'cierre') 
        NOT NULL
        COMMENT 'Tipo de excepción de horario',
    todo_el_dia BOOLEAN NOT NULL DEFAULT TRUE,
    hora_inicio TIME,
    hora_fin TIME,
    descripcion VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (business_location_id) REFERENCES business_locations(id) ON DELETE CASCADE,
    INDEX idx_location_fecha (business_location_id, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Actualización con valor ENUM
UPDATE schedule_exceptions 
SET tipo = 'feriado' 
WHERE id = 1;
```

---

## Migraciones Laravel (Schema Builder)

En migraciones Laravel para MariaDB, se usa el método `enum()` nativo del Schema Builder:

```php
// database/migrations/xxxx_create_appointments_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id(); // BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
            $table->foreignId('business_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('employee_id')->nullable()->constrained();
            $table->foreignId('location_id')->constrained('business_locations');
            $table->foreignId('service_id')->constrained();
            $table->dateTime('fecha_hora_inicio');
            $table->dateTime('fecha_hora_fin');
            
            // ENUM inline
            $table->enum('estado', ['pending', 'confirmed', 'completed', 'cancelled', 'no_show'])
                  ->default('pending')
                  ->comment('Estado actual de la cita');
            
            $table->string('codigo_confirmacion', 20)->unique();
            $table->json('custom_data')->nullable();
            $table->timestamps(); // created_at, updated_at
            $table->softDeletes(); // deleted_at
            
            // Índices
            $table->index(['business_id', 'employee_id', 'fecha_hora_inicio', 'estado']);
            $table->index(['employee_id', 'fecha_hora_inicio']);
        });
        
        // CHECK constraint (MariaDB 10.2.1+)
        DB::statement('ALTER TABLE appointments ADD CONSTRAINT chk_fecha_fin_mayor 
                       CHECK (fecha_hora_fin > fecha_hora_inicio)');
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
```

### Ejemplo: Schedule Exceptions
```php
Schema::create('schedule_exceptions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('business_location_id')->constrained()->onDelete('cascade');
    $table->date('fecha');
    
    $table->enum('tipo', ['feriado', 'vacaciones', 'cierre'])
          ->comment('Tipo de excepción de horario');
    
    $table->boolean('todo_el_dia')->default(true);
    $table->time('hora_inicio')->nullable();
    $table->time('hora_fin')->nullable();
    $table->string('descripcion')->nullable();
    $table->timestamps();
    
    $table->index(['business_location_id', 'fecha']);
});
```
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
