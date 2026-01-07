# Mapeo de Matriz de Permisos RBAC

**Versión**: 1.0  
**Fecha**: 01 de enero de 2026  
**Documento Base**: Arquitectura SaaS Multi-Tenant - Sección 2 (Definición de Roles y Permisos)  
**Estado**: Aprobado para implementación en FASE 3

---

## Introducción

Este documento traduce la matriz de permisos del documento arquitectónico a una estructura clara y exhaustiva para implementación en base de datos. La matriz define qué acción (Create, Read, Update, Delete) puede realizar cada rol en cada módulo.

---

## Leyenda de Matriz

| Símbolo | Significado | Implementación |
|---------|------------|---|
| **CRUD** | Create, Read, Update, Delete | Permisos: create, read, update, delete |
| **R** | Read only | Permiso: read |
| **U** | Update only | Permiso: update |
| **C** | Create only | Permiso: create |
| **R/U** | Read or Update | Permisos: read, update |
| **—** | No permitido | Sin permisos |
| **(*)** | Scope: solo datos propios | Validado en API |
| **(sucursal)** | Scope: solo su sucursal | Validado por business_id + location_id |
| **(todo)** | Scope: todo el negocio | Validado por business_id |
| **(todos)** | Scope: toda la plataforma | Sin filtro de business_id |

---

## Matriz Completa de Permisos

### MÓDULO: Perfil (Usuario)

| Recurso | Acción | USUARIO_FINAL | NEGOCIO_STAFF | NEGOCIO_MANAGER | NEGOCIO_ADMIN | PLATAFORMA_ADMIN |
|---------|--------|---|---|---|---|---|
| Perfil | Create | C (propio) | C (propio) | C (propio) | C (propio) | C (todo) |
| Perfil | Read | R (propio) | R (propio) | R (propio) | R (propio) | R (todos) |
| Perfil | Update | U (propio) | U (propio) | U (propio) | U (propio) | U (todos) |
| Perfil | Delete | — | — | — | — | — |

**Permisos a crear**:
- `perfil.create`
- `perfil.read`
- `perfil.update`

**Restricción de Scope**:
```sql
-- Usuario solo puede crear/leer/actualizar su propio perfil
WHERE user_id = auth()->id()
```

---

### MÓDULO: Negocio

| Recurso | Acción | USUARIO_FINAL | NEGOCIO_STAFF | NEGOCIO_MANAGER | NEGOCIO_ADMIN | PLATAFORMA_ADMIN |
|---------|--------|---|---|---|---|---|
| Negocio Info | Read | R | — | R (sucursal) | R (todo) | R (todos) |
| Negocio Info | Create | — | — | — | — | C |
| Negocio Info | Update | — | — | — | CRUD (todo) | CRUD (todos) |
| Negocio Info | Delete | — | — | — | — | — |

**Permisos a crear**:
- `negocio.read`
- `negocio.create`
- `negocio.update`
- `negocio.delete`

**Restricción de Scope**:
```sql
-- USUARIO_FINAL: solo lee negocio público (no requiere business_id en filtro)
-- NEGOCIO_MANAGER: solo lee/actualiza su sucursal asignada
WHERE business_id = auth()->user()->current_business_id

-- NEGOCIO_ADMIN: acceso completo al negocio
WHERE business_id = auth()->user()->business_id

-- PLATAFORMA_ADMIN: acceso a todos
-- No hay filtro
```

---

### MÓDULO: Sucursal

| Recurso | Acción | USUARIO_FINAL | NEGOCIO_STAFF | NEGOCIO_MANAGER | NEGOCIO_ADMIN | PLATAFORMA_ADMIN |
|---------|--------|---|---|---|---|---|
| Sucursal | Read | R | — | R (asignada) | R (todas) | R (todas) |
| Sucursal | Create | — | — | — | CRUD | CRUD |
| Sucursal | Update | — | — | U (asignada) | CRUD | CRUD |
| Sucursal | Delete | — | — | — | D | D |

**Permisos a crear**:
- `sucursal.read`
- `sucursal.create`
- `sucursal.update`
- `sucursal.delete`

**Restricción de Scope**:
```sql
-- NEGOCIO_MANAGER: solo sucursal asignada
WHERE business_id = ? AND location_id = auth()->user()->assigned_location_id

-- NEGOCIO_ADMIN: todas las sucursales del negocio
WHERE business_id = ?

-- PLATAFORMA_ADMIN: todas
-- Sin filtro
```

---

### MÓDULO: Servicio

| Recurso | Acción | USUARIO_FINAL | NEGOCIO_STAFF | NEGOCIO_MANAGER | NEGOCIO_ADMIN | PLATAFORMA_ADMIN |
|---------|--------|---|---|---|---|---|
| Servicio | Read | R | R (asignados) | R (sucursal) | R (todo) | R (todos) |
| Servicio | Create | — | — | — | CRUD | CRUD |
| Servicio | Update | — | — | — | CRUD | CRUD |
| Servicio | Delete | — | — | — | D | D |

**Permisos a crear**:
- `servicio.read`
- `servicio.create`
- `servicio.update`
- `servicio.delete`

**Restricción de Scope**:
```sql
-- USUARIO_FINAL: acceso público (no requiere business_id)
-- Ver todos los servicios de cualquier negocio

-- NEGOCIO_STAFF: solo servicios asignados (vía employee_services)
WHERE EXISTS (
  SELECT 1 FROM employee_services 
  WHERE employee_id = auth()->user()->employee_id 
  AND service_id = services.id
)

-- NEGOCIO_MANAGER: servicios de su sucursal
WHERE business_id = ? AND location_id IN (
  SELECT id FROM business_locations WHERE business_id = ?
)

-- NEGOCIO_ADMIN: todos los servicios del negocio
WHERE business_id = ?

-- PLATAFORMA_ADMIN: acceso sin restricción
```

---

### MÓDULO: Empleado

| Recurso | Acción | USUARIO_FINAL | NEGOCIO_STAFF | NEGOCIO_MANAGER | NEGOCIO_ADMIN | PLATAFORMA_ADMIN |
|---------|--------|---|---|---|---|---|
| Empleado | Read | R (lista pública) | — | R (sucursal) | R (todo) | R (todos) |
| Empleado | Create | — | — | — | CRUD | CRUD |
| Empleado | Update | — | — | — | CRUD | CRUD |
| Empleado | Delete | — | — | — | D | D |

**Permisos a crear**:
- `empleado.read`
- `empleado.create`
- `empleado.update`
- `empleado.delete`

**Restricción de Scope**:
```sql
-- USUARIO_FINAL: solo lista pública (nombres, disponibilidad)
-- Sin acceso a detalles sensibles

-- NEGOCIO_MANAGER: empleados de su sucursal
WHERE business_id = ? AND location_id IN (
  SELECT id FROM business_locations WHERE id = ?
)

-- NEGOCIO_ADMIN: todos los empleados
WHERE business_id = ?

-- PLATAFORMA_ADMIN: acceso sin restricción
```

---

### MÓDULO: Agenda (Slots/Disponibilidad)

| Recurso | Acción | USUARIO_FINAL | NEGOCIO_STAFF | NEGOCIO_MANAGER | NEGOCIO_ADMIN | PLATAFORMA_ADMIN |
|---------|--------|---|---|---|---|---|
| Slot | Create | C (reservar) | — | — | — | — |
| Slot | Read | R | R (propia) | R (sucursal) | R (todo) | R (todos) |

**Permisos a crear**:
- `agenda.create` (crear/reservar slot)
- `agenda.read` (ver disponibilidad)

**Nota**: No hay Update/Delete en slots. Las citas se gestionan en módulo Cita.

**Restricción de Scope**:
```sql
-- USUARIO_FINAL: acceso a todos los slots disponibles públicos
-- Sin restricción (calcula disponibilidad por el motor)

-- NEGOCIO_STAFF: solo su agenda personal
WHERE employee_id = auth()->user()->employee_id

-- NEGOCIO_MANAGER: agenda de sucursal
WHERE business_id = ? AND location_id = ?

-- NEGOCIO_ADMIN: agenda completa del negocio
WHERE business_id = ?

-- PLATAFORMA_ADMIN: acceso sin restricción
```

---

### MÓDULO: Cita

| Recurso | Acción | USUARIO_FINAL | NEGOCIO_STAFF | NEGOCIO_MANAGER | NEGOCIO_ADMIN | PLATAFORMA_ADMIN |
|---------|--------|---|---|---|---|---|
| Cita | Create | C (propia) | — | — | — | — |
| Cita | Read | R (propias) | R (asignadas) | R (sucursal) | R (todo) | R (todos) |
| Cita | Update | U (estado propio) | U (estado asignadas) | U (sucursal) | CRUD | CRUD |
| Cita | Delete | — | — | — | D | D |

**Permisos a crear**:
- `cita.create` (crear cita)
- `cita.read`
- `cita.update`
- `cita.delete`

**Restricción de Scope**:
```sql
-- USUARIO_FINAL: solo sus propias citas
WHERE user_id = auth()->id()

-- NEGOCIO_STAFF: citas asignadas a él
WHERE employee_id = auth()->user()->employee_id

-- NEGOCIO_MANAGER: citas de su sucursal
WHERE business_id = ? AND location_id = ?

-- NEGOCIO_ADMIN: todas las citas del negocio
WHERE business_id = ?

-- PLATAFORMA_ADMIN: acceso sin restricción
```

**Restricción de Update específico**:
```sql
-- USUARIO_FINAL: solo puede cambiar estado a 'cancelled' (cancelar propias citas)
-- NEGOCIO_STAFF: puede cambiar estado a 'completed', 'no_show'
-- NEGOCIO_MANAGER: puede cambiar cualquier estado de su sucursal
-- NEGOCIO_ADMIN: CRUD completo
-- PLATAFORMA_ADMIN: CRUD completo
```

---

### MÓDULO: Reportes Financieros

| Recurso | Acción | USUARIO_FINAL | NEGOCIO_STAFF | NEGOCIO_MANAGER | NEGOCIO_ADMIN | PLATAFORMA_ADMIN |
|---------|--------|---|---|---|---|---|
| Reportes | Read | — | — | R (sucursal) | R (todo) | R (todos) |
| Reportes | Create | — | — | — | — | — |
| Reportes | Update | — | — | — | — | — |
| Reportes | Delete | — | — | — | — | — |

**Permisos a crear**:
- `reportes_financieros.read`

**Nota**: No es Create/Update/Delete; son datos derivados de citas.

**Restricción de Scope**:
```sql
-- NEGOCIO_MANAGER: solo ingresos de su sucursal
WHERE business_id = ? AND location_id = ?

-- NEGOCIO_ADMIN: ingresos totales del negocio
WHERE business_id = ?

-- PLATAFORMA_ADMIN: ingresos de toda la plataforma
-- Sin filtro
```

---

## Tabla de Permisos Consolidada para BD

Total de permisos a crear: **22 permisos**

| # | Módulo | Acción | Nombre en BD | Descripción |
|---|--------|--------|---|---|
| 1 | Perfil | Create | `perfil.create` | Crear perfil propio |
| 2 | Perfil | Read | `perfil.read` | Leer perfil |
| 3 | Perfil | Update | `perfil.update` | Actualizar perfil |
| 4 | Negocio | Create | `negocio.create` | Crear negocio |
| 5 | Negocio | Read | `negocio.read` | Leer información negocio |
| 6 | Negocio | Update | `negocio.update` | Actualizar información negocio |
| 7 | Negocio | Delete | `negocio.delete` | Eliminar negocio |
| 8 | Sucursal | Create | `sucursal.create` | Crear sucursal |
| 9 | Sucursal | Read | `sucursal.read` | Leer sucursal |
| 10 | Sucursal | Update | `sucursal.update` | Actualizar sucursal |
| 11 | Sucursal | Delete | `sucursal.delete` | Eliminar sucursal |
| 12 | Servicio | Create | `servicio.create` | Crear servicio |
| 13 | Servicio | Read | `servicio.read` | Leer servicio |
| 14 | Servicio | Update | `servicio.update` | Actualizar servicio |
| 15 | Servicio | Delete | `servicio.delete` | Eliminar servicio |
| 16 | Empleado | Create | `empleado.create` | Crear empleado |
| 17 | Empleado | Read | `empleado.read` | Leer empleado |
| 18 | Empleado | Update | `empleado.update` | Actualizar empleado |
| 19 | Empleado | Delete | `empleado.delete` | Eliminar empleado |
| 20 | Agenda | Create | `agenda.create` | Crear/reservar cita (slot) |
| 21 | Agenda | Read | `agenda.read` | Ver disponibilidad |
| 22 | Cita | Create | `cita.create` | Crear cita |
| 23 | Cita | Read | `cita.read` | Leer cita |
| 24 | Cita | Update | `cita.update` | Actualizar cita |
| 25 | Cita | Delete | `cita.delete` | Eliminar cita |
| 26 | Reportes | Read | `reportes_financieros.read` | Leer reportes financieros |

---

## Matriz de Permisos por Rol

### USUARIO_FINAL

```
Permisos:
- perfil.create (propio)
- perfil.read (propio)
- perfil.update (propio)
- servicio.read (público)
- empleado.read (público)
- agenda.read (público - motor de disponibilidad)
- cita.create (propia)
- cita.read (propias)
- cita.update (solo cancelar propias)

Scopes:
- Sin business_id (acceso público)
- Filtrado por user_id = auth()->id()
```

### NEGOCIO_STAFF

```
Permisos:
- perfil.create (propio)
- perfil.read (propio)
- perfil.update (propio)
- servicio.read (asignados)
- agenda.read (propia)
- cita.read (asignadas)
- cita.update (cambiar estado de asignadas)

Scopes:
- business_id = auth()->user()->business_id
- Filtrado por employee_id = auth()->user()->employee_id
```

### NEGOCIO_MANAGER

```
Permisos:
- perfil.create (propio)
- perfil.read (propio)
- perfil.update (propio)
- negocio.read (su negocio)
- sucursal.read (asignada)
- sucursal.update (asignada)
- servicio.read (sucursal)
- empleado.read (sucursal)
- empleado.create
- empleado.update
- empleado.delete
- agenda.read (sucursal)
- cita.read (sucursal)
- cita.update (sucursal)
- reportes_financieros.read (sucursal)

Scopes:
- business_id = auth()->user()->business_id
- location_id = auth()->user()->assigned_location_id
```

### NEGOCIO_ADMIN

```
Permisos:
- perfil.create (propio)
- perfil.read (propio)
- perfil.update (propio)
- negocio.read (todo)
- negocio.update (todo)
- negocio.delete
- sucursal.create
- sucursal.read (todas)
- sucursal.update (todas)
- sucursal.delete
- servicio.create
- servicio.read (todas)
- servicio.update (todas)
- servicio.delete
- empleado.create
- empleado.read (todos)
- empleado.update (todos)
- empleado.delete
- agenda.read (todo)
- cita.read (todas)
- cita.update (todas)
- cita.delete
- reportes_financieros.read (todo)

Scopes:
- business_id = auth()->user()->business_id
- Sin restricción de location_id
```

### PLATAFORMA_ADMIN

```
Permisos:
- Todos los permisos (26 totales)

Scopes:
- Sin filtros de business_id, location_id, user_id
- Acceso universal a todos los datos
```

---

## Decisiones de Diseño RBAC

### [DECISIÓN RBAC-1]: Sistema Custom vs Spatie Permission

**Elegida**: Sistema Custom con tablas propias

**Justificación**:
- Matriz de permisos requiere granularidad específica (módulo + acción)
- Spatie Permission está optimizado para 2-3 roles; aquí hay 5 con scopes complejos
- Scopes multi-tenant requieren lógica custom (business_id + location_id)
- Mejor control y documentación para el equipo

### [DECISIÓN RBAC-2]: ¿Permisos directos a usuarios?

**Elegida**: Solo vía roles

**Justificación**:
- Simplifica auditoria y cambios de permisos
- Redondea: Usuario → Rol → Permisos
- Evita inconsistencias si alguien tiene permiso sin rol

### [DECISIÓN RBAC-3]: ¿Roles globales o por negocio?

**Elegida**: Ambos

**Justificación**:
- `USUARIO_FINAL` y `PLATAFORMA_ADMIN` son globales (business_id = NULL)
- `NEGOCIO_STAFF`, `NEGOCIO_MANAGER`, `NEGOCIO_ADMIN` son por negocio
- Soportado por tabla `business_user_roles` con business_id NULLABLE

---

## Validación de Escopes en API

**Patrón a implementar en Laravel**:

```php
// Controlador ejemplo
public function update(Request $request, Appointment $appointment)
{
    // Validar permiso a nivel de rol
    $this->authorize('cita.update');
    
    // Validar scope (multi-tenant)
    if ($appointment->business_id !== auth()->user()->business_id) {
        abort(403);
    }
    
    // Validar scope adicional si es manager
    if (auth()->user()->hasRole('NEGOCIO_MANAGER')) {
        if ($appointment->location_id !== auth()->user()->assigned_location_id) {
            abort(403);
        }
    }
    
    // Validación específica de cambio de estado
    $this->validateStateTransition($appointment, $request->estado);
    
    // Proceder con actualización
}
```

---

## Siguiente Paso

Esta matriz será traducida a INSERTs SQL para tabla `permissions` y `role_permissions` en FASE 3.

---

## Referencias

- Documento arquitectónico, Sección 2: Definición de Roles y Permisos
- Documento arquitectónico, Sección 3: Historias de Usuario
