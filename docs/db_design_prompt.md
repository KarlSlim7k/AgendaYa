# PROMPT PROFESIONAL: DISEÑO DE BASE DE DATOS SAAS MULTI-TENANT

## CONTEXTO DEL PROYECTO

Estoy desarrollando una **plataforma SaaS multi-tenant para gestión de citas empresariales** (peluquerías, clínicas, talleres, etc.). El proyecto utiliza arquitectura multi-tenant con estrategia de **Tenant ID en tablas** (single database con `business_id` para segregación).

**Stack Backend**: Laravel + MySQL / MariaDB / PostegreSQL

**Documento de arquitectura**: He adjuntado el documento completo de arquitectura del sistema que contiene:
- Modelado del dominio (Sección 4)
- Definición de roles y permisos RBAC (Sección 2)
- Historias de usuario con criterios de aceptación (Sección 3)
- Motor de disponibilidad y reglas de negocio (Sección 5)
- Estrategia multi-tenant (Sección 6)

---

## OBJETIVO

Necesito que me ayudes a crear el **diseño completo de la base de datos PostgreSQL** con las siguientes características:

### 1. ESTRUCTURA COMPLETA DE TABLAS

Para cada entidad mencionada en la Sección 4 del documento, necesito:

- **Nombre de tabla** en convención Laravel (plural, snake_case)
- **Todos los campos** con:
  - Tipo de dato PostgreSQL específico
  - Longitud/precisión cuando aplique
  - NOT NULL / NULLABLE
  - Valores DEFAULT
  - Comentarios descriptivos
- **Primary Keys** (generalmente `id` tipo BIGSERIAL)
- **Foreign Keys** con:
  - Tabla referenciada
  - Acción ON DELETE (CASCADE, SET NULL, RESTRICT)
  - Acción ON UPDATE
- **Campos de auditoría estándar**:
  - `created_at TIMESTAMP`
  - `updated_at TIMESTAMP`
  - `deleted_at TIMESTAMP` (para soft deletes donde aplique)
- **Campos especiales**:
  - `business_id` en TODAS las tablas que requieran segregación multi-tenant
  - Columnas JSON para metadatos/campos personalizables (ej: `meta`, `custom_data`)

### 2. ÍNDICES Y OPTIMIZACIÓN

- **Índices compuestos** críticos para queries multi-tenant (ej: `business_id` + `fecha`)
- **Índices UNIQUE** donde corresponda (ej: email por negocio)
- **Índices para búsquedas** (LIKE, full-text search)
- **Índices GIN** para columnas JSON en PostgreSQL
- Justificación breve de cada índice propuesto

### 3. CONSTRAINTS Y VALIDACIONES

- **CHECK constraints** para validar datos (ej: precios > 0, duraciones > 0)
- **ENUM types** en PostgreSQL para estados y categorías (según documento)
- **UNIQUE constraints compuestos** donde sea necesario
- Validaciones de integridad referencial

### 4. TABLAS ESPECÍFICAS REQUERIDAS

Basándote ESTRICTAMENTE en la Sección 4 del documento, genera la estructura completa para:

#### Tenant Raíz (Plataforma):
- `platform_admins`
- `platform_settings`

#### Tenant Negocio (Core):
- `businesses`
- `business_locations`
- `services`
- `employees`
- `employee_services` (pivote)
- `schedule_templates`
- `schedule_exceptions`

#### Tenant Usuario (Global):
- `users`
- `user_favorite_businesses`

#### Sistema RBAC (Multi-Tenant):
- `roles`
- `permissions`
- `role_permissions` (pivote)
- `business_user_roles` (asignación multi-tenant de roles)
- `user_permissions` (permisos directos opcionales)

#### Agenda y Reservas:
- `appointments`
- `appointment_status_histories`

#### Recursos (Fase 2 - marcar claramente):
- `resources`
- `appointment_resources`

### 5. CONSIDERACIONES ESPECIALES

#### Multi-Tenancy:
- Implementar **global scopes** compatibles con Laravel
- Estrategia de aislamiento por `business_id`
- Tablas que NO requieren `business_id` (usuarios finales, configuración global)

#### Roles y Permisos (Sección 2) - **CRÍTICO**:
Implementar sistema RBAC completo basado en la **Tabla de Permisos (Sección 2)** del documento.

**Roles a soportar**:
1. USUARIO_FINAL
2. NEGOCIO_STAFF
3. NEGOCIO_MANAGER
4. NEGOCIO_ADMIN
5. PLATAFORMA_ADMIN

**Requisitos específicos**:
- Sistema multi-tenant: Un usuario puede tener diferentes roles en diferentes negocios
- Tabla `business_user_roles` para asignar roles específicos por negocio
- Permisos granulares por módulo según la tabla del documento:
  - Perfil: CRUD
  - Negocio: R, CRUD (según rol)
  - Sucursal: R, CRUD (según rol y scope)
  - Servicio: R, CRUD (según rol y scope)
  - Empleado: R, CRUD (según rol y scope)
  - Agenda Slot: C, R, CRUD (según rol)
  - Cita Estado: R/U, U, CRUD (según rol)
  - Reportes Financieros: R (según rol y scope)

**Opciones de implementación** (elegir una y justificar):
- **Opción A**: Sistema custom con tablas propias siguiendo exactamente la matriz de permisos
- **Opción B**: Adaptación de Spatie Permission con extensiones multi-tenant

La solución debe soportar consultas eficientes tipo:
```
¿Usuario X tiene permiso "servicio.update" en Negocio Y?
¿Usuario X puede ver reportes financieros de Sucursal Z?
```

#### Motor de Disponibilidad (Sección 5):
- Estructura que soporte las 7 reglas de disponibilidad mencionadas
- Campos para buffers (pre y post cita)
- Soporte para recursos compartidos con capacidad

#### Notificaciones:
- Tabla para tracking de notificaciones enviadas (opcional pero recomendado)
- Campos para templates personalizables

---

## FORMATO DE ENTREGA ESPERADO

### PARTE 1: Diagrama ERD
- Descripción textual de relaciones entre entidades
- Cardinalidad (1:1, 1:N, N:M)
- Identificar tablas pivote

### PARTE 2: Scripts SQL
Para cada tabla, proporcionar:

```sql
-- Crear ENUM types (si aplica)
CREATE TYPE appointment_status AS ENUM ('pending', 'confirmed', 'completed', 'cancelled', 'no_show');

-- Crear tabla con todos los detalles
CREATE TABLE appointments (
    id BIGSERIAL PRIMARY KEY,
    business_id BIGINT NOT NULL,
    user_id BIGINT NOT NULL,
    -- ... resto de campos con comentarios
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign Keys
    CONSTRAINT fk_appointments_business FOREIGN KEY (business_id) 
        REFERENCES businesses(id) ON DELETE CASCADE,
    -- ... resto de FKs
    
    -- Constraints
    CONSTRAINT chk_appointment_dates CHECK (fecha_hora_fin > fecha_hora_inicio)
);

-- Índices
CREATE INDEX idx_appointments_business_date ON appointments(business_id, fecha_hora_inicio);
-- ... resto de índices
```

### PARTE 3: Sistema RBAC Completo

#### 3.1 Estructura de Tablas RBAC
Proporcionar scripts SQL completos para:

```sql
-- Tabla de roles con los 5 roles del sistema
CREATE TABLE roles (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL, -- 'USUARIO_FINAL', 'NEGOCIO_STAFF', etc.
    description TEXT,
    guard_name VARCHAR(50) DEFAULT 'web',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de permisos granulares
CREATE TABLE permissions (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL, -- 'perfil.create', 'negocio.read', etc.
    module VARCHAR(50) NOT NULL, -- 'perfil', 'negocio', 'sucursal', etc.
    action VARCHAR(20) NOT NULL, -- 'create', 'read', 'update', 'delete'
    description TEXT,
    guard_name VARCHAR(50) DEFAULT 'web',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla pivote: qué permisos tiene cada rol
CREATE TABLE role_permissions (
    role_id BIGINT NOT NULL,
    permission_id BIGINT NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);

-- CRÍTICO: Tabla para asignar roles a usuarios POR NEGOCIO (multi-tenant)
CREATE TABLE business_user_roles (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    business_id BIGINT, -- NULL para roles globales (PLATAFORMA_ADMIN, USUARIO_FINAL)
    role_id BIGINT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    
    -- Un usuario solo puede tener un rol por negocio
    UNIQUE (user_id, business_id, role_id)
);

-- Índices para consultas de permisos
CREATE INDEX idx_business_user_roles_user ON business_user_roles(user_id);
CREATE INDEX idx_business_user_roles_business ON business_user_roles(business_id);
CREATE INDEX idx_business_user_roles_lookup ON business_user_roles(user_id, business_id);
```

#### 3.2 Seeder de Roles y Permisos
Proporcionar script SQL INSERT para popular:

1. **Los 5 roles** según Sección 2 del documento
2. **Todos los permisos** basados en la matriz de la Tabla de Permisos:
   - `perfil.create`, `perfil.read`, `perfil.update`, `perfil.delete`
   - `negocio.read`, `negocio.create`, `negocio.update`, `negocio.delete`
   - `sucursal.create`, `sucursal.read`, `sucursal.update`, `sucursal.delete`
   - `servicio.create`, `servicio.read`, `servicio.update`, `servicio.delete`
   - `empleado.create`, `empleado.read`, `empleado.update`, `empleado.delete`
   - `agenda.create`, `agenda.read`, `agenda.update`, `agenda.delete`
   - `cita.read`, `cita.update`, `cita.delete`
   - `reportes_financieros.read`

3. **Asignación de permisos a roles** (tabla `role_permissions`) siguiendo EXACTAMENTE la matriz de permisos del documento.

Ejemplo de estructura esperada:
```sql
-- INSERT roles
INSERT INTO roles (name, description) VALUES
('USUARIO_FINAL', 'Usuario final de la aplicación móvil'),
('NEGOCIO_STAFF', 'Empleado/Proveedor del servicio'),
-- ... resto

-- INSERT permissions
INSERT INTO permissions (name, module, action, description) VALUES
('perfil.create', 'perfil', 'create', 'Crear perfil propio'),
('perfil.read', 'perfil', 'read', 'Ver perfil propio'),
-- ... resto según matriz

-- INSERT role_permissions (mapeo exacto de la tabla del documento)
-- Ejemplo: USUARIO_FINAL tiene CRUD en su propio perfil
INSERT INTO role_permissions (role_id, permission_id) 
SELECT r.id, p.id FROM roles r, permissions p 
WHERE r.name = 'USUARIO_FINAL' AND p.name IN ('perfil.create', 'perfil.read', 'perfil.update', 'perfil.delete');
-- ... resto de mapeos
```

#### 3.3 Queries de Ejemplo
Proporcionar 3-5 queries SQL que demuestren:
```sql
-- 1. Verificar si usuario tiene permiso específico en un negocio
SELECT EXISTS (
    SELECT 1 FROM business_user_roles bur
    JOIN role_permissions rp ON bur.role_id = rp.role_id
    JOIN permissions p ON rp.permission_id = p.id
    WHERE bur.user_id = ? 
      AND (bur.business_id = ? OR bur.business_id IS NULL)
      AND p.name = ?
) AS has_permission;

-- 2. Obtener todos los permisos de un usuario en un negocio
-- 3. Listar usuarios con rol específico en un negocio
-- etc.
```

### PARTE 3: Migraciones Laravel
Proporcionar ejemplo de migración Laravel equivalente para:
1. **2-3 tablas principales** (ej: businesses, appointments)
2. **Sistema RBAC completo** (roles, permissions, role_permissions, business_user_roles)

Incluir:
- Métodos `up()` y `down()`
- Definición de foreign keys con `constrained()` y `cascadeOnDelete()`
- Índices con `index()` y `unique()`
- Uso de `morphs()` si aplica para tablas polimórficas

### PARTE 3B: Seeder Laravel para RBAC
Proporcionar clase seeder completa que:
```php
class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        // 1. Crear los 5 roles
        // 2. Crear todos los permisos según matriz
        // 3. Asignar permisos a roles según documento
    }
}
```

### PARTE 4: Consideraciones de Escalabilidad

### PARTE 4: Consideraciones de Escalabilidad
- Estrategia de particionamiento futuro (mencionada en Sección 14 - R5)
- Campos que podrían necesitar archivado histórico
- Recomendaciones de mantenimiento

---

## RESTRICCIONES IMPORTANTES

⚠️ **CRÍTICO**: 
1. **NO modificar** la estructura de entidades definida en la Sección 4 del documento
2. **NO agregar** entidades no mencionadas sin justificación explícita
3. **Respetar** las relaciones descritas en "Relaciones Clave"
4. **Mantener coherencia** con las Historias de Usuario (Sección 3) - los campos deben soportar todos los criterios de aceptación
5. **Seguir** las recomendaciones técnicas de la Sección 6 (PostgreSQL, columnas JSON para campos personalizados)

## PREGUNTAS A RESOLVER DURANTE EL DISEÑO

Si encuentras ambigüedades en el documento, por favor:
1. Propón una solución basada en mejores prácticas
2. Marca claramente con `[DECISIÓN DE DISEÑO]` las elecciones que hagas
3. Ofrece alternativas cuando sea pertinente

Ejemplo de áreas que podrían requerir decisiones:
- ¿Los empleados tienen tabla de usuarios separada o son un user_type?
- ¿Cómo se almacenan los diferentes tipos de admin (platform vs business)?
- ¿Necesitamos tabla de `password_resets` o la maneja Laravel por defecto?
- ¿Implementamos soft deletes en todas las tablas o solo algunas?
- **[RBAC]** ¿Usamos sistema custom o adaptamos Spatie Permission?
- **[RBAC]** ¿Los roles son globales o pueden tener configuraciones personalizadas por negocio?
- **[RBAC]** ¿Necesitamos permisos directos a usuarios (user_permissions) o solo vía roles?

---

## ENTREGABLE FINAL

Documento técnico completo con:
1. **Diagrama conceptual** (descripción textual clara de relaciones)
2. **Scripts SQL completos** para PostgreSQL 14+
3. **Scripts SQL de RBAC** (tablas + seeders con matriz completa de permisos)
4. **Ejemplos de migraciones Laravel** (2-3 tablas principales + sistema RBAC)
5. **Seeder Laravel** para roles y permisos pre-configurados
6. **Guía de índices** con justificación
7. **Queries de ejemplo** para verificación de permisos multi-tenant
8. **Checklist de validación** contra los requisitos del documento arquitectónico

**Estructura de documento esperada**:
```
1. DIAGRAMA ERD
2. ENUMS Y TIPOS PERSONALIZADOS
3. TABLAS PRINCIPALES (SQL)
4. SISTEMA RBAC (SQL + Seeders)
5. ÍNDICES Y CONSTRAINTS
6. MIGRACIONES LARAVEL
7. SEEDER LARAVEL RBAC
8. QUERIES DE VALIDACIÓN
9. CONSIDERACIONES DE ESCALABILIDAD
10. CHECKLIST DE CUMPLIMIENTO
```

---

## ARCHIVO ADJUNTO

[Adjuntar: ARQUITECTURA DE PLATAFORMA SAAS MULTI-TENANT PARA CITAS EMPRESARIALES.pdf]

---

**Nota final**: Este diseño será la base de todo el proyecto. La consistencia con el documento arquitectónico es fundamental para evitar refactoring costoso en fases posteriores.