<?php
require_once __DIR__ . '/db.php';

// ── Iniciar sesión segura ─────────────────────────────────────
function session_init(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 3600,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_start();
    }
}

// ── Autenticar usuario ────────────────────────────────────────
function login(string $username, string $password): array|false {
    $pdo = DB::get();

    $stmt = $pdo->prepare("
        SELECT u.id_usuario, u.username, u.email, u.password_hash,
               u.id_status, s.nombre AS status,
               u.id_rol,    r.nombre AS rol
        FROM usuarios u
        JOIN cat_status s ON s.id_status = u.id_status
        JOIN roles      r ON r.id_rol    = u.id_rol
        WHERE u.username = ? OR u.email = ?
        LIMIT 1
    ");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();

    if (!$user)                                  return false;
    if ($user['status'] !== 'activo')            return false;
    if (!password_verify($password, $user['password_hash'])) return false;

    // Actualizar último acceso
    $pdo->prepare("UPDATE usuarios SET fecha_ultimo_acceso = NOW() WHERE id_usuario = ?")
        ->execute([$user['id_usuario']]);

    // Registrar auditoría
    audit($user['id_usuario'], 'LOGIN', 'Inicio de sesión exitoso');

    return $user;
}

// ── Destruir sesión ───────────────────────────────────────────
function logout(): void {
    session_init();
    if (isset($_SESSION['user'])) {
        audit($_SESSION['user']['id_usuario'], 'LOGOUT', 'Cierre de sesión');
    }
    $_SESSION = [];
    session_destroy();
}

// ── Verificar que hay sesión activa ──────────────────────────
function require_auth(): void {
    session_init();
    if (empty($_SESSION['user'])) {
        header('Location: /login.php');
        exit;
    }
}

// ── Verificar rol específico ──────────────────────────────────
function require_role(string ...$roles): void {
    require_auth();
    $userRol = strtolower($_SESSION['user']['rol']);
    foreach ($roles as $r) {
        if ($userRol === strtolower($r)) return;
    }
    // Sin permiso → redirigir al dashboard propio
    header('Location: /dashboard/' . $userRol . '.php');
    exit;
}

// ── Usuario actual ────────────────────────────────────────────
function current_user(): array {
    return $_SESSION['user'] ?? [];
}

// ── Registrar en auditoría ───────────────────────────────────
function audit(int $id_usuario, string $accion, string $detalle = ''): void {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        DB::get()->prepare("
            INSERT INTO auditoria (id_usuario, accion, detalle, ip_address)
            VALUES (?, ?, ?, ?)
        ")->execute([$id_usuario, $accion, $detalle, $ip]);
    } catch (Throwable) { /* auditoría no debe cortar el flujo */ }
}
