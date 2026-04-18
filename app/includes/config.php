<?php
// ── Cargar variables de entorno desde .env ──────────────────────
function load_env(string $path): void {
    if (!file_exists($path)) {
        return; // Si no existe .env, usar valores por defecto
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Saltar comentarios
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Parsear líneas en formato KEY=VALUE
        if (strpos($line, '=') !== false) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // No sobrescribir si ya está definida en el entorno
            if (!getenv($key)) {
                putenv("$key=$value");
            }
        }
    }
}

// Cargar .env desde la raíz del proyecto (suben 3 niveles: includes -> app -> raíz)
$env_path = dirname(__DIR__, 2) . '/.env';
load_env($env_path);


