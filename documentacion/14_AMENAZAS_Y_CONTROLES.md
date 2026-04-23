# Amenazas y Controles (Threat Model basico)

Objetivo: identificar amenazas probables y controles aplicados.

## Amenazas comunes

- Robo de sesion/token (cookie theft).
- Acceso por URL directa sin permiso.
- Inyeccion SQL.
- CSRF en acciones sensibles.
- Escalamiento de privilegios por manipulacion de form (id_rol/id_status).
- Fuga de informacion por errores detallados.
- Caida de DB y perdida de disponibilidad.

## Controles implementados

- Tokens en cookies `HttpOnly` (reduce robo por XSS).
- `require_permission()` en backend en cada pantalla.
- Validaciones server-side (roles/status deben existir).
- Uso de PDO con prepared statements.
- Mensaje generico al fallar DB (no expone DSN/credenciales).
- Auditoria de eventos relevantes.
- Cluster Galera para tolerancia a fallos de DB.
- Failover en `app/includes/db.php` por `DB_HOSTS`.

## Controles recomendados (siguiente iteracion)

- CSRF tokens en formularios POST (proteccion real).
- Rate-limit de login (por IP/usuario).
- Rotacion de refresh token en cada uso + deteccion de reuse.
- Headers de seguridad (CSP, HSTS si hay HTTPS real).
- Revisar permisos minimos por rol (principio de menor privilegio).

