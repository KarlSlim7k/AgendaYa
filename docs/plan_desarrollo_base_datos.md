# Plan de Desarrollo de Base de Datos - Citas Empresariales SaaS

## Resumen de Entregables para la Base de Datos

### Estructura General
Se desarrollará una **base de datos PostgreSQL 14+** única (single database) con estrategia multi-tenant por `business_id`. Todas las tablas de negocio incluirán este campo para segregación automática mediante scopes globales de Laravel.

### Componentes Principales a Generar

#### 1. **TIPOS ENUMERADOS PostgreSQL**
- `appointment_status` → pending, confirmed, completed, cancelled, no_show
- `schedule_exception_type` → feriado, vacaciones, cierre
- `resource_type` → fisico, virtual
- `user_role_type` → USUARIO_FINAL, NEGOCIO_STAFF, NEGOCIO_MANAGER, NEGOCIO_ADMIN, PLATAFORMA_ADMIN

#### 2. **TABLAS CORE (Plataforma + Negocio)**
- **Plataforma**: `platform_admins`, `platform_settings`
- **Negocio**: `businesses`, `business_locations`, `services`, `employees`, `employee_services` (pivote)
- **Horarios**: `schedule_templates`, `schedule_exceptions`
- **Usuarios**: `users`, `user_favorite_businesses`
- **Agenda**: `appointments`, `appointment_status_histories`
- **Recursos** (Fase 2): `resources`, `appointment_resources` (pivote)

#### 3. **TABLAS RBAC (Sistema de Permisos)**
- `roles` → 5 roles del sistema
- `permissions` → Permisos granulares por módulo (perfil, negocio, sucursal, etc.)
- `role_permissions` (pivote) → Asignación rol-permiso
- `business_user_roles` → Usuarios con roles específicos por negocio (multi-tenant)

#### 4. **TABLA AUXILIAR (Tracking Opcional)**
- `notification_logs` → Registro de notificaciones enviadas (email/WhatsApp) para auditoria

---

## Especificaciones Técnicas Incluidas

### Campos Especiales Agregados
- **Services**: `buffer_pre_minutos`, `buffer_post_minutos` (INTEGER, default 0)
- **Services**: `meta` (JSONB para campos personalizables)
- **Appointments**: `codigo_confirmacion` (VARCHAR UNIQUE para check-in)
- **Appointments**: `custom_data` (JSONB para datos personalizados del cliente)
- **Appointments**: `notas_cliente` (TEXT para observaciones)

### Campos de Auditoría Estándar
- `created_at TIMESTAMP` (auto)
- `updated_at TIMESTAMP` (auto)
- `deleted_at TIMESTAMP` (soft deletes en tablas críticas: users, businesses, employees)

### Índices Críticos para Performance
- **Multi-tenant**: `business_id` + fecha/estado en appointments, schedule_exceptions
- **Búsquedas**: email, teléfono en users
- **Disponibilidad**: employee_id + fecha_hora en appointments
- **JSON**: Índices GIN en meta, custom_data
- **Uniqueness**: business_id + nombre en servicios/empleados (por sucursal)

### Constraints y Validaciones
- **CHECK**: precios >= 0, duraciones > 0, buffer_minutos >= 0
- **CHECK**: fecha_hora_fin > fecha_hora_inicio en appointments
- **UNIQUE COMPUESTA**: (user_id, business_id, role_id) en business_user_roles
- **UNIQUE**: email global en users, email único por business en employees
- **FOREIGN KEYS**: Con ON DELETE CASCADE o SET NULL según relación

---

## Plan de Desarrollo en Fases

### **FASE 0: Preparación y Diseño** (Duración: 3-5 días)

**Objetivo**: Definir y validar el schema completo antes de implementación.

**Entregables**:
1. **Diagrama ERD conceptual** (texto) con todas las relaciones
2. **Script de ENUM types** PostgreSQL
3. **Mapeo de permisos** (matriz Excel/tabla) desde documento arquitectónico
4. **Checklist de validación** contra requisitos del documento
5. **Especificación de índices** con justificación de cada uno

**Actividades**:
- Revisar histórico de errores en reservas (RIESGO R1: concurrencia)
- Confirmar campos JSON con equipo de frontend
- Validar nombres de tablas con convención Laravel

**Criterios de aceptación**:
- ✓ Diagram validado por al menos 2 miembros del equipo
- ✓ Todos los permisos mapeados a roles
- ✓ Script SQL probado en PostgreSQL local

---

### **FASE 1: Tablas Base y Plataforma** (Duración: 1 semana)

**Objetivo**: Crear estructura fundamental del sistema (plataforma, usuarios, negocio).

**Entregables**:
1. **Script SQL completo** para tablas:
   - `platform_admins`
   - `platform_settings`
   - `users` (usuarios finales globales)
   - `businesses`
   - `business_locations`

2. **Migraciones Laravel** para las 5 tablas anteriores con:
   - Métodos `up()` y `down()`
   - Foreign keys con `constrained()` y `cascadeOnDelete()`
   - Índices básicos

3. **Seeder** para datos de prueba:
   - 2 platform admins
   - 5 usuarios finales
   - 3 negocios con 2 sucursales cada uno

**Datos en BD**: ~10 registros de prueba

**Criterios de aceptación**:
- ✓ Migraciones ejecutan sin errores
- ✓ Relaciones FK funcionan correctamente
- ✓ Soft deletes funcionales en users, businesses
- ✓ Queries básicas retornan datos esperados

---

### **FASE 2: Tablas de Configuración de Negocio** (Duración: 1 semana)

**Objetivo**: Crear estructura para que negocios configuren servicios, empleados y horarios.

**Entregables**:
1. **Script SQL** para tablas:
   - `services`
   - `employees`
   - `employee_services` (pivote)
   - `schedule_templates`
   - `schedule_exceptions`

2. **Migraciones Laravel** incluyendo:
   - ENUM types (appointment_status, schedule_exception_type)
   - Campos JSON (meta en services)
   - Campos buffer_pre/post
   - Índices compuestos (business_id + location_id)

3. **Seeder** con:
   - 10 servicios (peluquería, masaje, consulta médica, etc.)
   - 5 empleados por negocio
   - Horarios base (lunes-viernes 09:00-18:00, sábado 09:00-14:00)
   - 3 excepciones de ejemplo (festivos, vacaciones)

**Datos en BD**: ~50 registros

**Criterios de aceptación**:
- ✓ Tabla pivote `employee_services` carga correctamente
- ✓ Schedule templates generan sin conflictos
- ✓ Excepciones se aplican correctamente
- ✓ Índices aceleran búsquedas de disponibilidad

---

### **FASE 3: Sistema RBAC Completo** (Duración: 1 semana)

**Objetivo**: Implementar sistema de permisos granulares multi-tenant.

**Entregables**:
1. **Script SQL** para tablas:
   - `roles` (5 roles)
   - `permissions` (20+ permisos según matriz)
   - `role_permissions` (pivote)
   - `business_user_roles`

2. **Seeder SQL** con:
   - Inserción de 5 roles
   - Inserción de 20+ permisos (perfil, negocio, sucursal, servicio, empleado, agenda, cita, reportes)
   - Mapeo exacto de permisos a roles según tabla del documento arquitectónico
   - Asignación de roles a usuarios de prueba por negocio

3. **Queries de validación** (5-7 queries):
   - ¿Usuario X tiene permiso "servicio.create" en Negocio Y?
   - Obtener todos los permisos del usuario en un negocio
   - Listar usuarios con rol específico
   - Verificar acceso multi-tenant

4. **Documentación de RBAC** con ejemplos de uso

**Datos en BD**: 5 roles + 25 permisos + 30+ asignaciones

**Criterios de aceptación**:
- ✓ Todos los 5 roles creados
- ✓ Matriz de permisos 100% implementada
- ✓ Queries retornan permisos correctos
- ✓ USUARIO_FINAL no puede ver usuarios de otro negocio
- ✓ NEGOCIO_ADMIN tiene acceso completo a su negocio

---

### **FASE 4: Tablas de Reservas y Citas** (Duración: 1.5 semanas)

**Objetivo**: Crear estructura para gestión de citas y validación de disponibilidad.

**Entregables**:
1. **Script SQL** para tablas:
   - `appointments`
   - `appointment_status_histories`
   - Campos especiales: `codigo_confirmacion`, `custom_data`, buffers aplicables

2. **Migraciones Laravel** con:
   - ENUM appointment_status (pending, confirmed, completed, cancelled, no_show)
   - Índices críticos para motor de disponibilidad:
     - (business_id, employee_id, fecha_hora_inicio, estado)
     - (business_id, location_id, fecha_hora_inicio)
   - CHECK constraints para validar fechas
   - Soft deletes en appointments

3. **Seeder** con:
   - 20 citas de ejemplo (pasadas, presente, futuras)
   - Historial de cambios de estado
   - Variedad de servicios y empleados

4. **Queries de desempeño** para validar:
   - Obtener slots disponibles (sin overlaps)
   - Validar doble booking
   - Listar citas por fecha y empleado

**Datos en BD**: 20 appointments + 40 history records

**Criterios de aceptación**:
- ✓ Appointments persisten correctamente
- ✓ Historial de estado se registra automáticamente
- ✓ Índices aceleran búsquedas (< 200ms para 1000 citas)
- ✓ Soft deletes no muestran citas canceladas en listas

---

### **FASE 5: Tabla de Notificaciones (Opcional pero Recomendada)** (Duración: 3-5 días)

**Objetivo**: Agregar tracking de notificaciones para auditoría.

**Entregables**:
1. **Script SQL**:
   - `notification_logs` con campos: id, user_id, business_id, tipo (email/whatsapp), estado (enviado/fallido/reintentado), appointment_id, mensaje_resumen, intentos, último_intento, created_at

2. **Migración Laravel**:
   - Foreign keys a users, businesses, appointments
   - Índices en user_id + created_at para reportes
   - GIN index en metadatos JSON (opcional)

3. **Seeder**: 10 notificaciones de prueba

**Criterios de aceptación**:
- ✓ Tabla opcional, no bloquea operación
- ✓ Queries de reporte funcionan
- ✓ No ralentiza citas normales

---

### **FASE 6: Recursos Compartidos (FASE 2 DEL PROYECTO)** (Duración: 1 semana)

**Objetivo**: Agregar soporte para recursos (salas, camillas, etc.) con capacidad limitada.

**Entregables**:
1. **Script SQL**:
   - `resources` (id, business_id, location_id, nombre, tipo, capacidad, meta)
   - `appointment_resources` (pivote, id, appointment_id, resource_id)

2. **Migraciones Laravel**:
   - ENUM resource_type
   - CHECK constraint (capacidad >= 1)
   - Índices en business_id + location_id

3. **Seeder**: 5 recursos de ejemplo

**Criterios de aceptación**:
- ✓ Recursos se asignan a citas
- ✓ Capacidad se valida en reservas
- ✓ No interfiere con flujo de disponibilidad actual

**NOTA**: Esta fase se marca claramente como POST-MVP.

---

### **FASE 7: Testing, Documentación y Deploy** (Duración: 1.5 semanas)

**Objetivo**: Validar schema completo y documentar para desarrollo.

**Entregables**:
1. **Script de pruebas SQL**:
   - 30+ queries de validación
   - Casos de concurrencia (doble booking)
   - Casos de edge (buffers infinitos, excepciones parciales)
   - Performance baseline (tiempo de queries críticas)

2. **Documentación técnica**:
   - README con instrucciones de setup
   - Diagrama ERD final (texto e imagen si aplica)
   - Guía de índices y su justificación
   - Troubleshooting de problemas comunes

3. **Scripts de reset y datos de prueba**:
   - Script `reset_db.sh` para desarrollo local
   - Seeds completos reproducibles
   - Datos realistas (nombres mexicanos, números de teléfono válidos)

4. **Migration checker** (bash/PHP):
   - Validar que todas las migraciones ejecutan en orden
   - Verificar integridad referencial

**Criterios de aceptación**:
- ✓ Script reset ejecuta en < 10 segundos
- ✓ Todos los tests pasan
- ✓ Documentación es clara y completa
- ✓ Equipo frontend puede usar seeders sin ayuda
- ✓ Base de datos lista para Sprint 1 de desarrollo

---

## Timeline General

| Fase | Duración | Acumulado | Inicio | Fin |
|------|----------|-----------|--------|-----|
| **FASE 0** | 3-5 días | 3-5 días | Día 1 | Día 5 |
| **FASE 1** | 1 semana | 1.5 semanas | Día 6 | Día 12 |
| **FASE 2** | 1 semana | 2.5 semanas | Día 13 | Día 19 |
| **FASE 3** | 1 semana | 3.5 semanas | Día 20 | Día 26 |
| **FASE 4** | 1.5 semanas | 5 semanas | Día 27 | Día 37 |
| **FASE 5** | 3-5 días | 5.5 semanas | Día 38 | Día 42 |
| **FASE 6** | 1 semana | 6.5 semanas | Post-MVP | - |
| **FASE 7** | 1.5 semanas | 8 semanas | Día 43 | Día 54 |

**Total MVP (Fases 0-5)**: ~6 semanas
**Total Completo (Fases 0-7)**: ~8 semanas

---

## Decisiones de Diseño Documentadas

### [DECISIÓN 1]: No tabla `password_resets` explícita
**Razón**: Laravel Sanctum maneja tokens en cache/memoria. Agregar tabla sería redundante y complicaría schema sin beneficio.

### [DECISIÓN 2]: Tabla `notification_logs` ligera y opcional
**Razón**: No crítica para operación MVP, pero permite auditoría posterior sin refactoring. Incluida en Fase 5.

### [DECISIÓN 3]: Campos `buffer_pre_minutos` y `buffer_post_minutos` en services
**Razón**: Requeridos por motor de disponibilidad. Campos numéricos simples sin overhead.

### [DECISIÓN 4]: `custom_data` JSON en appointments
**Razón**: Soporta campos personalizables sin modificar schema. Compatible con requisito "no-code" del documento.

### [DECISIÓN 5]: RBAC custom, no Spatie Permission
**Razón**: Matriz de permisos requiere granularidad específica (por módulo, por acción). Custom permite control total sin abstracciones innecesarias.

### [DECISIÓN 6]: Multi-tenant por `business_id` con global scopes
**Razón**: Escalable, simple de migrar a particionamiento futuro. Sin riesgo de fuga de datos con scopes estrictos.

---

## Riesgos Mitigados durante Desarrollo BD

| Riesgo | Mitigación en BD | Fase |
|--------|-----------------|------|
| **R1: Doble booking** | Lock pessimista (SELECT FOR UPDATE), índices en employee_id+fecha | FASE 4 |
| **R2: Performance slots** | Índices compuestos, caching en aplicación | FASE 4 |
| **R3: Fuga datos multi-tenant** | Global scopes, constraints UNIQUE compuestos | FASES 1-3 |
| **R4: Inconsistencia RBAC** | Matriz 100% implementada, queries de validación | FASE 3 |
| **R5: Crecimiento BD** | Índices estratégicos, soft deletes, estructura preparada para particionamiento | TODAS |

---

## Preparación para Sprint 1 (Desarrollo Backend)

Al finalizar FASE 7, la BD estará lista para:
1. ✓ Crear modelos Laravel con scopes globales
2. ✓ Implementar autenticación Sanctum
3. ✓ Desarrollar API REST con middlewares de permisos
4. ✓ Implementar motor de disponibilidad

**Entregables a equipo de desarrollo**:
- Scripts SQL compilados y funcionales
- Migraciones Laravel ejecutables
- Seeders para datos de prueba
- Documentación de schema
- Guía de RBAC para autorizaciones
- Checklist de validación
