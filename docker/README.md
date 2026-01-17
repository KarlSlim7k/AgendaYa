# 🐳 Docker Setup - Desarrollo Local

## ⚠️ IMPORTANTE: SOLO PARA DESARROLLO LOCAL

**Este setup Docker es ÚNICAMENTE para desarrollo local.** 

❌ **NO usar en producción Neubox Tellit**  
✅ **Solo para desarrollo en tu máquina local**

---

## 📋 Requisitos Previos

- Docker Desktop instalado
- Docker Compose instalado
- Git
- 4GB RAM mínimo disponible

---

## 🚀 Inicio Rápido

### 1. Clonar el repositorio (si aún no lo has hecho)

```bash
git clone https://github.com/KarlSlim7k/CitasEmpresariales.git
cd CitasEmpresariales
```

### 2. Iniciar servicios Docker

```bash
cd docker
docker-compose up -d
```

**Servicios disponibles:**
- 🌐 **Web**: http://localhost:8080
- 📧 **MailHog**: http://localhost:8025
- 🗄️ **MariaDB**: localhost:3307
- 🔴 **Redis**: localhost:6380

### 3. Instalar Laravel (primera vez)

```bash
# Entrar al contenedor PHP
docker exec -it citas_php_dev bash

# Dentro del contenedor:
composer install
cp docker/.env.docker.example .env
php artisan key:generate
php artisan migrate --seed
exit
```

### 4. Acceder a la aplicación

Abrir en navegador: http://localhost:8080

---

## 🛠️ Comandos Útiles

### Gestión de contenedores

```bash
# Iniciar servicios
docker-compose up -d

# Ver logs
docker-compose logs -f

# Detener servicios
docker-compose down

# Detener y eliminar volúmenes (RESET COMPLETO)
docker-compose down -v
```

### Ejecutar comandos Artisan

```bash
# Entrar al contenedor PHP
docker exec -it citas_php_dev bash

# Ejecutar migraciones
php artisan migrate

# Ejecutar seeders
php artisan db:seed

# Limpiar cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

### Ejecutar Composer

```bash
# Instalar dependencias
docker exec -it citas_php_dev composer install

# Actualizar dependencias
docker exec -it citas_php_dev composer update
```

### Ejecutar NPM (Vite)

```bash
# Entrar al contenedor PHP
docker exec -it citas_php_dev bash

# Instalar Node (si no está)
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt-get install -y nodejs

# Instalar dependencias
npm install

# Desarrollo con hot-reload
npm run dev

# Build producción
npm run build
```

### Acceso a MariaDB

```bash
# Desde host (requiere cliente mysql)
mysql -h 127.0.0.1 -P 3307 -u laravel_user -p
# Password: laravel_password

# Desde contenedor MariaDB
docker exec -it citas_mariadb_dev mariadb -u laravel_user -p citas_empresariales
```

### Logs en tiempo real

```bash
# Todos los servicios
docker-compose logs -f

# Solo PHP
docker-compose logs -f php

# Solo MariaDB
docker-compose logs -f mariadb
```

---

## 🔧 Estructura de Archivos Docker

```
docker/
├── docker-compose.yml        # Orquestación de servicios
├── Dockerfile                 # Imagen PHP 8.2
├── .env.docker.example        # Variables de entorno
├── README.md                  # Esta documentación
├── nginx/
│   └── default.conf           # Configuración Nginx
├── php/
│   └── php.ini                # Configuración PHP
└── init-scripts/
    └── 01_create_test_users.sql  # Scripts de inicialización BD
```

---

## 🔍 Testing de Emails con MailHog

Todos los emails enviados por la aplicación se capturan en MailHog:

1. Abrir http://localhost:8025
2. Ver emails sin necesidad de SMTP real
3. Perfecto para testing de notificaciones

---

## 🐛 Troubleshooting

### Puerto ya en uso

```bash
# Si el puerto 8080 está ocupado, editar docker-compose.yml:
# Cambiar "8080:80" por "8081:80" (u otro puerto libre)
```

### Permisos de archivos

```bash
# Si hay errores de permisos:
docker exec -it citas_php_dev chown -R laravel:laravel /var/www/html/storage
docker exec -it citas_php_dev chown -R laravel:laravel /var/www/html/bootstrap/cache
```

### Reiniciar todo desde cero

```bash
cd docker
docker-compose down -v  # Elimina contenedores y volúmenes
docker-compose up -d
docker exec -it citas_php_dev composer install
docker exec -it citas_php_dev php artisan migrate:fresh --seed
```

---

## 📦 Diferencias con Producción

| Aspecto | Docker (Local) | Neubox Tellit (Producción) |
|---------|----------------|---------------------------|
| **Servidor Web** | Nginx | Apache 2.4.66 |
| **PHP** | PHP-FPM 8.2 | PHP 8.2 CGI/FastCGI |
| **MariaDB** | Contenedor 11.4.9 | Servidor compartido 11.4.9 |
| **Redis** | Contenedor | No disponible (usar DB cache) |
| **Logs** | Docker logs | cPanel logs |
| **Deploy** | docker-compose up | FTP + composer install |

---

## ⚠️ NO Subir a Producción

Los siguientes archivos/carpetas **NUNCA** deben subirse al servidor Neubox:

- ❌ `docker/` (toda la carpeta)
- ❌ `.dockerignore`
- ❌ Archivos `.env.docker*`

El `.gitignore` ya está configurado para ignorar estos archivos.

---

## 📚 Recursos Adicionales

- Docker Docs: https://docs.docker.com/
- Docker Compose: https://docs.docker.com/compose/
- Laravel Sail (alternativa oficial): https://laravel.com/docs/sail
