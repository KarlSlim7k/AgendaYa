-- =====================================================
-- CITAS EMPRESARIALES - PostgreSQL 14+
-- Script de Creación de ENUM Types
-- Versión: 1.0
-- Fecha: 13 de enero de 2026
-- =====================================================

-- =====================================================
-- ENUM 1: Estados de Cita
-- =====================================================
DO $$ BEGIN
    CREATE TYPE appointment_status AS ENUM (
        'pending',      -- Cita pendiente de confirmación
        'confirmed',    -- Cita confirmada
        'completed',    -- Cita completada exitosamente
        'cancelled',    -- Cita cancelada
        'no_show'       -- Usuario no asistió
    );
EXCEPTION
    WHEN duplicate_object THEN null;
END $$;

COMMENT ON TYPE appointment_status IS 'Estados posibles de una cita en su ciclo de vida';

-- =====================================================
-- ENUM 2: Tipo de Excepción de Horario
-- =====================================================
DO $$ BEGIN
    CREATE TYPE schedule_exception_type AS ENUM (
        'feriado',      -- Día feriado nacional
        'vacaciones',   -- Período de vacaciones
        'cierre'        -- Cierre temporal
    );
EXCEPTION
    WHEN duplicate_object THEN null;
END $$;

COMMENT ON TYPE schedule_exception_type IS 'Tipos de excepciones al horario base de una sucursal';

-- =====================================================
-- ENUM 3: Tipo de Recurso (Fase 2)
-- =====================================================
DO $$ BEGIN
    CREATE TYPE resource_type AS ENUM (
        'fisico',       -- Recurso físico (sala, camilla, etc.)
        'virtual'       -- Recurso virtual (enlace Zoom, etc.)
    );
EXCEPTION
    WHEN duplicate_object THEN null;
END $$;

COMMENT ON TYPE resource_type IS 'Clasificación de recursos compartidos';

-- =====================================================
-- ENUM 4: Estado de Negocio
-- =====================================================
DO $$ BEGIN
    CREATE TYPE business_status AS ENUM (
        'pending',      -- Pendiente de aprobación
        'approved',     -- Aprobado y activo
        'suspended',    -- Suspendido
        'inactive'      -- Inactivo/Archivado
    );
EXCEPTION
    WHEN duplicate_object THEN null;
END $$;

COMMENT ON TYPE business_status IS 'Estado de registro y aprobación de un negocio';

-- =====================================================
-- ENUM 5: Plan de Suscripción
-- =====================================================
DO $$ BEGIN
    CREATE TYPE subscription_plan AS ENUM (
        'basic',        -- Plan básico
        'standard',     -- Plan estándar
        'premium'       -- Plan premium
    );
EXCEPTION
    WHEN duplicate_object THEN null;
END $$;

COMMENT ON TYPE subscription_plan IS 'Niveles de suscripción disponibles para negocios';

-- =====================================================
-- ENUM 6: Estado de Empleado
-- =====================================================
DO $$ BEGIN
    CREATE TYPE employee_status AS ENUM (
        'disponible',       -- Empleado disponible
        'no_disponible',    -- Empleado no disponible temporalmente
        'vacaciones',       -- En vacaciones
        'baja'              -- De baja
    );
EXCEPTION
    WHEN duplicate_object THEN null;
END $$;

COMMENT ON TYPE employee_status IS 'Estados de disponibilidad de empleados';

-- =====================================================
-- ENUM 7: Tipo de Notificación
-- =====================================================
DO $$ BEGIN
    CREATE TYPE notification_type AS ENUM (
        'email',        -- Correo electrónico
        'whatsapp',     -- WhatsApp (Fase 2)
        'sms'           -- SMS (Fase 2)
    );
EXCEPTION
    WHEN duplicate_object THEN null;
END $$;

COMMENT ON TYPE notification_type IS 'Canales de notificación disponibles';

-- =====================================================
-- ENUM 8: Estado de Notificación
-- =====================================================
DO $$ BEGIN
    CREATE TYPE notification_status AS ENUM (
        'enviado',      -- Enviado exitosamente
        'fallido',      -- Falló el envío
        'reintentando', -- En proceso de reintento
        'descartado'    -- Descartado después de intentos
    );
EXCEPTION
    WHEN duplicate_object THEN null;
END $$;

COMMENT ON TYPE notification_status IS 'Estado actual de una notificación enviada';

-- =====================================================
-- Verificar ENUMs creados
-- =====================================================
SELECT 
    t.typname AS enum_name,
    string_agg(e.enumlabel, ', ' ORDER BY e.enumsortorder) AS values
FROM pg_type t
JOIN pg_enum e ON t.oid = e.enumtypid
WHERE t.typname IN (
    'appointment_status',
    'schedule_exception_type',
    'resource_type',
    'business_status',
    'subscription_plan',
    'employee_status',
    'notification_type',
    'notification_status'
)
GROUP BY t.typname
ORDER BY t.typname;
