param(
    [string]$ProjectRoot = (Resolve-Path (Join-Path $PSScriptRoot "..")).Path,
    [string]$PrimaryContainer = "mysql-db",
    [string]$ReplicaContainer = "mysql-replica-db",
    [string]$RootPassword = "contra123",
    [string]$DatabaseName = "userDB",
    [string]$ReplicationUser = "replicator",
    [string]$ReplicationPassword = "replica123!"
)

$ErrorActionPreference = "Stop"

function Invoke-Compose {
    param([string]$Command)
    Push-Location $ProjectRoot
    try {
        Invoke-Expression $Command
    } finally {
        Pop-Location
    }
}

function Wait-ForHealthyContainer {
    param(
        [string]$ContainerName,
        [int]$TimeoutSeconds = 120
    )

    $deadline = (Get-Date).AddSeconds($TimeoutSeconds)

    while ((Get-Date) -lt $deadline) {
        $status = docker inspect --format='{{if .State.Health}}{{.State.Health.Status}}{{else}}{{.State.Status}}{{end}}' $ContainerName 2>$null
        if ($status -eq "healthy" -or $status -eq "running") {
            Write-Host "[$ContainerName] listo con estado $status"
            return
        }
        Start-Sleep -Seconds 2
    }

    throw "El contenedor $ContainerName no alcanzo estado healthy/running a tiempo."
}

function Invoke-MySql {
    param(
        [string]$ContainerName,
        [string]$Sql,
        [bool]$UseDatabase = $true
    )

    $args = @("exec", "-e", "MYSQL_PWD=$RootPassword", $ContainerName, "mysql", "-uroot")
    if ($UseDatabase) {
        $args += @("-D", $DatabaseName)
    }
    $args += @("-e", $Sql)
    & docker @args
}

function Get-TableCount {
    param([string]$ContainerName)

    $result = docker exec -e "MYSQL_PWD=$RootPassword" $ContainerName mysql -N -B -uroot -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '$DatabaseName';"
    return [int]($result | Select-Object -First 1)
}

function Get-ReplicationHealthScore {
    param([string]$ContainerName)

    $status = docker exec -e "MYSQL_PWD=$RootPassword" $ContainerName mysql -N -B -uroot -e "SHOW REPLICA STATUS" 2>$null
    if ([string]::IsNullOrWhiteSpace($status)) {
        return 1
    }

    $columns = $status -split "`t"
    if ($columns.Length -lt 39) {
        return 0
    }

    $ioRunning = $columns[10]
    $sqlRunning = $columns[11]
    $lastIoErrno = $columns[34]
    $lastSqlErrno = $columns[36]

    if ($ioRunning -eq "Yes" -and $sqlRunning -eq "Yes" -and $lastIoErrno -eq "0" -and $lastSqlErrno -eq "0") {
        return 3
    }

    if ($sqlRunning -eq "Yes" -or $ioRunning -eq "Yes") {
        return 2
    }

    return 0
}

function Get-ServiceHostName {
    param([string]$ContainerName)

    if ($ContainerName -eq $PrimaryContainer) {
        return "mysql"
    }
    if ($ContainerName -eq $ReplicaContainer) {
        return "mysql-replica"
    }
    throw "No se reconoce el contenedor $ContainerName para resolver su hostname Docker."
}

Write-Host "Levantando infraestructura Docker..."
Invoke-Compose "docker compose up -d --build"

Wait-ForHealthyContainer -ContainerName $PrimaryContainer
Wait-ForHealthyContainer -ContainerName $ReplicaContainer

Write-Host "Creando usuario de replicacion en ambos nodos..."
$replUserSql = @"
CREATE USER IF NOT EXISTS '$ReplicationUser'@'%' IDENTIFIED BY '$ReplicationPassword';
GRANT REPLICATION SLAVE, REPLICATION CLIENT ON *.* TO '$ReplicationUser'@'%';
FLUSH PRIVILEGES;
"@
Invoke-MySql -ContainerName $PrimaryContainer -Sql $replUserSql
Invoke-MySql -ContainerName $ReplicaContainer -Sql $replUserSql -UseDatabase:$false

$primaryTableCount = Get-TableCount -ContainerName $PrimaryContainer
$replicaTableCount = Get-TableCount -ContainerName $ReplicaContainer
$primaryHealth = Get-ReplicationHealthScore -ContainerName $PrimaryContainer
$replicaHealth = Get-ReplicationHealthScore -ContainerName $ReplicaContainer

$sourceContainer = $PrimaryContainer
$targetContainer = $ReplicaContainer

if ($replicaTableCount -gt $primaryTableCount) {
    $sourceContainer = $ReplicaContainer
    $targetContainer = $PrimaryContainer
} elseif ($replicaTableCount -eq $primaryTableCount -and $replicaHealth -gt $primaryHealth) {
    $sourceContainer = $ReplicaContainer
    $targetContainer = $PrimaryContainer
}

$sourceHost = Get-ServiceHostName -ContainerName $sourceContainer
$targetHost = Get-ServiceHostName -ContainerName $targetContainer

Write-Host "Nodo fuente para resync: $sourceContainer ($sourceHost) con $([Math]::Max($primaryTableCount, $replicaTableCount)) tablas y score de salud $([Math]::Max($primaryHealth, $replicaHealth))."
Write-Host "Clonando $sourceContainer hacia $targetContainer..."
docker exec -e "MYSQL_PWD=$RootPassword" $targetContainer mysql -uroot -e "STOP REPLICA; RESET REPLICA ALL; RESET MASTER; DROP DATABASE IF EXISTS $DatabaseName; CREATE DATABASE $DatabaseName;"
docker exec -e "MYSQL_PWD=$RootPassword" $sourceContainer sh -lc "exec mysqldump -uroot --databases $DatabaseName --single-transaction --triggers --routines --events --set-gtid-purged=ON --hex-blob" `
    | docker exec -i -e "MYSQL_PWD=$RootPassword" $targetContainer mysql -uroot

Write-Host "Configurando replica -> principal..."
$replicaSql = @"
STOP REPLICA;
RESET REPLICA ALL;
CHANGE REPLICATION SOURCE TO
  SOURCE_HOST='mysql',
  SOURCE_PORT=3306,
  SOURCE_USER='$ReplicationUser',
  SOURCE_PASSWORD='$ReplicationPassword',
  SOURCE_AUTO_POSITION=1,
  GET_SOURCE_PUBLIC_KEY=1;
START REPLICA;
"@
Invoke-MySql -ContainerName $ReplicaContainer -Sql $replicaSql

Write-Host "Configurando principal -> replica para failback automatico..."
$primarySql = @"
STOP REPLICA;
RESET REPLICA ALL;
CHANGE REPLICATION SOURCE TO
  SOURCE_HOST='mysql-replica',
  SOURCE_PORT=3306,
  SOURCE_USER='$ReplicationUser',
  SOURCE_PASSWORD='$ReplicationPassword',
  SOURCE_AUTO_POSITION=1,
  GET_SOURCE_PUBLIC_KEY=1;
START REPLICA;
"@
Invoke-MySql -ContainerName $PrimaryContainer -Sql $primarySql

Write-Host "Estado actual de replicacion en principal:"
docker exec -e "MYSQL_PWD=$RootPassword" $PrimaryContainer mysql -uroot -e "SHOW REPLICA STATUS"

Write-Host "Estado actual de replicacion en replica:"
docker exec -e "MYSQL_PWD=$RootPassword" $ReplicaContainer mysql -uroot -e "SHOW REPLICA STATUS"

Write-Host "Replicacion bidireccional inicializada."
