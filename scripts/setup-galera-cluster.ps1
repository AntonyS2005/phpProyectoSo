param(
    [string]$ProjectRoot = (Resolve-Path (Join-Path $PSScriptRoot "..")).Path,
    [string]$RootPassword = "contra123",
    [string]$DatabaseName = "userDB"
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

function Wait-ForHealthy {
    param(
        [string]$ContainerName,
        [int]$TimeoutSeconds = 240
    )

    $deadline = (Get-Date).AddSeconds($TimeoutSeconds)
    while ((Get-Date) -lt $deadline) {
        $status = docker inspect --format='{{if .State.Health}}{{.State.Health.Status}}{{else}}{{.State.Status}}{{end}}' $ContainerName 2>$null
        if ($status -eq "healthy" -or $status -eq "running") {
            Write-Host "[$ContainerName] healthy"
            return
        }
        Start-Sleep -Seconds 3
    }

    throw "El contenedor $ContainerName no quedo healthy a tiempo."
}

function Get-TableCount {
    param([string]$ContainerName)

    $result = docker exec -e "MYSQL_PWD=$RootPassword" $ContainerName mysql -N -B -uroot -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DatabaseName';"
    return [int]($result | Select-Object -First 1)
}

function Invoke-SqlFile {
    param(
        [string]$ContainerName,
        [string]$FilePath
    )

    Get-Content $FilePath | docker exec -i -e "MYSQL_PWD=$RootPassword" $ContainerName mysql -uroot $DatabaseName | Out-Null
}

Write-Host "Recreando stack Galera..."
Invoke-Compose "docker compose down --remove-orphans"
Invoke-Compose "docker compose up -d --build"

Wait-ForHealthy "db-node-1"
Wait-ForHealthy "db-node-2"
Wait-ForHealthy "db-node-3"
Wait-ForHealthy "php-app"

$tableCount = Get-TableCount "db-node-1"
if ($tableCount -eq 0) {
    Write-Host "Inicializando schema y seed en db-node-1..."
    Invoke-SqlFile "db-node-1" (Join-Path $ProjectRoot "schema.sql")
    Invoke-SqlFile "db-node-1" (Join-Path $ProjectRoot "seed.sql")
    Start-Sleep -Seconds 8
} else {
    Write-Host "db-node-1 ya tenia datos; se conserva estado existente."
}

$node1Count = Get-TableCount "db-node-1"
$node2Count = Get-TableCount "db-node-2"
$node3Count = Get-TableCount "db-node-3"

Write-Host "Tablas por nodo: node1=$node1Count node2=$node2Count node3=$node3Count"

Write-Host "Estado wsrep_cluster_size:"
docker exec -e "MYSQL_PWD=$RootPassword" db-node-1 mysql -N -B -uroot -e "SHOW STATUS LIKE 'wsrep_cluster_size';"
docker exec -e "MYSQL_PWD=$RootPassword" db-node-2 mysql -N -B -uroot -e "SHOW STATUS LIKE 'wsrep_cluster_size';"
docker exec -e "MYSQL_PWD=$RootPassword" db-node-3 mysql -N -B -uroot -e "SHOW STATUS LIKE 'wsrep_cluster_size';"

Write-Host "Cluster Galera listo."
