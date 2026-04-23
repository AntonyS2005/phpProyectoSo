# Runbook de Incidentes

## 1) La app no carga (`8080`)

- `docker compose ps`
- `docker logs php-app --tail 100`
- probar: `curl http://localhost:8080/login.php`

## 2) Error "No se pudo conectar a la base de datos"

- revisar `DB_HOSTS` en `.env.docker`
- revisar nodos:
  - `docker compose ps`
  - `docker exec -e MYSQL_PWD=contra123 db-node-1 mariadb -uroot -e "SHOW STATUS LIKE 'wsrep_cluster_size';"`
- si cluster no tiene quorum, levantar al menos 2 nodos.

## 3) Cluster partido (dev)

En desarrollo, el caso mas comun es volumen viejo + reinicios desordenados.

- reset limpio:
  - `docker compose down -v --remove-orphans`
  - `powershell -ExecutionPolicy Bypass -File .\scripts\setup-galera-cluster.ps1`

## 4) Acceso denegado inesperado

- revisar si el rol perdio permisos en `permissions.php`.
- revisar auditoria (`ACCESS_DENIED`) y ruta.
- si se edito `recurso_accion`, verificar que el permiso aun es una combinacion valida.

## 5) Credenciales demo no funcionan

- re-aplicar seed:
  - `Get-Content seed.sql | docker exec -i -e MYSQL_PWD=contra123 db-node-1 mariadb -uroot userDB`

