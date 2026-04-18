-- ================================================
--  SEED DATA  —  Datos de prueba
--  Ejecutar DESPUÉS de crear las tablas
-- ================================================

-- Estados
INSERT IGNORE INTO cat_status (id_status, nombre) VALUES
  (1, 'activo'),
  (2, 'inactivo'),
  (3, 'bloqueado');

-- Roles
INSERT IGNORE INTO roles (id_rol, nombre, descripcion) VALUES
  (1, 'admin',    'Control total: CRUD de usuarios'),
  (2, 'consulta', 'Solo lectura'),
  (3, 'ingreso',  'Acceso básico al sistema');

-- Recursos
INSERT IGNORE INTO cat_recurso (nombre, descripcion) VALUES
  ('usuarios',      'Gestión de usuarios'),
  ('reportes',      'Reportes del sistema'),
  ('configuracion', 'Configuración general');

-- Acciones
INSERT IGNORE INTO cat_accion (nombre, descripcion) VALUES
  ('CREATE', 'Crear registros'),
  ('READ',   'Leer registros'),
  ('UPDATE', 'Actualizar registros'),
  ('DELETE', 'Eliminar registros');

-- Permisos para Admin (todo sobre usuarios)
INSERT IGNORE INTO permisos (id_rol, id_recurso, id_accion)
SELECT 1, r.id_recurso, a.id_accion
FROM cat_recurso r, cat_accion a
WHERE r.nombre = 'usuarios';

-- Permisos para Consulta (solo READ)
INSERT IGNORE INTO permisos (id_rol, id_recurso, id_accion)
SELECT 2, r.id_recurso, a.id_accion
FROM cat_recurso r, cat_accion a
WHERE r.nombre = 'usuarios' AND a.nombre = 'READ';

-- Usuarios de prueba
-- Contraseña de todos: Test1234!
INSERT IGNORE INTO usuarios (id_status, id_rol, username, email, password_hash, email_verificado) VALUES
  (1, 1, 'admin',    'admin@test.com',    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1),
  (1, 2, 'consulta', 'consulta@test.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1),
  (1, 3, 'ingreso',  'ingreso@test.com',  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);

-- Perfiles
INSERT IGNORE INTO usuarios_perfil (id_usuario, nombre, apellido)
SELECT id_usuario, 'Super', 'Admin'    FROM usuarios WHERE username = 'admin';
INSERT IGNORE INTO usuarios_perfil (id_usuario, nombre, apellido)
SELECT id_usuario, 'Usuario', 'Consulta' FROM usuarios WHERE username = 'consulta';
INSERT IGNORE INTO usuarios_perfil (id_usuario, nombre, apellido)
SELECT id_usuario, 'Usuario', 'Ingreso'  FROM usuarios WHERE username = 'ingreso';
