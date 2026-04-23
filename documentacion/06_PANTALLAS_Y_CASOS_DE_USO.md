# Pantallas y Casos de Uso

Este documento describe cada pantalla, su proposito y sus casos de uso.

## Convenciones

- Recurso/accion requerida: lo que valida `require_permission()`.
- Entradas: parametros GET/POST.
- Salidas: vista, redirect, efectos en DB.

## Login

Ruta: `app/login.php`

- Proposito: autenticar usuario y emitir tokens.
- Permiso requerido: ninguno (publico).
- Entradas:
  - POST: `username`, `password`
- Salidas:
  - OK: redirect a home (primera pagina permitida)
  - Error: mensaje de credenciales incorrectas o usuario inactivo
- Auditoria:
  - `LOGIN` exitoso

## Logout

Ruta: `app/logout.php`

- Proposito: cerrar sesion y revocar tokens.
- Salidas:
  - redirect a login
- Auditoria:
  - `LOGOUT`

## Overview

Ruta: `app/dashboard/overview.php`

- Proposito: KPIs y resumen operativo.
- Recurso/accion: `reportes:READ`
- Muestra:
  - conteos (usuarios, roles, permisos, sesiones activas, eventos del dia)
  - sesiones activas recientes
  - usuarios recientes
  - auditoria reciente

## Usuarios (CRUD)

Ruta: `app/dashboard/users.php`

- Proposito: administrar `usuarios` y `usuarios_perfil`.
- Recurso/accion:
  - listar: `usuarios:READ`
  - crear: `usuarios:CREATE`
  - actualizar: `usuarios:UPDATE`
  - eliminar: `usuarios:DELETE`
- Entradas:
  - GET: `edit` (id_usuario)
  - POST: formulario de usuario + perfil
- Validaciones:
  - email valido
  - rol y status deben existir en DB (no se aceptan valores inventados)
  - no se permite borrar el propio usuario
- Auditoria:
  - `CREATE_USER`, `UPDATE_USER`, `DELETE_USER`

## Perfil (self-service)

Ruta: `app/dashboard/profile.php`

- Proposito: ver/editar datos propios.
- Recurso/accion: `usuarios:READ`
- Acciones:
  - cambiar email y password
  - editar perfil basico

## Estados (catalogo)

Ruta: `app/dashboard/statuses.php`

- Proposito: administrar `cat_status`.
- Recurso/accion: `configuracion:READ` (+ CREATE/UPDATE/DELETE segun accion)
- Regla:
  - no eliminar si hay usuarios asociados

## Roles (catalogo)

Ruta: `app/dashboard/roles.php`

- Proposito: administrar `roles`.
- Recurso/accion: `configuracion:READ` (+ CREATE/UPDATE/DELETE segun accion)
- Regla:
  - no eliminar si hay usuarios asociados

## Recursos (catalogo + acciones permitidas)

Ruta: `app/dashboard/resources.php`

- Proposito:
  - administrar `cat_recurso`
  - administrar `recurso_accion` (acciones validas por recurso)
- Recurso/accion: `configuracion:READ` (+ CREATE/UPDATE/DELETE)
- Accion especial:
  - guardar acciones por recurso (POST `form_action=actions`)

## Acciones (catalogo)

Ruta: `app/dashboard/actions.php`

- Proposito: administrar `cat_accion`.
- Recurso/accion: `configuracion:READ` (+ CREATE/UPDATE/DELETE)
- Regla:
  - no eliminar si la accion esta referenciada por `recurso_accion` o `permisos`

## Permisos (matriz)

Ruta: `app/dashboard/permissions.php`

- Proposito: editar `permisos` globales.
- Recurso/accion:
  - ver: `configuracion:READ`
  - guardar: `configuracion:UPDATE`
- Restriccion:
  - solo se permiten combinaciones existentes en `recurso_accion`
- Auditoria:
  - `UPDATE_PERMISSIONS`

## Sesiones (operacion)

Ruta: `app/dashboard/sessions.php`

- Proposito: listar sesiones y revocar.
- Recurso/accion:
  - ver: `reportes:READ`
  - revocar: `reportes:UPDATE`
- Auditoria:
  - `REVOKE_SESSION`

## Auditoria (bitacora)

Ruta: `app/dashboard/audit.php`

- Proposito: ver eventos de auditoria con filtros basicos.
- Recurso/accion: `reportes:READ`

