# Plan de desarrollo — Usuario Final (AgendaYa)

## Resumen

Plan por fases para el flujo de USUARIO_FINAL: descubrir negocios, revisar servicios, consultar disponibilidad, reservar y administrar citas desde la app móvil.

## Estado actual

- Ya existe la base backend necesaria: autenticación móvil, perfil, negocios públicos, disponibilidad, servicios, empleados, citas, horarios y reportes.
- También existe el panel web interno para negocio.
- Este plan se enfoca en la experiencia del usuario final en Flutter y en cerrar brechas de UX, notificaciones y release.

## Alcance

Incluye app móvil Flutter, consumo de API Laravel, notificaciones transaccionales, pruebas y preparación para lanzamiento.

No incluye en esta fase: pagos, reseñas, push notifications nativas ni recursos compartidos.

## Fases

### Fase 0 — Base móvil y alineación
**Entregable:** app lista para desarrollo continuo.
- Definir navegación, tema visual y estructura base de Flutter.
- Alinear variables de entorno para mobile y backend.
- Verificar contrato de API y respuestas esperadas.
- Confirmar estados vacíos, errores y loading states.

**Aceptación:**
- La app arranca con navegación base.
- El proyecto consume el entorno correcto y no depende de datos hardcodeados.

### Fase 1 — Autenticación y perfil
**Entregable:** registro, login y perfil del usuario final.
- Integrar register/login/logout con Sanctum.
- Mostrar y editar perfil del usuario.
- Guardar token en almacenamiento seguro.
- Validar email verificado, teléfono mexicano y contraseñas seguras.

**Aceptación:**
- Un usuario puede autenticarse, ver su perfil y cerrar sesión desde la app.

### Fase 2 — Descubrimiento de negocios
**Entregable:** búsqueda y navegación de negocios/servicios.
- Listado de negocios con filtros por categoría, texto y ubicación.
- Vista de detalle del negocio.
- Lista de servicios y empleados públicos.
- Manejo de estados vacíos y errores de conexión.

**Aceptación:**
- El usuario puede encontrar un negocio y revisar qué servicios ofrece antes de reservar.

### Fase 3 — Disponibilidad y selección de horario
**Entregable:** selector de slot disponible confiable.
- Consumir /api/v1/availability/slots.
- Aplicar reglas de horario, excepciones, buffers y timezone de sucursal.
- Caché de slots y refresco cuando cambie la disponibilidad.
- UI de calendario y selección de hora.

**Aceptación:**
- El usuario ve únicamente horarios válidos y consistentes con la sucursal seleccionada.

### Fase 4 — Reservas y mis citas
**Entregable:** creación y administración de citas del usuario final.
- Crear cita desde la app.
- Listar citas futuras e historiales.
- Cancelar citas desde el móvil.
- Mostrar detalle de cita, confirmación y estado.

**Aceptación:**
- El usuario puede reservar, consultar y cancelar sus citas sin salir de la app.

### Fase 5 — Notificaciones y recordatorios
**Entregable:** confirmaciones y recordatorios operativos.
- Confirmación inmediata de cita.
- Recordatorios 24h/1h vía email y WhatsApp cuando estén habilitados.
- Fallback a email si WhatsApp falla.
- Registro de envío y errores en logs.

**Aceptación:**
- Las notificaciones salen de forma consistente y quedan trazadas para soporte.

### Fase 6 — QA, E2E y salida a staging
**Entregable:** versión lista para validación interna.
- Pruebas unitarias, integración y flujos E2E móviles.
- Pipeline CI para backend y app móvil.
- Checklist de release y smoke test en staging.

**Aceptación:**
- Se puede probar el flujo completo del usuario final con un entorno estable.

## Dependencias

- Los endpoints backend deben permanecer compatibles con la app móvil.
- La zona horaria de sucursal debe usarse para mostrar horarios.
- `business_id` debe respetarse en cada query y log relevante.

## Tareas y seguimiento

- Las tareas del plan se reflejan en la base de datos de seguimiento para ejecución y control de avance.

## Notas

- Priorizar la reserva sin doble booking.
- No mezclar la experiencia de usuario final con la del panel de negocio.
- Mantener mensajes y validaciones en español claro para México.
