# CitasEmpresariales - Plataforma SaaS Multi-Tenant

Sistema de gestión de citas empresariales que conecta usuarios finales con negocios de servicios (peluquerías, clínicas, talleres, etc.).

## Stack Tecnológico

- **Backend + Frontend Web**: Laravel 12+ + MariaDB 11.4.9
- **Frontend Web**: Laravel Blade + Livewire 3.x + Alpine.js 3.x + Tailwind CSS
- **App Móvil**: Flutter 3.x (planeado)
- **Multi-Tenancy**: Single Database con `business_id`
- **Autenticación Web**: Laravel Breeze (sessions)
- **Autenticación API**: Laravel Sanctum (tokens)

## Estado Actual: Sprint 1 - Fundación

### Fase 0 (Diseño) ✅
- Documentación arquitectónica completa en `/docs`
- Especificaciones de base de datos en `/database`
- Motor de disponibilidad pseudocódigo documentado

### Fase 1 (Implementación) 🚧 EN CURSO
- ✅ Estructura Laravel 12 inicializada
- ⏳ Migraciones base de datos
- ⏳ Modelos con Global Scopes
- ⏳ Sistema RBAC
- ⏳ Seeders con datos realistas

## Inicio Rápido

### Desarrollo Local (Docker)

```bash
# 1. Instalar dependencias
make install
# O manualmente:
composer install
npm install

# 2. Configurar entorno
cp .env.example .env
php artisan key:generate

# 3. Iniciar Docker (BD + servicios)
cd docker
docker-compose up -d

# 4. Ejecutar migraciones
php artisan migrate:fresh --seed

# 5. Iniciar dev server
npm run dev
```

Accesos locales:
- **App**: http://localhost:8080
- **MailHog**: http://localhost:8025
- **MariaDB**: localhost:3307

### Producción (Neubox Tellit)

El deploy a producción se hace vía **FTP/cPanel** (sin Docker).

```bash
# Build assets
npm run build

# Subir vía FTP:
# - /app, /bootstrap, /config, /database, /public, /resources, /routes
# - /vendor (después de composer install en servidor)
# - .env (configurado con credenciales cPanel)
```

Ver [DESARROLLO_VS_PRODUCCION.md](DESARROLLO_VS_PRODUCCION.md) para diferencias detalladas.

## Estructura del Proyecto

```
CitasEmpresariales/
├── app/                    # Código fuente Laravel
│   ├── Http/
│   ├── Models/             # Eloquent models con Global Scopes
│   ├── Livewire/           # Componentes Livewire
│   ├── Services/           # Lógica de negocio (AvailabilityService)
│   └── Policies/           # Autorización RBAC
├── database/
│   ├── migrations/         # Migraciones versionadas
│   ├── seeders/            # Datos de prueba
│   ├── factories/          # Factories para testing
│   ├── schemas/            # ERD y diagramas
│   └── sql/                # Scripts MariaDB
├── docker/                 # SOLO desarrollo local
│   ├── docker-compose.yml
│   └── README.md
├── docs/                   # Documentación arquitectónica
│   ├── motor_disponibilidad_pseudocodigo.md
│   └── plan_desarrollo_base_datos.md
├── public/                 # Punto de entrada web
├── resources/
│   ├── views/              # Blade templates
│   ├── css/                # Tailwind CSS
│   └── js/                 # Alpine.js + Axios
├── routes/
│   ├── web.php             # Rutas web (sessions)
│   └── api.php             # Rutas API (Sanctum)
└── tests/                  # Tests PHPUnit
```

## Comandos Útiles

```bash
# Desarrollo
make dev                    # Vite HMR en localhost:5173
make test                   # PHPUnit tests
make migrate                # Ejecutar migraciones
make fresh                  # Reset BD + seeders
make clean                  # Limpiar cache

# Laravel Artisan
php artisan migrate:fresh --seed
php artisan db:seed --class=RolesAndPermissionsSeeder
php artisan test --filter=AvailabilityTest
php artisan route:list
php artisan tinker

# Docker
cd docker
docker-compose up -d        # Iniciar servicios
docker-compose down         # Detener
docker-compose logs -f      # Ver logs
```

## Multi-Tenancy

Todas las tablas con `business_id` usan **Global Scopes**:

```php
// Automático - filtra por tenant actual
$services = Service::all();

// Explícito - buena práctica
$services = Service::where('business_id', $businessId)->get();
```

## Sistema RBAC

5 roles jerárquicos con 26 permisos granulares:

- `USUARIO_FINAL`: Solo ver sus propias citas
- `NEGOCIO_STAFF`: Empleado, agenda asignada
- `NEGOCIO_MANAGER`: Gerente de sucursal
- `NEGOCIO_ADMIN`: Admin completo del negocio
- `PLATAFORMA_ADMIN`: Superadmin sin filtros

Ver [database/documentation/01_mapeo_matriz_permisos_rbac.md](database/documentation/01_mapeo_matriz_permisos_rbac.md)

## Documentación

- **Motor de Disponibilidad**: [docs/motor_disponibilidad_pseudocodigo.md](docs/motor_disponibilidad_pseudocodigo.md)
- **Plan de Desarrollo**: [docs/plan_desarrollo_base_datos.md](docs/plan_desarrollo_base_datos.md)
- **ERD Conceptual**: [database/schemas/01_diagrama_erd_conceptual.md](database/schemas/01_diagrama_erd_conceptual.md)
- **ENUMs MariaDB**: [database/sql/00_especificacion_enums.md](database/sql/00_especificacion_enums.md)
- **Índices Críticos**: [database/documentation/02_especificacion_indices.md](database/documentation/02_especificacion_indices.md)
- **Copilot Instructions**: [.github/copilot-instructions.md](.github/copilot-instructions.md)

## Seguridad

- ✅ Multi-tenant isolation con Global Scopes
- ✅ Lock pessimista para prevenir doble booking
- ✅ RBAC completo en políticas y middleware
- ✅ Validación input con Form Requests
- ✅ Sanctum tokens con expiración 24h
- ✅ Password hashing Bcrypt cost 10+
- ✅ Rate limiting en endpoints públicos

## Testing

```bash
php artisan test                           # Todos los tests
php artisan test --filter=MultiTenantTest # Multi-tenancy
php artisan test --filter=RBACTest        # Permisos
php artisan test --parallel               # Paralelizado
```

## Contribución

Este es un proyecto en desarrollo activo. Ver [docs/plan_desarrollo_base_datos.md](docs/plan_desarrollo_base_datos.md) para roadmap completo.

**Sprint Actual**: Sprint 1 - Fundación (Semanas 1-2)

## Licencia

Propietario - Karol Delgado © 2024
