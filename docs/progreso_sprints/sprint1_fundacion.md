# Sprint 1: Fundación - COMPLETADO ✅

**Fecha inicio**: 17 de enero de 2026  
**Fecha fin**: 17 de enero de 2026  
**Estado**: ✅ 100% COMPLETADO  
**Duración**: 1 día

---

## Objetivos del Sprint

Establecer la fundación del sistema multi-tenant con:
- Arquitectura Laravel 12 + MariaDB 11.4.9
- Sistema RBAC completo
- Base de datos con estructura FASE 1
- Autenticación y autorización
- Docker para desarrollo local
- Datos de prueba realistas

---

## Logros Completados

### 1. Infraestructura y Ambiente (✅)

**Docker Aislado**
- MariaDB 11.4.9 (puerto 3307)
- PHP 8.4-FPM (requerido por Laravel 12)
- Nginx Alpine (puerto 8081)
- Redis 7 (puerto 6380)
- MailHog (puertos 1025/8025)

**Comandos Docker:**
```bash
cd docker
docker compose up -d
docker compose down
docker compose logs -f
```

**Laravel 12 + Dependencias**
- Composer: 112 paquetes instalados
- NPM: 115 paquetes instalados
- Livewire 3.7.4
- Laravel Breeze 2.3.8 (Blade + Dark Mode)
- Laravel Sanctum 4.2.3
- Tailwind CSS 4.1.12
- Alpine.js 3.x
- Vite 5.4.21

---

### 2. Base de Datos (✅)

**10 Migraciones FASE 1 ejecutadas:**

| # | Migración | Tablas Creadas | Descripción |
|---|-----------|----------------|-------------|
| 1 | `0001_fase1_create_platform_admins_table` | platform_admins | Superadmins globales |
| 2 | `0002_fase1_create_platform_settings_table` | platform_settings | Configuraciones globales |
| 3 | `0003_fase1_create_users_table` | users, sessions, password_reset_tokens | Usuarios finales + auth |
| 4 | `0004_fase1_create_businesses_table` | businesses | Negocios/Tenants |
| 5 | `0005_fase1_create_business_locations_table` | business_locations | Sucursales multi-tenant |
| 6 | `0006_fase1_create_roles_table` | roles | 5 roles RBAC |
| 7 | `0007_fase1_create_permissions_table` | permissions | 26 permisos granulares |
| 8 | `0008_fase1_create_role_permissions_table` | role_permissions | Matriz RBAC |
| 9 | `0009_fase1_create_business_user_roles_table` | business_user_roles | Asignaciones multi-tenant |
| 10 | `0010_fase1_create_cache_and_jobs_tables` | cache, cache_locks, jobs, job_batches, failed_jobs | Auxiliares Laravel |

**Total: 17 tablas, 0.92 MB**

**Comando para reset:**
```bash
php artisan migrate:fresh --seed
```

---

### 3. Modelos Eloquent (✅)

**8 Modelos con Global Scopes multi-tenant:**

| Modelo | Archivo | Características |
|--------|---------|-----------------|
| User | `app/Models/User.php` | MustVerifyEmail, Notifiable, SoftDeletes, HasApiTokens |
| Business | `app/Models/Business.php` | Tenant principal, SoftDeletes |
| BusinessLocation | `app/Models/BusinessLocation.php` | Global Scope business_id, SoftDeletes |
| Role | `app/Models/Role.php` | 5 roles jerárquicos |
| Permission | `app/Models/Permission.php` | 26 permisos módulo.acción |
| BusinessUserRole | `app/Models/BusinessUserRole.php` | Pivot multi-tenant, Global Scope |
| PlatformAdmin | `app/Models/PlatformAdmin.php` | Superadmin sin restricciones |
| PlatformSetting | `app/Models/PlatformSetting.php` | Configuraciones globales |

**Características clave:**
- Global Scopes automáticos para multi-tenancy
- SoftDeletes en tablas críticas
- Relationships completas
- Métodos helper para RBAC (`hasRoleInBusiness`, `hasPermissionInBusiness`)

---

### 4. Sistema RBAC (✅)

**5 Roles Jerárquicos:**

| Rol | Nivel | Permisos | Descripción |
|-----|-------|----------|-------------|
| USUARIO_FINAL | 0 | 9 | Usuario móvil, solo citas propias |
| NEGOCIO_STAFF | 1 | 7 | Empleado, agenda asignada |
| NEGOCIO_MANAGER | 2 | 15 | Gerente sucursal |
| NEGOCIO_ADMIN | 3 | 23 | Admin del negocio completo |
| PLATAFORMA_ADMIN | 4 | 26 | Superadmin sin restricciones |

**26 Permisos en 7 Módulos:**

| Módulo | Permisos | Formato |
|--------|----------|---------|
| perfil | 3 | perfil.create, perfil.read, perfil.update |
| negocio | 4 | negocio.{create,read,update,delete} |
| sucursal | 4 | sucursal.{create,read,update,delete} |
| servicio | 4 | servicio.{create,read,update,delete} |
| empleado | 4 | empleado.{create,read,update,delete} |
| agenda | 2 | agenda.create, agenda.read |
| cita | 4 | cita.{create,read,update,delete} |
| reportes | 1 | reportes.read |

**Total asignaciones: 80**

**Comando para verificar RBAC:**
```bash
php artisan tinker --execute="
\$roles = \App\Models\Role::with('permissions')->orderBy('nivel_jerarquia')->get();
\$roles->each(function(\$role) {
    echo PHP_EOL . \$role->nombre . ' (nivel ' . \$role->nivel_jerarquia . '): ' . \$role->permissions->count() . ' permisos' . PHP_EOL;
    \$role->permissions->groupBy('modulo')->each(function(\$perms, \$modulo) {
        echo '  ' . \$modulo . ': ' . \$perms->pluck('accion')->join(', ') . PHP_EOL;
    });
});
"
```

---

### 5. Factories y Seeders (✅)

**4 Factories con datos mexicanos:**
- `UserFactory` - Nombres/apellidos mexicanos, teléfonos +52
- `BusinessFactory` - Negocios con RFC válido, categorías variadas
- `BusinessLocationFactory` - Direcciones CDMX reales, coordenadas GPS
- `PlatformAdminFactory` - Superadmins

**6 Seeders orquestados:**
1. `RolesAndPermissionsSeeder` - RBAC completo
2. `PlatformAdminSeeder` - 1 superadmin
3. `BusinessSeeder` - 5 negocios variados
4. `BusinessLocationSeeder` - 11 sucursales distribuidas
5. `UserSeeder` - 35 usuarios (10 específicos + 25 generados)
6. `BusinessUserRoleSeeder` - 10 asignaciones de roles

**Comando para re-seed:**
```bash
php artisan db:seed
```

---

### 6. Datos de Prueba (✅)

**Negocios creados (5 total):**

| Negocio | Categoría | Estado | Sucursales | RFC |
|---------|-----------|--------|------------|-----|
| Estilos Modernos | peluqueria | approved | 3 | EMO010101ABC |
| Clínica San Rafael | clinica | approved | 4 | CSR020202DEF |
| Taller Mecánico Rodríguez | taller_mecanico | approved | 2 | TMR030303GHI |
| Spa Relax Total | spa | approved | 2 | SRT040404JKL |
| Consultorio Dr. Pérez | consultorio | pending | 0 | CDP050505MNO |

**Sucursales (11 total, 10 activas):**
- **Estilos Modernos**: Polanco (matriz), Roma, Condesa
- **Clínica San Rafael**: Centro, Del Valle, Narvarte, Santa Fe (inactiva)
- **Taller Rodríguez**: Matriz Agrícola Oriental, Tlalpan
- **Spa Relax**: Interlomas, Coyoacán

**Usuarios (35 total):**
- 10 específicos: Carlos Martínez, María García, José Rodríguez, Ana López, Luis González, Laura Hernández, Miguel Díaz, Sofía Morales, Pedro Jiménez, Isabel Mendoza
- 25 generados con Factory (nombres mexicanos aleatorios)

**Asignaciones de roles (10):**
- 4 NEGOCIO_ADMIN (1 por negocio aprobado)
- 2 NEGOCIO_MANAGER (Estilos Modernos, Clínica San Rafael)
- 4 NEGOCIO_STAFF (2 en Taller, 2 en Spa)
- 25 usuarios restantes sin rol asignado (USUARIO_FINAL implícito)

---

## Credenciales de Acceso

### Superadmin de Plataforma
```
Email: admin@citasempresariales.com
Password: password
Rol: PLATAFORMA_ADMIN (acceso total)
```

### Usuarios con Roles de Negocio
```
# NEGOCIO_ADMIN de Estilos Modernos
Email: carlos.martinez@example.com
Password: password
Rol: NEGOCIO_ADMIN en negocio_id=1

# NEGOCIO_ADMIN de Clínica San Rafael
Email: maria.garcia@example.com
Password: password
Rol: NEGOCIO_ADMIN en negocio_id=2

# NEGOCIO_MANAGER de Estilos Modernos
Email: luis.gonzalez@example.com
Password: password
Rol: NEGOCIO_MANAGER en negocio_id=1

# NEGOCIO_STAFF de Taller Rodríguez
Email: miguel.diaz@example.com
Password: password
Rol: NEGOCIO_STAFF en negocio_id=3
```

### Usuarios Finales (sin roles asignados)
```
Cualquiera de los 35 usuarios / password
Ejemplos: ana.lopez@example.com, pedro.jimenez@example.com
```

---

## Testing (✅)

**29 tests pasando (76 assertions):**
- ✅ Tests de autenticación (4)
- ✅ Tests de verificación de email (3)
- ✅ Tests de confirmación de password (3)
- ✅ Tests de reset de password (4)
- ✅ Tests de actualización de password (2)
- ✅ Tests de registro (2)
- ✅ Tests de perfil (5)
- ✅ Tests de sistema básico (5)
- ✅ Test unitario ejemplo (1)

**Comando para ejecutar tests:**
```bash
php artisan test
php artisan test --filter=BasicSystemTest
php artisan test --filter=Auth
```

---

## Estructura de Archivos Clave

```
CitasEmpresariales/
├── app/
│   ├── Models/                     # 8 modelos Eloquent
│   │   ├── User.php
│   │   ├── Business.php
│   │   ├── BusinessLocation.php
│   │   ├── Role.php
│   │   ├── Permission.php
│   │   ├── BusinessUserRole.php
│   │   ├── PlatformAdmin.php
│   │   └── PlatformSetting.php
│   └── Http/
│       └── Controllers/
│           └── Auth/               # Breeze controllers
├── database/
│   ├── migrations/                 # 10 migraciones FASE 1
│   ├── factories/                  # 4 factories
│   ├── seeders/                    # 6 seeders + DatabaseSeeder
│   ├── documentation/              # Especificaciones técnicas
│   └── schemas/                    # Diagramas ERD
├── resources/
│   ├── views/
│   │   ├── auth/                   # Vistas Breeze (nombre + apellidos)
│   │   └── profile/                # Vistas de perfil
│   ├── css/
│   │   └── app.css                 # Tailwind CSS
│   └── js/
│       └── app.js                  # Alpine.js + Vite
├── tests/
│   ├── Feature/                    # 28 tests de características
│   └── Unit/                       # 1 test unitario
└── docker/                         # Ambiente desarrollo aislado
    ├── Dockerfile                  # PHP 8.4-FPM
    ├── docker-compose.yml          # 5 servicios
    └── nginx/
        └── default.conf
```

---

## Comandos Útiles

### Docker
```bash
# Iniciar ambiente
cd docker && docker compose up -d

# Ver logs
docker compose logs -f

# Parar ambiente
docker compose down

# Rebuild PHP (si cambias Dockerfile)
docker compose build php
docker compose up -d
```

### Laravel
```bash
# Instalar dependencias
composer install
npm install

# Generar key
php artisan key:generate

# Migraciones
php artisan migrate
php artisan migrate:fresh --seed

# Ver base de datos
php artisan db:show
php artisan db:table users

# Testing
php artisan test
php artisan test --coverage

# Limpiar cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

### Tinker (exploración)
```bash
# Ver datos
php artisan tinker --execute="\App\Models\User::count()"
php artisan tinker --execute="\App\Models\Business::with('locations')->get()"

# Verificar RBAC
php artisan tinker --execute="\$user = \App\Models\User::first(); echo \$user->hasPermissionInBusiness('cita.create', 1);"
```

### NPM
```bash
# Desarrollo (con hot reload)
npm run dev

# Producción
npm run build

# Watch mode
npm run watch
```

---

## Acceso a la Aplicación

**URL**: http://localhost:8081

**Páginas disponibles:**
- `/` - Homepage con branding CitasEmpresariales
- `/login` - Página de login
- `/register` - Registro de usuarios (nombre + apellidos)
- `/dashboard` - Dashboard (requiere autenticación)
- `/profile` - Perfil de usuario

**API Endpoints (Breeze):**
- `POST /login` - Autenticar usuario
- `POST /register` - Crear usuario
- `POST /logout` - Cerrar sesión
- `GET /api/user` - Usuario autenticado (Sanctum)

---

## Estado de Base de Datos

```sql
-- Resumen de datos (17 tablas, 0.92 MB)
SELECT 'Roles' as tabla, COUNT(*) as registros FROM roles
UNION ALL SELECT 'Permisos', COUNT(*) FROM permissions
UNION ALL SELECT 'Asignaciones RBAC', COUNT(*) FROM role_permissions
UNION ALL SELECT 'Platform Admins', COUNT(*) FROM platform_admins
UNION ALL SELECT 'Negocios', COUNT(*) FROM businesses
UNION ALL SELECT 'Sucursales', COUNT(*) FROM business_locations
UNION ALL SELECT 'Usuarios', COUNT(*) FROM users
UNION ALL SELECT 'Roles Asignados', COUNT(*) FROM business_user_roles;
```

**Resultado esperado:**
| Tabla | Registros |
|-------|-----------|
| Roles | 5 |
| Permisos | 26 |
| Asignaciones RBAC | 80 |
| Platform Admins | 1 |
| Negocios | 5 |
| Sucursales | 11 |
| Usuarios | 35 |
| Roles Asignados | 10 |

---

## Próximos Pasos (Sprint 2)

### FASE 2: Core Negocio
Estimado: 2 semanas

**Módulos a implementar:**
1. **Servicios** (services)
   - CRUD completo con Global Scope
   - Factory y seeder (20-30 servicios variados)
   - Relación con sucursales
   - Custom fields (meta JSON)

2. **Empleados** (employees)
   - CRUD completo
   - Relación con usuarios (opcional)
   - Asignación a servicios (employee_services)
   - Seeder (15-20 empleados)

3. **Horarios** (schedule_templates, schedule_exceptions)
   - Plantillas de horarios por sucursal
   - Excepciones (feriados, vacaciones)
   - Seeder con horarios realistas

4. **Motor de Disponibilidad**
   - Generación de slots disponibles
   - Aplicación de 7 reglas de negocio
   - Tests de disponibilidad
   - Cache con Redis (5 min)

**Entregables Sprint 2:**
- 4 migraciones nuevas
- 3 modelos adicionales
- 3 factories
- Motor de disponibilidad funcional
- API endpoints para slots
- 15+ tests de disponibilidad

---

## Notas Técnicas

### Multi-Tenancy
- **Estrategia**: Single Database con `business_id`
- **Implementación**: Global Scopes en modelos
- **Validación**: Siempre verificar `business_id` en mutations
- **Excepciones**: Users, Roles, Permissions son globales

### RBAC
- **Sistema custom** (no Spatie)
- **Permisos**: Formato `módulo.acción`
- **Validación**: `hasPermissionInBusiness($permission, $businessId)`
- **Scopes adicionales**: location_id para MANAGER, employee_id para STAFF

### Autenticación
- **Web**: Laravel Breeze (session-based)
- **API Móvil**: Laravel Sanctum (Bearer tokens)
- **Verificación**: Email verification obligatoria para usuarios finales

### Frontend
- **Stack**: Blade + Livewire 3 + Alpine.js 3 + Tailwind CSS 4
- **Build**: Vite 5.4.21 (1.09s build time)
- **Assets**: 32.50 KB CSS, 81.91 KB JS
- **Dark Mode**: Habilitado en Breeze

### Database
- **Motor**: MariaDB 11.4.9
- **Collation**: utf8mb4_unicode_ci
- **Timezone**: America/Mexico_City
- **Backup**: Diario recomendado (mysqldump)

---

## Métricas del Sprint

| Métrica | Valor |
|---------|-------|
| **Duración** | 1 día |
| **Archivos creados** | 45+ |
| **Líneas de código** | ~3,500 |
| **Migraciones** | 10 |
| **Modelos** | 8 |
| **Seeders** | 6 |
| **Tests** | 29 (100% passing) |
| **Tablas BD** | 17 |
| **Registros seed** | 168 |
| **Cobertura tests** | 76 assertions |

---

## Validación Final

**Checklist de completitud:**

- [x] Docker funcionando (5 contenedores)
- [x] Laravel 12 instalado con todas dependencias
- [x] 10 migraciones ejecutadas sin errores
- [x] 8 modelos con Global Scopes implementados
- [x] RBAC completo (5 roles + 26 permisos + 80 asignaciones)
- [x] 6 seeders generando datos coherentes
- [x] 35 usuarios + 5 negocios + 11 sucursales
- [x] Breeze autenticación funcionando
- [x] 29/29 tests pasando
- [x] Aplicación accesible en http://localhost:8081
- [x] Documentación completa en `/database/documentation`

**Estado final: ✅ SPRINT 1 COMPLETADO AL 100%**

---

## Contacto y Soporte

**Repositorio**: KarlSlim7k/CitasEmpresariales  
**Branch**: main  
**Documentación**: `/docs` y `/database/documentation`  
**Issues**: Crear issue en GitHub para bugs o mejoras

---

*Documento generado: 17 de enero de 2026*  
*Última actualización: Sprint 1 completado*
