# CitasEmpresariales - Plataforma SaaS Multi-Tenant

Sistema de gestión de citas empresariales que conecta usuarios finales con negocios de servicios (peluquerías, clínicas, talleres, etc.).

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

### Próximo: Sprint 3 - Citas y Disponibilidad
- Motor de disponibilidad completo
- Gestión de citas con validación de slots
- Prevención de doble booking con locks
- Estados de citas y transiciones
- Notificaciones básicas

## Estructura del Proyecto

```
CitasEmpresariales/
├── app/                    # Código fuente Laravel
│   ├── Http/               # Controllers, Requests, Resources
│   ├── Models/             # Eloquent models con Global Scopes
│   ├── Livewire/           # Componentes Livewire
│   ├── Services/           # Lógica de negocio
│   └── Policies/           # Autorización RBAC
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
│   ├── views/              # Blade templates
│   ├── css/                # Tailwind CSS
│   └── js/                 # Alpine.js
├── routes/
│   ├── web.php             # Rutas web (sessions)
│   └── api.php             # Rutas API (Sanctum)
└── tests/                  # Tests PHPUnit
```

## Documentación

- **Motor de Disponibilidad**: [docs/motor_disponibilidad_pseudocodigo.md](docs/motor_disponibilidad_pseudocodigo.md)
- **Plan de Desarrollo**: [docs/plan_desarrollo_base_datos.md](docs/plan_desarrollo_base_datos.md)
- **ERD Conceptual**: [database/schemas/01_diagrama_erd_conceptual.md](database/schemas/01_diagrama_erd_conceptual.md)
- **Progreso por Sprints**: [docs/progreso_sprints/](docs/progreso_sprints/)
- **Copilot Instructions**: [.github/copilot-instructions.md](.github/copilot-instructions.md)

## Multi-Tenancy

Todas las tablas con `business_id` usan **Global Scopes** automáticos para aislamiento de datos por tenant.

## Sistema RBAC

5 roles jerárquicos con 26 permisos granulares:
- `USUARIO_FINAL`: Usuario móvil, solo citas propias
- `NEGOCIO_STAFF`: Empleado, agenda asignada
- `NEGOCIO_MANAGER`: Gerente de sucursal
- `NEGOCIO_ADMIN`: Admin completo del negocio
- `PLATAFORMA_ADMIN`: Superadmin sin restricciones

## Licencia

Propietario - Karol Delgado © 2024
