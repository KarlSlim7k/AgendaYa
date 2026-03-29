# Skills, Herramientas y Tecnologías 2024-2025
## Para AgendaYa — SaaS Multi-Tenant de Citas Empresariales

> Documento generado el 2026-03-23 con base en investigación de tendencias 2024-2025.
> Stack actual: Laravel 12 + MariaDB 11.4.9 + Livewire 3 + Alpine.js + Tailwind CSS + Sanctum/Breeze + PHPUnit.
> Stack futuro planificado: Next.js + TypeScript + ShadCN + TanStack Query + Zustand / React Native + Expo / PostgreSQL.

---

## 1. Frontend Web

### Stack actual: TALL (Tailwind + Alpine.js + Livewire 3 + Laravel)

| Herramienta / Skill | Descripción | Por qué es útil para AgendaYa |
|---|---|---|
| **Livewire 3 + Volt** | Livewire 3 unifica la lógica de componentes en un solo archivo con la sintaxis Volt (similar a Vue SFC). Soporta lazy loading, optimistic UI y wire:navigate para SPA-like navigation. | Permite construir el wizard de citas, calendario de disponibilidad y panel de RBAC sin escribir JavaScript complejo. wire:navigate elimina recargas de página completa. |
| **Alpine.js 3.x + Plugins** | Librería de 7kb para interactividad declarativa. Los plugins oficiales (Focus, Intersect, Persist, Collapse, Mask) cubren el 80% de los casos de UI sin salir del TALL stack. | Ideal para dropdowns de horarios, modales de confirmación de cita, validación de formularios en tiempo real y masks de teléfono/fecha en el frontend. |
| **Tailwind CSS 4.x** | Tailwind 4 (2025) reescribe el motor en Oxide (Rust), elimina el archivo de configuración y agrega CSS nativo cascade layers. 3-5x más rápido en builds. | Permite mantener un design system consistente para el panel web y el panel de admin multi-tenant sin CSS personalizado propenso a conflictos. |
| **Flux UI / Filament 3** | Flux UI es el componente oficial de Livewire/Tailwind de Caleb Porzio. Filament 3 es un framework de admin panels con tablas, formularios y widgets listos para producción. | Filament es ideal para el panel de super-admin multi-tenant (gestión de tenants, reportes). Flux UI acelera la construcción del panel de cliente/negocio. |
| **ShadCN/UI + Next.js** *(migración futura)* | Colección de componentes React headless construidos sobre Radix UI + Tailwind CSS. No es una librería de dependencia, los componentes se copian directamente al proyecto. | Para la migración futura a Next.js, ShadCN garantiza accesibilidad (ARIA), temas dark/light y diseño consistente sin romper el bundle con componentes no usados. |

**Skills clave a desarrollar:**
- Livewire 3: lazy components, wire:model.live, wire:poll, wire:navigate, JS hooks (lifecycle)
- Alpine.js: $store, $dispatch, x-teleport, plugins Mask e Intersect
- Tailwind: CSS variables, @layer, responsive design tokens, dark mode

---

## 2. Backend PHP / Laravel

| Herramienta / Paquete | Descripción | Por qué es útil para AgendaYa |
|---|---|---|
| **stancl/tenancy 3.x** | Paquete de multi-tenancy más completo para Laravel. Soporta multi-database tenancy, single-database tenancy, domain/subdomain routing por tenant y bootstrapping automático. | El núcleo de la arquitectura multi-tenant. Permite aislar datos por empresa cliente (tenant), con bases de datos separadas o tablas compartidas con `tenant_id` scope automático. |
| **spatie/laravel-permission** | RBAC con roles y permisos, cacheado automático, compatible con multi-tenancy. Más de 20M de descargas en Packagist. | Gestiona los roles de AgendaYa: `super-admin`, `tenant-admin`, `staff`, `client` con permisos granulares por módulo (citas, usuarios, reportes, configuración). |
| **spatie/laravel-activitylog** | Log automático de eventos en modelos Eloquent y eventos personalizados, almacenado en tabla `activity_log`. | Auditoría de quién creó/canceló/modificó una cita, cambios de configuración del tenant, acciones del admin. Requerido para compliance y resolución de disputas. |
| **Laravel Horizon** | Dashboard y configuración de colas Redis. Monitorea throughput, tiempo de ejecución y fallas de jobs en tiempo real. | Las notificaciones de citas (email, WhatsApp, push) deben procesarse en cola. Horizon permite observar el estado de estos jobs sin entrar al servidor. |
| **Laravel Telescope** | Debug assistant: inspecciona requests, queries SQL, jobs, notificaciones, cache, excepciones y más desde el browser. | Imprescindible en desarrollo y staging para detectar N+1 queries en el calendario de disponibilidad y optimizar el rendimiento de la agenda. |

**Skills clave a desarrollar:**
- Service/Repository pattern para lógica de disponibilidad de citas
- Laravel Queues: jobs, batches, chains
- Eloquent: eager loading, query scopes globales por tenant, índices
- Laravel Actions pattern (opciones: lorisleiva/laravel-actions)

---

## 3. Base de Datos

### Stack actual: MariaDB 11.4.9 → Migración futura: PostgreSQL

| Herramienta / Técnica | Descripción | Por qué es útil para AgendaYa |
|---|---|---|
| **Releem** | Agente de monitoreo que analiza workload específico de la app y sugiere más de 30 variables de configuración MariaDB/MySQL (buffer pool, cache sizes, timeouts). | Optimiza automáticamente MariaDB 11.4.9 en producción para las consultas específicas de AgendaYa sin intervención manual del DBA. |
| **Laravel Query Optimization (Eager Loading + índices)** | Técnicas nativas: `with()`, `withCount()`, `select()` explícito, `chunk()` para reportes, índices compuestos en columnas de filtrado frecuente. | El motor de disponibilidad consulta slots por `tenant_id + staff_id + fecha`. Índices compuestos en estas columnas reducen el tiempo de query de segundos a milisegundos. |
| **Laravel Migrations + Schema (best practices)** | Usar `json` columns para configuración flexible del tenant, `enum` estricto para estados de citas, `softDeletes` para cancelaciones auditables. | Los estados de citas (`pending`, `confirmed`, `cancelled`, `completed`, `no-show`) con soft deletes permiten reportes históricos completos sin perder datos. |
| **Laravel Pulse** | Dashboard de observabilidad en tiempo real (Laravel 11+): queries lentas, usuarios activos, errores, uso de caché. Se instala sin Redis. | Detecta queries lentas en el motor de disponibilidad y cuellos de botella en las rutas más usadas del panel. Alternativa ligera a Telescope para producción. |
| **pgvector + PostgreSQL** *(migración futura)* | Extensión de PostgreSQL para búsqueda vectorial y JSON avanzado. PostgreSQL ofrece CTEs, window functions y JSONB superiores a MariaDB. | En la migración futura, PostgreSQL habilitará: reportes complejos con window functions, búsqueda de disponibilidad con range types y escalabilidad superior. |

**Skills clave a desarrollar:**
- Análisis de EXPLAIN ANALYZE en MariaDB/MySQL
- Índices compuestos y covering indexes
- Laravel migrations: columnas calculadas, índices parciales
- Configuración innodb_buffer_pool_size (70-80% RAM disponible)

---

## 4. Seguridad

| Herramienta / Práctica | Descripción | Por qué es útil para AgendaYa |
|---|---|---|
| **spatie/laravel-permission + Policies** | RBAC granular con Laravel Policies para autorización a nivel de modelo + Gates para permisos globales. | Garantiza que un `staff` de Tenant A no pueda ver citas de Tenant B. Las Policies de Laravel son el mecanismo más robusto para autorización por recurso. |
| **Laravel Rate Limiting (ThrottleRequests)** | Middleware nativo con configuración por ruta, usuario autenticado o IP. Soporta límites por minuto, hora y día con Redis o cache. | Protege el endpoint de booking público contra scraping y ataques de enumeración de disponibilidad. El endpoint de login debe tener rate limiting estricto (5 intentos/minuto). |
| **metasoftdevs/laravel-breeze-2fa** | 2FA para Laravel Breeze con soporte TOTP (Google Authenticator), Email OTP y SMS. Integración directa con el flujo de Breeze. | Cuentas de `tenant-admin` y `super-admin` deben tener 2FA obligatorio dado el nivel de acceso a datos empresariales sensibles. |
| **OWASP Top 10:2025 en Laravel** | Checklist: SQL Injection (Eloquent/prepared statements), XSS (Blade escaping automático), CSRF (middleware nativo), IDOR (Policies), Rate Limiting, TLS 1.3. | Laravel por defecto mitiga SQL Injection, XSS y CSRF. Los riesgos residuales para AgendaYa son IDOR (acceso cruzado entre tenants) y exposición de datos en APIs. |
| **Laravel Sanctum (SPA + Mobile tokens)** | Autenticación stateless con API tokens de larga duración para la app móvil y cookie-based para la SPA. Token abilities permiten scopes granulares. | Para la app Flutter/React Native, Sanctum emite tokens con abilities específicas (`citas:read`, `citas:write`) sin necesidad de OAuth completo. |

**Skills clave a desarrollar:**
- Laravel Policies y Gates para multi-tenancy
- Headers de seguridad HTTP (CSP, HSTS, X-Frame-Options) vía middleware
- Validación estricta de inputs con Form Requests
- Gestión de secretos: .env en producción, rotación de API keys

---

## 5. Mobile

### Stack actual planeado: Flutter 3.x → Alternativa: React Native + Expo

| Herramienta / Librería | Descripción | Por qué es útil para AgendaYa |
|---|---|---|
| **Flutter 3.x + firebase_messaging** | Plugin oficial de Firebase Cloud Messaging para Flutter. Soporta foreground/background/terminated states, notification channels y deep links. | Notificaciones de recordatorio de cita 24h/1h antes, confirmación de booking y cancelaciones. FCM es gratuito y confiable para millones de dispositivos. |
| **flutter_local_notifications** | Librería para notificaciones locales programadas en Flutter (iOS + Android). Soporta scheduled notifications, grouped notifications y custom sounds. | Complementa FCM para recordatorios offline y alertas programadas localmente cuando no hay conexión. Crítico para el recordatorio de cita. |
| **Dio + Retrofit (Flutter)** | Dio es el cliente HTTP más completo para Flutter. Retrofit genera clientes type-safe desde anotaciones. Soporta interceptors para tokens Sanctum. | Maneja la autenticación con Laravel Sanctum, retry automático, logging de requests y manejo centralizado de errores HTTP en la app móvil. |
| **React Native + Expo EAS** *(alternativa/migración)* | Expo EAS (Expo Application Services) es el pipeline de build y distribución cloud de React Native. EAS Build compila en la nube sin Mac local para iOS. | Si se migra a React Native, EAS elimina la necesidad de Mac con Xcode para builds iOS, reduciendo el costo de infraestructura CI/CD para la app. |
| **Notifee (React Native)** *(si se migra a RN)* | Librería de notificaciones locales para React Native con soporte de notification channels, badges, actions y media attachments. Mantenida activamente en 2025. | Más features que las librerías nativas de RN para notificaciones locales. Permite notificaciones con botones de acción directa (Confirmar/Cancelar cita desde la notif). |

**Skills clave a desarrollar:**
- Flutter: BLoC o Riverpod para state management
- Deep linking para abrir la app desde links de cita en email/WhatsApp
- Push notification handling en background (Flutter)
- Integración con Laravel Sanctum desde mobile (token storage seguro con flutter_secure_storage)

---

## 6. DevOps / Deployment

| Herramienta | Descripción | Por qué es útil para AgendaYa |
|---|---|---|
| **Docker + Laravel Sail** | Laravel Sail es la CLI oficial de Docker para desarrollo local. Provee PHP, MariaDB, Redis y otros servicios con un solo comando. | Garantiza paridad entre entornos dev/staging/producción. Elimina el "funciona en mi máquina". Docker multi-stage builds para imágenes de producción optimizadas. |
| **GitHub Actions** | CI/CD nativo de GitHub. Workflows para: tests automáticos en PR, linting (Pint), build de assets y deploy a producción en merge a main. | Pipeline gratuito para repos públicos/privados con el plan de GitHub. Integra directamente con Laravel Forge y Envoyer vía API tokens. |
| **Laravel Forge** | Servicio de provisioning de servidores VPS (DigitalOcean, AWS, Linode, Hetzner). Configura Nginx, PHP, MariaDB, Redis, SSL (Let's Encrypt) y deploy automático. | Elimina la configuración manual del servidor. Ideal para el modelo de hosting actual (VPS/shared). Deploy con zero downtime con Envoyer integrado. Desde ~$19/mes. |
| **Laravel Envoyer** | Zero-downtime deployment para PHP. Mantiene 5 releases, rollback con un click, hooks pre/post deploy (migrations, cache:clear, queue:restart). | Las migraciones de base de datos en producción se ejecutan sin downtime visible para los clientes del SaaS. Critical para un producto en producción 24/7. |
| **Sentry + Laravel Sentry SDK** | Plataforma de error tracking y performance monitoring. Captura excepciones con contexto completo (user, tenant, request) y alertas en tiempo real. | En producción, detecta errores de booking antes que el cliente los reporte. El contexto de tenant en Sentry permite diagnosticar si un error afecta a un solo tenant o a todos. |

**Skills clave a desarrollar:**
- Docker: multi-stage builds, docker-compose para dev y staging
- GitHub Actions: matrix testing (PHP 8.2/8.3), artefactos, secrets
- Nginx: configuración para multi-tenant con subdominios
- Estrategias Blue-Green y Rolling deployment

---

## 7. Notificaciones

| Canal / Herramienta | Descripción | Por qué es útil para AgendaYa |
|---|---|---|
| **Laravel Notifications + Mail (Resend/Mailgun)** | Sistema de notificaciones unificado de Laravel. Resend (2024) es el nuevo estándar para email transaccional: API simple, alta deliverability, SDK oficial para Laravel. | Envío de confirmaciones de cita, recordatorios, cancleaciones y facturas. Resend ofrece 3,000 emails/mes gratis y analytics de apertura integrados. |
| **netflie/laravel-notification-whatsapp** | Canal de notificación de Laravel para WhatsApp Business Cloud API (Meta oficial). Soporta mensajes de texto, templates y media. | WhatsApp tiene >90% de penetración en México. Los recordatorios de cita por WhatsApp tienen tasa de apertura 5x mayor que email. Esencial para la audiencia objetivo. |
| **MissaelAnda/laravel-whatsapp** | Wrapper completo de la WhatsApp Business Cloud API para Laravel: mensajes, templates, webhooks de respuesta y gestión de conversaciones. | Permite recibir respuestas de confirmación del cliente ("1 para confirmar, 2 para cancelar") via webhook, automatizando la gestión de asistencia. |
| **Firebase Cloud Messaging (FCM) vía Laravel** | Envío de push notifications a la app móvil desde el backend Laravel usando la Admin SDK o el paquete `laravel-fcm`. | Notificaciones push para la app móvil de staff y clientes: nueva cita asignada, cambio de horario, recordatorio inmediato. Complementa email y WhatsApp. |
| **Laravel Echo + Reverb (in-app)** | Notificaciones en tiempo real dentro del panel web sin recargar. Reverb es el WebSocket server oficial de Laravel (gratuito, self-hosted). | El staff del negocio ve nuevas reservas en su panel en tiempo real sin F5. Los contadores de notificaciones se actualizan instantáneamente vía WebSockets. |

**Skills clave a desarrollar:**
- Configuración de WhatsApp Business API (Meta for Developers)
- Templates de WhatsApp aprobados por Meta para transaccionales
- Queue-based notification dispatch (no enviar en el request cycle)
- Manejo de bounces y unsubscribes en email

---

## 8. Pagos

| Herramienta | Descripción | Por qué es útil para AgendaYa |
|---|---|---|
| **Laravel Cashier (Stripe)** | Wrapper oficial de Laravel para Stripe Billing. Gestiona suscripciones, trials, proration, facturas, webhooks y cancelaciones con gracia. Cashier 16 para Laravel 12. | Si AgendaYa cobra a los negocios por suscripción mensual (plan básico/pro/enterprise), Cashier + Stripe es el estándar para SaaS B2B con clientes internacionales. |
| **wandesnet/mercadopago-laravel** | Paquete Laravel para MercadoPago que soporta Laravel 12 y PHP 8.1+. Incluye pagos únicos, suscripciones recurrentes y webhooks. Actualizado a febrero 2025. | MercadoPago tiene >100M de usuarios en México/LATAM. Para negocios locales mexicanos, es el gateway preferido por la facilidad de onboarding y pagos en OXXO/tarjetas locales. |
| **MercadoPago SDK oficial (mercadopago/dx-php)** | SDK oficial PHP de MercadoPago. Soporta PHP 8.2+, Checkout Pro, Checkout API, suscripciones y marketplace split payments. | El SDK oficial tiene soporte directo de MercadoPago, documentación actualizada y compatibilidad garantizada con la API más reciente. |
| **Stripe Checkout + Payment Links** | Hosted payment pages de Stripe. No requiere PCI compliance en el servidor propio. Soporta 135+ monedas y métodos de pago locales en México (OXXO vía Stripe). | Para cobros puntuales por cita (no solo suscripción SaaS), Stripe Checkout permite implementar pagos al cliente final del negocio con mínimo código backend. |
| **Lemon Squeezy / Paddle** *(alternativa)* | Merchant of Record: ellos se hacen responsables del IVA/impuestos globales. Lemon Squeezy tiene integración nativa con Laravel vía paquete oficial. | Si AgendaYa cobra a clientes internacionales, un MoR simplifica enormemente el cumplimiento fiscal. Considerar para la expansión fuera de México. |

**Estrategia recomendada para AgendaYa:**
- Suscripciones de tenants (negocios): **Stripe Cashier** (clientes enterprise/internacionales) + **MercadoPago** (negocios mexicanos)
- Cobro al cliente final por cita: **Stripe Checkout** o **MercadoPago Checkout Pro**

---

## 9. Testing

| Herramienta / Práctica | Descripción | Por qué es útil para AgendaYa |
|---|---|---|
| **Pest PHP 3.x** | Framework de testing moderno construido sobre PHPUnit. Sintaxis funcional más limpia, parallel testing nativo, architecture tests y snapshot testing. Pest 3 (2025) es el default de Laravel 12 Breeze. | Tests más legibles y mantenibles que PHPUnit puro. El parallel testing reduce el tiempo de la suite completa en 60-80%. Los arch tests garantizan que no haya acceso directo entre modelos de tenants distintos. |
| **Laravel HTTP Testing (actingAs + assertJson)** | API de testing nativa de Laravel para simular requests autenticados, verificar responses JSON, estados HTTP y efectos secundarios (jobs disparados, emails enviados). | Esencial para testear todos los endpoints del API de AgendaYa: booking de citas, gestión de disponibilidad, webhooks de pagos. `actingAs($tenant)` simula el contexto multi-tenant. |
| **Laravel Factories + Seeders** | Model factories con Faker para generar datos de prueba realistas. Seeders para estado inicial de la base de datos en tests de integración. | Genera escenarios complejos de testing: tenant con 5 staff, 20 servicios y 100 citas en distintos estados para probar el motor de disponibilidad bajo carga realista. |
| **Dusk (Browser Testing)** | Testing end-to-end de UI con Chrome real vía ChromeDriver. Testea flujos de Livewire/Alpine.js como lo haría un usuario real. | Testea el wizard de agendamiento completo (selección de servicio → staff → horario → confirmación) incluyendo la interactividad de Livewire en el navegador. |
| **Arquitectura de Tests: Feature > Unit** | Priorizar feature tests (cobertura real de comportamiento) sobre unit tests puros. Unit tests solo para lógica compleja y pura (calculadora de disponibilidad, reglas de negocio). | El motor de disponibilidad de citas tiene lógica compleja (solapamientos, bloqueos, zonas horarias, recurrencias). Esta lógica merece unit tests precisos además de feature tests de integración. |

**Skills clave a desarrollar:**
- Pest: `describe()`, `it()`, `beforeEach()`, `dataset()`, `arch()`
- Mocking de servicios externos (email, WhatsApp, pagos) con `Mail::fake()`, `Notification::fake()`
- Contract testing de la API para el cliente móvil
- GitHub Actions: correr la suite completa en PR antes de merge

---

## 10. Real-Time

| Herramienta | Descripción | Por qué es útil para AgendaYa |
|---|---|---|
| **Laravel Reverb** | Primer servidor WebSocket oficial de Laravel (lanzado con Laravel 11, marzo 2024). Protocolo compatible con Pusher. Self-hosted, gratuito, sin límites de conexión. | Para AgendaYa: actualizaciones en tiempo real del calendario del staff (nueva cita aparece instantáneamente), contador de notificaciones del admin y estado de confirmación de citas. Zero costo extra vs Pusher. |
| **Laravel Echo** | Librería JavaScript que se suscribe a canales y escucha eventos de Reverb/Pusher desde el frontend. Compatible con Livewire 3 nativo. | Livewire 3 tiene integración nativa con Echo. Los componentes del calendario pueden escuchar eventos y re-renderizarse automáticamente cuando hay nuevas citas sin polling. |
| **Pusher Channels** | Servicio WebSocket cloud (alternativa a Reverb). Tier gratuito: 200K mensajes/día, 100 conexiones simultáneas. SLA garantizado. | Alternativa para producción si no se quiere administrar el servidor Reverb. El tier gratuito es suficiente para los primeros tenants. Protocolo idéntico a Reverb. |
| **Laravel Broadcasting Events** | Sistema de eventos de Laravel que se "broadcastea" via WebSocket automáticamente con `implements ShouldBroadcast`. Soporta canales públicos, privados y de presencia. | `AppointmentBooked`, `AppointmentCancelled`, `StaffAvailabilityChanged` como eventos broadcasted. Los canales privados garantizan que solo el tenant correcto recibe los eventos. |
| **Polling con Livewire wire:poll** *(fallback)* | `wire:poll.5s` en componentes Livewire hace peticiones al servidor cada N segundos. Fallback simple si WebSockets no están disponibles en el hosting. | En hosting compartido sin soporte WebSockets, `wire:poll` en el dashboard de citas del día es un fallback funcional con bajo overhead para paneles con <50 citas/día. |

**Skills clave a desarrollar:**
- Configuración de Reverb en producción (Nginx proxy, SSL)
- Canales privados con autorización por tenant (broadcasting/channels.php)
- Livewire 3 + Echo: escuchar eventos en componentes
- Presencia channels para mostrar staff conectados en tiempo real

---

## Resumen de Prioridades por Fase

### Fase inmediata (Sprint 5-6, stack actual)
1. **stancl/tenancy** — Consolidar el modelo multi-tenant
2. **spatie/laravel-permission** — RBAC completo si no está implementado
3. **Laravel Reverb + Echo** — Real-time en el panel del staff
4. **Pest PHP** — Migrar/complementar tests PHPUnit existentes
5. **wandesnet/mercadopago-laravel** — Pagos para mercado mexicano
6. **netflie/laravel-notification-whatsapp** — WhatsApp Business API

### Fase media (próximos 3-6 meses)
1. **Docker + GitHub Actions** — CI/CD profesional
2. **Laravel Forge + Envoyer** — Deployment sin downtime
3. **Flutter + firebase_messaging** — App móvil con push notifications
4. **Laravel Cashier (Stripe)** — Suscripciones para clientes internacionales
5. **Sentry** — Error tracking en producción

### Fase de migración futura (6-12 meses)
1. **Next.js 15 + TypeScript + ShadCN** — Migración del frontend
2. **TanStack Query + Zustand** — State management del nuevo frontend
3. **React Native + Expo EAS** — App móvil con React Native
4. **PostgreSQL** — Migración de base de datos

---

## Fuentes y Referencias

- [TALL Stack oficial](https://tallstack.dev/)
- [Livewire 3 Docs](https://livewire.laravel.com/)
- [Tenancy for Laravel](https://tenancyforlaravel.com/)
- [Laravel SaaS Packages — LaravelDaily](https://laraveldaily.com/post/laravel-saas-useful-packages-tools)
- [spatie/laravel-permission](https://github.com/spatie/laravel-permission)
- [spatie/laravel-activitylog](https://github.com/spatie/laravel-activitylog)
- [Laravel Reverb](https://reverb.laravel.com/)
- [Laravel Cashier (Stripe)](https://laravel.com/docs/12.x/billing)
- [MercadoPago Laravel Package](https://github.com/wandesnet/mercadopago-laravel)
- [MercadoPago SDK PHP oficial](https://github.com/mercadopago/sdk-php)
- [Pest PHP](https://pestphp.com/)
- [Laravel Testing Docs](https://laravel.com/docs/12.x/testing)
- [Notifee (React Native)](https://notifee.app/)
- [Flutter FCM Docs](https://firebase.google.com/docs/cloud-messaging/flutter/get-started)
- [Laravel Forge Docs](https://forge.laravel.com/docs/sites/deployments)
- [GitHub Actions + Laravel Forge](https://driesvints.com/blog/building-and-deploying-laravel-with-github-actions/)
- [Laravel Notifications WhatsApp](https://github.com/netflie/laravel-notification-whatsapp)
- [WhatsApp Laravel Wrapper](https://github.com/MissaelAnda/laravel-whatsapp)
- [Releem — MySQL/MariaDB Tuning](https://releem.com/)
- [Laravel MySQL Optimization Guide](https://dudi.dev/optimize-laravel-database-queries/)
- [Laravel Security Best Practices 2025](https://benjamincrozat.com/laravel-security-best-practices)
- [OWASP Top 10:2025 para Laravel](https://ilyaskazi.medium.com/fortify-your-laravel-app-the-owasp-top-10-2025-edition-b3bf7ec3bfec)
- [ShadCN/UI Awesome List](https://github.com/birobirobiro/awesome-shadcn-ui)
- [TanStack Query + Zustand + Next.js](https://medium.com/@fadli99xyz/mastering-scalable-state-management-in-next-js-with-tanstack-query-zustand-and-typescript-ecc0205db12e)
- [Best Laravel Starter Kits 2025](https://saasykit.com/blog/best-laravel-multi-tenant-saas-starter-kits-for-2025)
- [Laravel Breeze 2FA](https://packagist.org/packages/metasoftdevs/laravel-breeze-2fa)
- [Laravel Pulse — Observabilidad](https://laravel.com/docs/12.x/pulse)
- [Beyond Telescope: Sentry + Pulse + OpenTelemetry](https://medium.com/@maharshmangal2400/beyond-telescope-real-observability-in-laravel-with-sentry-pulse-and-opentelemetry-73b4bfc063c5)
