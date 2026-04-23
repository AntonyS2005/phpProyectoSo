# Autenticacion y Tokens

## Componentes

- Cookie `refresh_token` (24h): restaura sesion y emite access.
- Cookie `access_token` (15m): habilita requests protegidos.
- `sesiones`: entidad maestra para operar revocaciones y auditoria.

Implementacion: `app/includes/auth.php`.

## Flujo de login

1. Usuario envía `username` + `password` en `app/login.php`.
2. `login()` valida:
   - usuario existe
   - status == `activo`
   - password bcrypt correcto
3. Se crea:
   - fila `sesiones` (activa, expira 24h)
   - fila `refresh_tokens` (hash, expira 24h, activa=1, referencia a sesion)
   - fila `access_tokens` (hash, expira 15m, referencia al refresh)
4. Se setean cookies HttpOnly.
5. Se guarda `$_SESSION['user']` y `$_SESSION['permissions']`.
6. Se registra `auditoria` (LOGIN).

## Request protegido

En cada pantalla protegida:

- `require_permission(recurso, accion)`:
  - fuerza auth (`require_auth()`)
  - valida access token vigente, si no:
    - intenta renovarlo con refresh token vigente
  - refresca snapshot de usuario/rol/permisos (evita sesion stale)
  - si no hay permiso: deniega y redirige a una ruta segura

## Renovacion (refresh)

- Si `access_token` expiro, pero refresh sigue activo:
  - inserta nuevo `access_tokens`
  - actualiza cookie `access_token`
  - audita `ACCESS_REFRESH`

## Revocacion

Se puede revocar:

- logout: revoca refresh + expira access y marca sesion inactiva
- operacion admin: revoca `sesiones` y cascada de tokens relacionados

Pantalla: `app/dashboard/sessions.php`.

