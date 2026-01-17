# Stack Tecnológico Actualizado - Citas Empresariales SaaS

**Fecha de actualización**: 16 de enero de 2026

## Cambio de Arquitectura

### Stack Anterior (Descartado)
- ❌ **Backend**: Laravel API
- ❌ **Frontend**: Next.js + React + TypeScript + ShadCN/UI
- ❌ **Problema**: Next.js requiere Node.js en servidor (no disponible en Neubox Tellit)

### Stack Actual (Aprobado)
- ✅ **Backend + Frontend**: Laravel 12+ Monolito
- ✅ **Frontend**: Blade + Livewire 3.x + Alpine.js 3.x + Tailwind CSS
- ✅ **Base de Datos**: MariaDB 11.4.9+
- ✅ **App Móvil**: Flutter 3.x + Dart
- ✅ **Ventaja**: 100% compatible con hosting compartido Neubox

---

## Stack Detallado

### Backend (Laravel 12+)

| Componente | Versión | Propósito |
|------------|---------|-----------|
| **Laravel Framework** | 12.x | Framework PHP base |
| **MariaDB** | 11.4.9+ | Base de datos |
| **Laravel Breeze** | Latest | Autenticación web (session-based) |
| **Laravel Sanctum** | 4.x | Autenticación API móvil (token-based) |
| **Livewire** | 3.x | Componentes reactivos full-stack |
| **Spatie Permissions** | 6.x | RBAC multi-tenant (opcional) |

### Frontend Web (Blade + Livewire)

| Componente | Versión | Propósito |
|------------|---------|-----------|
| **Laravel Blade** | Nativo | Motor de plantillas |
| **Livewire** | 3.x | Componentes reactivos sin JavaScript |
| **Alpine.js** | 3.x | JavaScript ligero para interactividad |
| **Tailwind CSS** | 3.x | Utilidades CSS |
| **Heroicons** | Latest | Iconos SVG |
| **Vite** | 5.x | Bundler para assets |

### App Móvil (Flutter)

| Componente | Versión | Propósito |
|------------|---------|-----------|
| **Flutter** | 3.x | Framework multiplataforma |
| **Dart** | 3.x | Lenguaje de programación |
| **Riverpod/Provider** | Latest | State management |
| **Dio/HTTP** | Latest | Cliente HTTP para API |
| **Flutter Secure Storage** | Latest | Almacenamiento seguro de tokens |

---

## Ventajas del Stack Actualizado

### ✅ Compatibilidad Total con Neubox
1. **Sin Node.js requerido**: Todo corre con PHP 8.2
2. **Sin servidores adicionales**: Todo en un solo hosting
3. **Acceso cPanel**: Configuración completa vía web
4. **Sin complejidades**: No necesitas SSH

### ✅ Desarrollo Más Rápido
1. **Menos código**: No necesitas API REST completa
2. **Livewire = SPA sin JavaScript**: Reactivo pero simple
3. **Alpine.js**: Solo para interacciones pequeñas
4. **Un solo proyecto**: No mantener 2 repositorios (frontend/backend)

### ✅ Performance Mejorado
1. **Server-side rendering**: Mejor SEO
2. **Menos requests HTTP**: Livewire minimiza llamadas API
3. **Caché integrado**: Laravel cache en mismo servidor

### ✅ Seguridad Mejorada
1. **CSRF automático**: Laravel maneja tokens
2. **Session-based auth**: Más seguro que JWT para web
3. **Sanctum para móvil**: Tokens solo para Flutter app

---

## Arquitectura del Sistema

```
┌─────────────────────────────────────────────────────────┐
│                    NEUBOX TELLIT                        │
│                  (Hosting Compartido)                   │
│                                                         │
│  ┌────────────────────────────────────────────────┐   │
│  │         Laravel 12 Monolito                    │   │
│  │                                                 │   │
│  │  ┌──────────────┐         ┌─────────────────┐ │   │
│  │  │  Web Routes  │         │   API Routes    │ │   │
│  │  │  (Session)   │         │   (Sanctum)     │ │   │
│  │  └──────┬───────┘         └────────┬────────┘ │   │
│  │         │                           │          │   │
│  │  ┌──────▼────────┐         ┌───────▼────────┐ │   │
│  │  │ Blade Views   │         │  JSON API      │ │   │
│  │  │ + Livewire    │         │  Controllers   │ │   │
│  │  │ + Alpine.js   │         │  + Resources   │ │   │
│  │  └───────────────┘         └────────────────┘ │   │
│  │                                                 │   │
│  │         ┌───────────────────────┐              │   │
│  │         │   Eloquent Models     │              │   │
│  │         │   + Global Scopes     │              │   │
│  │         └──────────┬────────────┘              │   │
│  └────────────────────┼─────────────────────────┘   │
│                       │                              │
│            ┌──────────▼──────────┐                  │
│            │   MariaDB 11.4.9    │                  │
│            │  (Multi-Tenant DB)  │                  │
│            └─────────────────────┘                  │
└─────────────────────────────────────────────────────┘
                       ▲
                       │
         ┌─────────────┴──────────────┐
         │                            │
    ┌────▼─────┐              ┌──────▼──────┐
    │ Usuarios │              │  App Móvil  │
    │   Web    │              │  (Flutter)  │
    │ (Blade)  │              │ + Sanctum   │
    └──────────┘              └─────────────┘
```

---

## Flujo de Autenticación

### Para Usuarios Web (Panel Admin/Negocios)

```php
// Login tradicional con Laravel Breeze
1. Usuario → GET /login (formulario Blade)
2. Usuario → POST /login (credenciales)
3. Laravel → Valida y crea sesión
4. Redirect → /dashboard
5. Middleware auth → Valida sesión en cada request
```

**Características:**
- ✅ Session cookies (HttpOnly, Secure)
- ✅ CSRF protection automático
- ✅ Remember me opcional
- ✅ Password reset con email

### Para App Móvil (Flutter)

```php
// API REST con Sanctum tokens
1. App → POST /api/v1/auth/login {email, password}
2. Laravel → Valida credenciales
3. Laravel → Genera token Sanctum
4. Response → {token: "xyz123...", user: {...}}
5. App → Guarda token en Flutter Secure Storage
6. Requests → Header: "Authorization: Bearer xyz123..."
```

**Características:**
- ✅ Bearer tokens
- ✅ Token abilities/scopes
- ✅ Token expiration
- ✅ Token revocation

---

## Estructura de Proyecto Laravel

```
citas-empresariales/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/                    # Laravel Breeze auth
│   │   │   ├── Api/                     # API para móvil
│   │   │   │   ├── V1/
│   │   │   │   │   ├── AuthController.php
│   │   │   │   │   ├── AppointmentController.php
│   │   │   │   │   └── BusinessController.php
│   │   │   ├── DashboardController.php
│   │   │   ├── NegocioController.php
│   │   │   ├── SucursalController.php
│   │   │   ├── ServicioController.php
│   │   │   └── CitaController.php
│   │   └── Middleware/
│   │       ├── CheckBusinessAccess.php  # Multi-tenant
│   │       └── CheckPermission.php      # RBAC
│   ├── Livewire/                        # Componentes Livewire
│   │   ├── Auth/
│   │   ├── Dashboard/
│   │   ├── Negocios/
│   │   │   ├── WizardAlta.php          # Wizard de 5 pasos
│   │   │   ├── ListaNegocios.php
│   │   │   └── FormularioNegocio.php
│   │   ├── Sucursales/
│   │   ├── Servicios/
│   │   ├── Empleados/
│   │   ├── Citas/
│   │   │   ├── CalendarioDisponibilidad.php
│   │   │   ├── FormularioCita.php
│   │   │   └── ListaCitas.php
│   │   └── Shared/
│   │       ├── Modal.php
│   │       ├── NotificationToast.php
│   │       └── ConfirmDialog.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Business.php
│   │   ├── BusinessLocation.php
│   │   ├── Service.php
│   │   ├── Employee.php
│   │   ├── Appointment.php
│   │   └── Scopes/
│   │       └── BusinessScope.php        # Global scope multi-tenant
│   ├── Services/
│   │   ├── AvailabilityService.php      # Motor de disponibilidad
│   │   ├── AppointmentService.php
│   │   └── NotificationService.php
│   └── Policies/
│       ├── BusinessPolicy.php
│       ├── AppointmentPolicy.php
│       └── ...
├── database/
│   ├── migrations/
│   ├── seeders/
│   └── factories/
├── resources/
│   ├── views/
│   │   ├── layouts/
│   │   │   ├── app.blade.php           # Layout principal
│   │   │   ├── guest.blade.php
│   │   │   └── navigation.blade.php
│   │   ├── auth/                       # Laravel Breeze
│   │   ├── dashboard/
│   │   │   └── index.blade.php
│   │   ├── negocios/
│   │   │   ├── index.blade.php
│   │   │   ├── create.blade.php        # Wizard
│   │   │   └── edit.blade.php
│   │   ├── sucursales/
│   │   ├── servicios/
│   │   ├── empleados/
│   │   ├── citas/
│   │   ├── livewire/                   # Componentes Livewire
│   │   └── components/                 # Blade components
│   ├── css/
│   │   └── app.css                     # Tailwind
│   └── js/
│       ├── app.js                      # Alpine.js
│       └── bootstrap.js
├── routes/
│   ├── web.php                         # Rutas web (Blade)
│   ├── api.php                         # Rutas API (móvil)
│   └── console.php                     # Comandos artisan
├── tests/
│   ├── Feature/
│   └── Unit/
├── .env                                # Configuración
├── composer.json                       # Dependencias PHP
├── package.json                        # Dependencias JS
└── vite.config.js                      # Build config
```

---

## Dependencias Composer Requeridas

```json
{
    "require": {
        "php": "^8.2",
        "laravel/framework": "^12.0",
        "laravel/breeze": "^2.0",
        "laravel/sanctum": "^4.0",
        "livewire/livewire": "^3.0",
        "laravel/tinker": "^2.10"
    },
    "require-dev": {
        "fakerphp/faker": "^1.23",
        "laravel/pint": "^1.13",
        "phpunit/phpunit": "^11.5",
        "mockery/mockery": "^1.6"
    }
}
```

---

## Dependencias NPM Requeridas

```json
{
    "devDependencies": {
        "alpinejs": "^3.14",
        "tailwindcss": "^3.4",
        "autoprefixer": "^10.4",
        "postcss": "^8.4",
        "vite": "^5.0",
        "laravel-vite-plugin": "^1.0",
        "@tailwindcss/forms": "^0.5"
    }
}
```

---

## Configuración de Tailwind CSS

```javascript
// tailwind.config.js
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './app/Livewire/**/*.php',
    ],
    theme: {
        extend: {
            colors: {
                primary: '#3b82f6',
                secondary: '#64748b',
                success: '#22c55e',
                danger: '#ef4444',
                warning: '#f59e0b',
            },
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
    ],
}
```

---

## Configuración de Vite

```javascript
// vite.config.js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
    ],
});
```

---

## Ejemplo: Componente Livewire

```php
// app/Livewire/Citas/FormularioCita.php
namespace App\Livewire\Citas;

use Livewire\Component;
use App\Models\Service;
use App\Models\Employee;
use App\Services\AvailabilityService;
use Carbon\Carbon;

class FormularioCita extends Component
{
    public $serviceId;
    public $employeeId;
    public $fecha;
    public $slots = [];
    public $selectedSlot;
    
    protected $rules = [
        'serviceId' => 'required|exists:services,id',
        'employeeId' => 'required|exists:employees,id',
        'fecha' => 'required|date|after:today',
        'selectedSlot' => 'required',
    ];
    
    public function updatedFecha()
    {
        // Livewire auto-llama este método cuando cambia la fecha
        $this->loadAvailableSlots();
    }
    
    public function loadAvailableSlots()
    {
        if ($this->serviceId && $this->employeeId && $this->fecha) {
            $availabilityService = new AvailabilityService();
            $this->slots = $availabilityService->generateSlots(
                Service::find($this->serviceId),
                Employee::find($this->employeeId),
                Carbon::parse($this->fecha)
            );
        }
    }
    
    public function submit()
    {
        $this->validate();
        
        // Crear cita...
        
        session()->flash('message', 'Cita creada exitosamente');
        return redirect()->route('citas.index');
    }
    
    public function render()
    {
        return view('livewire.citas.formulario-cita', [
            'services' => Service::all(),
            'employees' => Employee::all(),
        ]);
    }
}
```

```blade
{{-- resources/views/livewire/citas/formulario-cita.blade.php --}}
<div class="max-w-2xl mx-auto">
    <form wire:submit.prevent="submit" class="space-y-6">
        <!-- Servicio -->
        <div>
            <label for="service" class="block text-sm font-medium">Servicio</label>
            <select wire:model.live="serviceId" id="service" class="mt-1 block w-full">
                <option value="">Seleccionar servicio</option>
                @foreach($services as $service)
                    <option value="{{ $service->id }}">
                        {{ $service->nombre }} - ${{ $service->precio }}
                    </option>
                @endforeach
            </select>
            @error('serviceId') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>
        
        <!-- Empleado -->
        <div>
            <label for="employee" class="block text-sm font-medium">Empleado</label>
            <select wire:model.live="employeeId" id="employee" class="mt-1 block w-full">
                <option value="">Seleccionar empleado</option>
                @foreach($employees as $employee)
                    <option value="{{ $employee->id }}">{{ $employee->nombre }}</option>
                @endforeach
            </select>
            @error('employeeId') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>
        
        <!-- Fecha -->
        <div>
            <label for="fecha" class="block text-sm font-medium">Fecha</label>
            <input wire:model.live="fecha" type="date" id="fecha" class="mt-1 block w-full">
            @error('fecha') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>
        
        <!-- Slots disponibles -->
        @if(count($slots) > 0)
            <div>
                <label class="block text-sm font-medium mb-2">Horarios disponibles</label>
                <div class="grid grid-cols-3 gap-2">
                    @foreach($slots as $slot)
                        <button 
                            type="button"
                            wire:click="$set('selectedSlot', '{{ $slot }}')"
                            class="px-4 py-2 border rounded-lg 
                                {{ $selectedSlot === $slot ? 'bg-blue-500 text-white' : 'bg-white' }}
                                hover:bg-blue-100 transition"
                        >
                            {{ $slot }}
                        </button>
                    @endforeach
                </div>
            </div>
        @endif
        
        <!-- Submit -->
        <button 
            type="submit" 
            class="w-full bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600"
            wire:loading.attr="disabled"
        >
            <span wire:loading.remove>Crear Cita</span>
            <span wire:loading>Creando...</span>
        </button>
    </form>
</div>
```

---

## Próximos Pasos

1. ✅ Instalar Laravel Breeze en proyecto existente
2. ✅ Instalar Livewire 3
3. ✅ Configurar Tailwind CSS + Alpine.js
4. ✅ Crear migraciones MariaDB
5. ✅ Implementar RBAC multi-tenant
6. ✅ Desarrollar componentes Livewire
7. ✅ Desplegar en Neubox cPanel

---

## Recursos de Aprendizaje

- **Livewire**: https://livewire.laravel.com/
- **Alpine.js**: https://alpinejs.dev/
- **Laravel Breeze**: https://laravel.com/docs/11.x/starter-kits#breeze
- **Tailwind CSS**: https://tailwindcss.com/
- **Laravel Sanctum**: https://laravel.com/docs/11.x/sanctum
