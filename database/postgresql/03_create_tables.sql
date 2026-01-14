-- =====================================================
-- CITAS EMPRESARIALES - PostgreSQL 14+
-- Script de Creación de Tablas
-- Versión: 1.0
-- Fecha: 13 de enero de 2026
-- =====================================================

-- =====================================================
-- CONTEXTO 1: PLATAFORMA (Nivel Raíz)
-- =====================================================

-- Tabla: users (GLOBAL - sin business_id)
CREATE TABLE IF NOT EXISTS users (
    id BIGSERIAL PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    telefono VARCHAR(20) NULL,
    avatar_url VARCHAR(500) NULL,
    email_verified_at TIMESTAMP NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
);

COMMENT ON TABLE users IS 'Usuarios globales de la plataforma';
COMMENT ON COLUMN users.nombre IS 'Nombre completo del usuario';
COMMENT ON COLUMN users.email IS 'Email único global';
COMMENT ON COLUMN users.password IS 'Password hasheado con bcrypt';
COMMENT ON COLUMN users.telefono IS 'Teléfono con formato +52';
COMMENT ON COLUMN users.avatar_url IS 'URL de avatar/foto de perfil';
COMMENT ON COLUMN users.email_verified_at IS 'Fecha de verificación de email';
COMMENT ON COLUMN users.remember_token IS 'Token para remember me';
COMMENT ON COLUMN users.deleted_at IS 'Soft delete';

-- Índices para users
CREATE UNIQUE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_email_verified ON users(email_verified_at) WHERE email_verified_at IS NOT NULL;
CREATE INDEX idx_users_created ON users(created_at DESC);

-- Tabla: platform_admins
CREATE TABLE IF NOT EXISTS platform_admins (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    super_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_platform_admins_user 
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

COMMENT ON TABLE platform_admins IS 'Administradores de la plataforma';
COMMENT ON COLUMN platform_admins.user_id IS 'FK a users';
COMMENT ON COLUMN platform_admins.super_admin IS 'Si es super admin con todos los permisos';

CREATE UNIQUE INDEX idx_platform_admins_user ON platform_admins(user_id);

-- Tabla: platform_settings
CREATE TABLE IF NOT EXISTS platform_settings (
    id BIGSERIAL PRIMARY KEY,
    clave VARCHAR(100) NOT NULL,
    valor JSONB NULL,
    descripcion TEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

COMMENT ON TABLE platform_settings IS 'Configuración global de la plataforma';
COMMENT ON COLUMN platform_settings.clave IS 'Clave única de configuración';
COMMENT ON COLUMN platform_settings.valor IS 'Valor de configuración en JSONB';
COMMENT ON COLUMN platform_settings.descripcion IS 'Descripción de la configuración';

CREATE UNIQUE INDEX idx_platform_settings_clave ON platform_settings(clave);

-- =====================================================
-- CONTEXTO 2: NEGOCIO (Core - Multi-Tenant)
-- =====================================================

-- Tabla: businesses (TENANT ROOT)
CREATE TABLE IF NOT EXISTS businesses (
    id BIGSERIAL PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    razon_social VARCHAR(255) NULL,
    rfc VARCHAR(13) NULL,
    logo_url VARCHAR(500) NULL,
    categoria VARCHAR(100) NULL,
    telefono VARCHAR(20) NULL,
    email VARCHAR(255) NULL,
    sitio_web VARCHAR(500) NULL,
    plan subscription_plan DEFAULT 'basic',
    estado business_status DEFAULT 'pending',
    meta JSONB NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
);

COMMENT ON TABLE businesses IS 'Negocios/Tenants de la plataforma';
COMMENT ON COLUMN businesses.nombre IS 'Nombre comercial del negocio';
COMMENT ON COLUMN businesses.razon_social IS 'Razón social para facturación';
COMMENT ON COLUMN businesses.rfc IS 'RFC mexicano (12-13 caracteres)';
COMMENT ON COLUMN businesses.logo_url IS 'URL del logo';
COMMENT ON COLUMN businesses.categoria IS 'Categoría del negocio';
COMMENT ON COLUMN businesses.plan IS 'Plan de suscripción';
COMMENT ON COLUMN businesses.estado IS 'Estado del negocio';
COMMENT ON COLUMN businesses.meta IS 'Metadatos adicionales del negocio';

CREATE INDEX idx_businesses_nombre ON businesses(nombre);
CREATE INDEX idx_businesses_estado ON businesses(estado);
CREATE INDEX idx_businesses_categoria ON businesses(categoria);
CREATE INDEX idx_businesses_created ON businesses(created_at DESC);
CREATE INDEX idx_businesses_meta_gin ON businesses USING GIN(meta);

-- Tabla: business_locations (Sucursales)
CREATE TABLE IF NOT EXISTS business_locations (
    id BIGSERIAL PRIMARY KEY,
    business_id BIGINT NOT NULL,
    nombre VARCHAR(255) NOT NULL,
    direccion TEXT NULL,
    ciudad VARCHAR(100) NULL,
    estado_geografico VARCHAR(100) NULL,
    codigo_postal VARCHAR(10) NULL,
    telefono VARCHAR(20) NULL,
    email VARCHAR(255) NULL,
    zona_horaria VARCHAR(50) DEFAULT 'America/Mexico_City',
    latitud DECIMAL(10, 8) NULL,
    longitud DECIMAL(11, 8) NULL,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    CONSTRAINT fk_locations_business 
        FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
);

COMMENT ON TABLE business_locations IS 'Sucursales de cada negocio';
COMMENT ON COLUMN business_locations.business_id IS 'FK al negocio (tenant)';
COMMENT ON COLUMN business_locations.nombre IS 'Nombre de la sucursal';
COMMENT ON COLUMN business_locations.zona_horaria IS 'Timezone de la sucursal';
COMMENT ON COLUMN business_locations.latitud IS 'Coordenada para mapa';
COMMENT ON COLUMN business_locations.longitud IS 'Coordenada para mapa';

CREATE INDEX idx_locations_business ON business_locations(business_id);
CREATE UNIQUE INDEX idx_locations_business_nombre ON business_locations(business_id, nombre);
CREATE INDEX idx_locations_created ON business_locations(created_at DESC);

-- Tabla: services (Servicios)
CREATE TABLE IF NOT EXISTS services (
    id BIGSERIAL PRIMARY KEY,
    business_id BIGINT NOT NULL,
    nombre VARCHAR(255) NOT NULL,
    descripcion TEXT NULL,
    precio DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    duracion_minutos INTEGER NOT NULL DEFAULT 30,
    buffer_pre_minutos INTEGER DEFAULT 0,
    buffer_post_minutos INTEGER DEFAULT 0,
    requiere_confirmacion BOOLEAN DEFAULT FALSE,
    activo BOOLEAN DEFAULT TRUE,
    meta JSONB NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    CONSTRAINT fk_services_business 
        FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    CONSTRAINT chk_services_precio CHECK (precio >= 0),
    CONSTRAINT chk_services_duracion CHECK (duracion_minutos >= 15),
    CONSTRAINT chk_services_buffer_pre CHECK (buffer_pre_minutos >= 0),
    CONSTRAINT chk_services_buffer_post CHECK (buffer_post_minutos >= 0)
);

COMMENT ON TABLE services IS 'Servicios ofrecidos por cada negocio';
COMMENT ON COLUMN services.business_id IS 'FK al negocio (tenant)';
COMMENT ON COLUMN services.precio IS 'Precio del servicio';
COMMENT ON COLUMN services.duracion_minutos IS 'Duración en minutos';
COMMENT ON COLUMN services.buffer_pre_minutos IS 'Buffer antes de la cita';
COMMENT ON COLUMN services.buffer_post_minutos IS 'Buffer después de la cita';
COMMENT ON COLUMN services.meta IS 'Metadatos (deposito, instrucciones, custom_fields)';

CREATE INDEX idx_services_business ON services(business_id);
CREATE UNIQUE INDEX idx_services_business_nombre ON services(business_id, nombre);
CREATE INDEX idx_services_created ON services(created_at DESC);
CREATE INDEX idx_services_meta_gin ON services USING GIN(meta);

-- Tabla: employees (Empleados)
CREATE TABLE IF NOT EXISTS employees (
    id BIGSERIAL PRIMARY KEY,
    business_id BIGINT NOT NULL,
    user_account_id BIGINT NULL,
    nombre VARCHAR(255) NOT NULL,
    email VARCHAR(255) NULL,
    telefono VARCHAR(20) NULL,
    avatar_url VARCHAR(500) NULL,
    cargo VARCHAR(100) NULL,
    estado employee_status DEFAULT 'disponible',
    meta JSONB NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    CONSTRAINT fk_employees_business 
        FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    CONSTRAINT fk_employees_user_account 
        FOREIGN KEY (user_account_id) REFERENCES users(id) ON DELETE SET NULL
);

COMMENT ON TABLE employees IS 'Empleados de cada negocio';
COMMENT ON COLUMN employees.business_id IS 'FK al negocio (tenant)';
COMMENT ON COLUMN employees.user_account_id IS 'FK opcional a users si tiene cuenta';
COMMENT ON COLUMN employees.estado IS 'Estado de disponibilidad';

CREATE INDEX idx_employees_business ON employees(business_id);
CREATE UNIQUE INDEX idx_employees_business_email ON employees(business_id, email) WHERE email IS NOT NULL;
CREATE INDEX idx_employees_user_account ON employees(user_account_id) WHERE user_account_id IS NOT NULL;
CREATE INDEX idx_employees_deleted ON employees(business_id, deleted_at) WHERE deleted_at IS NULL;

-- Tabla: employee_services (Pivote N:M)
CREATE TABLE IF NOT EXISTS employee_services (
    id BIGSERIAL PRIMARY KEY,
    employee_id BIGINT NOT NULL,
    service_id BIGINT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_employee_services_employee 
        FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    CONSTRAINT fk_employee_services_service 
        FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
);

COMMENT ON TABLE employee_services IS 'Relación entre empleados y servicios que pueden realizar';

CREATE UNIQUE INDEX idx_employee_services_unique ON employee_services(employee_id, service_id);
CREATE INDEX idx_employee_services_employee ON employee_services(employee_id);
CREATE INDEX idx_employee_services_service ON employee_services(service_id);

-- Tabla: schedule_templates (Plantillas de Horario)
CREATE TABLE IF NOT EXISTS schedule_templates (
    id BIGSERIAL PRIMARY KEY,
    business_location_id BIGINT NOT NULL,
    dia_semana SMALLINT NOT NULL,
    hora_apertura TIME NOT NULL,
    hora_cierre TIME NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_schedule_templates_location 
        FOREIGN KEY (business_location_id) REFERENCES business_locations(id) ON DELETE CASCADE,
    CONSTRAINT chk_schedule_templates_dia CHECK (dia_semana BETWEEN 0 AND 6),
    CONSTRAINT chk_schedule_templates_horario CHECK (hora_cierre > hora_apertura)
);

COMMENT ON TABLE schedule_templates IS 'Horarios base por día de semana de cada sucursal';
COMMENT ON COLUMN schedule_templates.business_location_id IS 'FK a sucursal';
COMMENT ON COLUMN schedule_templates.dia_semana IS '0=Domingo, 6=Sábado';
COMMENT ON COLUMN schedule_templates.hora_apertura IS 'Hora de apertura';
COMMENT ON COLUMN schedule_templates.hora_cierre IS 'Hora de cierre';

CREATE INDEX idx_schedule_templates_location ON schedule_templates(business_location_id);
CREATE UNIQUE INDEX idx_schedule_templates_location_dia ON schedule_templates(business_location_id, dia_semana);

-- Tabla: schedule_exceptions (Excepciones de Horario)
CREATE TABLE IF NOT EXISTS schedule_exceptions (
    id BIGSERIAL PRIMARY KEY,
    business_location_id BIGINT NOT NULL,
    fecha DATE NOT NULL,
    tipo schedule_exception_type NOT NULL,
    todo_el_dia BOOLEAN DEFAULT TRUE,
    hora_inicio TIME NULL,
    hora_fin TIME NULL,
    motivo VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_schedule_exceptions_location 
        FOREIGN KEY (business_location_id) REFERENCES business_locations(id) ON DELETE CASCADE
);

COMMENT ON TABLE schedule_exceptions IS 'Excepciones de horario (feriados, vacaciones, cierres)';
COMMENT ON COLUMN schedule_exceptions.tipo IS 'Tipo de excepción';
COMMENT ON COLUMN schedule_exceptions.todo_el_dia IS 'Si aplica todo el día';

CREATE INDEX idx_schedule_exceptions_location_fecha ON schedule_exceptions(business_location_id, fecha);
CREATE INDEX idx_schedule_exceptions_fecha ON schedule_exceptions(fecha);

-- =====================================================
-- CONTEXTO 3: USUARIOS (Global)
-- =====================================================

-- Tabla: user_favorite_businesses
CREATE TABLE IF NOT EXISTS user_favorite_businesses (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    business_id BIGINT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_user_favorites_user 
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_user_favorites_business 
        FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
);

COMMENT ON TABLE user_favorite_businesses IS 'Negocios favoritos de cada usuario';

CREATE UNIQUE INDEX idx_user_favorites_unique ON user_favorite_businesses(user_id, business_id);
CREATE INDEX idx_user_favorites_user ON user_favorite_businesses(user_id);
CREATE INDEX idx_user_favorites_business ON user_favorite_businesses(business_id);

-- =====================================================
-- CONTEXTO 4: RBAC (Control de Acceso Multi-Tenant)
-- =====================================================

-- Tabla: roles
CREATE TABLE IF NOT EXISTS roles (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT NULL,
    guard_name VARCHAR(50) DEFAULT 'web',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

COMMENT ON TABLE roles IS 'Roles del sistema RBAC';
COMMENT ON COLUMN roles.name IS 'Nombre único del rol';

CREATE UNIQUE INDEX idx_roles_name ON roles(name);

-- Tabla: permissions
CREATE TABLE IF NOT EXISTS permissions (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    module VARCHAR(50) NOT NULL,
    action VARCHAR(20) NOT NULL,
    description TEXT NULL,
    guard_name VARCHAR(50) DEFAULT 'web',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

COMMENT ON TABLE permissions IS 'Permisos granulares del sistema';
COMMENT ON COLUMN permissions.name IS 'Nombre único del permiso (modulo.accion)';
COMMENT ON COLUMN permissions.module IS 'Módulo al que pertenece';
COMMENT ON COLUMN permissions.action IS 'Acción (create, read, update, delete)';

CREATE UNIQUE INDEX idx_permissions_name ON permissions(name);
CREATE INDEX idx_permissions_module ON permissions(module);

-- Tabla: role_permissions (Pivote)
CREATE TABLE IF NOT EXISTS role_permissions (
    role_id BIGINT NOT NULL,
    permission_id BIGINT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (role_id, permission_id),
    
    CONSTRAINT fk_role_permissions_role 
        FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    CONSTRAINT fk_role_permissions_permission 
        FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);

COMMENT ON TABLE role_permissions IS 'Asignación de permisos a roles';

CREATE INDEX idx_role_permissions_role ON role_permissions(role_id);

-- Tabla: business_user_roles (Multi-Tenant CRÍTICO)
CREATE TABLE IF NOT EXISTS business_user_roles (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    business_id BIGINT NULL,
    role_id BIGINT NOT NULL,
    location_id BIGINT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_business_user_roles_user 
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_business_user_roles_business 
        FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    CONSTRAINT fk_business_user_roles_role 
        FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    CONSTRAINT fk_business_user_roles_location 
        FOREIGN KEY (location_id) REFERENCES business_locations(id) ON DELETE SET NULL
);

COMMENT ON TABLE business_user_roles IS 'Asignación de roles a usuarios por negocio (multi-tenant)';
COMMENT ON COLUMN business_user_roles.business_id IS 'NULL para roles globales';
COMMENT ON COLUMN business_user_roles.location_id IS 'Sucursal asignada para NEGOCIO_MANAGER';

CREATE UNIQUE INDEX idx_business_user_roles_unique ON business_user_roles(user_id, business_id, role_id);
CREATE INDEX idx_business_user_roles_user ON business_user_roles(user_id);
CREATE INDEX idx_business_user_roles_business ON business_user_roles(business_id);
CREATE INDEX idx_business_user_roles_lookup ON business_user_roles(user_id, business_id);

-- =====================================================
-- CONTEXTO 5: AGENDA Y RESERVAS
-- =====================================================

-- Tabla: appointments (CRÍTICA)
CREATE TABLE IF NOT EXISTS appointments (
    id BIGSERIAL PRIMARY KEY,
    business_id BIGINT NOT NULL,
    user_id BIGINT NOT NULL,
    business_location_id BIGINT NOT NULL,
    service_id BIGINT NOT NULL,
    employee_id BIGINT NULL,
    fecha_hora_inicio TIMESTAMP NOT NULL,
    fecha_hora_fin TIMESTAMP NOT NULL,
    estado appointment_status DEFAULT 'pending',
    codigo_confirmacion VARCHAR(20) NOT NULL,
    notas_cliente TEXT NULL,
    notas_internas TEXT NULL,
    custom_data JSONB NULL,
    precio_final DECIMAL(10, 2) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
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
    CONSTRAINT chk_appointments_fechas CHECK (fecha_hora_fin > fecha_hora_inicio)
);

COMMENT ON TABLE appointments IS 'Citas/Reservas de la plataforma';
COMMENT ON COLUMN appointments.business_id IS 'FK al negocio (tenant)';
COMMENT ON COLUMN appointments.estado IS 'Estado de la cita';
COMMENT ON COLUMN appointments.codigo_confirmacion IS 'Código único de confirmación';
COMMENT ON COLUMN appointments.custom_data IS 'Campos personalizados';

-- Índices críticos para motor de disponibilidad
CREATE INDEX idx_appointments_employee_fecha ON appointments(employee_id, fecha_hora_inicio, estado) 
    WHERE estado != 'cancelled';
CREATE INDEX idx_appointments_location_fecha ON appointments(business_location_id, fecha_hora_inicio) 
    WHERE estado != 'cancelled';
CREATE INDEX idx_appointments_business ON appointments(business_id);
CREATE INDEX idx_appointments_user ON appointments(user_id);
CREATE INDEX idx_appointments_estado ON appointments(business_id, estado);
CREATE UNIQUE INDEX idx_appointments_codigo ON appointments(codigo_confirmacion);
CREATE INDEX idx_appointments_soft_delete ON appointments(business_id, deleted_at) WHERE deleted_at IS NULL;
CREATE INDEX idx_appointments_custom_data_gin ON appointments USING GIN(custom_data);

-- Tabla: appointment_status_histories
CREATE TABLE IF NOT EXISTS appointment_status_histories (
    id BIGSERIAL PRIMARY KEY,
    appointment_id BIGINT NOT NULL,
    estado_anterior VARCHAR(20) NULL,
    estado_nuevo VARCHAR(20) NOT NULL,
    cambiado_por BIGINT NULL,
    motivo TEXT NULL,
    fecha_cambio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_appointment_histories_appointment 
        FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
    CONSTRAINT fk_appointment_histories_user 
        FOREIGN KEY (cambiado_por) REFERENCES users(id) ON DELETE SET NULL
);

COMMENT ON TABLE appointment_status_histories IS 'Historial de cambios de estado de citas';
COMMENT ON COLUMN appointment_status_histories.estado_anterior IS 'Estado previo';
COMMENT ON COLUMN appointment_status_histories.estado_nuevo IS 'Nuevo estado';
COMMENT ON COLUMN appointment_status_histories.cambiado_por IS 'Usuario que realizó el cambio';

CREATE INDEX idx_appointment_histories_appointment ON appointment_status_histories(appointment_id);
CREATE INDEX idx_appointment_histories_fecha ON appointment_status_histories(fecha_cambio DESC);

-- =====================================================
-- CONTEXTO 6: RECURSOS (FASE 2)
-- =====================================================

-- Tabla: resources
CREATE TABLE IF NOT EXISTS resources (
    id BIGSERIAL PRIMARY KEY,
    business_id BIGINT NOT NULL,
    business_location_id BIGINT NOT NULL,
    nombre VARCHAR(255) NOT NULL,
    tipo resource_type NOT NULL DEFAULT 'fisico',
    capacidad INTEGER DEFAULT 1,
    descripcion TEXT NULL,
    activo BOOLEAN DEFAULT TRUE,
    meta JSONB NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_resources_business 
        FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    CONSTRAINT fk_resources_location 
        FOREIGN KEY (business_location_id) REFERENCES business_locations(id) ON DELETE CASCADE,
    CONSTRAINT chk_resources_capacidad CHECK (capacidad >= 1)
);

COMMENT ON TABLE resources IS 'Recursos compartidos (salas, equipos) - FASE 2';
COMMENT ON COLUMN resources.tipo IS 'Tipo de recurso (físico/virtual)';
COMMENT ON COLUMN resources.capacidad IS 'Capacidad simultánea';

CREATE INDEX idx_resources_business ON resources(business_id);
CREATE INDEX idx_resources_location ON resources(business_location_id);

-- Tabla: appointment_resources (Pivote)
CREATE TABLE IF NOT EXISTS appointment_resources (
    id BIGSERIAL PRIMARY KEY,
    appointment_id BIGINT NOT NULL,
    resource_id BIGINT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_appointment_resources_appointment 
        FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
    CONSTRAINT fk_appointment_resources_resource 
        FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE CASCADE
);

COMMENT ON TABLE appointment_resources IS 'Asignación de recursos a citas - FASE 2';

CREATE UNIQUE INDEX idx_appointment_resources_unique ON appointment_resources(appointment_id, resource_id);
CREATE INDEX idx_appointment_resources_appointment ON appointment_resources(appointment_id);
CREATE INDEX idx_appointment_resources_resource ON appointment_resources(resource_id);

-- =====================================================
-- CONTEXTO 7: NOTIFICACIONES (FASE 5)
-- =====================================================

-- Tabla: notification_logs
CREATE TABLE IF NOT EXISTS notification_logs (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NULL,
    business_id BIGINT NULL,
    appointment_id BIGINT NULL,
    tipo notification_type NOT NULL,
    evento VARCHAR(100) NOT NULL,
    estado notification_status DEFAULT 'enviado',
    destinatario VARCHAR(255) NOT NULL,
    asunto VARCHAR(255) NULL,
    intentos INTEGER DEFAULT 1,
    ultimo_intento TIMESTAMP NULL,
    error_mensaje TEXT NULL,
    meta JSONB NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    CONSTRAINT fk_notification_logs_user 
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_notification_logs_business 
        FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE SET NULL,
    CONSTRAINT fk_notification_logs_appointment 
        FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL
);

COMMENT ON TABLE notification_logs IS 'Log de notificaciones enviadas - FASE 5';
COMMENT ON COLUMN notification_logs.tipo IS 'Canal de notificación';
COMMENT ON COLUMN notification_logs.evento IS 'Tipo de evento (confirmacion, recordatorio, etc)';
COMMENT ON COLUMN notification_logs.estado IS 'Estado del envío';

CREATE INDEX idx_notification_logs_user ON notification_logs(user_id);
CREATE INDEX idx_notification_logs_appointment ON notification_logs(appointment_id);
CREATE INDEX idx_notification_logs_estado_fecha ON notification_logs(estado, created_at DESC);
CREATE INDEX idx_notification_logs_created ON notification_logs(created_at DESC);

-- =====================================================
-- TABLAS ADICIONALES DE LARAVEL
-- =====================================================

-- Tabla: password_reset_tokens (Laravel estándar)
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    email VARCHAR(255) PRIMARY KEY,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL
);

-- Tabla: personal_access_tokens (Laravel Sanctum)
CREATE TABLE IF NOT EXISTS personal_access_tokens (
    id BIGSERIAL PRIMARY KEY,
    tokenable_type VARCHAR(255) NOT NULL,
    tokenable_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    token VARCHAR(64) NOT NULL,
    abilities TEXT NULL,
    last_used_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE UNIQUE INDEX idx_personal_access_tokens_token ON personal_access_tokens(token);
CREATE INDEX idx_personal_access_tokens_tokenable ON personal_access_tokens(tokenable_type, tokenable_id);

-- Tabla: failed_jobs (Laravel Queue)
CREATE TABLE IF NOT EXISTS failed_jobs (
    id BIGSERIAL PRIMARY KEY,
    uuid VARCHAR(255) NOT NULL,
    connection TEXT NOT NULL,
    queue TEXT NOT NULL,
    payload TEXT NOT NULL,
    exception TEXT NOT NULL,
    failed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE UNIQUE INDEX idx_failed_jobs_uuid ON failed_jobs(uuid);

-- Tabla: jobs (Laravel Queue)
CREATE TABLE IF NOT EXISTS jobs (
    id BIGSERIAL PRIMARY KEY,
    queue VARCHAR(255) NOT NULL,
    payload TEXT NOT NULL,
    attempts SMALLINT NOT NULL,
    reserved_at INTEGER NULL,
    available_at INTEGER NOT NULL,
    created_at INTEGER NOT NULL
);

CREATE INDEX idx_jobs_queue ON jobs(queue);

-- =====================================================
-- FUNCIÓN PARA ACTUALIZAR updated_at AUTOMÁTICAMENTE
-- =====================================================

CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Triggers para updated_at
CREATE TRIGGER update_users_updated_at BEFORE UPDATE ON users FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_platform_admins_updated_at BEFORE UPDATE ON platform_admins FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_platform_settings_updated_at BEFORE UPDATE ON platform_settings FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_businesses_updated_at BEFORE UPDATE ON businesses FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_business_locations_updated_at BEFORE UPDATE ON business_locations FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_services_updated_at BEFORE UPDATE ON services FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_employees_updated_at BEFORE UPDATE ON employees FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_schedule_templates_updated_at BEFORE UPDATE ON schedule_templates FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_schedule_exceptions_updated_at BEFORE UPDATE ON schedule_exceptions FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_roles_updated_at BEFORE UPDATE ON roles FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_permissions_updated_at BEFORE UPDATE ON permissions FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_business_user_roles_updated_at BEFORE UPDATE ON business_user_roles FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_appointments_updated_at BEFORE UPDATE ON appointments FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_resources_updated_at BEFORE UPDATE ON resources FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_personal_access_tokens_updated_at BEFORE UPDATE ON personal_access_tokens FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
