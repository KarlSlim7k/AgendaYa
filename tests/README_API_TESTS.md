# Tests API Sprint 2

## Estado Actual

✅ **Completado:**
- 4 archivos de tests creados con cobertura completa:
  - ServiceApiTest.php (10 tests)
  - EmployeeApiTest.php (6 tests)
  - ScheduleApiTest.php (9 tests)
  - AvailabilityApiTest.php (5 tests)
  
✅ **Factories creados:**
  - ServiceFactory
  - EmployeeFactory
  - ScheduleTemplateFactory
  - ScheduleExceptionFactory

⚠️ **Pendiente Fase 3:**

Los tests requieren que la tabla `users` tenga la columna `current_business_id` para el funcionamiento completo del multi-tenancy.

### Blocker Identificado

**Columna faltante:** `current_business_id` en tabla `users`

Esta columna es necesaria para:
1. Global Scopes en modelos Service y Employee
2. Validación de tenant ownership en controllers
3. Queries automáticos filtrados por tenant

### Solución Propuesta

Crear migración para agregar esta columna:

```php
Schema::table('users', function (Blueprint $table) {
    $table->foreignId('current_business_id')
        ->nullable()
        ->after('email_verified_at')
        ->constrained('businesses')
        ->nullOnDelete();
    $table->index('current_business_id');
});
```

### Casos de Prueba Implementados

#### ServiceApiTest
- ✅ Lista servicios para usuario autenticado  
- ✅ Crea servicio con datos válidos
- ✅ Valida campos requeridos
- ✅ Valida duración mínima (15 minutos)
- ✅ Actualiza servicio correctamente
- ✅ Previene actualizar servicios de otro negocio (multi-tenant)
- ✅ Elimina servicio correctamente
- ✅ Requiere autenticación
- ✅ Filtra por término de búsqueda
- ✅ Filtra por estado activo/inactivo

#### EmployeeApiTest
- ✅ Crea empleado con servicios asignados
- ✅ Actualiza empleado y sincroniza servicios
- ✅ Filtra empleados por servicio
- ✅ Filtra empleados por estado
- ✅ Elimina empleado
- ✅ Valida email único por negocio (multi-tenant)

#### ScheduleApiTest
- ✅ Lista templates de horarios por sucursal
- ✅ Crea/actualiza template (upsert logic)
- ✅ Valida hora_cierre > hora_apertura
- ✅ Valida dia_semana (0-6)
- ✅ Lista excepciones de horarios
- ✅ Crea excepción de horario
- ✅ Valida hora_inicio/fin cuando no es todo_el_dia
- ✅ Elimina excepción
- ✅ Previene acceso a horarios de otro negocio (multi-tenant)

#### AvailabilityApiTest
- ✅ Retorna slots disponibles (endpoint público)
- ✅ Valida parámetros requeridos
- ✅ Valida fecha_fin después de fecha_inicio
- ✅ Limita rango a 30 días máximo
- ✅ Filtra slots por empleado específico

### Ejecutar Tests (cuando esté listo)

```bash
# Todos los tests API
php artisan test --filter=Api

# Tests específicos
php artisan test --filter=ServiceApiTest
php artisan test --filter=EmployeeApiTest
php artisan test --filter=ScheduleApiTest
php artisan test --filter=AvailabilityApiTest
```

### Métricas de Cobertura

- **Total tests:** 30
- **Assertions esperadas:** ~150+
- **Cobertura:** CRUD completo + validaciones + multi-tenancy

