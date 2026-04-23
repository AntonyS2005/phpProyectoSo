# Infraestructura (Galera Cluster)

## Topologia (Docker)

Contenedores:

- `php-app` (Apache + PHP 8.2)
- `db-node-1` (MariaDB Galera)
- `db-node-2` (MariaDB Galera)
- `db-node-3` (MariaDB Galera)

Red: `shared-net`

Puertos publicados:

- App: `8080`
- DB nodo 1: `3306`
- DB nodo 2: `3307`
- DB nodo 3: `3308`

## Como arranca el cluster

Script:

- `scripts/setup-galera-cluster.ps1`

Hace:

- `docker compose down --remove-orphans`
- `docker compose up -d --build`
- inicializa `schema.sql` y `seed.sql` en `db-node-1` si el cluster esta vacio
- valida `wsrep_cluster_size=3`

## Bootstrap seguro

Riesgo clasico:

- si el nodo 1 siempre arranca en modo bootstrap, puede aislarse y partir el cluster

Solucion aplicada:

- `infra/galera/node1-entrypoint.sh`
  - bootstrap solo en primer arranque (o cuando no hay peers vivos)
  - si detecta peers vivos en `db-node-2`/`db-node-3`, se une sin bootstrap

## Failover para la app

Archivo:

- `app/includes/db.php`

Variable:

- `DB_HOSTS=db-node-1:3306,db-node-2:3306,db-node-3:3306`

La app intenta conectarse en orden. Si falla un host, prueba el siguiente.

## Escenarios operativos

### Caida de 1 nodo

- El cluster conserva quorum (2/3).
- La app puede seguir conectando a nodos disponibles.
- Al volver el nodo, Galera lo resincroniza (SST/IST).

### Caida total del cluster

- Requiere decidir desde que nodo se bootstrappea el quorum.
- En entornos reales se resuelve con runbook + automatizacion controlada (evitar split-brain).

