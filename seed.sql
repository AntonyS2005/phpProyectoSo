-- ================================================
--  SEED DATA  —  Datos de prueba
--  Ejecutar DESPUÉS de crear las tablas
-- ================================================

-- Estados
INSERT INTO cat_status (id_status, nombre) VALUES
  (1, 'activo'),
  (2, 'inactivo'),
  (3, 'bloqueado')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

-- Roles
INSERT INTO roles (id_rol, nombre, descripcion) VALUES
  (1, 'admin',    'Control total: CRUD de usuarios'),
  (2, 'consulta', 'Solo lectura'),
  (3, 'ingreso',  'Acceso básico al sistema')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), descripcion = VALUES(descripcion);

-- Recursos
INSERT INTO cat_recurso (nombre, descripcion) VALUES
  ('usuarios',      'Gestión de usuarios'),
  ('reportes',      'Reportes del sistema'),
  ('configuracion', 'Configuración general')
ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion);

-- Acciones
INSERT INTO cat_accion (nombre, descripcion) VALUES
  ('CREATE', 'Crear registros'),
  ('READ',   'Leer registros'),
  ('UPDATE', 'Actualizar registros'),
  ('DELETE', 'Eliminar registros')
ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion);

-- Acciones validas por recurso
INSERT IGNORE INTO recurso_accion (id_recurso, id_accion)
SELECT r.id_recurso, a.id_accion
FROM cat_recurso r
JOIN cat_accion a;

-- Permisos para Admin (todo)
INSERT IGNORE INTO permisos (id_rol, id_recurso, id_accion)
SELECT 1, r.id_recurso, a.id_accion
FROM cat_recurso r, cat_accion a;

-- Permisos para Consulta (solo READ)
INSERT IGNORE INTO permisos (id_rol, id_recurso, id_accion)
SELECT 2, r.id_recurso, a.id_accion
FROM cat_recurso r, cat_accion a
WHERE a.nombre = 'READ';

-- Permisos para Ingreso (READ + CREATE sobre usuarios)
INSERT IGNORE INTO permisos (id_rol, id_recurso, id_accion)
SELECT 3, r.id_recurso, a.id_accion
FROM cat_recurso r, cat_accion a
WHERE r.nombre = 'usuarios' AND a.nombre IN ('READ', 'CREATE');

-- Usuarios de prueba
-- Contraseña: Test1234! (hash bcrypt)
INSERT INTO usuarios (id_status, id_rol, username, email, password_hash, email_verificado) VALUES
  (1, 1, 'admin',    'admin@test.com',    '$2y$12$Jf8uXYJSmCKFM2i3Jn4xHO9eVMFmZkD4lE/S66tB8AQv/thYy4dVW', 1),
  (1, 2, 'consulta', 'consulta@test.com', '$2y$12$Jf8uXYJSmCKFM2i3Jn4xHO9eVMFmZkD4lE/S66tB8AQv/thYy4dVW', 1),
  (1, 3, 'ingreso',  'ingreso@test.com',  '$2y$12$Jf8uXYJSmCKFM2i3Jn4xHO9eVMFmZkD4lE/S66tB8AQv/thYy4dVW', 1)
ON DUPLICATE KEY UPDATE
  id_status = VALUES(id_status),
  id_rol = VALUES(id_rol),
  username = VALUES(username),
  email = VALUES(email),
  password_hash = VALUES(password_hash),
  email_verificado = VALUES(email_verificado);

-- Perfiles
INSERT INTO usuarios_perfil (id_usuario, nombre, apellido)
SELECT id_usuario, 'Super', 'Admin' FROM usuarios WHERE username = 'admin'
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), apellido = VALUES(apellido);

INSERT INTO usuarios_perfil (id_usuario, nombre, apellido)
SELECT id_usuario, 'Usuario', 'Consulta' FROM usuarios WHERE username = 'consulta'
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), apellido = VALUES(apellido);

INSERT INTO usuarios_perfil (id_usuario, nombre, apellido)
SELECT id_usuario, 'Usuario', 'Ingreso' FROM usuarios WHERE username = 'ingreso'
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), apellido = VALUES(apellido);
