# Changelog - AgendaYa

Todos los cambios importantes del proyecto se documentan en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es/1.0.0/),
y este proyecto adhiere a [Versionamiento Semántico](https://semver.org/lang/es/).

---

## [Sprint 3] - 2026-01-18

### ✨ Agregado

#### Componentes Livewire
- **AppointmentsList:** Lista paginada de citas con filtros (estado, fechas, búsqueda)
- **CreateAppointment:** Wizard de 3 pasos para crear citas con validación en tiempo real
- **BusinessDashboard:** Panel con 6 KPIs, 3 gráficos y lista de próximas citas
- **ScheduleManagement:** Gestión de horarios semanales y excepciones (feriados, vacaciones)

#### Vistas
- `/appointments` - Lista de citas
- `/appointments/create` - Formulario de creación de citas
- `/dashboard` - Dashboard con métricas del negocio
- `/schedules` - Gestión de horarios y excepciones

#### Funcionalidades
- Modal de detalles de cita con información completa
- Selector de período (hoy/semana/mes/año) en dashboard
- Toggle de días activos/inactivos en horarios
- Generación dinámica de slots disponibles
- Auto-creación de usuarios en flujo de reserva
- Badges de color para estados de cita
- Loading states con `wire:loading`
- Validación de disponibilidad en tiempo real

#### Tests
- 5 tests de `CreateAppointment` component
- Tests de integración con `AvailabilityService`
- Validación de wizard multi-paso

#### Seeders
- `TestUserWithBusinessSeeder`: Crea usuario con negocio completo para testing
- Script `verify_sprint3.php`: Verificación automatizada de componentes

### 🔧 Corregido

#### Modelo User
- Agregado `current_business_id` al array `$fillable` (causaba que no se guardara con `update()`)

#### Tests
- Corrección de assertions en `CreateAppointmentTest` para Livewire 3
- Cambio de callback functions a método `contains()` directo
- Agregado `$this->user->refresh()` después de actualizar usuario
- Simplificación de validaciones de test

#### Configuración
- **SESSION_DOMAIN:** Cambiado de `.tudominio.com` a `null` para desarrollo local
- **APP_URL:** Actualizado de `https://tudominio.com` a `http://127.0.0.1:8000`
- **SANCTUM_STATEFUL_DOMAINS:** Agregados `localhost,127.0.0.1`

#### Sesiones
- Limpieza de archivos de caché y sesiones
- Corrección de error 419 CSRF en login

### 📊 Estadísticas
- **Tests:** 105 pasando (613 assertions)
- **Duración:** 3.82s
- **Líneas de código:** +2,342 líneas
- **Componentes:** 4 nuevos componentes Livewire
- **Vistas:** 8 nuevas vistas Blade

---

## [Sprint 2] - 2026-01-17

### ✨ Agregado

#### API REST Completa
- Endpoints CRUD para Services, Employees, Schedules
- Endpoint público `/api/v1/availability/slots`
- API Resources para respuestas estructuradas
- Paginación en listados

#### Motor de Disponibilidad
- Servicio `AvailabilityService` con 7 reglas de negocio
- Generación de slots con respeto a horarios base
- Validación de excepciones (feriados, vacaciones)
- Prevención de solapamiento con buffers
- Caché de slots por 5 minutos

#### Sistema RBAC
- 5 roles implementados: USUARIO_FINAL, NEGOCIO_STAFF, NEGOCIO_MANAGER, NEGOCIO_ADMIN, PLATAFORMA_ADMIN
- 18 permisos granulares
- Middleware de autorización
- Policies por recurso
- Global Scopes multi-tenant

#### Tests
- 100 tests implementados (605 assertions)
- Tests de concurrencia (doble booking)
- Tests de RBAC multi-tenant
- Tests de transiciones de estado
- Tests del motor de disponibilidad

### 🔧 Corregido
- Optimización de queries N+1 con eager loading
- Validación de rangos de fecha (máximo 30 días)
- Mejora de índices para queries de disponibilidad

---

## [Sprint 1] - 2026-01-16

### ✨ Agregado

#### Base de Datos
- 15 tablas principales implementadas
- Migraciones para MariaDB/MySQL
- Factories para todos los modelos
- Seeders de datos iniciales
- Índices optimizados para multi-tenancy

#### Modelos Eloquent
- 12 modelos con relaciones definidas
- Global Scopes para multi-tenancy
- Soft Deletes en tablas críticas
- Casts y mutators

#### Autenticación
- Laravel Breeze instalado
- Sanctum para API móvil
- Registro y login funcionando
- Email verification

#### Configuración Inicial
- Estructura de proyecto Laravel 11
- Tailwind CSS configurado
- Livewire 3 instalado
- Variables de entorno definidas

---

## [Sprint 0] - 2026-01-15

### 📋 Planeación

#### Documentación
- Diseño de base de datos completo
- ERD conceptual y lógico
- Especificación de índices
- Matriz RBAC detallada
- Plan de desarrollo de 6 sprints

#### Definición de Arquitectura
- Multi-tenancy: Single Database + business_id
- Stack tecnológico: Laravel + Livewire + Alpine.js
- Patrón de permisos: módulo.acción
- Estrategia de caché

#### Mockups y Wireframes
- Flujo de usuario final
- Panel de administración
- Wizard de alta de negocio

---

## Formato

### Tipos de Cambios
- **✨ Agregado:** Para funcionalidades nuevas
- **🔧 Corregido:** Para corrección de bugs
- **♻️ Cambiado:** Para cambios en funcionalidades existentes
- **🗑️ Obsoleto:** Para funcionalidades que serán eliminadas
- **❌ Eliminado:** Para funcionalidades eliminadas
- **🔒 Seguridad:** Para vulnerabilidades corregidas
- **📊 Rendimiento:** Para mejoras de performance

---

**Última actualización:** 18 de Enero de 2026
