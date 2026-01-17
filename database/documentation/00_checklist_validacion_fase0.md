# Checklist de Validación - FASE 0

**Versión**: 1.0  
**Fecha**: 01 de enero de 2026  
**Documento de Referencia**: Arquitectura SaaS Multi-Tenant (PDF)  
**Objetivo**: Validar que el diseño de BD cumple con todos los requisitos arquitectónicos antes de FASE 1

---

## Instrucciones de Uso

Este checklist debe ser completado y firmado antes de proceder a FASE 1. Cada item debe marcarse como ✅ (Cumple), ⚠️ (Cumple con observaciones), o ❌ (No cumple).

**Responsable de validación**: Arquitecto de BD + Lider de Proyecto  
**Fecha de validación**: _______________

---

## SECCIÓN 1: Estructura de Tablas vs Documento Arquitectónico

### 1.1 Tablas de Plataforma

| Item | Requisito | Estado | Observaciones |
|------|-----------|--------|---|
| 1.1.1 | Tabla `platform_admins` existe | ⬜ | |
| 1.1.2 | Tabla `platform_settings` existe | ⬜ | |
| 1.1.3 | `platform_admins` tiene FK a `users` | ⬜ | |
| 1.1.4 | `platform_settings` almacena pares clave-valor JSON | ⬜ | |

### 1.2 Tablas de Negocio (Core)

| Item | Requisito | Estado | Observaciones |
|------|-----------|--------|---|
| 1.2.1 | Tabla `businesses` es raíz del tenant (tiene `id`) | ⬜ | |
| 1.2.2 | Tabla `business_locations` existe y tiene FK a businesses | ⬜ | |
| 1.2.3 | Tabla `services` existe con `duracion_minutos`, `precio` | ⬜ | |
| 1.2.4 | `services` tiene `buffer_pre_minutos` y `buffer_post_minutos` | ⬜ | |
| 1.2.5 | `services` tiene campo `meta` (JSON para custom fields) | ⬜ | |
| 1.2.6 | Tabla `employees` existe con FK opcional a `users` | ⬜ | |
| 1.2.7 | Tabla `employee_services` es pivote N:M correcto | ⬜ | |
| 1.2.8 | Tabla `schedule_templates` tiene día_semana (0-6) y horarios | ⬜ | |
| 1.2.9 | Tabla `schedule_exceptions` tiene fecha, tipo (ENUM), rango horario | ⬜ | |

### 1.3 Tablas de Usuarios (Global)

| Item | Requisito | Estado | Observaciones |
|------|-----------|--------|---|
| 1.3.1 | Tabla `users` es GLOBAL (sin business_id) | ⬜ | |
| 1.3.2 | `users` tiene email UNIQUE y password | ⬜ | |
| 1.3.3 | `users` tiene `email_verified_at` para verificación | ⬜ | |
| 1.3.4 | Tabla `user_favorite_businesses` existe con FK a ambas tablas | ⬜ | |

### 1.4 Tablas de Agenda y Reservas

| Item | Requisito | Estado | Observaciones |
|------|-----------|--------|---|
| 1.4.1 | Tabla `appointments` existe con todos los campos requeridos | ⬜ | |
| 1.4.2 | `appointments` tiene `business_id` (segregación multi-tenant) | ⬜ | |
| 1.4.3 | `appointments` tiene `estado` (ENUM appointment_status) | ⬜ | |
| 1.4.4 | `appointments` tiene FK a user, location, service, employee | ⬜ | |
| 1.4.5 | `appointments` tiene `codigo_confirmacion` UNIQUE | ⬜ | |
| 1.4.6 | `appointments` tiene `custom_data` (JSON) | ⬜ | |
| 1.4.7 | `appointments` tiene CHECK (fecha_fin > fecha_inicio) | ⬜ | |
| 1.4.8 | Tabla `appointment_status_histories` con historial completo | ⬜ | |

### 1.5 Tablas de RBAC

| Item | Requisito | Estado | Observaciones |
|------|-----------|--------|---|
| 1.5.1 | Tabla `roles` con 5 roles definidos | ⬜ | |
| 1.5.2 | Tabla `permissions` con permisos granulares (módulo.acción) | ⬜ | |
| 1.5.3 | Tabla `role_permissions` es pivote N:M correcto | ⬜ | |
| 1.5.4 | Tabla `business_user_roles` con `business_id` NULLABLE | ⬜ | |
| 1.5.5 | `business_user_roles` tiene UNIQUE (user_id, business_id, role_id) | ⬜ | |

### 1.6 Tablas Optionales (Fase 2+)

| Item | Requisito | Estado | Observaciones |
|------|-----------|--------|---|
| 1.6.1 | Tabla `resources` (marcada como Fase 2) | ⬜ | |
| 1.6.2 | Tabla `appointment_resources` pivote (marcada como Fase 2) | ⬜ | |
| 1.6.3 | Tabla `notification_logs` (marcada como Fase 5) | ⬜ | |

---

## SECCIÓN 2: Campos de Auditoría y Control

| Item | Requisito | Estado | Observaciones |
|------|-----------|--------|---|
| 2.1 | Todas las tablas tienen `created_at` TIMESTAMP | ⬜ | |
| 2.2 | Todas las tablas tienen `updated_at` TIMESTAMP | ⬜ | |
| 2.3 | Tablas críticas tienen `deleted_at` TIMESTAMP (soft delete) | ⬜ | |
| 2.4 | Soft deletes incluyen: users, businesses, employees, appointments | ⬜ | |

---

## SECCIÓN 3: Segregación Multi-Tenant

| Item | Requisito | Estado | Observaciones |
|------|-----------|--------|---|
| 3.1 | Todas las tablas operacionales tienen `business_id` | ⬜ | |
| 3.2 | `users` NO tiene `business_id` (es global) | ⬜ | |
| 3.3 | Tablas pivote heredan business_id implícitamente | ⬜ | |
| 3.4 | Documentado que Laravel usará global scopes en modelos | ⬜ | |
| 3.5 | Tabla `business_user_roles` permite roles globales (business_id NULL) | ⬜ | |

---

## SECCIÓN 4: ENUM Types Requeridos

| Item | Requisito | Estado | Observaciones |
|------|-----------|--------|---|
| 4.1 | ENUM `appointment_status` con 5 valores | ⬜ | |
| 4.2 | ENUM `schedule_exception_type` con 3 valores | ⬜ | |
| 4.3 | ENUM `resource_type` con 2 valores (Fase 2) | ⬜ | |
| 4.4 | ENUM `business_status` con 4 valores | ⬜ | |
| 4.5 | ENUM `notification_type` y `notification_status` (Fase 5) | ⬜ | |

---

## SECCIÓN 5: Constraints y Validaciones

| Item | Requisito | Estado | Observaciones |
|------|-----------|--------|---|
| 5.1 | CHECK (precio >= 0) en servicios | ⬜ | |
| 5.2 | CHECK (duracion_minutos > 0) en servicios | ⬜ | |
| 5.3 | CHECK (buffer_minutos >= 0) en servicios | ⬜ | |
| 5.4 | CHECK (capacidad >= 1) en resources | ⬜ | |
| 5.5 | CHECK (fecha_hora_fin > fecha_hora_inicio) en appointments | ⬜ | |
| 5.6 | UNIQUE (user_id, business_id, role_id) en business_user_roles | ⬜ | |
| 5.7 | UNIQUE constraints en email (users global, employees por negocio) | ⬜ | |
| 5.8 | UNIQUE (business_id, nombre) en locations, services | ⬜ | |

---

## SECCIÓN 6: Foreign Keys y Integridad Referencial

| Item | Requisito | Estado | Observaciones |
|------|-----------|--------|---|
| 6.1 | FK businesses ← users (platform_admins) | ⬜ | |
| 6.2 | FK business_locations → businesses ON DELETE CASCADE | ⬜ | |
| 6.3 | FK services → businesses ON DELETE CASCADE | ⬜ | |
| 6.4 | FK employees → businesses ON DELETE CASCADE | ⬜ | |
| 6.5 | FK employee_services → employees ON DELETE CASCADE | ⬜ | |
| 6.6 | FK employee_services → services ON DELETE CASCADE | ⬜ | |
| 6.7 | FK schedule_templates → business_locations ON DELETE CASCADE | ⬜ | |
| 6.8 | FK schedule_exceptions → business_locations ON DELETE CASCADE | ⬜ | |
| 6.9 | FK appointments → businesses, users, locations, services, employees | ⬜ | |
| 6.10 | FK appointment_status_histories → appointments ON DELETE CASCADE | ⬜ | |
| 6.11 | FK role_permissions → roles, permissions ON DELETE CASCADE | ⬜ | |
| 6.12 | FK business_user_roles → users, businesses, roles ON DELETE CASCADE | ⬜ | |
| 6.13 | FK user_favorite_businesses → users, businesses ON DELETE CASCADE | ⬜ | |

---

## SECCIÓN 7: Índices y Performance

| Item | Requisito | Estado | Observaciones |
|------|-----------|--------|---|
| 7.1 | Índice multi-tenant en appointments: (employee_id, fecha, estado) | ⬜ | |
| 7.2 | Índice multi-tenant en appointments: (location_id, fecha) | ⬜ | |
| 7.3 | Índice en schedule_exceptions: (location_id, fecha) | ⬜ | |
| 7.4 | Índice en business_user_roles: (user_id, business_id) | ⬜ | |
| 7.5 | UNIQUE índices en emails (users, employees) | ⬜ | |
| 7.6 | Índices GIN en campos JSON (meta, custom_data) | ⬜ | |
| 7.7 | Documentado baseline performance esperado | ⬜ | |

---

## SECCIÓN 8: Motor de Disponibilidad

| Item | Requisito | Estado | Observaciones |
|------|-----------|--------|---|
| 8.1 | Schedule templates soportan 7 días (0-6) | ⬜ | |
| 8.2 | Schedule exceptions pueden ser todo el día o parcial | ⬜ | |
| 8.3 | Appointments soporta FK nullable a employee | ⬜ | |
| 8.4 | Services tienen buffer_pre y buffer_post | ⬜ | |
| 8.5 | Resources y appointment_resources para capacidad (Fase 2) | ⬜ | |
| 8.6 | CHECK constraint en fecha_hora_fin > fecha_hora_inicio | ⬜ | |
| 8.7 | Campos necesarios documentados en pseudocódigo motor | ⬜ | |

---

## SECCIÓN 9: Matriz de Permisos RBAC

| Item | Requisito | Estado | Observaciones |
|------|-----------|--------|---|
| 9.1 | 5 roles del sistema están definidos | ⬜ | |
| 9.2 | 26+ permisos granulares (módulo.acción) están definidos | ⬜ | |
| 9.3 | Matriz de permisos x roles mapeada completamente | ⬜ | |
| 9.4 | Scopes documentados para cada rol | ⬜ | |
| 9.5 | Tabla business_user_roles permite asignación multi-tenant | ⬜ | |
| 9.6 | Queries de ejemplo incluidas para verificar permisos | ⬜ | |

---

## SECCIÓN 10: Documentación y Organización

| Item | Requisito | Estado | Observaciones |
|------|-----------|--------|---|
| 10.1 | Diagrama ERD conceptual (texto) completado | ⬜ | |
| 10.2 | Especificación de ENUM types documentada | ⬜ | |
| 10.3 | Mapeo de matriz RBAC documentado | ⬜ | |
| 10.4 | Especificación de índices con justificación | ⬜ | |
| 10.5 | Archivo estructura en carpetas: /database/schemas, /sql, /documentation | ⬜ | |
| 10.6 | Todos los documentos en tono profesional | ⬜ | |
| 10.7 | Referencias cruzadas entre documentos | ⬜ | |

---

## SECCIÓN 11: Compatibilidad con Stack Técnico

| Item | Requisito | Estado | Observaciones |
|------|-----------|--------|---|
| 11.1 | Diseño compatible con MariaDB 11.4.9+ | ⬜ | |
| 11.2 | Diseño compatible con Laravel 12+ | ⬜ | |
| 11.3 | ENUM types inline para columnas | ⬜ | |
| 11.4 | JSON fields para custom_data | ⬜ | |
| 11.5 | Soft deletes para Laravel (deleted_at) | ⬜ | |
| 11.6 | Global scopes documentados para modelos | ⬜ | |

---

## SECCIÓN 12: Alineamiento con Historias de Usuario

| Item | Requisito | Estado | Observaciones |
|------|-----------|--------|---|
| 12.1 | HU1 (registro usuario) → campos en `users` | ⬜ | |
| 12.2 | HU2 (registro negocio) → campos en `businesses` | ⬜ | |
| 12.3 | HU4 (datos negocio) → campos en `businesses` + `meta` | ⬜ | |
| 12.4 | HU5 (sucursal) → tabla `business_locations` | ⬜ | |
| 12.5 | HU6 (horarios) → tabla `schedule_templates` | ⬜ | |
| 12.6 | HU7 (servicios) → tabla `services` | ⬜ | |
| 12.7 | HU8 (empleados) → tabla `employees` + `employee_services` | ⬜ | |
| 12.8 | HU9 (excepciones) → tabla `schedule_exceptions` | ⬜ | |
| 12.9 | HU10+ (búsqueda/reserva) → query en `businesses`, `services`, `appointments` | ⬜ | |
| 12.10 | HU22-23 (admin plataforma) → tablas `roles`, `permissions`, `business_user_roles` | ⬜ | |

---

## SECCIÓN 13: Riesgos Técnicos (del documento arquitectónico)

| Item | Requisito | Estado | Observaciones |
|------|-----------|--------|---|
| 13.1 | R1 (doble booking) → índices y lock pessimista en design | ⬜ | |
| 13.2 | R2 (performance slots) → índices compuestos documentados | ⬜ | |
| 13.3 | R3 (complejidad multi-tenant) → global scopes en design | ⬜ | |
| 13.4 | R5 (crecimiento BD) → estructura lista para particionamiento | ⬜ | |

---

## SECCIÓN 14: Decisiones de Diseño Documentadas

| Item | Requisito | Estado | Observaciones |
|------|-----------|--------|---|
| 14.1 | [DECISIÓN 1] Sin tabla password_resets explícita | ⬜ | |
| 14.2 | [DECISIÓN 2] Tabla notification_logs incluida (Fase 5) | ⬜ | |
| 14.3 | [DECISIÓN 3] Campos buffer en services | ⬜ | |
| 14.4 | [DECISIÓN 4] custom_data JSON en appointments | ⬜ | |
| 14.5 | [DECISIÓN 5] RBAC custom (no Spatie Permission) | ⬜ | |
| 14.6 | [DECISIÓN 6] Multi-tenant por business_id | ⬜ | |

---

## SECCIÓN 15: Validaciones de Scripts SQL/Migraciones

| Item | Requisito | Estado | Observaciones |
|------|-----------|--------|---|
| 15.1 | Scripts SQL compilables en MariaDB 11.4.9+ | ⬜ | |
| 15.2 | ENUM types inline creables sin errores | ⬜ | |
| 15.3 | Tablas creables sin errores FK | ⬜ | |
| 15.4 | Índices creables sin conflictos | ⬜ | |
| 15.5 | Constraints validables (CHECK, UNIQUE) | ⬜ | |

---

## Resultado Final

### Resumen de Validación

- ✅ Items completados: _______ / 95
- ⚠️ Items con observaciones: _______
- ❌ Items no cumplidos: _______

### Estado General

- [ ] **APROBADO**: Procede a FASE 1
- [ ] **APROBADO CON OBSERVACIONES**: Procede a FASE 1 con mitigación documentada
- [ ] **NO APROBADO**: Requiere revisión antes de FASE 1

---

## Firmas de Validación

| Rol | Nombre | Fecha | Firma |
|-----|--------|-------|-------|
| Arquitecto de BD | _________________ | ___/___/___ | _______ |
| Lider de Proyecto | _________________ | ___/___/___ | _______ |
| Tech Lead Backend | _________________ | ___/___/___ | _______ |

---

## Observaciones Generales

```
[Espacio para notas y decisiones tomadas durante validación]
```

---

## Fecha de Liberación a FASE 1

**Aprobado para FASE 1 el**: ___/___/___

---

## Siguiente Paso

Una vez aprobado este checklist, procede con:
1. Crear scripts SQL finales en `/database/sql/`
2. Crear seeders en `/database/seeders/`
3. Iniciar FASE 1: Tablas Base y Plataforma
