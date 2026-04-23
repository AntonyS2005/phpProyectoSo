# Modelo de datos

## Entidades principales

### Usuarios

- `usuarios`
  - credenciales y estado
  - rol asociado (`id_rol`)
  - status (`id_status`)
- `usuarios_perfil`
  - datos extendidos (nombre, apellido, telefono, fecha_nacimiento)

### Seguridad (gobierno)

- `roles`
- `cat_recurso`
- `cat_accion`
- `recurso_accion`: define que acciones son validas por recurso
- `permisos`: permisos efectivos (id_rol + id_recurso + id_accion)

### Sesiones y tokens

- `sesiones`: registro maestro de sesion (activa, expiracion, revocacion)
- `refresh_tokens`: token de 24h asociado a `sesiones`
- `access_tokens`: token de 15 min asociado a `refresh_tokens`

### Auditoria

- `auditoria`: bitacora (usuario, accion, detalle, ip)

## Relaciones (resumen)

- `usuarios.id_rol` -> `roles.id_rol`
- `usuarios.id_status` -> `cat_status.id_status`
- `usuarios_perfil.id_usuario` -> `usuarios.id_usuario`

- `permisos.id_rol` -> `roles.id_rol`
- `permisos.id_recurso` -> `cat_recurso.id_recurso`
- `permisos.id_accion` -> `cat_accion.id_accion`

- `recurso_accion.id_recurso` -> `cat_recurso.id_recurso`
- `recurso_accion.id_accion` -> `cat_accion.id_accion`

- `sesiones.id_usuario` -> `usuarios.id_usuario`
- `refresh_tokens.id_sesion` -> `sesiones.id_sesion`
- `refresh_tokens.id_usuario` -> `usuarios.id_usuario`
- `access_tokens.id_refresh_token` -> `refresh_tokens.id_refresh_token`

## Reglas de integridad y negocio

- No se permite eliminar un rol con usuarios asociados.
- No se permite eliminar un status en uso.
- No se permite asignar un permiso si la combinacion recurso-accion no existe en `recurso_accion`.
- Revocar una sesion revoca refresh y expira access tokens relacionados.

