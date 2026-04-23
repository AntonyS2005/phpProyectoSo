<?php
// ── Conexión a la base de datos ──────────────────────────────
class DB {
    private static ?PDO $instance = null;
    private static bool $envLoaded = false;
    private static ?array $connectedHost = null;

    private static function loadEnvFile(): void {
        if (self::$envLoaded) {
            return;
        }

        self::$envLoaded = true;

        $root = dirname(__DIR__, 2);
        $envFiles = [
            $root . '/.env',
            $root . '/.env.docker',
        ];

        foreach ($envFiles as $envFile) {
            if (!is_readable($envFile)) {
                continue;
            }

            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($lines === false) {
                continue;
            }

            foreach ($lines as $line) {
                $line = trim($line);

                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                    continue;
                }

                $commentPos = strpos($line, ' #');
                if ($commentPos !== false) {
                    $line = trim(substr($line, 0, $commentPos));
                }

                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                if ($key === '') {
                    continue;
                }

                $firstChar = $value[0] ?? '';
                $lastChar = $value !== '' ? substr($value, -1) : '';
                if (($firstChar === '"' || $firstChar === "'") && $lastChar === $firstChar) {
                    $value = substr($value, 1, -1);
                }

                if (getenv($key) === false || getenv($key) === '') {
                    putenv($key . '=' . $value);
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                }
            }

            break;
        }
    }

    private static function env(string $key, string $default): string {
        $value = getenv($key);
        if ($value === false || $value === '') {
            return $default;
        }

        return $value;
    }

    private static function normalizeDbName(string $name): string {
        return strcasecmp($name, 'mysql') === 0 ? 'userDB' : $name;
    }

    private static function connectionTargets(): array {
        $hosts = trim(self::env('DB_HOSTS', ''));
        if ($hosts !== '') {
            $targets = [];
            foreach (explode(',', $hosts) as $index => $entry) {
                $entry = trim($entry);
                if ($entry === '') {
                    continue;
                }

                $parts = explode(':', $entry, 2);
                $host = trim($parts[0] ?? '');
                $port = trim($parts[1] ?? '') ?: self::env('DB_PORT', '3306');

                if ($host === '') {
                    continue;
                }

                $targets[] = [
                    'host' => $host,
                    'port' => $port,
                    'label' => 'cluster-node-' . ($index + 1),
                ];
            }

            if ($targets) {
                return $targets;
            }
        }

        $primaryHost = self::env('DB_HOST_PRIMARY', self::env('DB_HOST', '127.0.0.1'));
        $primaryPort = self::env('DB_PORT_PRIMARY', self::env('DB_PORT', '3306'));
        $secondaryHost = self::env('DB_HOST_SECONDARY', '');
        $secondaryPort = self::env('DB_PORT_SECONDARY', self::env('DB_PORT', '3306'));

        $targets = [
            ['host' => $primaryHost, 'port' => $primaryPort, 'label' => 'primary'],
        ];

        if ($secondaryHost !== '' && !($secondaryHost === $primaryHost && $secondaryPort === $primaryPort)) {
            $targets[] = ['host' => $secondaryHost, 'port' => $secondaryPort, 'label' => 'secondary'];
        }

        return $targets;
    }

    public static function connectedHost(): ?array {
        return self::$connectedHost;
    }

    public static function get(): PDO {
        self::loadEnvFile();

        if (self::$instance === null) {
            $name = self::normalizeDbName(self::env('DB_NAME', 'userDB'));
            $user = self::env('DB_USER', 'root');
            $pass = self::env('DB_PASS', '');
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_TIMEOUT            => 3,
            ];

            foreach (self::connectionTargets() as $target) {
                $dsn = "mysql:host={$target['host']};port={$target['port']};dbname={$name};charset=utf8mb4";

                try {
                    self::$instance = new PDO($dsn, $user, $pass, $options);
                    self::$connectedHost = $target;
                    break;
                } catch (PDOException) {
                    self::$instance = null;
                }
            }

            if (self::$instance === null) {
                die(json_encode(['error' => 'No se pudo conectar a la base de datos.']));
            }
        }
        return self::$instance;
    }
}
