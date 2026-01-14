# Base de Datos - Citas Empresariales SaaS

## Estructura de Carpetas

```
database/
├── mysql/                          # Scripts para MySQL 8.0+
│   ├── 01_create_database.sql      # Crear base de datos
│   ├── 02_create_tables.sql        # Crear todas las tablas
│   └── 03_seeders_rbac.sql         # Seeders de roles y permisos
│
├── mariadb/                        # Scripts para MariaDB 10.6+
│   ├── 01_create_database.sql      # Crear base de datos
│   ├── 02_create_tables.sql        # Crear todas las tablas
│   └── 03_seeders_rbac.sql         # Seeders de roles y permisos
│
├── postgresql/                     # Scripts para PostgreSQL 14+
│   ├── 01_create_database.sql      # Crear base de datos
│   ├── 02_create_enums.sql         # Crear ENUM types nativos
│   ├── 03_create_tables.sql        # Crear todas las tablas
│   ├── 04_seeders_rbac.sql         # Seeders de roles y permisos
│   └── 05_queries_examples.sql     # Queries de ejemplo y validación
│
├── documentation/                  # Documentación técnica
│   ├── 00_checklist_validacion_fase0.md
│   ├── 01_mapeo_matriz_permisos_rbac.md
│   └── 02_especificacion_indices.md
│
├── schemas/                        # Diagramas ERD
│   └── 01_diagrama_erd_conceptual.md
│
└── sql/                            # Especificaciones SQL
    └── 00_especificacion_enums.md
```

## Orden de Ejecución

### PostgreSQL 14+

```bash
# 1. Crear base de datos y extensiones
psql -U postgres -f postgresql/01_create_database.sql

# 2. Conectar a la base de datos y ejecutar el resto
psql -U postgres -d citas_empresariales -f postgresql/02_create_enums.sql
psql -U postgres -d citas_empresariales -f postgresql/03_create_tables.sql
psql -U postgres -d citas_empresariales -f postgresql/04_seeders_rbac.sql

# 5. (Opcional) Verificar con queries de ejemplo
psql -U postgres -d citas_empresariales -f postgresql/05_queries_examples.sql
```

### MySQL 8.0+

```bash
# 1. Crear base de datos
mysql -u root -p < mysql/01_create_database.sql

# 2. Crear tablas
mysql -u root -p citas_empresariales < mysql/02_create_tables.sql

# 3. Insertar seeders RBAC
mysql -u root -p citas_empresariales < mysql/03_seeders_rbac.sql
```

### MariaDB 10.6+

```bash
# 1. Crear base de datos
mysql -u root -p < mariadb/01_create_database.sql

# 2. Crear tablas
mysql -u root -p citas_empresariales < mariadb/02_create_tables.sql

# 3. Insertar seeders RBAC
mysql -u root -p citas_empresariales < mariadb/03_seeders_rbac.sql
```

## Diferencias entre Motores

| Característica | PostgreSQL | MySQL/MariaDB |
|----------------|------------|---------------|
| **ENUM Types** | Tipos nativos con CREATE TYPE | ENUM inline en columnas |
| **JSON** | JSONB (indexable con GIN) | JSON (indexable limitado) |
| **AUTO_INCREMENT** | BIGSERIAL | BIGINT UNSIGNED AUTO_INCREMENT |
| **CHECK Constraints** | Completamente soportados | MySQL 8.0+, MariaDB siempre |
| **Partial Indexes** | Soportados (WHERE clause) | No soportados |
| **Triggers updated_at** | Función + Trigger | ON UPDATE CURRENT_TIMESTAMP |
| **Collation** | UTF8/es_MX.UTF-8 | utf8mb4_unicode_ci |

## Tablas del Sistema

### Contexto de Plataforma (sin business_id)
- `users` - Usuarios globales
- `platform_admins` - Administradores de plataforma
- `platform_settings` - Configuración global
- `roles` - Roles del sistema
- `permissions` - Permisos granulares
- `role_permissions` - Asignación rol-permiso

### Contexto de Negocio (con business_id - Multi-Tenant)
- `businesses` - Negocios/Tenants
- `business_locations` - Sucursales
- `services` - Servicios ofrecidos
- `employees` - Empleados
- `employee_services` - Pivote empleado-servicio
- `schedule_templates` - Horarios base
- `schedule_exceptions` - Excepciones de horario
- `appointments` - Citas/Reservas
- `appointment_status_histories` - Historial de cambios
- `resources` - Recursos compartidos (Fase 2)
- `appointment_resources` - Pivote cita-recurso (Fase 2)
- `notification_logs` - Log de notificaciones (Fase 5)
- `business_user_roles` - Asignación de roles por negocio

### Contexto de Usuario
- `user_favorite_businesses` - Negocios favoritos

### Tablas de Laravel
- `password_reset_tokens` - Tokens de recuperación
- `personal_access_tokens` - Tokens de Sanctum
- `failed_jobs` - Jobs fallidos
- `jobs` - Cola de trabajos

## Sistema RBAC

### Roles del Sistema (5)
1. **USUARIO_FINAL** - Usuario de app móvil
2. **NEGOCIO_STAFF** - Empleado del negocio
3. **NEGOCIO_MANAGER** - Gerente de sucursal
4. **NEGOCIO_ADMIN** - Administrador del negocio
5. **PLATAFORMA_ADMIN** - Superadministrador

### Permisos (26)
Formato: `modulo.accion`

| Módulo | Permisos |
|--------|----------|
| perfil | create, read, update |
| negocio | create, read, update, delete |
| sucursal | create, read, update, delete |
| servicio | create, read, update, delete |
| empleado | create, read, update, delete |
| agenda | create, read |
| cita | create, read, update, delete |
| reportes_financieros | read |

## Índices Críticos

### Para Motor de Disponibilidad
- `idx_appointments_employee_fecha` - Buscar citas por empleado/fecha
- `idx_appointments_location_fecha` - Buscar citas por sucursal/fecha
- `idx_schedule_exceptions_location_fecha` - Verificar excepciones

### Para Multi-Tenancy
- `idx_*_business` - Filtrar por negocio en todas las tablas

### Para RBAC
- `idx_business_user_roles_lookup` - Verificación rápida de permisos

## Configuración de Entorno Laravel

### PostgreSQL (.env)
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=citas_empresariales
DB_USERNAME=postgres
DB_PASSWORD=your_password
```

### MySQL (.env)
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=citas_empresariales
DB_USERNAME=root
DB_PASSWORD=your_password
```

### MariaDB (.env)
```env
DB_CONNECTION=mariadb
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=citas_empresariales
DB_USERNAME=root
DB_PASSWORD=your_password
```

## Notas de Implementación

### Soft Deletes
Implementado en: `users`, `businesses`, `employees`, `appointments`, `business_locations`, `services`, `notification_logs`

### Campos JSON/JSONB
- `businesses.meta` - Metadatos del negocio
- `services.meta` - Configuración del servicio (depósitos, instrucciones)
- `appointments.custom_data` - Campos personalizados de cita
- `employees.meta` - Datos adicionales del empleado
- `resources.meta` - Configuración del recurso
- `notification_logs.meta` - Datos del envío

### Timestamps
Todas las tablas incluyen `created_at` y `updated_at` para auditoría.

## Próximos Pasos

1. **Fase 1**: Implementar migraciones Laravel
2. **Fase 2**: Implementar modelos Eloquent con Global Scopes
3. **Fase 3**: Implementar sistema RBAC en middleware
4. **Fase 4**: Implementar motor de disponibilidad
5. **Fase 5**: Implementar sistema de notificaciones

## Referencias

- [Documento de Arquitectura](../docs/db_design_prompt.md)
- [Motor de Disponibilidad](../docs/motor_disponibilidad_pseudocodigo.md)
- [Plan de Desarrollo](../docs/plan_desarrollo_base_datos.md)
