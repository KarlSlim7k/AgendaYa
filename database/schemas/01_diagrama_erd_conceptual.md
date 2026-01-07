# Diagrama ERD Conceptual - Plataforma SaaS de Citas Empresariales

**Versión**: 1.0  
**Fecha**: 01 de enero de 2026  
**Autor**: Equipo de Arquitectura  
**Estado**: Aprobado para FASE 1

---

## Descripción General

El modelo de datos sigue una arquitectura multi-tenant con segregación por `business_id`. Las relaciones se organizan en cuatro contextos principales:

1. **Contexto de Plataforma** (nivel raíz)
2. **Contexto de Negocio** (core de operación)
3. **Contexto de Usuarios** (global, compartido)
4. **Contexto de RBAC** (control de acceso)

---

## Contexto 1: Plataforma (Nivel Raíz)

```
┌─────────────────────────────────────────────────────────┐
│ PLATAFORMA                                              │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  ┌──────────────────────────────────────────────────┐  │
│  │ platform_admins (1)                              │  │
│  │ • id (PK)                                        │  │
│  │ • user_id (FK → users)                           │  │
│  │ • super_admin (BOOLEAN)                          │  │
│  │ • created_at, updated_at                         │  │
│  └──────────────────────────────────────────────────┘  │
│                                                         │
│  ┌──────────────────────────────────────────────────┐  │
│  │ platform_settings (1)                            │  │
│  │ • id (PK)                                        │  │
│  │ • clave (VARCHAR UNIQUE)                         │  │
│  │ • valor (JSON)                                   │  │
│  │ • updated_at                                     │  │
│  └──────────────────────────────────────────────────┘  │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

**Relaciones**:
- `platform_admins` → `users` (N:1) - Un admin es un usuario

---

## Contexto 2: Negocio (Core)

```
┌──────────────────────────────────────────────────────────────────────┐
│ NEGOCIO (TENANT)                                                     │
├──────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  ┌────────────────────────────────────────────────────────────────┐ │
│  │ businesses (1)                                                 │ │
│  │ • id (PK) ← TENANT ROOT                                        │ │
│  │ • nombre (VARCHAR UNIQUE)                                      │ │
│  │ • razon_social (VARCHAR)                                       │ │
│  │ • logo_url (VARCHAR)                                           │ │
│  │ • categoria (VARCHAR)                                          │ │
│  │ • telefono (VARCHAR)                                           │ │
│  │ • email (VARCHAR)                                              │ │
│  │ • plan (VARCHAR) → premium, standard, basic                    │ │
│  │ • estado (VARCHAR) → pending, approved, suspended              │ │
│  │ • meta (JSONB) → datos adicionales                             │ │
│  │ • created_at, updated_at, deleted_at                           │ │
│  └────────────────────────────────────────────────────────────────┘ │
│                          ╱ │ ╲                                       │
│                         ╱  │  ╲                                      │
│                        ╱   │   ╲                                     │
│                       ╱    │    ╲                                    │
│           ┌──────────┴─┐ ┌─┴─────────┐ ┌──────────────┐             │
│           │ 1:N        │ │ 1:N       │ │ 1:N          │             │
│           │            │ │           │ │              │             │
│  ┌────────▼──────────────────────┐ ┌──▼─────────────────────────┐  │
│  │ business_locations            │ │ services                   │  │
│  │ • id (PK)                     │ │ • id (PK)                  │  │
│  │ • business_id (FK) TENANT     │ │ • business_id (FK) TENANT  │  │
│  │ • nombre (VARCHAR)            │ │ • nombre (VARCHAR)         │  │
│  │ • direccion (TEXT)            │ │ • descripcion (TEXT)       │  │
│  │ • telefono (VARCHAR)          │ │ • precio (DECIMAL)         │  │
│  │ • zona_horaria (VARCHAR)      │ │ • duracion_minutos (INT)   │  │
│  │ • created_at, updated_at      │ │ • buffer_pre_minutos (INT) │  │
│  │                               │ │ • buffer_post_minutos (INT)│  │
│  │ 1:N ┌─ schedule_templates     │ │ • meta (JSONB)             │  │
│  │ │    • id (PK)                │ │ • created_at, updated_at   │  │
│  │ │    • location_id (FK)       │ │                            │  │
│  │ │    • dia_semana (INT 0-6)   │ │          │                 │  │
│  │ │    • hora_apertura (TIME)   │ │          │ 1:N             │  │
│  │ │    • hora_cierre (TIME)     │ │          │                 │  │
│  │ │    • created_at, updated_at │ │          │                 │  │
│  │ └────────────────────────────── │ ┌────────▼──────────────┐  │  │
│  │                               │ │ employee_services      │  │  │
│  │ 1:N ┌─ schedule_exceptions    │ │ (Pivote N:M)           │  │  │
│  │ │    • id (PK)                │ │ • employee_id (FK)     │  │  │
│  │ │    • location_id (FK)       │ │ • service_id (FK)      │  │  │
│  │ │    • fecha (DATE)           │ │ • created_at           │  │  │
│  │ │    • tipo (ENUM)            │ └────────────────────────┘  │  │
│  │ │    • todo_el_dia (BOOLEAN)  │                            │  │
│  │ │    • hora_inicio (TIME)     │                            │  │
│  │ │    • hora_fin (TIME)        │                            │  │
│  │ │    • created_at, updated_at │                            │  │
│  │ └────────────────────────────── │                            │  │
│  └────────────────────────────┘ └──────────────────────────────┘  │
│           │                                                        │
│           │ 1:N                                                    │
│           │                                                        │
│  ┌────────▼──────────────────────┐                               │
│  │ employees                      │                               │
│  │ • id (PK)                      │                               │
│  │ • business_id (FK) TENANT      │                               │
│  │ • nombre (VARCHAR)             │                               │
│  │ • email (VARCHAR)              │                               │
│  │ • telefono (VARCHAR)           │                               │
│  │ • user_account_id (FK→users)   │ OPCIONAL                      │
│  │ • estado (VARCHAR)             │ disponible/no_disponible      │
│  │ • created_at, updated_at       │                               │
│  │ • deleted_at                   │                               │
│  └────────────────────────────────┘                               │
│                                                                      │
└──────────────────────────────────────────────────────────────────────┘
```

**Relaciones**:
- `businesses` → `business_locations` (1:N) - Un negocio tiene múltiples sucursales
- `business_locations` → `schedule_templates` (1:N) - Horarios base por día
- `business_locations` → `schedule_exceptions` (1:N) - Excepciones de horario
- `businesses` → `services` (1:N) - Servicios ofrecidos
- `services` ← → `employees` (N:M vía `employee_services`) - Qué servicios puede hacer cada empleado
- `businesses` → `employees` (1:N) - Empleados del negocio

---

## Contexto 3: Usuarios (Global)

```
┌──────────────────────────────────────────────┐
│ USUARIOS (GLOBAL, COMPARTIDO)                │
├──────────────────────────────────────────────┤
│                                              │
│  ┌────────────────────────────────────────┐ │
│  │ users (1)                              │ │
│  │ • id (PK)                              │ │
│  │ • nombre (VARCHAR)                     │ │
│  │ • email (VARCHAR UNIQUE)               │ │
│  │ • password (VARCHAR)                   │ │
│  │ • telefono (VARCHAR)                   │ │
│  │ • avatar_url (VARCHAR)                 │ │
│  │ • email_verified_at (TIMESTAMP)        │ │
│  │ • remember_token (VARCHAR)             │ │
│  │ • created_at, updated_at, deleted_at   │ │
│  └────────────────────────────────────────┘ │
│           │                                  │
│           │ 1:N (NO FILTRADO POR TENANT)    │
│           │                                  │
│  ┌────────▼────────────────────────────────┐ │
│  │ user_favorite_businesses                │ │
│  │ • id (PK)                               │ │
│  │ • user_id (FK → users)                  │ │
│  │ • business_id (FK → businesses)         │ │
│  │ • created_at                            │ │
│  │ • UNIQUE (user_id, business_id)         │ │
│  └────────────────────────────────────────┘ │
│                                              │
└──────────────────────────────────────────────┘
```

**Notas importantes**:
- `users` NO tiene `business_id` → tabla global
- `user_favorite_businesses` sí tiene segregación implícita por business_id

---

## Contexto 4: RBAC (Control de Acceso)

```
┌──────────────────────────────────────────────────────────────────────┐
│ RBAC (MULTI-TENANT)                                                  │
├──────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  ┌────────────────────────┐     ┌────────────────────────────────┐ │
│  │ roles                  │     │ permissions                    │ │
│  │ • id (PK)              │     │ • id (PK)                      │ │
│  │ • name (VARCHAR UNI)   │     │ • name (VARCHAR UNIQUE)        │ │
│  │ • description (TEXT)   │     │ • module (VARCHAR)             │ │
│  │ • guard_name           │     │ • action (VARCHAR)             │ │
│  │ • created_at           │     │ • description (TEXT)           │ │
│  └────────────────────────┘     │ • guard_name                   │ │
│           │                      │ • created_at                   │ │
│           │ N:M                  └────────────────────────────────┘ │
│           │                                          ▲               │
│           │         ┌──────────────────────────────────┘               │
│           │         │ M:N                                             │
│           │         │                                                 │
│  ┌────────▼─────────▼──────────────────────────┐                     │
│  │ role_permissions (Pivote)                   │                     │
│  │ • role_id (FK)                              │                     │
│  │ • permission_id (FK)                        │                     │
│  │ • PRIMARY KEY (role_id, permission_id)      │                     │
│  └─────────────────────────────────────────────┘                     │
│                                                                      │
│  ┌──────────────────────────────────────────────┐                   │
│  │ business_user_roles (MULTI-TENANT CRÍTICO)   │                   │
│  │ • id (PK)                                    │                   │
│  │ • user_id (FK → users)                       │                   │
│  │ • business_id (FK → businesses) NULLABLE     │                   │
│  │ • role_id (FK → roles)                       │                   │
│  │ • created_at, updated_at                     │                   │
│  │ • UNIQUE (user_id, business_id, role_id)     │                   │
│  │                                              │                   │
│  │ NOTA: NULL business_id = rol global          │                   │
│  │ (PLATAFORMA_ADMIN, USUARIO_FINAL)            │                   │
│  └──────────────────────────────────────────────┘                   │
│                                                                      │
└──────────────────────────────────────────────────────────────────────┘
```

**Relaciones**:
- `roles` ← → `permissions` (N:M vía `role_permissions`) - Qué permisos tiene cada rol
- `users` ← → `roles` (N:M vía `business_user_roles`) - Roles de usuario por negocio

---

## Contexto 5: Agenda y Reservas

```
┌──────────────────────────────────────────────────────────────────────┐
│ AGENDA Y RESERVAS (MULTI-TENANT)                                     │
├──────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │ appointments                                                 │  │
│  │ • id (PK)                                                   │  │
│  │ • business_id (FK) TENANT                                   │  │
│  │ • user_id (FK → users)                                      │  │
│  │ • location_id (FK → business_locations)                     │  │
│  │ • service_id (FK → services)                                │  │
│  │ • employee_id (FK → employees) NULLABLE                     │  │
│  │ • fecha_hora_inicio (TIMESTAMP)                             │  │
│  │ • fecha_hora_fin (TIMESTAMP)                                │  │
│  │ • estado (ENUM) pending|confirmed|completed|etc            │  │
│  │ • codigo_confirmacion (VARCHAR UNIQUE)                      │  │
│  │ • notas_cliente (TEXT)                                      │  │
│  │ • custom_data (JSONB) campos personalizados                 │  │
│  │ • created_at, updated_at, deleted_at                        │  │
│  │                                                              │  │
│  │ CHECK (fecha_hora_fin > fecha_hora_inicio)                 │  │
│  └──────────────────────────────────────────────────────────────┘  │
│           │                                                          │
│           │ 1:N                                                      │
│           │                                                          │
│  ┌────────▼──────────────────────────────────────────────────────┐ │
│  │ appointment_status_histories                                 │ │
│  │ • id (PK)                                                    │ │
│  │ • appointment_id (FK)                                        │ │
│  │ • estado_anterior (VARCHAR) NULLABLE                         │ │
│  │ • estado_nuevo (VARCHAR)                                     │ │
│  │ • cambiado_por (BIGINT) usuario_id que realizó cambio       │ │
│  │ • fecha_cambio (TIMESTAMP)                                   │ │
│  └────────────────────────────────────────────────────────────────┘ │
│                                                                      │
│  [FASE 2 - RECURSOS]                                                 │
│                                                                      │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │ resources                                                    │  │
│  │ • id (PK)                                                    │  │
│  │ • business_id (FK) TENANT                                    │  │
│  │ • location_id (FK → business_locations)                      │  │
│  │ • nombre (VARCHAR)                                           │  │
│  │ • tipo (ENUM) fisico|virtual                                │  │
│  │ • capacidad (INT >= 1)                                       │  │
│  │ • meta (JSONB)                                               │  │
│  │ • created_at, updated_at                                     │  │
│  └──────────────────────────────────────────────────────────────┘  │
│           │                                                          │
│           │ N:M                                                      │
│           │                                                          │
│  ┌────────▼──────────────────────────────────────────────────────┐ │
│  │ appointment_resources (Pivote)                               │ │
│  │ • id (PK)                                                    │ │
│  │ • appointment_id (FK)                                        │ │
│  │ • resource_id (FK)                                           │ │
│  │ • PRIMARY KEY (appointment_id, resource_id)                  │ │
│  └────────────────────────────────────────────────────────────────┘ │
│                                                                      │
└──────────────────────────────────────────────────────────────────────┘
```

**Relaciones**:
- `appointments` → `users` (N:1) - Cita de un usuario
- `appointments` → `businesses` (N:1) - Cita en un negocio (tenant)
- `appointments` → `business_locations` (N:1) - Cita en una sucursal específica
- `appointments` → `services` (N:1) - Servicio reservado
- `appointments` → `employees` (N:1) - Empleado asignado (puede ser NULL)
- `appointments` → `appointment_status_histories` (1:N) - Historial de cambios
- `appointments` ← → `resources` (N:M vía `appointment_resources`) - Recursos usados

---

## Contexto 6: Notificaciones (Logging)

```
┌──────────────────────────────────────────────┐
│ NOTIFICACIONES (LOGGING - FASE 5)             │
├──────────────────────────────────────────────┤
│                                              │
│  ┌────────────────────────────────────────┐ │
│  │ notification_logs                      │ │
│  │ • id (PK)                              │ │
│  │ • user_id (FK → users) NULLABLE        │ │
│  │ • business_id (FK) TENANT NULLABLE     │ │
│  │ • appointment_id (FK) NULLABLE         │ │
│  │ • tipo (VARCHAR) email|whatsapp        │ │
│  │ • estado (VARCHAR) enviado|fallido     │ │
│  │ • destinatario (VARCHAR)               │ │
│  │ • asunto (VARCHAR)                     │ │
│  │ • intentos (INT default 0)             │ │
│  │ • ultimo_intento (TIMESTAMP)           │ │
│  │ • meta (JSONB) datos adicionales       │ │
│  │ • created_at                           │ │
│  │ • deleted_at                           │ │
│  └────────────────────────────────────────┘ │
│                                              │
└──────────────────────────────────────────────┘
```

---

## Resumen de Cardinalidades

| Tabla 1 | Relación | Tabla 2 | Justificación |
|---------|----------|---------|---------------|
| businesses | 1:N | business_locations | Un negocio tiene múltiples sucursales |
| business_locations | 1:N | schedule_templates | Una sucursal tiene horarios para cada día |
| business_locations | 1:N | schedule_exceptions | Una sucursal tiene múltiples excepciones |
| businesses | 1:N | services | Un negocio ofrece múltiples servicios |
| services | N:M | employees | Empleados pueden hacer múltiples servicios |
| businesses | 1:N | employees | Un negocio tiene múltiples empleados |
| businesses | 1:N | appointments | Un negocio tiene múltiples citas |
| users | 1:N | appointments | Un usuario hace múltiples citas |
| business_locations | 1:N | appointments | Una sucursal tiene múltiples citas |
| services | 1:N | appointments | Un servicio se reserva múltiples veces |
| employees | 1:N | appointments | Un empleado tiene múltiples citas |
| appointments | 1:N | appointment_status_histories | Una cita tiene histórico de cambios |
| roles | N:M | permissions | Un rol tiene múltiples permisos |
| users | N:M | roles (via BUR) | Un usuario tiene múltiples roles por negocio |
| businesses | 1:N | resources | Un negocio tiene múltiples recursos |
| appointments | N:M | resources | Una cita usa múltiples recursos |
| users | 1:N | user_favorite_businesses | Un usuario tiene múltiples favoritos |

---

## Restricciones de Multi-Tenancy

- **Tabla `users`**: NO incluye `business_id` (es global)
- **Todas las otras tablas operacionales**: INCLUYEN `business_id` explícitamente
- **Scope global en Laravel**: Automáticamente filtra por `business_id` en queries
- **Tabla pivote `business_user_roles`**: `business_id` puede ser NULL para roles globales

---

## Notas de Implementación

1. **Soft Deletes**: Implementado en `users`, `businesses`, `employees`, `appointments`, `notification_logs`
2. **Auditoría temporal**: `created_at`, `updated_at` en todas las tablas
3. **Campos JSON**: `meta` en services, businesses; `custom_data` en appointments; `meta` en resources
4. **Constraints CHECK**: Validación de duraciones, precios, capacidades
5. **Índices compuestos**: Críticos para performance (business_id + fecha en appointments)

---

## Siguiente Paso

Este diagrama conceptual será traducido a scripts SQL en la siguiente entrega de FASE 0.
