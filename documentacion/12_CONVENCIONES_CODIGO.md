# Convenciones de Codigo (PHP)

Objetivo: que el codigo sea facil de mantener aunque sea PHP "clásico".

## Estructura recomendada

- `app/dashboard/*.php`: controladores/vistas (delgados).
- `app/includes/repository.php`: consultas de lectura (SELECT).
- `app/includes/services.php`: escritura y reglas de negocio (INSERT/UPDATE/DELETE + transacciones).
- `app/includes/auth.php`: auth + tokens + enforcement.
- `app/includes/layout.php`: shell UI.
- `app/includes/app.php`: helpers de flujo (flash, redirect, nav).

## Regla de oro

No mezclar:

- SQL complejo dentro de la vista (mover a repository/services).
- checks de permiso solo en frontend (siempre backend).

## Patrones aplicados

- PRG (POST-Redirect-GET): evita doble submit.
- Validacion server-side: todo input se valida en el servidor.
- Auditoria: cambios sensibles dejan rastro.
- Failover DB: la app prueba varios hosts del cluster.

## Anti-patrones a evitar

- guardar passwords en claro.
- construir SQL con concatenacion de strings con input sin bind.
- confiar en "ocultar botones" como seguridad.
- usar una sola pagina para muchas responsabilidades sin separar (vista vs servicio).

## Seguridad basica en PHP

- Escapar salida HTML con `h()`.
- Cookies `HttpOnly` para tokens.
- Timeouts y expiraciones claras.
- Acceso denegado con redireccion segura.

