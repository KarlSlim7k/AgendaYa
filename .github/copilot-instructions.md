# GitHub Copilot Instructions - Citas Empresariales SaaS

## Project Overview

**Plataforma SaaS multi-tenant para gestión de citas empresariales** que conecta usuarios finales con negocios de servicios (peluquerías, clínicas, talleres, etc.).

### Stack Tecnológico
- **Backend API**: Laravel 10+ + PostgreSQL 14+ + Laravel Sanctum
- **Frontend Web (Panel Admin/Negocios)**: Next.js + React + TypeScript + ShadCN/UI
- **App Móvil (Usuarios Finales)**: React Native + Expo + TypeScript
- **Multi-Tenancy**: Single Database con segregación por columna `business_id`
- **Autenticación**: REST API con Bearer tokens (Laravel Sanctum)

### Estado Actual del Proyecto
El proyecto está en **Fase 0 (Diseño)** con documentación arquitectónica completa. La implementación de código seguirá las especificaciones en `/docs` y `/database`.

### Contexto de Mercado (México)
- **WhatsApp**: Canal principal de notificaciones (Twilio/WhatsApp Business API)
- **Teléfono móvil**: Formato mexicano (+52) requerido en validaciones
- **Pagos**: Preparado para MercadoPago/Stripe (Fase 2)
- **Timezone default**: `America/Mexico_City`

### Propuesta de Valor
Sistema **"no-code"** donde negocios pueden auto-configurarse sin desarrollo:
- Wizard de alta de negocio (5 pasos)
- Configuración visual de servicios, horarios, empleados
- Campos personalizables (custom fields) por negocio
- Plantillas de mensajes editables para recordatorios

### Visión del Producto

**Flujo End-to-End:**
1. Usuario final busca negocio → filtra por categoría/ubicación
2. Selecciona servicio → ve disponibilidad en calendario
3. Reserva cita → recibe confirmación por email/WhatsApp
4. Recordatorio 24h antes → asiste o cancela
5. Post-cita → historial guardado, posibilidad de reseña

**Alcance MVP (6 semanas, Fases 0-5):**
- ✅ Multi-tenant con RBAC completo
- ✅ Motor de disponibilidad con prevención de doble booking
- ✅ Gestión de negocios, sucursales, servicios, empleados, horarios
- ✅ Reserva de citas con validación en tiempo real
- ✅ Notificaciones email básicas
- ✅ Panel web Next.js para administración
- ✅ App móvil Expo para usuarios finales

**Post-MVP (Fases 6+):**
- ⏳ Recursos compartidos (salas, equipos) con capacidad limitada
- ⏳ Notificaciones WhatsApp (Twilio)
- ⏳ Pagos online (MercadoPago/Stripe)
- ⏳ Sistema de reseñas y ratings
- ⏳ Reportes avanzados y analytics

---

## Architecture

### Multi-Tenancy Strategy

**Estrategia**: Tenant ID en tablas (`business_id`) - Single Database

```php
// Implementación requerida: Global Scope en modelos Eloquent
protected static function booted()
{
    static::addGlobalScope('business', function (Builder $builder) {
        if (auth()->check() && auth()->user()->current_business_id) {
            $builder->where('business_id', auth()->user()->current_business_id);
        }
    });
}
```

**Tablas que requieren `business_id`**: 
- `business_locations`, `services`, `employees`, `employee_services`
- `schedule_templates`, `schedule_exceptions`, `appointments`
- `business_user_roles`, `notification_logs`

**Tablas GLOBALES (sin `business_id`)**:
- `users` (usuarios finales compartidos)
- `platform_admins`, `platform_settings`
- `roles`, `permissions`, `role_permissions`

### RBAC Multi-Tenant (5 Roles)

| Rol | Scope | Descripción |
|-----|-------|-------------|
| `USUARIO_FINAL` | Global | Usuario de app móvil, solo citas propias |
| `NEGOCIO_STAFF` | Asignados | Empleado, ve su agenda/servicios asignados |
| `NEGOCIO_MANAGER` | Sucursal | Gerente, CRUD de su sucursal asignada |
| `NEGOCIO_ADMIN` | Todo negocio | Admin del tenant, CRUD completo del negocio |
| `PLATAFORMA_ADMIN` | Plataforma | Superadmin, acceso total sin filtros |

**Implementación RBAC**: Custom (no Spatie) - Ver [01_mapeo_matriz_permisos_rbac.md](database/documentation/01_mapeo_matriz_permisos_rbac.md)

```php
// Patrón de permisos: módulo.acción
$permisos = [
    'perfil.read', 'perfil.update',
    'negocio.read', 'negocio.update',
    'sucursal.read', 'sucursal.create', 'sucursal.update', 'sucursal.delete',
    'servicio.read', 'servicio.create', 'servicio.update', 'servicio.delete',
    'empleado.read', 'empleado.create', 'empleado.update', 'empleado.delete',
    'agenda.read', 'agenda.create',
    'cita.read', 'cita.create', 'cita.update', 'cita.delete',
    'reportes.read'
];

// Query de validación multi-tenant
// ¿Usuario X tiene permiso "servicio.update" en Negocio Y?
SELECT 1 FROM business_user_roles bur
JOIN role_permissions rp ON bur.role_id = rp.role_id
JOIN permissions p ON rp.permission_id = p.id
WHERE bur.user_id = ? AND bur.business_id = ? AND p.name = 'servicio.update';
```

### Motor de Disponibilidad

Sistema core para gestión de slots. Implementa 7 reglas de negocio:

1. **R1 Horario Base**: Citas dentro de `schedule_templates` (hora_apertura/cierre por día)
2. **R2 Excepciones**: Bloqueos en `schedule_exceptions` (feriados, vacaciones, cierres)
3. **R3 No Solapamiento**: Un empleado/recurso no puede tener citas simultáneas
4. **R4 Duración Servicio**: Slot >= duración del servicio
5. **R5 Buffer Post-Cita**: Tiempo muerto después de cita (`buffer_post_minutos`)
6. **R6 Buffer Pre-Cita**: Tiempo muerto antes de cita (`buffer_pre_minutos`)
7. **R7 Capacidad Recursos**: Límite de citas simultáneas por recurso (Fase 2)

**Pseudocódigo completo**: [motor_disponibilidad_pseudocodigo.md](docs/motor_disponibilidad_pseudocodigo.md)

```php
// Prevención de doble booking - Lock Pessimista
DB::transaction(function () use ($appointmentData) {
    // SELECT FOR UPDATE para bloquear slots durante validación
    $existingAppointments = Appointment::where('employee_id', $employeeId)
        ->whereBetween('fecha_hora_inicio', [$startWindow, $endWindow])
        ->lockForUpdate()
        ->get();
    
    // Validar solapamiento con buffers
    foreach ($existingAppointments as $existing) {
        if ($this->overlapsWithBuffers($existing, $appointmentData)) {
            throw new SlotNotAvailableException();
        }
    }
    
    return Appointment::create($appointmentData);
});
```

### Caching de Slots

```php
// Cache por 5 minutos para reducir carga en motor de disponibilidad
$cacheKey = "slots:{$businessId}:{$locationId}:{$serviceId}:{$fecha}";
$slots = Cache::remember($cacheKey, 300, function () use ($params) {
    return $this->availabilityService->generateSlots(...$params);
});

// CRÍTICO: Invalidar cache cuando hay cambios
Cache::forget($cacheKey); // Al crear/cancelar cita
Cache::tags(['tenant_' . $businessId])->flush(); // Al modificar horarios
```

### Transiciones de Estado de Cita

```php
// Estados válidos y transiciones permitidas
$transiciones = [
    'pending'   => ['confirmed', 'cancelled'],
    'confirmed' => ['completed', 'cancelled', 'no_show'],
    'completed' => [], // Terminal
    'cancelled' => [], // Terminal
    'no_show'   => [], // Terminal
];

// Validar transición antes de cambiar estado
if (!in_array($nuevoEstado, $transiciones[$estadoActual])) {
    throw new InvalidStateTransitionException();
}
```

---

## Database Conventions

### Naming Conventions

| Elemento | Convención | Ejemplo |
|----------|------------|---------|
| Tablas | snake_case, plural | `business_locations`, `employee_services` |
| Columnas | snake_case | `fecha_hora_inicio`, `buffer_pre_minutos` |
| Primary Keys | `id` (BIGSERIAL) | - |
| Foreign Keys | `{tabla_singular}_id` | `business_id`, `employee_id` |
| Timestamps | Laravel standard | `created_at`, `updated_at`, `deleted_at` |
| ENUMs PostgreSQL | snake_case | `appointment_status`, `schedule_exception_type` |

### ENUM Types

```sql
-- Estados de cita (ciclo de vida)
CREATE TYPE appointment_status AS ENUM (
    'pending',    -- Pendiente confirmación
    'confirmed',  -- Confirmada y activa
    'completed',  -- Finalizada exitosamente
    'cancelled',  -- Cancelada
    'no_show'     -- Usuario no asistió
);

-- Tipos de excepción de horario
CREATE TYPE schedule_exception_type AS ENUM (
    'feriado',     -- Día feriado nacional
    'vacaciones',  -- Período vacacional
    'cierre'       -- Cierre temporal
);

-- Tipos de recurso (Fase 2)
CREATE TYPE resource_type AS ENUM (
    'fisico',   -- Sala, camilla, silla
    'virtual'   -- Link Zoom, plataforma video
);
```

### Critical Indexes

```sql
-- Multi-tenant: SIEMPRE indexar business_id + campo de búsqueda
CREATE INDEX idx_appointments_availability 
  ON appointments(business_id, employee_id, fecha_hora_inicio, estado);

-- Prevención doble booking
CREATE INDEX idx_appointments_employee_date 
  ON appointments(employee_id, fecha_hora_inicio);

-- UNIQUE compuestos por tenant
CREATE UNIQUE INDEX idx_services_business_nombre 
  ON services(business_id, nombre);
CREATE UNIQUE INDEX idx_employees_business_email 
  ON employees(business_id, email);

-- RBAC multi-tenant
CREATE UNIQUE INDEX idx_business_user_roles_unique 
  ON business_user_roles(user_id, business_id, role_id);
```

**Especificación completa**: [02_especificacion_indices.md](database/documentation/02_especificacion_indices.md)

### Custom Fields (Campos Personalizables)

```php
// Servicios con metadatos personalizables (JSONB)
$service = Service::create([
    'business_id' => $businessId,
    'nombre' => 'Corte de cabello',
    'duracion_minutos' => 30,
    'precio' => 150.00,
    'meta' => [
        'requiere_deposito' => true,
        'porcentaje_deposito' => 20,
        'instrucciones_previas' => 'Llegar con cabello limpio',
        'custom_fields' => [
            ['name' => 'tipo_corte', 'type' => 'select', 'options' => ['Clásico', 'Moderno', 'Fade']]
        ]
    ]
]);

// Citas con datos personalizados del cliente (JSONB)
$appointment = Appointment::create([
    // ... campos estándar
    'custom_data' => [
        'tipo_corte' => 'Fade',
        'preferencia_estilista' => 'Sin preferencia',
        'alergias' => 'Ninguna'
    ]
]);
```

### Wizard de Alta de Negocio (5 Pasos)

**Flujo completo de onboarding "no-code":**

```typescript
// Paso 1: Datos Básicos del Negocio
{
  nombre: string,              // "Peluquería Estilos"
  razon_social: string,        // Para facturación
  rfc: string,                 // RFC mexicano (validar formato)
  telefono: string,            // +52 55 1234 5678
  email: string,               // Email principal del negocio
  categoria: enum,             // 'peluqueria' | 'clinica' | 'taller' | etc.
  descripcion?: string         // Opcional, para perfil público
}

// Paso 2: Sucursal Principal
{
  nombre_sucursal: string,     // "Sucursal Centro"
  direccion: string,           // Calle, número, colonia
  ciudad: string,
  estado: string,
  codigo_postal: string,
  zona_horaria: string,        // Default: "America/Mexico_City"
  latitud?: number,            // Para mapa (opcional)
  longitud?: number
}

// Paso 3: Servicios (mínimo 1 requerido)
[
  {
    nombre: string,            // "Corte de cabello"
    descripcion?: string,
    duracion_minutos: number,  // 30, 45, 60, etc.
    precio: number,            // 150.00
    buffer_pre_minutos: 0,     // Default 0
    buffer_post_minutos: 0,    // Default 0
    meta?: {
      requiere_deposito?: boolean,
      porcentaje_deposito?: number,
      instrucciones_previas?: string
    }
  }
]

// Paso 4: Horarios de Atención
[
  {
    dia_semana: 0-6,           // 0=Domingo, 6=Sábado
    hora_apertura: "09:00",
    hora_cierre: "18:00",
    activo: boolean            // Permite desactivar días específicos
  }
]
// Sugerencia: Pre-llenar Lun-Vie 09:00-18:00, Sáb 09:00-14:00

// Paso 5: Empleados (mínimo 1 requerido)
[
  {
    nombre: string,
    email?: string,            // Opcional, para invitarlos al sistema
    telefono?: string,
    servicios_ids: number[],   // IDs de servicios que puede realizar
    rol: 'NEGOCIO_STAFF'       // Asignación automática
  }
]

// Post-Wizard:
// - Email de bienvenida con credenciales
// - Estado del negocio: 'pending' (requiere aprobación admin)
// - Redirect a dashboard con tour guiado
```

**Validaciones críticas:**
- RFC válido (formato mexicano: 12-13 caracteres)
- Teléfono con formato +52
- Horarios: hora_cierre > hora_apertura
- Al menos 1 servicio y 1 empleado para activar negocio
- Duración mínima de servicio: 15 minutos

---

## Code Conventions

### Laravel Backend

```php
// Controllers: Singular + Controller suffix
class AppointmentController extends Controller

// Form Requests: Store/Update + Model + Request
class StoreAppointmentRequest extends FormRequest
class UpdateAppointmentRequest extends FormRequest

// API Resources: Model + Resource
class AppointmentResource extends JsonResource

// Jobs: Verbo + Sustantivo + Job
class SendAppointmentReminderJob implements ShouldQueue

// Services: Model + Service
class AvailabilityService
class AppointmentService

// Policies: Model + Policy
class AppointmentPolicy
```

### Frontend Web (Next.js) - Convenciones Anticipadas

```typescript
// Estructura de carpetas App Router
app/
├── (auth)/           // Rutas públicas (login, registro)
├── (dashboard)/      // Rutas protegidas del panel
│   ├── negocios/
│   ├── sucursales/
│   ├── servicios/
│   ├── empleados/
│   └── citas/
├── api/              // API Routes (si se usan)
└── layout.tsx

// Hooks personalizados con prefijo 'use'
useAuth()             // Manejo de autenticación
useAppointments()     // CRUD de citas con React Query
useBusiness()         // Contexto del negocio actual
useAvailability()     // Motor de disponibilidad

// Componentes: PascalCase
<AppointmentCard />
<ServiceSelector />
<EmployeeAvatar />
<SlotPicker />
```

### App Móvil (React Native + Expo)

```typescript
// Estructura de navegación
src/
├── navigation/
│   ├── AppNavigator.tsx      // Stack principal
│   ├── AuthNavigator.tsx     // Stack de autenticación
│   └── TabNavigator.tsx      // Tabs principales
├── screens/
│   ├── HomeScreen.tsx
│   ├── BusinessDetailScreen.tsx
│   ├── BookingScreen.tsx
│   └── ProfileScreen.tsx
├── components/
├── hooks/
├── services/
└── stores/                   // Zustand o similar

// Almacenamiento seguro para tokens
import * as SecureStore from 'expo-secure-store';
await SecureStore.setItemAsync('auth_token', token);
```

### Multi-Tenant Code Patterns

```php
// SIEMPRE validar pertenencia al tenant antes de modificar
public function update(UpdateAppointmentRequest $request, Appointment $appointment)
{
    // Verificación obligatoria de tenant
    if ($appointment->business_id !== auth()->user()->current_business_id) {
        abort(403, 'Unauthorized access to resource');
    }
    
    // Continuar con lógica...
}

// Crear recursos con business_id automático
public function store(StoreServiceRequest $request)
{
    $service = Service::create([
        'business_id' => auth()->user()->current_business_id,
        ...$request->validated()
    ]);
    
    return new ServiceResource($service);
}

// Queries con scope explícito (redundante pero seguro)
$appointments = Appointment::query()
    ->where('business_id', $businessId) // Redundante con Global Scope pero explícito
    ->where('employee_id', $employeeId)
    ->whereBetween('fecha_hora_inicio', [$start, $end])
    ->get();
```

### API Response Structure

```php
// Respuesta exitosa (con Laravel API Resources)
{
    "data": { ... },
    "meta": {
        "current_page": 1,
        "per_page": 15,
        "total": 100
    },
    "links": { ... }
}

// Respuesta de error
{
    "message": "El slot ya no está disponible",
    "errors": {
        "fecha_hora_inicio": ["Conflicto con otra cita existente"]
    },
    "code": "SLOT_NOT_AVAILABLE"
}

// Códigos de error específicos del dominio
SLOT_NOT_AVAILABLE      // Slot ocupado durante validación
EMPLOYEE_NOT_AVAILABLE  // Empleado sin disponibilidad
OUTSIDE_BUSINESS_HOURS  // Fuera de horario laboral
EXCEPTION_BLOCKED       // Bloqueado por excepción (feriado, etc.)
TENANT_MISMATCH         // Recurso no pertenece al tenant
```

### API Endpoints por Módulo

**Autenticación & Usuario**
```php
// Registro y login
POST   /api/v1/auth/register                 // Usuario final {email, password, name, phone?}
POST   /api/v1/auth/register-business        // Negocio {business_name, tax_id, owner_email, phone}
POST   /api/v1/auth/login                    // Todos {email, password, guard}
POST   /api/v1/auth/logout                   // [Token required]
POST   /api/v1/auth/refresh                  // [Token required]
POST   /api/v1/auth/forgot-password          // Recuperación de cuenta
POST   /api/v1/auth/reset-password           // Reset password

// Perfil usuario
GET    /api/v1/user/profile                  // [Token required]
GET    /api/v1/user/appointments             // Mis citas [Token required]
```

**Negocios (Públicas)**
```php
GET    /api/v1/businesses                    // Lista con filtros ?category&search&location
GET    /api/v1/businesses/{id}               // Detalle público
GET    /api/v1/businesses/{id}/services      // Servicios disponibles
GET    /api/v1/businesses/{id}/employees     // Empleados (público: nombre, foto)
POST   /api/v1/businesses                    // Alta negocio (wizard) [Token required]
PUT    /api/v1/businesses/{id}               // Actualizar [Token business required]
```

**Gestión de Negocio (Panel)**
```php
// Dashboard
GET    /api/v1/business/dashboard            // Métricas [Token required]

// Sucursales
GET    /api/v1/business/locations            // Listar [Token required]
POST   /api/v1/business/locations            // Crear [Token required]
GET    /api/v1/locations/{id}                // Detalle
PUT    /api/v1/locations/{id}                // Actualizar
DELETE /api/v1/locations/{id}                // Eliminar

// Servicios
GET    /api/v1/business/services             // Listar [Token required]
POST   /api/v1/business/services             // Crear [Token required]
GET    /api/v1/services/{id}                 // Detalle
PUT    /api/v1/services/{id}                 // Actualizar
DELETE /api/v1/services/{id}                 // Eliminar

// Empleados
GET    /api/v1/business/employees            // Listar [Token required]
POST   /api/v1/business/employees            // Crear [Token required]
GET    /api/v1/employees/{id}                // Detalle
PUT    /api/v1/employees/{id}                // Actualizar
DELETE /api/v1/employees/{id}                // Eliminar

// Citas del negocio
GET    /api/v1/business/appointments         // Listar [Token required]
PATCH  /api/v1/appointments/{id}             // Cambiar estado
GET    /api/v1/appointments/{id}             // Detalle

// Horarios
GET    /api/v1/locations/{id}/schedules      // Plantillas horarias
POST   /api/v1/locations/{id}/schedules      // Crear plantilla
PUT    /api/v1/schedules/{id}                // Actualizar

// Excepciones
GET    /api/v1/locations/{id}/exceptions     // Feriados, vacaciones
POST   /api/v1/locations/{id}/exceptions     // Crear excepción
DELETE /api/v1/exceptions/{id}               // Eliminar
```

**Disponibilidad (Motor de Slots)**
```php
GET    /api/v1/availability/slots            // ?business_id&service_id&location_id&date&employee_id
```

**Citas (App Usuario)**
```php
POST   /api/v1/appointments                  // Crear cita [Token user required]
GET    /api/v1/appointments                  // Mis citas [Token user required]
PATCH  /api/v1/appointments/{id}/cancel      // Cancelar cita [Token user required]
```

**Administración de Plataforma**
```php
GET    /api/v1/admin/businesses/pending      // Negocios pendientes aprobación [Token admin]
POST   /api/v1/admin/businesses/{id}/approve // Aprobar negocio [Token admin]
GET    /api/v1/admin/platform-metrics        // Métricas de plataforma [Token admin]
```

---

## Notifications System

### Flujo de Notificaciones

**Canales:**
- **Email** (Fase 1): Confirmaciones, recordatorios, cambios
- **WhatsApp** (Fase 2): Recordatorios 24h/1h antes (Twilio Business API)
- **Push** (Fase 3): Notificaciones in-app

**Eventos y Plantillas:**

```php
// 1. Confirmación de Cita (Inmediato)
Event: AppointmentCreated
Canal: Email
Destinatarios: Usuario + Negocio
Plantilla: 
  "Hola {nombre_usuario}, tu cita para {servicio} el {fecha} a las {hora} 
   en {negocio} ha sido confirmada. Código: {codigo_confirmacion}"

// 2. Recordatorio 24h Antes
Event: AppointmentReminder24h
Canal: WhatsApp (fallback: Email)
Destinatario: Usuario
Plantilla: 
  "Recordatorio: Tienes cita mañana {fecha} a las {hora} en {negocio}. 
   Para cancelar: {link_cancelacion}"

// 3. Recordatorio 1h Antes
Event: AppointmentReminder1h
Canal: WhatsApp
Destinatario: Usuario
Plantilla: 
  "Tu cita en {negocio} es en 1 hora ({hora}). ¡Te esperamos!"

// 4. Cambio/Cancelación por Admin
Event: AppointmentUpdated | AppointmentCancelled
Canal: Email + WhatsApp
Destinatario: Usuario
Plantilla: 
  "Tu cita del {fecha} ha sido {cancelada/reprogramada}. 
   Nueva fecha: {nueva_fecha} | Motivo: {motivo}"

// 5. No-Show (Post-cita)
Event: AppointmentNoShow
Canal: Email (interno)
Destinatario: Negocio
Plantilla:
  "El cliente {nombre} no asistió a su cita del {fecha}. 
   Marcar como no-show en el sistema."
```

**Implementación con Laravel Queue:**

```php
// Jobs para notificaciones
class SendAppointmentConfirmationJob implements ShouldQueue
class SendAppointmentReminderJob implements ShouldQueue
class SendAppointmentCancellationJob implements ShouldQueue

// Scheduled job para recordatorios
// app/Console/Kernel.php
$schedule->command('appointments:send-reminders-24h')->hourly();
$schedule->command('appointments:send-reminders-1h')->everyFifteenMinutes();

// Tabla notification_logs (Fase 5)
// Tracking de envíos: éxito, fallo, reintentos
{
  id, user_id, business_id, appointment_id,
  tipo: 'email' | 'whatsapp' | 'push',
  evento: 'confirmacion' | 'recordatorio_24h' | 'recordatorio_1h' | 'cancelacion',
  estado: 'enviado' | 'fallido' | 'reintentado',
  intentos: number,
  ultimo_intento: timestamp,
  metadata: jsonb  // Detalles del envío
}
```

**Plantillas Editables:**
Negocios pueden personalizar plantillas desde el panel:
- Variables permitidas: `{nombre_usuario}`, `{servicio}`, `{fecha}`, `{hora}`, `{negocio}`, etc.
- Preview en tiempo real
- Validación de variables obligatorias

---

## Development Workflows

### Initial Setup (cuando exista código)

```bash
# Backend (Laravel)
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed

# Frontend Web (Next.js)
cd frontend-web
npm install
cp .env.example .env.local
npm run dev

# Mobile (React Native + Expo)
cd mobile
npm install
npx expo start
```

### Database Commands

```bash
# Reset completo con datos de prueba
php artisan migrate:fresh --seed

# Solo seeders específicos
php artisan db:seed --class=RolesAndPermissionsSeeder
php artisan db:seed --class=TestBusinessSeeder

# Generar migración para nueva tabla
php artisan make:migration create_tabla_name_table

# PostgreSQL: Backup/Restore
pg_dump citas_empresariales > backup_$(date +%Y%m%d).sql
psql citas_empresariales < backup.sql
```

### Testing

```bash
# Tests del motor de disponibilidad (críticos)
php artisan test --filter=AvailabilityTest
php artisan test --filter=AppointmentConcurrencyTest

# Tests multi-tenant
php artisan test --filter=MultiTenantTest

# Tests RBAC
php artisan test --filter=RBACTest

# Todos en paralelo
php artisan test --parallel
```

---

## Quality Standards

### Logging Multi-Tenant

```php
// SIEMPRE incluir business_id en logs para debugging
Log::info('Appointment created', [
    'business_id' => $appointment->business_id,
    'appointment_id' => $appointment->id,
    'user_id' => $appointment->user_id,
    'employee_id' => $appointment->employee_id,
    'service_id' => $appointment->service_id,
    'fecha_hora' => $appointment->fecha_hora_inicio,
]);

// Errores con contexto completo
Log::error('Slot validation failed', [
    'business_id' => $businessId,
    'employee_id' => $employeeId,
    'requested_slot' => $requestedSlot,
    'conflicting_appointment' => $conflictingAppointment?->id,
    'error' => $exception->getMessage(),
]);
```

### Security Checklist

**Autenticación & Autorización:**
- ✅ **Input Validation**: Form Requests en TODOS los endpoints
- ✅ **Multi-Tenant Isolation**: Global Scopes + validación explícita en mutaciones
- ✅ **RBAC Enforcement**: Middleware + Policies por recurso
- ✅ **Password Security**: Bcrypt con cost 10+, mínimo 8 caracteres
- ✅ **Token Management**: Sanctum tokens, expiración 24h, revocación al logout
- ✅ **Session Timeout**: 30 minutos de inactividad
- ✅ **Email Verification**: Obligatorio para usuarios finales antes de reservar
- ✅ **Password Recovery**: Token único, expiración 1h, un solo uso

**Protección de Datos:**
- ✅ **Concurrency Control**: Lock pessimista en creación de citas
- ✅ **Rate Limiting**: Endpoints públicos (login: 5/min, registro: 3/min, slots: 20/min)
- ✅ **SQL Injection**: Solo Eloquent/Query Builder parametrizado
- ✅ **Sensitive Data**: Nunca loguear passwords, tokens, datos personales sensibles
- ✅ **HTTPS Only**: Redirect HTTP → HTTPS en producción
- ✅ **CORS**: Whitelist de dominios permitidos
- ✅ **XSS Protection**: Sanitización de inputs, Content Security Policy
- ✅ **CSRF Protection**: Laravel CSRF tokens en forms

**GDPR & Compliance:**
- ✅ **Derecho al Olvido**: Endpoint DELETE /user/account (soft delete)
- ✅ **Exportación de Datos**: GET /user/data-export (JSON completo)
- ✅ **Consentimiento**: Checkbox obligatorio en registro para términos y privacidad
- ✅ **Anonimización**: Después de 2 años de inactividad, anonimizar datos sensibles
- ✅ **Auditoría**: Tabla `audit_logs` para cambios críticos (admin actions, cambios de permisos)

**Auditoría & Logs:**
```php
// Tabla audit_logs
{
  id, user_id, business_id,
  accion: 'created' | 'updated' | 'deleted',
  modelo: 'Appointment' | 'Service' | 'Employee',
  modelo_id: number,
  datos_previos: jsonb,      // Estado anterior
  datos_nuevos: jsonb,       // Estado nuevo
  ip_address: string,
  user_agent: string,
  created_at
}

// Registrar cambios críticos
AuditLog::create([
  'user_id' => auth()->id(),
  'business_id' => $appointment->business_id,
  'accion' => 'cancelled',
  'modelo' => 'Appointment',
  'modelo_id' => $appointment->id,
  'datos_previos' => $appointment->getOriginal(),
  'ip_address' => request()->ip(),
  'user_agent' => request()->userAgent(),
]);
```

**Backup & Recovery:**
- ✅ **Backups Automáticos**: Diarios (retención 30 días), semanales (retención 3 meses)
- ✅ **Disaster Recovery**: RPO 24h, RTO 4h
- ✅ **Restore Testing**: Mensual en ambiente staging

### Documentation Standards

```php
/**
 * Generate available appointment slots for a given service and date range.
 * 
 * Applies business rules:
 * 1. Base schedule from schedule_templates
 * 2. Schedule exceptions (holidays, vacations)
 * 3. Employee availability (existing appointments + buffers)
 * 4. Resource capacity (Phase 2)
 * 
 * @param Business $business Tenant context
 * @param Service $service Service being booked
 * @param Carbon $startDate Start of date range
 * @param Carbon $endDate End of date range
 * @param Employee|null $employee Optional specific employee filter
 * 
 * @return Collection<Slot> Available slots
 * 
 * @throws InvalidArgumentException If date range invalid
 * @throws NoScheduleConfiguredException If location has no schedule
 */
public function generateSlots(
    Business $business,
    Service $service,
    Carbon $startDate,
    Carbon $endDate,
    ?Employee $employee = null
): Collection;
```

---

## KPIs y Métricas

### Métricas por Usuario Final

```typescript
// Dashboard Usuario
{
  citas_totales: number,           // Histórico completo
  citas_proximas: number,          // Estado 'confirmed'
  citas_completadas: number,       // Estado 'completed'
  tasa_no_show: number,            // (no_show / total) * 100
  negocios_favoritos: number,      // Count de user_favorite_businesses
  ultima_cita: datetime,
  promedio_citas_mes: number       // Últimos 6 meses
}
```

### Métricas por Negocio

```php
// GET /api/v1/business/dashboard
{
  // Citas
  citas_hoy: number,
  citas_semana: number,
  citas_mes: number,
  tasa_ocupacion: number,          // (citas / slots_totales) * 100
  
  // Ingresos
  ingresos_mes: number,            // SUM(appointments.service.precio)
  ingreso_promedio_cita: number,
  
  // Clientes
  clientes_unicos_mes: number,     // DISTINCT user_id
  clientes_nuevos_mes: number,     // Primera cita este mes
  clientes_recurrentes: number,    // 2+ citas
  
  // Operación
  tasa_cancelacion: number,        // (cancelled / total) * 100
  tasa_no_show: number,
  tiempo_promedio_llenado: number, // Días entre creación slot y booking
  
  // Servicios top
  servicios_mas_solicitados: [
    { servicio_id, nombre, cantidad, ingresos }
  ],
  
  // Empleados
  empleado_mas_ocupado: { id, nombre, citas_mes }
}
```

### Métricas de Plataforma

```php
// GET /api/v1/admin/platform-metrics
{
  // Negocios
  negocios_totales: number,
  negocios_activos: number,        // Con >=1 cita último mes
  negocios_pendientes: number,     // Estado 'pending'
  churn_rate: number,              // Negocios inactivos último trimestre
  
  // Usuarios
  usuarios_totales: number,
  usuarios_activos: number,        // Con >=1 cita último mes
  nuevos_registros_mes: number,
  
  // Citas
  citas_totales_mes: number,
  citas_completadas_mes: number,
  tasa_exito_global: number,       // (completed / total) * 100
  
  // Revenue (si hay pagos)
  comision_plataforma_mes: number, // % de transacciones
  
  // Growth
  crecimiento_negocios: number,    // % vs mes anterior
  crecimiento_usuarios: number,
  crecimiento_citas: number
}
```

### Reportes Generables

**Para Negocios:**
1. Reporte de Ingresos (diario/semanal/mensual)
2. Reporte de Ocupación por Empleado
3. Reporte de Servicios Más Vendidos
4. Reporte de Clientes Recurrentes
5. Reporte de Cancelaciones y No-Shows

**Para Plataforma:**
1. Reporte de Actividad por Negocio
2. Reporte de Revenue (comisiones)
3. Reporte de Churn Rate
4. Reporte de NPS (Net Promoter Score) - Fase 2

---

## Key Files & Directories

### Documentación Arquitectónica
- [docs/motor_disponibilidad_pseudocodigo.md](docs/motor_disponibilidad_pseudocodigo.md) - Lógica completa del motor de disponibilidad
- [docs/plan_desarrollo_base_datos.md](docs/plan_desarrollo_base_datos.md) - Plan de fases de desarrollo

### Especificaciones de Base de Datos
- [database/sql/00_especificacion_enums.md](database/sql/00_especificacion_enums.md) - Tipos ENUM PostgreSQL
- [database/schemas/01_diagrama_erd_conceptual.md](database/schemas/01_diagrama_erd_conceptual.md) - ERD completo
- [database/documentation/01_mapeo_matriz_permisos_rbac.md](database/documentation/01_mapeo_matriz_permisos_rbac.md) - Matriz RBAC detallada
- [database/documentation/02_especificacion_indices.md](database/documentation/02_especificacion_indices.md) - Índices críticos

---

## Common Pitfalls

### ❌ Olvidar business_id en queries manuales
```php
// MAL: Query sin filtro de tenant
$services = Service::all();

// BIEN: Explícito aunque exista Global Scope
$services = Service::where('business_id', $businessId)->get();
```

### ❌ Crear citas sin lock pessimista
```php
// MAL: Race condition posible
$available = $this->checkAvailability($slot);
if ($available) {
    Appointment::create($data);
}

// BIEN: Transacción con lock
DB::transaction(function () use ($data) {
    Appointment::lockForUpdate()->where(...)->get();
    // Validar y crear
});
```

### ❌ Ignorar buffers en validación de disponibilidad
```php
// MAL: Solo comparar hora_inicio y hora_fin
$overlaps = $existing->hora_fin > $new->hora_inicio;

// BIEN: Incluir buffers pre y post
$existingEnd = $existing->hora_fin->addMinutes($service->buffer_post_minutos);
$newStart = $new->hora_inicio->subMinutes($service->buffer_pre_minutos);
$overlaps = $existingEnd > $newStart;
```

### ❌ Asignar roles sin validar contexto de negocio
```php
// MAL: Asignar rol global
$user->roles()->attach($roleId);

// BIEN: Asignar rol en contexto de negocio específico
BusinessUserRole::create([
    'user_id' => $user->id,
    'business_id' => $businessId,
    'role_id' => $roleId,
]);
```

---

## Edge Cases y Consideraciones Especiales

### Timezone Handling
```php
// Convertir todas las horas a UTC en BD, mostrar en zona de sucursal
$appointment->fecha_hora_inicio = Carbon::parse($input, $location->zona_horaria)
    ->setTimezone('UTC');

// Al mostrar, convertir a zona del negocio
$displayTime = $appointment->fecha_hora_inicio
    ->setTimezone($location->zona_horaria);
```

### Problemas Comunes Documentados

| Caso | Problema | Solución |
|------|----------|----------|
| Citas canceladas | ¿Contar para disponibilidad? | NO - filtrar `estado != 'cancelled'` |
| Timezone | Usuario y sucursal en diferentes zonas | UTC en BD, convertir a zona de sucursal |
| Buffers infinitos | Buffer > duración del servicio | Validar en creación de servicio |
| Recurso sin capacidad | capacidad = 0 | Validar mínimo 1 en creación |
| Empleado sin servicios | employee_id inválido para service_id | Validar FK en employee_services |
| Hora cierre medianoche | 24:00 vs 00:00 | Usar formato 24h, tipo TIME en BD |

---

## Riesgos Técnicos y Mitigación

### Top 10 Riesgos Identificados

| # | Riesgo | Probabilidad | Impacto | Mitigación |
|---|--------|--------------|---------|------------|
| 1 | **Doble booking en alta concurrencia** | Media | Crítico | Lock pessimista (SELECT FOR UPDATE), índices optimizados, tests de carga |
| 2 | **Performance del motor de disponibilidad** | Alta | Alto | Caché Redis (5min), índices compuestos, limitar rango de consulta a 30 días |
| 3 | **Inconsistencia de timezone** | Media | Alto | UTC en BD, conversión explícita en API responses, validación en frontend |
| 4 | **Escalabilidad del caché** | Media | Medio | Partición por tenant (business_id), cache tags, invalidación selectiva |
| 5 | **Fallo de WhatsApp/Twilio** | Baja | Alto | Fallback a email, retry automático 3x, logs de fallos |
| 6 | **Cambios de horario invalidan citas** | Media | Medio | Validación async de citas futuras, notificar afectados, opción de reprogramar |
| 7 | **Abuso de rate limiting** | Alta | Medio | IP-based + user-based rate limit, CAPTCHA en registro, firewall WAF |
| 8 | **Wizard incompleto deja negocio inválido** | Media | Alto | Validación en cada paso, draft state, email de recordatorio si abandona |
| 9 | **Data migration en producción** | Baja | Crítico | Backups pre-migration, rollback plan, maintenance mode, tests en staging |
| 10 | **GDPR compliance en exportación** | Media | Alto | Endpoint automatizado, formato JSON, incluir datos de auditoría, encriptación |

### Monitoreo y Alertas

**Métricas a monitorear (con thresholds):**
- Response time > 2s en endpoint /availability/slots → Alert
- Error rate > 5% en cualquier endpoint → Critical Alert
- Failed logins > 10 en 5min del mismo IP → Posible ataque, bloquear IP
- DB connections > 80% del pool → Scale up warning
- Slot cache hit rate < 70% → Revisar estrategia de caché
- WhatsApp delivery failure > 15% → Revisar Twilio, activar fallback

**Herramientas sugeridas:**
- Logs: Laravel Telescope (dev), ELK Stack o Datadog (prod)
- APM: New Relic o Scout APM
- Errors: Sentry
- Uptime: UptimeRobot o Pingdom

---

## Plan de Desarrollo (Sprints)

### Sprint 1 (Semanas 1-2): Fundación
- Setup Laravel + PostgreSQL + migraciones base
- Autenticación con Sanctum (registro, login, tokens)
- CRUD Negocios y Sucursales
- Seeders de datos de prueba

### Sprint 2 (Semanas 3-4): Core Negocio
- CRUD Servicios, Empleados, Horarios
- Motor de disponibilidad (generación de slots)
- Sistema RBAC completo
- API de disponibilidad pública

### Sprint 3 (Semanas 5-6): Reservas
- Flujo completo de creación de citas
- Validación con lock pessimista
- Historial de estados
- Panel web Next.js (estructura base)

### Sprint 4 (Semanas 7-8): MVP Completo
- App móvil Expo (flujo de reserva)
- Notificaciones email básicas
- Panel de reportes simple
- Testing E2E y deploy staging

---

## Additional Resources

- **PostgreSQL 14+ Docs**: https://www.postgresql.org/docs/14/
- **Laravel Multi-Tenancy**: https://tenancyforlaravel.com/docs/
- **Laravel Sanctum**: https://laravel.com/docs/sanctum
- **React Query (TanStack)**: https://tanstack.com/query/latest
- **Expo Documentation**: https://docs.expo.dev/
