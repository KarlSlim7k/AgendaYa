# AgendaYa- Plataforma SaaS Multi-Tenant

Sistema de gestión de citas empresariales que conecta usuarios finales con negocios de servicios (peluquerías, clínicas, talleres, etc.).

## Stack Tecnológico

- **Backend + Frontend Web**: Laravel 12+ + MariaDB 11.4.9
- **Frontend Web**: Laravel Blade + Livewire 3.x + Alpine.js 3.x + Tailwind CSS
- **App Móvil**: Flutter 3.x (planeado)
- **Multi-Tenancy**: Single Database con `business_id`
- **Autenticación Web**: Laravel Breeze (sessions)
- **Autenticación API**: Laravel Sanctum (tokens)

# AgendaYa - Plataforma SaaS Multi-Tenant

Plataforma completa de gestión de citas empresariales con panel web administrativo y APIs robustas. Conecta usuarios finales con negocios de servicios (peluquerías, clínicas, talleres, etc.) mediante un sistema multi-tenant seguro y escalable.

## Stack Tecnológico

- **Backend + Frontend Web**: Laravel 12+ + MariaDB 11.4.9
- **Frontend Web**: Laravel Blade + Livewire 3.x + Alpine.js 3.x + Tailwind CSS
- **App Móvil**: Flutter 3.x (planeado)
- **Multi-Tenancy**: Single Database con `business_id`
- **Autenticación Web**: Laravel Breeze (sessions)
- **Autenticación API**: Laravel Sanctum (tokens)

## Estado Actual

### Sprint 1: Fundación - ✅ COMPLETADO
- Infraestructura Laravel 12 + MariaDB 11.4.9
- Sistema RBAC completo con 5 roles y 26 permisos
- Base de datos con estructura FASE 1 (17 tablas)
- Modelos Eloquent con Global Scopes multi-tenant
- Autenticación y autorización implementadas
- Docker para desarrollo local
- Datos de prueba realistas (5 negocios, 11 sucursales, 35 usuarios)

### Sprint 2: API Core - ✅ COMPLETADO
- API endpoints completos para Servicios, Empleados y Horarios
- Form Requests con validaciones RBAC
- API Resources para transformación de datos
- Controllers API con lógica CRUD
- Tests completos (68 tests, 530 aserciones)
- Endpoint de disponibilidad pública

### Sprint 3: Frontend Web - ✅ COMPLETADO
- Panel web completo con 8 componentes Livewire
- Gestión de citas: listado, creación y detalles
- Dashboard con métricas y KPIs en tiempo real
- Gestión de horarios semanales y excepciones
- Motor de disponibilidad integrado
- Tests completos (105 tests, 613 aserciones)

### Sprint 4: Web Panel & Notificaciones - ✅ COMPLETADO
- CRUD completo: Servicios, Empleados, Horarios
- Perfil de negocio editable
- Sistema de notificaciones por email automático
- Reportes y analytics avanzados
- Modales de confirmación y UX mejorada
- Tests completos (153 tests, 891 aserciones)

### Próximo: Sprint 5 - App Móvil
- Desarrollo de app Flutter para usuarios finales
- Integración con APIs existentes
- Autenticación móvil con Sanctum
- Reserva de citas desde dispositivo móvil
- Notificaciones push (planeado)

## Estructura del Proyecto

```
AgendaYa/
├── app/                    # Código fuente Laravel
│   ├── Http/               # Controllers, Requests, Resources
│   ├── Models/             # Eloquent models con Global Scopes
│   ├── Livewire/           # 20+ componentes funcionales
│   │   ├── Appointments/   # Gestión de citas
│   │   ├── Dashboard/      # Panel de métricas
│   │   ├── Services/       # CRUD servicios
│   │   ├── Employees/      # CRUD empleados
│   │   ├── Schedule/       # Gestión de horarios
│   │   ├── Business/       # Perfil de negocio
│   │   └── Reports/        # Reportes y analytics
│   ├── Services/           # Lógica de negocio (AvailabilityService)
│   ├── Policies/           # Autorización RBAC
│   └── Notifications/      # Sistema de emails
├── database/
│   ├── migrations/         # Migraciones versionadas
│   ├── seeders/            # Datos de prueba
│   ├── factories/          # Factories para testing
│   ├── schemas/            # ERD y diagramas
│   └── sql/                # Scripts MariaDB
├── docs/                   # Documentación arquitectónica
│   ├── motor_disponibilidad_pseudocodigo.md
│   ├── plan_desarrollo_base_datos.md
│   └── progreso_sprints/   # Avance por sprints
├── public/                 # Punto de entrada web
├── resources/
│   ├── views/              # Blade templates + Livewire
│   │   ├── livewire/       # 20+ vistas de componentes
│   │   ├── auth/           # Vistas de autenticación
│   │   └── layouts/        # Layouts principales
│   ├── css/                # Tailwind CSS
│   └── js/                 # Alpine.js
├── routes/
│   ├── web.php             # Rutas web (sessions)
│   └── api.php             # Rutas API (Sanctum)
└── tests/                  # Tests PHPUnit (326 tests total)
```

## Documentación

- **Motor de Disponibilidad**: [docs/motor_disponibilidad_pseudocodigo.md](docs/motor_disponibilidad_pseudocodigo.md)
- **Plan de Desarrollo**: [docs/plan_desarrollo_base_datos.md](docs/plan_desarrollo_base_datos.md)
- **ERD Conceptual**: [database/schemas/01_diagrama_erd_conceptual.md](database/schemas/01_diagrama_erd_conceptual.md)
- **Progreso por Sprints**: [docs/progreso_sprints/](docs/progreso_sprints/)
- **Copilot Instructions**: [.github/copilot-instructions.md](.github/copilot-instructions.md)

## Características Principales

### ✅ Panel Web Administrativo
- **Dashboard con métricas en tiempo real**: KPIs, gráficos y tendencias
- **Gestión completa de citas**: Creación, listado, estados y detalles
- **CRUD de servicios y empleados**: Con validaciones y sincronización
- **Sistema de horarios flexibles**: Plantillas semanales + excepciones
- **Reportes avanzados**: Analytics y exportación de datos

### ✅ APIs Robustas
- **Motor de disponibilidad inteligente**: 7 reglas de negocio
- **Prevención de doble booking**: Locks pesimistas
- **Sistema RBAC completo**: 5 roles, 26 permisos granulares
- **Multi-tenancy seguro**: Aislamiento por `business_id`

### ✅ Notificaciones Automáticas
- **Emails de confirmación**: Inmediatos al crear cita
- **Recordatorios programados**: 24h y 1h antes
- **Cancelaciones**: Con motivos y opciones de reprogramación
- **Logging completo**: Tracking de envíos y estados

### ✅ Testing Exhaustivo
- **326 tests totales**: 2,035 aserciones
- **Cobertura completa**: CRUD, RBAC, multi-tenancy, concurrencia
- **Tests de integración**: APIs, componentes Livewire, notificaciones

## Arquitectura

### Multi-Tenancy
Todas las tablas con `business_id` usan **Global Scopes** automáticos para aislamiento de datos por tenant.

### Sistema RBAC
5 roles jerárquicos con 26 permisos granulares:
- `USUARIO_FINAL`: Usuario móvil, solo citas propias
- `NEGOCIO_STAFF`: Empleado, agenda asignada
- `NEGOCIO_MANAGER`: Gerente de sucursal
- `NEGOCIO_ADMIN`: Admin completo del negocio
- `PLATAFORMA_ADMIN`: Superadmin sin restricciones

## Licencia

Propietario - DevelomentGroup7k © 2024
