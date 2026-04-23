# Arquitectura

## Vista general

Arquitectura monolitica server-rendered:

- Frontend: HTML generado por PHP + Tailwind v4 + DaisyUI (CSS compilado).
- Backend: PHP 8.2 con capas ligeras (helpers + repository + services).
- DB: MariaDB Galera (3 nodos) con replicacion sincronica.

## Frontend (UI)

### Estilo y componentes

- Estilos fuente: `app/assets/css/tailwind.css`
- Estilos compilados: `app/assets/css/app.css`
- Layout responsivo: `app/includes/layout.php`

Patrones UI:

- Shell con drawer/side menu
- Paneles tipo "glass-panel"
- Formularios con `form-control`, `input`, `textarea`, `select`, `checkbox`
- Tablas con `table`, `table-zebra`
- Mensajes flash: alertas arriba del contenido

### Navegacion

El menu lateral se construye desde permisos efectivos del usuario:

- `app/includes/app.php`: `permission_sections()` y `app_navigation()`
- `app/includes/auth.php`: `has_permission()` / `current_permissions()`

## Backend (PHP)

### Capas

- DB: `app/includes/db.php` (conexion + failover por lista de hosts)
- Auth y seguridad: `app/includes/auth.php`
- Helpers UI/flujo: `app/includes/app.php` y `app/includes/layout.php`
- Consultas (read): `app/includes/repository.php`
- Escrituras/negocio (write): `app/includes/services.php`

Reglas:

- Las vistas (`app/dashboard/*.php`) deben ser controladores delgados:
  - validar permiso
  - manejar POST
  - obtener datos del repo
  - renderizar layout + template

### Convenciones

- PRG: POST -> redirect (evita doble submit).
- Auditoria: operaciones sensibles registran evento.
- Validaciones: siempre server-side.

## Infraestructura

Ver `documentacion/07_INFRAESTRUCTURA_GALERA.md`.

