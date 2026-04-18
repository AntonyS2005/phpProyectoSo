<?php
// Cargar configuración de .env
require_once __DIR__ . '/config.php';

// ── Conexión a la base de datos ──────────────────────────────
class DB {
    private static ?PDO $instance = null;

    public static function get(): PDO {
        if (self::$instance === null) {
            // Defaults para desarrollo local (php -S + MySQL en el host)
            $host = getenv('DB_HOST') ?: '127.0.0.1';
            $port = getenv('DB_PORT') ?: '3306';
            $name = getenv('DB_NAME') ?: 'MySQL';
            $user = getenv('DB_USER') ?: 'root';
            $pass = getenv('DB_PASS') ?: 'contra123';

            $dsn = "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4";

            try {
                self::$instance = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                // No exponer detalles en producción
                die(json_encode(['error' => 'No se pudo conectar a la base de datos.']));
            }
        }
        return self::$instance;
    }
}
