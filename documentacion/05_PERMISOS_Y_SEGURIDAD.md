# Permisos y Seguridad

## Modelo RBAC extendido

Permiso = `rol` + `recurso` + `accion`

Catalogos:

- `roles`
- `cat_recurso`
- `cat_accion`
- `recurso_accion` (que acciones son validas por recurso)

Permisos efectivos:

- `permisos`

## Enforcement real (backend)

Se controla en servidor, no solo ocultando botones:

- `require_permission(recurso, accion)` en `app/includes/auth.php`
- Cada pantalla de `app/dashboard/*.php` inicia validando permiso.
- Acciones POST sensibles vuelven a validar permiso dentro del handler.

## UX del editor de permisos

Pantalla: `app/dashboard/permissions.php`

- Vista por rol y tarjetas por recurso (evita tabla gigante).
- Resumen de permisos activos.
- Botones para marcar/limpiar por rol o global.
- Solo muestra combinaciones permitidas por `recurso_accion`.
- Botones de seguridad UX:
  - `Deshacer cambios` (reset)
  - `Restaurar desde BD` (reload)

## Edicion de acciones por recurso

Pantalla: `app/dashboard/resources.php`

- En modo edicion, permite seleccionar acciones validas para ese recurso.
- Al guardar:
  - actualiza `recurso_accion`
  - limpia permisos invalidos para ese recurso
  - audita `UPDATE_RESOURCE_ACTIONS`

## Auditoria

Tabla: `auditoria`

Se registran eventos como:

- LOGIN / LOGOUT / LOGIN_REFRESH / ACCESS_REFRESH
- ACCESS_DENIED
- CRUD de usuarios/catalogos
- UPDATE_PERMISSIONS / UPDATE_RESOURCE_ACTIONS
- REVOKE_SESSION

Pantalla: `app/dashboard/audit.php`

