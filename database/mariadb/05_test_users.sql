-- =====================================================
-- AgendaYa - Usuarios de prueba
-- Importar en phpMyAdmin para testing
-- =====================================================
-- Contraseñas (bcrypt de "password123"):
--   admin@agendaya.mx     → PLATAFORMA_ADMIN
--   negocio@agendaya.mx   → NEGOCIO_ADMIN (con negocio de prueba)
-- =====================================================

USE agendaya_agendaya;

-- =====================================================
-- USUARIOS
-- =====================================================

INSERT INTO users (nombre, email, password, telefono, email_verified_at, created_at, updated_at) VALUES
(
    'Admin Plataforma',
    'admin@agendaya.mx',
    '$2y$12$pQrzvq89woyg3bdUo53XFeiH95zQG9AfkRQ9EQ0S9Y3xz/MWUOfLa',
    '+5215500000001',
    NOW(),
    NOW(),
    NOW()
),
(
    'Admin Negocio Demo',
    'negocio@agendaya.mx',
    '$2y$12$pQrzvq89woyg3bdUo53XFeiH95zQG9AfkRQ9EQ0S9Y3xz/MWUOfLa',
    '+5215500000002',
    NOW(),
    NOW(),
    NOW()
);

-- =====================================================
-- PLATFORM_ADMIN para el primer usuario
-- =====================================================

INSERT INTO platform_admins (user_id, super_admin, created_at, updated_at)
SELECT id, TRUE, NOW(), NOW()
FROM users WHERE email = 'admin@agendaya.mx';

-- =====================================================
-- NEGOCIO DE PRUEBA
-- =====================================================

INSERT INTO businesses (nombre, categoria, telefono, email, plan, estado, created_at, updated_at) VALUES
('Negocio Demo AgendaYa', 'Servicios', '+5215500000002', 'negocio@agendaya.mx', 'standard', 'approved', NOW(), NOW());

-- =====================================================
-- ROL NEGOCIO_ADMIN al segundo usuario en el negocio demo
-- =====================================================

INSERT INTO business_user_roles (user_id, business_id, role_id, created_at, updated_at)
SELECT
    u.id,
    b.id,
    r.id,
    NOW(),
    NOW()
FROM users u, businesses b, roles r
WHERE u.email = 'negocio@agendaya.mx'
  AND b.email = 'negocio@agendaya.mx'
  AND r.name = 'NEGOCIO_ADMIN';

-- =====================================================
-- ROL PLATAFORMA_ADMIN al primer usuario
-- =====================================================

INSERT INTO business_user_roles (user_id, business_id, role_id, created_at, updated_at)
SELECT
    u.id,
    NULL,
    r.id,
    NOW(),
    NOW()
FROM users u, roles r
WHERE u.email = 'admin@agendaya.mx'
  AND r.name = 'PLATAFORMA_ADMIN';

-- Verificar
SELECT email, nombre FROM users WHERE email IN ('admin@agendaya.mx', 'negocio@agendaya.mx');
