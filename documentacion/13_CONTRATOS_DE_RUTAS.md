# Contratos de Rutas (Paginas)

Este documento sirve como "contrato" entre UI, backend y seguridad.

## Publicas

- `/login.php`
  - GET: formulario
  - POST: autentica y emite tokens
- `/logout.php`
  - GET: revoca y redirige

## Dashboard (protegidas)

Todas requieren `require_permission(...)` al inicio.

- `/dashboard/overview.php`
  - permiso: `reportes:READ`

- `/dashboard/users.php`
  - permiso: `usuarios:READ`
  - POST create: `usuarios:CREATE`
  - POST update: `usuarios:UPDATE`
  - POST delete: `usuarios:DELETE`

- `/dashboard/profile.php`
  - permiso: `usuarios:READ`

- `/dashboard/statuses.php`
  - permiso base: `configuracion:READ`
  - POST create/update/delete: `configuracion:CREATE/UPDATE/DELETE`

- `/dashboard/roles.php`
  - permiso base: `configuracion:READ`
  - POST create/update/delete: `configuracion:CREATE/UPDATE/DELETE`

- `/dashboard/resources.php`
  - permiso base: `configuracion:READ`
  - POST recurso create/update/delete: `configuracion:CREATE/UPDATE/DELETE`
  - POST `form_action=actions`: `configuracion:UPDATE` (edita `recurso_accion`)

- `/dashboard/actions.php`
  - permiso base: `configuracion:READ`
  - POST create/update/delete: `configuracion:CREATE/UPDATE/DELETE`

- `/dashboard/permissions.php`
  - permiso ver: `configuracion:READ`
  - permiso guardar: `configuracion:UPDATE`

- `/dashboard/sessions.php`
  - permiso ver: `reportes:READ`
  - permiso revocar: `reportes:UPDATE`

- `/dashboard/audit.php`
  - permiso: `reportes:READ`

## Redirects legacy

Existen rutas legacy (compatibilidad) que redirigen al nuevo dashboard.

