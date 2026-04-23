# Operacion y Runbook

## Arranque (cluster)

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\setup-galera-cluster.ps1
```

URL:

- `http://localhost:8080/login.php`

Credenciales demo:

- `admin` / `Test1234!`

## Verificacion rapida

1. Estado de contenedores:
   - `docker compose ps`
2. Salud del cluster:
   - `docker exec -e MYSQL_PWD=contra123 db-node-1 mariadb -uroot -e "SHOW STATUS LIKE 'wsrep_cluster_size';"`
3. App disponible:
   - `curl http://localhost:8080/login.php`

## Prueba de tolerancia a fallos (demo)

1. Apagar un nodo:
   - `docker stop db-node-1`
2. Login debe seguir funcionando (app se conecta a otro host).
3. Encender nodo:
   - `docker start db-node-1`
4. Verificar `wsrep_cluster_size` vuelve a 3.

## Troubleshooting

### La app no conecta a la DB

- revisa `docker compose ps`
- revisa `DB_HOSTS` en `.env.docker`
- revisa que al menos 2 nodos DB esten healthy

### El cluster queda partido (1 + 2)

En desarrollo, lo mas comun es estado de volumen antiguo + reinicios:

- hacer arranque limpio:
  - `docker compose down -v --remove-orphans`
  - `powershell -ExecutionPolicy Bypass -File .\scripts\setup-galera-cluster.ps1`

## Backups (educativo)

Ejemplo (dump logico desde un nodo sano):

```powershell
docker exec -e MYSQL_PWD=contra123 db-node-2 mariadb-dump -uroot --databases userDB > backup.sql
```

