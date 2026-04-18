<?php
// ── Conexión a la base de datos ──────────────────────────────
class DB {
    private static ?PDO $instance = null;

    public static function get(): PDO {
        if (self::$instance === null) {
            $host = getenv('DB_HOST') ?: 'mysql';
            $port = getenv('DB_PORT') ?: '3306';
            $name = getenv('DB_NAME') ?: 'usuariosdb';
            $user = getenv('DB_USER') ?: 'root';
            $pass = getenv('DB_PASS') ?: '';

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
