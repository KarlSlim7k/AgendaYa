-- =====================================================
-- CITAS EMPRESARIALES - MariaDB 10.6+
-- Script de Creación de Tablas
-- Versión: 1.0
-- Fecha: 13 de enero de 2026
-- =====================================================

USE citas_empresariales;

-- =====================================================
-- NOTA: MariaDB utiliza ENUM inline como MySQL.
-- Los CHECK constraints son completamente funcionales.
-- =====================================================

-- =====================================================
-- CONTEXTO 1: PLATAFORMA (Nivel Raíz)
-- =====================================================

-- Tabla: users (GLOBAL - sin business_id)
CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL COMMENT 'Nombre completo del usuario',
    email VARCHAR(255) NOT NULL COMMENT 'Email único global',
    password VARCHAR(255) NOT NULL COMMENT 'Password hasheado con bcrypt',
    telefono VARCHAR(20) NULL COMMENT 'Teléfono con formato +52',
    avatar_url VARCHAR(500) NULL COMMENT 'URL de avatar/foto de perfil',
    email_verified_at TIMESTAMP NULL COMMENT 'Fecha de verificación de email',
    remember_token VARCHAR(100) NULL COMMENT 'Token para remember me',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL COMMENT 'Soft delete',
    
    UNIQUE INDEX idx_users_email (email),
    INDEX idx_users_email_verified (email_verified_at),
    INDEX idx_users_created (created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Usuarios globales de la plataforma';

-- Tabla: platform_admins
CREATE TABLE IF NOT EXISTS platform_admins (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL COMMENT 'FK a users',
    super_admin BOOLEAN DEFAULT FALSE COMMENT 'Si es super admin con todos los permisos',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_platform_admins_user 
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    UNIQUE INDEX idx_platform_admins_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Administradores de la plataforma';

-- Tabla: platform_settings
CREATE TABLE IF NOT EXISTS platform_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(100) NOT NULL COMMENT 'Clave única de configuración',
    valor JSON NULL COMMENT 'Valor de configuración en JSON',
    descripcion TEXT NULL COMMENT 'Descripción de la configuración',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE INDEX idx_platform_settings_clave (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Configuración global de la plataforma';

-- =====================================================
-- CONTEXTO 2: NEGOCIO (Core - Multi-Tenant)
-- =====================================================

-- Tabla: businesses (TENANT ROOT)
CREATE TABLE IF NOT EXISTS businesses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL COMMENT 'Nombre comercial del negocio',
    razon_social VARCHAR(255) NULL COMMENT 'Razón social para facturación',
    rfc VARCHAR(13) NULL COMMENT 'RFC mexicano (12-13 caracteres)',
    logo_url VARCHAR(500) NULL COMMENT 'URL del logo',
    categoria VARCHAR(100) NULL COMMENT 'Categoría del negocio',
    telefono VARCHAR(20) NULL COMMENT 'Teléfono principal',
    email VARCHAR(255) NULL COMMENT 'Email del negocio',
    sitio_web VARCHAR(500) NULL COMMENT 'Sitio web del negocio',
    plan ENUM('basic', 'standard', 'premium') DEFAULT 'basic' COMMENT 'Plan de suscripción',
    estado ENUM('pending', 'approved', 'suspended', 'inactive') DEFAULT 'pending' COMMENT 'Estado del negocio',
    meta JSON NULL COMMENT 'Metadatos adicionales del negocio',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL COMMENT 'Soft delete',
    
    INDEX idx_businesses_nombre (nombre),
    INDEX idx_businesses_estado (estado),
    INDEX idx_businesses_categoria (categoria),
    INDEX idx_businesses_created (created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Negocios/Tenants de la plataforma';

-- Tabla: business_locations (Sucursales)
CREATE TABLE IF NOT EXISTS business_locations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL COMMENT 'FK al negocio (tenant)',
    nombre VARCHAR(255) NOT NULL COMMENT 'Nombre de la sucursal',
    direccion TEXT NULL COMMENT 'Dirección completa',
    ciudad VARCHAR(100) NULL,
    estado_geografico VARCHAR(100) NULL COMMENT 'Estado/Provincia',
    codigo_postal VARCHAR(10) NULL,
    telefono VARCHAR(20) NULL,
    email VARCHAR(255) NULL,
    zona_horaria VARCHAR(50) DEFAULT 'America/Mexico_City' COMMENT 'Timezone de la sucursal',
    latitud DECIMAL(10, 8) NULL COMMENT 'Coordenada para mapa',
    longitud DECIMAL(11, 8) NULL COMMENT 'Coordenada para mapa',
    activo BOOLEAN DEFAULT TRUE COMMENT 'Si la sucursal está activa',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    CONSTRAINT fk_locations_business 
        FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    
    INDEX idx_locations_business (business_id),
    UNIQUE INDEX idx_locations_business_nombre (business_id, nombre),
    INDEX idx_locations_created (created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Sucursales de cada negocio';

-- Tabla: services (Servicios)
CREATE TABLE IF NOT EXISTS services (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL COMMENT 'FK al negocio (tenant)',
    nombre VARCHAR(255) NOT NULL COMMENT 'Nombre del servicio',
    descripcion TEXT NULL COMMENT 'Descripción del servicio',
    precio DECIMAL(10, 2) NOT NULL DEFAULT 0.00 COMMENT 'Precio del servicio',
    duracion_minutos INT UNSIGNED NOT NULL DEFAULT 30 COMMENT 'Duración en minutos',
    buffer_pre_minutos INT UNSIGNED DEFAULT 0 COMMENT 'Buffer antes de la cita',
    buffer_post_minutos INT UNSIGNED DEFAULT 0 COMMENT 'Buffer después de la cita',
    requiere_confirmacion BOOLEAN DEFAULT FALSE COMMENT 'Si requiere confirmación manual',
    activo BOOLEAN DEFAULT TRUE COMMENT 'Si el servicio está activo',
    meta JSON NULL COMMENT 'Metadatos adicionales (deposito, instrucciones, custom_fields)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    CONSTRAINT fk_services_business 
        FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    CONSTRAINT chk_services_precio CHECK (precio >= 0),
    CONSTRAINT chk_services_duracion CHECK (duracion_minutos >= 15),
    
    INDEX idx_services_business (business_id),
    UNIQUE INDEX idx_services_business_nombre (business_id, nombre),
    INDEX idx_services_created (created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Servicios ofrecidos por cada negocio';

-- Tabla: employees (Empleados)
CREATE TABLE IF NOT EXISTS employees (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL COMMENT 'FK al negocio (tenant)',
    user_account_id BIGINT UNSIGNED NULL COMMENT 'FK opcional a users si tiene cuenta',
    nombre VARCHAR(255) NOT NULL COMMENT 'Nombre del empleado',
    email VARCHAR(255) NULL COMMENT 'Email del empleado',
    telefono VARCHAR(20) NULL,
    avatar_url VARCHAR(500) NULL,
    cargo VARCHAR(100) NULL COMMENT 'Cargo o puesto',
    estado ENUM('disponible', 'no_disponible', 'vacaciones', 'baja') DEFAULT 'disponible',
    meta JSON NULL COMMENT 'Datos adicionales del empleado',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    CONSTRAINT fk_employees_business 
        FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    CONSTRAINT fk_employees_user_account 
        FOREIGN KEY (user_account_id) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_employees_business (business_id),
    UNIQUE INDEX idx_employees_business_email (business_id, email),
    INDEX idx_employees_user_account (user_account_id),
    INDEX idx_employees_deleted (business_id, deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Empleados de cada negocio';

-- Tabla: employee_services (Pivote N:M)
CREATE TABLE IF NOT EXISTS employee_services (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id BIGINT UNSIGNED NOT NULL,
    service_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_employee_services_employee 
        FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    CONSTRAINT fk_employee_services_service 
        FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    
    UNIQUE INDEX idx_employee_services_unique (employee_id, service_id),
    INDEX idx_employee_services_employee (employee_id),
    INDEX idx_employee_services_service (service_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Relación entre empleados y servicios que pueden realizar';

-- Tabla: schedule_templates (Plantillas de Horario)
CREATE TABLE IF NOT EXISTS schedule_templates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_location_id BIGINT UNSIGNED NOT NULL COMMENT 'FK a sucursal',
    dia_semana TINYINT UNSIGNED NOT NULL COMMENT '0=Domingo, 6=Sábado',
    hora_apertura TIME NOT NULL COMMENT 'Hora de apertura',
    hora_cierre TIME NOT NULL COMMENT 'Hora de cierre',
    activo BOOLEAN DEFAULT TRUE COMMENT 'Si el día está activo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_schedule_templates_location 
        FOREIGN KEY (business_location_id) REFERENCES business_locations(id) ON DELETE CASCADE,
    CONSTRAINT chk_schedule_templates_dia CHECK (dia_semana BETWEEN 0 AND 6),
    CONSTRAINT chk_schedule_templates_horario CHECK (hora_cierre > hora_apertura),
    
    INDEX idx_schedule_templates_location (business_location_id),
    UNIQUE INDEX idx_schedule_templates_location_dia (business_location_id, dia_semana)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Horarios base por día de semana de cada sucursal';

-- Tabla: schedule_exceptions (Excepciones de Horario)
CREATE TABLE IF NOT EXISTS schedule_exceptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_location_id BIGINT UNSIGNED NOT NULL COMMENT 'FK a sucursal',
    fecha DATE NOT NULL COMMENT 'Fecha de la excepción',
    tipo ENUM('feriado', 'vacaciones', 'cierre') NOT NULL COMMENT 'Tipo de excepción',
    todo_el_dia BOOLEAN DEFAULT TRUE COMMENT 'Si aplica todo el día',
    hora_inicio TIME NULL COMMENT 'Hora inicio si no es todo el día',
    hora_fin TIME NULL COMMENT 'Hora fin si no es todo el día',
    motivo VARCHAR(255) NULL COMMENT 'Motivo de la excepción',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_schedule_exceptions_location 
        FOREIGN KEY (business_location_id) REFERENCES business_locations(id) ON DELETE CASCADE,
    
    INDEX idx_schedule_exceptions_location_fecha (business_location_id, fecha),
    INDEX idx_schedule_exceptions_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Excepciones de horario (feriados, vacaciones, cierres)';

-- =====================================================
-- CONTEXTO 3: USUARIOS (Global)
-- =====================================================

-- Tabla: user_favorite_businesses
CREATE TABLE IF NOT EXISTS user_favorite_businesses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    business_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_user_favorites_user 
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_user_favorites_business 
        FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    
    UNIQUE INDEX idx_user_favorites_unique (user_id, business_id),
    INDEX idx_user_favorites_user (user_id),
    INDEX idx_user_favorites_business (business_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Negocios favoritos de cada usuario';

-- =====================================================
-- CONTEXTO 4: RBAC (Control de Acceso Multi-Tenant)
-- =====================================================

-- Tabla: roles
CREATE TABLE IF NOT EXISTS roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL COMMENT 'Nombre único del rol',
    description TEXT NULL COMMENT 'Descripción del rol',
    guard_name VARCHAR(50) DEFAULT 'web',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE INDEX idx_roles_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Roles del sistema RBAC';

-- Tabla: permissions
CREATE TABLE IF NOT EXISTS permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT 'Nombre único del permiso (modulo.accion)',
    module VARCHAR(50) NOT NULL COMMENT 'Módulo al que pertenece',
    action VARCHAR(20) NOT NULL COMMENT 'Acción (create, read, update, delete)',
    description TEXT NULL COMMENT 'Descripción del permiso',
    guard_name VARCHAR(50) DEFAULT 'web',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE INDEX idx_permissions_name (name),
    INDEX idx_permissions_module (module)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Permisos granulares del sistema';

-- Tabla: role_permissions (Pivote)
CREATE TABLE IF NOT EXISTS role_permissions (
    role_id BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (role_id, permission_id),
    
    CONSTRAINT fk_role_permissions_role 
        FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    CONSTRAINT fk_role_permissions_permission 
        FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    
    INDEX idx_role_permissions_role (role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Asignación de permisos a roles';

-- Tabla: business_user_roles (Multi-Tenant CRÍTICO)
CREATE TABLE IF NOT EXISTS business_user_roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    business_id BIGINT UNSIGNED NULL COMMENT 'NULL para roles globales',
    role_id BIGINT UNSIGNED NOT NULL,
    location_id BIGINT UNSIGNED NULL COMMENT 'Sucursal asignada para NEGOCIO_MANAGER',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_business_user_roles_user 
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_business_user_roles_business 
        FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    CONSTRAINT fk_business_user_roles_role 
        FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    CONSTRAINT fk_business_user_roles_location 
        FOREIGN KEY (location_id) REFERENCES business_locations(id) ON DELETE SET NULL,
    
    UNIQUE INDEX idx_business_user_roles_unique (user_id, business_id, role_id),
    INDEX idx_business_user_roles_user (user_id),
    INDEX idx_business_user_roles_business (business_id),
    INDEX idx_business_user_roles_lookup (user_id, business_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Asignación de roles a usuarios por negocio (multi-tenant)';

-- =====================================================
-- CONTEXTO 5: AGENDA Y RESERVAS
-- =====================================================

-- Tabla: appointments (CRÍTICA)
CREATE TABLE IF NOT EXISTS appointments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL COMMENT 'FK al negocio (tenant)',
    user_id BIGINT UNSIGNED NOT NULL COMMENT 'Usuario que reserva',
    business_location_id BIGINT UNSIGNED NOT NULL COMMENT 'Sucursal',
    service_id BIGINT UNSIGNED NOT NULL COMMENT 'Servicio reservado',
    employee_id BIGINT UNSIGNED NULL COMMENT 'Empleado asignado (puede ser NULL)',
    fecha_hora_inicio DATETIME NOT NULL COMMENT 'Inicio de la cita',
    fecha_hora_fin DATETIME NOT NULL COMMENT 'Fin de la cita',
    estado ENUM('pending', 'confirmed', 'completed', 'cancelled', 'no_show') DEFAULT 'pending',
    codigo_confirmacion VARCHAR(20) NOT NULL COMMENT 'Código único de confirmación',
    notas_cliente TEXT NULL COMMENT 'Notas del cliente',
    notas_internas TEXT NULL COMMENT 'Notas internas del negocio',
    custom_data JSON NULL COMMENT 'Campos personalizados',
    precio_final DECIMAL(10, 2) NULL COMMENT 'Precio final (puede diferir del servicio)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    CONSTRAINT fk_appointments_business 
        FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    CONSTRAINT fk_appointments_user 
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_appointments_location 
        FOREIGN KEY (business_location_id) REFERENCES business_locations(id) ON DELETE CASCADE,
    CONSTRAINT fk_appointments_service 
        FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE RESTRICT,
    CONSTRAINT fk_appointments_employee 
        FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE SET NULL,
    CONSTRAINT chk_appointments_fechas CHECK (fecha_hora_fin > fecha_hora_inicio),
    
    -- Índices críticos para motor de disponibilidad
    INDEX idx_appointments_employee_fecha (employee_id, fecha_hora_inicio, estado),
    INDEX idx_appointments_location_fecha (business_location_id, fecha_hora_inicio),
    INDEX idx_appointments_business (business_id),
    INDEX idx_appointments_user (user_id),
    INDEX idx_appointments_estado (business_id, estado),
    UNIQUE INDEX idx_appointments_codigo (codigo_confirmacion),
    INDEX idx_appointments_soft_delete (business_id, deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Citas/Reservas de la plataforma';

-- Tabla: appointment_status_histories
CREATE TABLE IF NOT EXISTS appointment_status_histories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    appointment_id BIGINT UNSIGNED NOT NULL,
    estado_anterior VARCHAR(20) NULL COMMENT 'Estado previo',
    estado_nuevo VARCHAR(20) NOT NULL COMMENT 'Nuevo estado',
    cambiado_por BIGINT UNSIGNED NULL COMMENT 'Usuario que realizó el cambio',
    motivo TEXT NULL COMMENT 'Motivo del cambio',
    fecha_cambio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_appointment_histories_appointment 
        FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
    CONSTRAINT fk_appointment_histories_user 
        FOREIGN KEY (cambiado_por) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_appointment_histories_appointment (appointment_id),
    INDEX idx_appointment_histories_fecha (fecha_cambio DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Historial de cambios de estado de citas';

-- =====================================================
-- CONTEXTO 6: RECURSOS (FASE 2)
-- =====================================================

-- Tabla: resources
CREATE TABLE IF NOT EXISTS resources (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL COMMENT 'FK al negocio (tenant)',
    business_location_id BIGINT UNSIGNED NOT NULL COMMENT 'Sucursal donde está el recurso',
    nombre VARCHAR(255) NOT NULL COMMENT 'Nombre del recurso',
    tipo ENUM('fisico', 'virtual') NOT NULL DEFAULT 'fisico',
    capacidad INT UNSIGNED DEFAULT 1 COMMENT 'Capacidad simultánea',
    descripcion TEXT NULL,
    activo BOOLEAN DEFAULT TRUE,
    meta JSON NULL COMMENT 'Metadatos adicionales',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_resources_business 
        FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    CONSTRAINT fk_resources_location 
        FOREIGN KEY (business_location_id) REFERENCES business_locations(id) ON DELETE CASCADE,
    CONSTRAINT chk_resources_capacidad CHECK (capacidad >= 1),
    
    INDEX idx_resources_business (business_id),
    INDEX idx_resources_location (business_location_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Recursos compartidos (salas, equipos) - FASE 2';

-- Tabla: appointment_resources (Pivote)
CREATE TABLE IF NOT EXISTS appointment_resources (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    appointment_id BIGINT UNSIGNED NOT NULL,
    resource_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_appointment_resources_appointment 
        FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
    CONSTRAINT fk_appointment_resources_resource 
        FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE CASCADE,
    
    UNIQUE INDEX idx_appointment_resources_unique (appointment_id, resource_id),
    INDEX idx_appointment_resources_appointment (appointment_id),
    INDEX idx_appointment_resources_resource (resource_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Asignación de recursos a citas - FASE 2';

-- =====================================================
-- CONTEXTO 7: NOTIFICACIONES (FASE 5)
-- =====================================================

-- Tabla: notification_logs
CREATE TABLE IF NOT EXISTS notification_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL COMMENT 'Usuario destinatario',
    business_id BIGINT UNSIGNED NULL COMMENT 'Negocio relacionado (tenant)',
    appointment_id BIGINT UNSIGNED NULL COMMENT 'Cita relacionada',
    tipo ENUM('email', 'whatsapp', 'sms') NOT NULL COMMENT 'Canal de notificación',
    evento VARCHAR(100) NOT NULL COMMENT 'Tipo de evento (confirmacion, recordatorio, etc)',
    estado ENUM('enviado', 'fallido', 'reintentando', 'descartado') DEFAULT 'enviado',
    destinatario VARCHAR(255) NOT NULL COMMENT 'Email o teléfono destino',
    asunto VARCHAR(255) NULL COMMENT 'Asunto (para emails)',
    intentos INT UNSIGNED DEFAULT 1 COMMENT 'Número de intentos',
    ultimo_intento TIMESTAMP NULL COMMENT 'Último intento de envío',
    error_mensaje TEXT NULL COMMENT 'Mensaje de error si falló',
    meta JSON NULL COMMENT 'Datos adicionales del envío',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    CONSTRAINT fk_notification_logs_user 
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_notification_logs_business 
        FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE SET NULL,
    CONSTRAINT fk_notification_logs_appointment 
        FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL,
    
    INDEX idx_notification_logs_user (user_id),
    INDEX idx_notification_logs_appointment (appointment_id),
    INDEX idx_notification_logs_estado_fecha (estado, created_at DESC),
    INDEX idx_notification_logs_created (created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Log de notificaciones enviadas - FASE 5';

-- =====================================================
-- TABLAS ADICIONALES DE LARAVEL
-- =====================================================

-- Tabla: password_reset_tokens (Laravel estándar)
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    email VARCHAR(255) NOT NULL PRIMARY KEY,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: personal_access_tokens (Laravel Sanctum)
CREATE TABLE IF NOT EXISTS personal_access_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tokenable_type VARCHAR(255) NOT NULL,
    tokenable_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    token VARCHAR(64) NOT NULL,
    abilities TEXT NULL,
    last_used_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE INDEX idx_personal_access_tokens_token (token),
    INDEX idx_personal_access_tokens_tokenable (tokenable_type, tokenable_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: failed_jobs (Laravel Queue)
CREATE TABLE IF NOT EXISTS failed_jobs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid VARCHAR(255) NOT NULL,
    connection TEXT NOT NULL,
    queue TEXT NOT NULL,
    payload LONGTEXT NOT NULL,
    exception LONGTEXT NOT NULL,
    failed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE INDEX idx_failed_jobs_uuid (uuid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: jobs (Laravel Queue)
CREATE TABLE IF NOT EXISTS jobs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    queue VARCHAR(255) NOT NULL,
    payload LONGTEXT NOT NULL,
    attempts TINYINT UNSIGNED NOT NULL,
    reserved_at INT UNSIGNED NULL,
    available_at INT UNSIGNED NOT NULL,
    created_at INT UNSIGNED NOT NULL,
    
    INDEX idx_jobs_queue (queue)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
