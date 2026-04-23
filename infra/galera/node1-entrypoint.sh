#!/bin/bash
set -euo pipefail

BOOTSTRAP_MARKER="/bitnami/mariadb/.bootstrap/done"
PEERS=("db-node-2" "db-node-3")

peer_is_reachable() {
  local host="$1"
  timeout 1 bash -c ">/dev/tcp/${host}/3306" >/dev/null 2>&1
}

has_reachable_peer() {
  local host
  for host in "${PEERS[@]}"; do
    if peer_is_reachable "$host"; then
      return 0
    fi
  done
  return 1
}

if [[ ! -f "$BOOTSTRAP_MARKER" ]]; then
  export MARIADB_GALERA_CLUSTER_BOOTSTRAP="yes"
  export MARIADB_GALERA_FORCE_SAFETOBOOTSTRAP="yes"
elif has_reachable_peer; then
  export MARIADB_GALERA_CLUSTER_BOOTSTRAP="no"
  export MARIADB_GALERA_FORCE_SAFETOBOOTSTRAP="no"
else
  export MARIADB_GALERA_CLUSTER_BOOTSTRAP="yes"
  export MARIADB_GALERA_FORCE_SAFETOBOOTSTRAP="yes"
fi

exec /opt/bitnami/scripts/mariadb-galera/entrypoint.sh /opt/bitnami/scripts/mariadb-galera/run.sh
