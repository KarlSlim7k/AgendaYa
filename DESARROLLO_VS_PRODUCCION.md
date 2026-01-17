# 🏗️ Estructura del Proyecto: Desarrollo vs Producción

## 📁 Separación de Ambientes

Este proyecto mantiene **separación clara** entre desarrollo local y producción:

```
CitasEmpresariales/
├── 🐳 docker/                    # ← SOLO DESARROLLO LOCAL
│   ├── docker-compose.yml
│   ├── Dockerfile
│   ├── .env.docker.example
│   ├── README.md
│   ├── nginx/
│   ├── php/
│   └── init-scripts/
│
├── app/                          # ← Código Laravel (ambos ambientes)
├── database/                     # ← Migraciones (ambos ambientes)
├── resources/                    # ← Vistas/Assets (ambos ambientes)
├── routes/                       # ← Rutas (ambos ambientes)
├── .env.example                  # ← Para PRODUCCIÓN (Neubox)
├── .dockerignore                 # ← Excluye archivos de Docker builds
└── composer.json                 # ← Dependencias (ambos ambientes)
```

---

## 🚀 Desarrollo Local (con Docker)

### Usar:
✅ Carpeta `docker/` completa
✅ Variables en `docker/.env.docker.example`
✅ Servicios: MariaDB, Redis, MailHog
✅ URL: http://localhost:8080

### Iniciar:
```bash
cd docker
docker-compose up -d
```

### Conectarse a BD:
```bash
Host: localhost
Port: 3307
User: laravel_user
Pass: laravel_password
Database: citas_empresariales
```

---

## 🌐 Producción (Neubox Tellit)

### NO usar:
❌ Carpeta `docker/` (ignorar completamente)
❌ Archivos `.env.docker*`
❌ Docker Compose
❌ Contenedores

### Usar:
✅ Archivo `.env` con configuración cPanel
✅ MariaDB del servidor (cPanel)
✅ Apache 2.4.66 (preinstalado)
✅ PHP 8.2 (preinstalado)

### Desplegar:
```bash
# 1. Subir código por FTP/Git (sin carpeta docker/)
# 2. En terminal cPanel:
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan config:cache
php artisan route:cache
php artisan migrate --force
```

### Conectarse a BD:
```bash
# Configurar en .env (desde cPanel):
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=tu_bd_cpanel
DB_USERNAME=tu_usuario_cpanel
DB_PASSWORD=tu_password_cpanel
```

---

## ⚠️ Diferencias Clave

| Concepto | Desarrollo (Docker) | Producción (Neubox) |
|----------|---------------------|---------------------|
| **Carpeta Docker** | ✅ Usar | ❌ Ignorar |
| **Archivo .env** | `docker/.env.docker` | `.env` en raíz |
| **MariaDB** | Contenedor puerto 3307 | Servidor cPanel puerto 3306 |
| **Redis** | Contenedor | ❌ No disponible |
| **MailHog** | localhost:8025 | ❌ SMTP real |
| **Servidor Web** | Nginx contenedor | Apache cPanel |
| **URL** | localhost:8080 | tudominio.com |

---

## 🔒 Archivos Ignorados en Git

El `.gitignore` está configurado para:
- ✅ **Incluir** carpeta `docker/` con configuraciones
- ❌ **Excluir** `docker/.env.docker` (credenciales locales)
- ❌ **Excluir** `docker-compose.override.yml` (overrides personales)

Esto permite que cada desarrollador tenga su propia configuración local sin afectar a otros.

---

## 📚 Documentación

- **Setup Docker**: Ver `docker/README.md`
- **Deploy Producción**: Ver `docs/DEPLOY.md` (cuando exista)
- **Stack Tecnológico**: Ver `docs/stack_tecnologico_actualizado.md`
