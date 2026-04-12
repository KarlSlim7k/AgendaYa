# Plan de desarrollo — Usuario Final (AgendaYa)

## Resumen

Plan por fases para el flujo de USUARIO_FINAL: descubrir negocios, revisar servicios, consultar disponibilidad, reservar y administrar citas desde app móvil y web.

**Prioridad:** Móvil > Web/Desktop — El 80%+ de usuarios accederá vía móvil, por lo que mobile-first guía el desarrollo.

## Estado actual

- Ya existe la base backend necesaria: autenticación móvil, perfil, negocios públicos, disponibilidad, servicios, empleados, citas, horarios y reportes.
- También existe el panel web interno para negocio.
- Este plan se enfoca en la experiencia del usuario final en Flutter (móvil) y web responsivo, cerrando brechas de UX, notificaciones y release.

## Plataforma objetivo

| Plataforma | Tecnología | Prioridad | Entrega |
|------------|------------|----------|---------|
| Móvil (iOS/Android) | Flutter | **P0** (primera) | App nativa |
| Web/Desktop | Flutter Web / PWA | P1 (segunda) | Responsive web |

## Alcance

**Móvil (P0):** App Flutter nativa, consumo API Laravel, notificaciones transaccionales, pruebas y preparación para lanzamiento.

**Web/Desktop (P1):** Versión responsive basada en componentes Flutter, accesible desde navegador.

**No incluye en esta fase:** Pagos integrados, reseñas públicas, push notifications nativas, recursos compartidos en redes sociales.

## Fases de desarrollo

### Fase 0 — Base móvil y alineación
**Prioridad:** P0 (móvil primero)

**Entregable:** App Flutter lista para desarrollo continuo.
- Definir navegación, tema visual y estructura base de Flutter.
- Configurar clean architecture con separación de capas.
- Alinear variables de entorno para móvil y backend (dev/staging/prod).
- Verificar contrato de API y respuestas esperadas.
- Confirmar estados vacíos, errores y loading states.
- Implementar Theme adaptive para móvil/web.
- **Móvil:** Navigation stack, deep linking configurado.
- **Web:** Router web compatible con Flutter Web/PWA.

**Aceptación:**
- La app móvil arranca con navegación base y theme adaptive.
- El proyecto consume el entorno correcto (no datos hardcodeados).
- Web responsive escala correctamente desde 320px hasta 4K.

---

### Fase 1 — Autenticación y perfil
**Prioridad:** P0 (móvil primero)

**Entregable:** Registro, login, perfil del usuario final.

**Móvil:**
- Integrar register/login/logout con Sanctum via HTTP.
- Mostrar y editar perfil del usuario.
- Guardar token en Flutter Secure Storage.
- Validar email verificado, teléfono mexicano (+52) y contraseñas seguras.
- Soporte para biometric auth (FaceID/TouchID) y gestos de seguridad.
- Notificaciones locales para recordatorios de sesión.

**Web:**
- Mismos flujos de auth con sessions cookies + JWT.
- "Recordarme" persistente via localStorage seguro.
- Logout global afecta móvil y web.

**Aceptación:**
- Un usuario puede autenticarse desde móvil y web.
- Ver perfil y cerrar sesión desde cualquier plataforma.
- Token persiste de forma segura por plataforma.

---

### Fase 2 — Descubrimiento de negocios
**Prioridad:** P0 (móvil primero)

**Entregable:** Búsqueda y navegación de negocios/servicios.

**Móvil:**
- Listado de negocios con filtros táctiles (categoría, texto, ubicación).
- Pull-to-refresh y paginación infinita.
- Vista de detalle del negocio con hero animations.
- Lista de servicios y empleados públicos.
- Manejo optimizado para conexiones lentas (offline parcial).
- Mapas integrados (Google Maps/Mapbox para UX nativa).

**Web:**
- Sidebar de filtros para pantallas grandes (sidebar left).
- Grid responsivo de negocios (1 col móvil, 2-3 cols desktop).
- Búsqueda realtime con debounce.
- Same URL sharing para negocios (OG tags).

**Aceptación:**
- El usuario puede encontrar un negocio y revisar servicios antes de reservar.
- Filtros funcionan offline-first para móvil.

---

### Fase 3 — Disponibilidad y selección de horario
**Prioridad:** P0 (móvil primero)

**Entregable:** Selector de slot disponible confiable.

**Móvil:**
- Consumir `/api/v1/availability/slots`.
- Aplicar reglas de horario, excepciones, buffers y timezone de sucursal.
- Caché local de slots con Invalidate-on-booking.
- UI de calendario touch-optimizada con swipe gestures.
- Timezone conversion automática basada en ubicación del usuario.

**Web:**
- DatePicker desktop-optimizado (mouse + keyboard).
- Visualización semanal/mensual toggleable.
- Same timezone logic que móvil para consistencia.

**Aceptación:**
- El usuario ve únicamente horarios válidos y consistentes con la sucursal seleccionada.
- Sin doble booking — atomic check antes de confirmar.

---

### Fase 4 — Reservas y mis citas
**Prioridad:** P0 (móvil primero)

**Entregable:** Creación y administración de citas del usuario final.

**Móvil:**
- Crear cita desde la app con loading state indicador.
- Listar citas futuras (tab upcoming) e historiales (tab past).
- Swipe actions para cancelar cita.
- Pull-to-refresh para estado actualizado.
- Widgets nativos para próximas citas en home screen.
- Deep linking a detalle de cita desde notificaciones.

**Web:**
- Tablas/datatable para pantallas grandes.
- Exportar citas a Google Calendar / iCal.
- Same gestión de citas con interfaz responsive.

**Aceptación:**
- El usuario puede reservar, consultar y cancelar sus citas sin salir de la app.
- Confirma booking con feedback haptic en móvil.

---

### Fase 5 — Notificaciones y recordatorios
**Prioridad:** P0 (móvil primero)

**Entregable:** Confirmaciones y recordatorios operativos.

**Móvil:**
- Confirmación inmediata de cita (push local + email).
- Recordatorios 24h/1h vía email y WhatsApp cuando estén habilitados.
- Fallback a email si WhatsApp falla.
- Registro detallado de envíos para soporte.
- Notificaciones locales para cuando no hay conexión.

**Web:**
- Notificaciones browser (Notification API) si permisos dados.
- Email confirmations con links profundos a web/móvil.
- SMS fallback opcional.

**Aceptación:**
- Las notificaciones salen de forma consistente y quedan trazadas para soporte.

---

### Fase 6 — QA, E2E y salida a staging
**Prioridad:** P0 (móvil primero)

**Entregable:** Versión lista para validación interna.

**Móvil:**
- Pruebas unitarias y de widget Flutter.
- Pruebas de integración con mock de API.
- Flujos E2E con Flutter_driver/Patrol.
- App size optimization (target < 30MB bundle).
- Build beta via TestFlight / Play Console.

**Web:**
- Browser matrix testing (Chrome, Firefox, Safari, Edge).
- Lighthouse audit (performance >90, accessibility >90, SEO >90).
- Service Worker para PWA offline capabilities.

**Ambos:**
- Pipeline CI/CD unificado (GitHub Actions / Codemagic).
- Checklist de release y smoke test en staging.

**Aceptación:**
- Se puede probar el flujo completo del usuario final con un entorno estable.

## Dependencias y arquitectura

### Backend
- Los endpoints API deben permanecer backward-compatible.
- Zona horaria de sucursal usada para mostrar horarios (timezone-aware).
- `business_id` respetado en cada query y log relevante.
- Rate limiting configurado para peaks de móvil (sin degradar web).

### Móvil-first principios
- Componentes compartidos entre Flutter (móvil + web) — máximo reuse code.
- Feature flags por platform: mismo backend, UI adaptive.
- Offline-first para móvil: sync estratégica cuando hay conexión.
- Network status detection para UX adaptiva (spinners vs cached data).

### Shared code strategy
```
lib/
├── core/              # Shared utilities
├── features/          # Feature modules
│   ├── auth/
│   ├── businesses/
│   ├── booking/
│   └── profile/
├── platform/           # Platform-specific adaptors
│   ├── mobile/        # iOS/Android specifics
│   └── web/           # Web/PWA specifics
└── widgets/           # Shared + adaptive widgets
```

## Tareas y seguimiento

- Las tareas del plan se reflejan en la base de datos de seguimiento para ejecución y control de avance.
- **Definition of Done (DoD):** Cada feature requiere:
  - [ ] Implementado en móvil
  - [ ] Implementado en web (o feature flag)
  - [ ] Tests unitarios pasan
  - [ ] Tests E2E pasan
  - [ ] Lighthouse web >90
  - [ ] Build succeed en Debug + Release

## Notas

- **Mobile-first:** Ningún feature se publica sin pasar por móvil primero.
- **Shared estado:** Misma cuenta, mismas citas — sync cross-platform.
- **Prioridad booking:** Nunca permitir doble booking — transacciones atómicas.
- **No mezclar:** Usuario final ≠ panel de negocio — separado y específico.
- **México-localized:** Mensajes, validaciones y formatos en español claro para México.
- **Performance budget:**
  - Móvil: Cold start < 2s, Time to Interactive < 3s.
  - Web: First Contentful Paint < 1.5s, TTI < 3s.
