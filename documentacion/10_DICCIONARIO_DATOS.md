# Diccionario de Datos

Este documento describe columnas y su uso. No reemplaza al esquema SQL, pero explica significado y reglas.

## `usuarios`

- `id_usuario`: PK.
- `id_status`: FK a `cat_status` (estado de cuenta).
- `id_rol`: FK a `roles`.
- `username`: identificador unico (login).
- `email`: correo del usuario (tambien puede usarse para login).
- `password_hash`: bcrypt hash (nunca se guarda password en claro).
- `email_verificado`: 0/1 (flag).
- `fecha_creacion`: fecha de alta.
- `fecha_ultimo_acceso`: ultima vez que inicio sesion.

Reglas:

- `username` y `email` deben ser unicos.
- no se debe permitir editar `password_hash` salvo por cambio de password.

## `usuarios_perfil`

- `id_usuario`: PK y FK a `usuarios`.
- `nombre`, `apellido`: datos opcionales.
- `telefono`: opcional.
- `fecha_nacimiento`: opcional.

## `cat_status`

- `id_status`: PK.
- `nombre`: valores tipicos: `activo`, `inactivo`, `bloqueado`.
- `descripcion`: opcional.

Regla:

- no se elimina si hay usuarios con ese status.

## `roles`

- `id_rol`: PK.
- `nombre`: nombre de rol (ej. `admin`).
- `descripcion`: proposito del rol.

Regla:

- no se elimina si hay usuarios con ese rol.

## `cat_recurso`

- `id_recurso`: PK.
- `nombre`: recurso funcional (ej. `usuarios`, `reportes`, `configuracion`).
- `descripcion`: opcional.

Nota:

- el `nombre` del recurso se usa en `require_permission(recurso, accion)` (debe ser estable).

## `cat_accion`

- `id_accion`: PK.
- `nombre`: accion (ej. `READ`, `CREATE`, `UPDATE`, `DELETE`).
- `descripcion`: opcional.

Nota:

- el `nombre` de accion se normaliza a mayuscula para keys.

## `recurso_accion`

Define combinaciones validas de acciones por recurso.

- `id_recurso_accion`: PK.
- `id_recurso`: FK a `cat_recurso`.
- `id_accion`: FK a `cat_accion`.

Regla:

- una combinacion `id_recurso + id_accion` es unica.
- si quitas una combinacion, se deben limpiar permisos invalidos (se hace en services).

## `permisos`

Permisos efectivos.

- `id_rol`: FK a `roles`.
- `id_recurso`: FK a `cat_recurso`.
- `id_accion`: FK a `cat_accion`.

Regla:

- solo se permiten combinaciones existentes en `recurso_accion`.

## `sesiones`

Sesion maestra.

- `id_sesion`: PK.
- `id_usuario`: FK a `usuarios`.
- `token_hash`: hash del refresh (sha256) como identificador de sesion.
- `ip_address`: IP del cliente.
- `user_agent`: UA del cliente (recortado).
- `activa`: 0/1.
- `fecha_inicio`: inicio de sesion.
- `fecha_ultima_actividad`: actualizacion por request.
- `fecha_expiracion`: TTL global (24h).
- `fecha_revocacion`: si se revoco.
- `motivo_revocacion`: texto corto.

## `refresh_tokens`

Token de sesion (24h).

- `id_refresh_token`: PK.
- `id_sesion`: FK a `sesiones`.
- `id_usuario`: FK a `usuarios`.
- `token_hash`: sha256 del refresh raw.
- `ip_address`, `user_agent`.
- `fecha_creacion`, `fecha_expiracion`.
- `activa`: 0/1.
- `fecha_revocacion`.

Regla:

- un refresh activo representa sesion valida (con sesion activa).

## `access_tokens`

Token de acceso (15 min).

- `id_access_token`: PK.
- `id_refresh_token`: FK a `refresh_tokens`.
- `id_usuario`: FK a `usuarios`.
- `token_hash`: sha256 del access raw.
- `ip_address`.
- `fecha_expiracion`.

Regla:

- se pueden emitir multiples access por el mismo refresh (renovaciones).

## `auditoria`

Bitacora.

- `id_auditoria`: PK.
- `id_usuario`: FK nullable (puede ser null en eventos anonimos).
- `accion`: codigo (ej. `LOGIN`, `ACCESS_DENIED`, `UPDATE_USER`).
- `detalle`: texto de contexto.
- `ip_address`: IP observada.
- `fecha`: timestamp del evento.

