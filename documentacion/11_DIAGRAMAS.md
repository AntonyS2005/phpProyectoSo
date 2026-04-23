# Diagramas (ASCII)

## ERD (alto nivel)

```
 usuarios (1) ---- (1) usuarios_perfil
     |
     +---- (N) sesiones (1) ---- (N) refresh_tokens (1) ---- (N) access_tokens
     |
     +---- (N) auditoria

 roles (1) ---- (N) permisos (N) ---- (1) cat_recurso
                   |
                   +---- (1) cat_accion

 cat_recurso (1) ---- (N) recurso_accion (N) ---- (1) cat_accion
```

## Flujo Auth (resumen)

```
Login POST
  -> valida usuario + bcrypt
  -> crea sesiones + refresh_tokens + access_tokens
  -> set cookies (HttpOnly)

Request protegido
  -> require_auth()
     -> si no session: intenta restore por refresh
     -> valida access token; si expiro: refresh => nuevo access
     -> refresca snapshot user/permissions
  -> require_permission()
     -> si no permiso => denied_redirect_path()
```

## Flujo Permisos

```
resources.php (editar recurso)
  -> define acciones validas (recurso_accion)
  -> limpia permisos invalidos

permissions.php
  -> UI por rol/recurso
  -> solo render combos permitidos por recurso_accion
  -> guarda matriz (permisos)
```

## Infra (Galera)

```
         +---------------------+
         |       php-app        |
         | tries DB_HOSTS list  |
         +----------+----------+
                    |
     +--------------+--------------+
     |                             |
 +---v----+     +---v----+     +---v----+
 | node-1 | <-> | node-2 | <-> | node-3 |
 | Galera | <-> | Galera | <-> | Galera |
 +--------+     +--------+     +--------+

quorum: 2/3
```

