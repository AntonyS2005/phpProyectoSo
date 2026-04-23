# Checklist QA

## Autenticacion

- Login valido (admin/consulta/ingreso).
- Login invalido muestra error.
- Usuario inactivo no entra.
- Logout revoca sesion y tokens.
- Expiracion de access renueva via refresh.

## Permisos

- Menu lateral cambia segun permisos.
- Acceso directo por URL es denegado sin permiso (backend).
- Edicion de permisos solo disponible con `configuracion:UPDATE`.
- Permisos solo permiten combinaciones existentes en `recurso_accion`.

## CRUD

- Usuarios: crear, editar, eliminar (no auto-eliminarse).
- Catalogos: roles/status/recursos/acciones con reglas de integridad.
- Recursos: editar acciones permitidas y verificar impacto en permisos.

## Sesiones y auditoria

- Sesiones: listar y revocar.
- Auditoria: eventos para LOGIN/LOGOUT/ACCESS_DENIED/CRUD.

## Responsive

- 360px: drawer funciona, tablas scroll/stack.
- 768px: layout estable, formularios legibles.
- 1280px+: cards y tablas alineadas.

## Infra (cluster)

- `wsrep_cluster_size=3` en arranque limpio.
- Caida de 1 nodo: app sigue.
- Rejoin: nodo vuelve y cluster regresa a 3.

