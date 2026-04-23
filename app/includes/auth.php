<?php
require_once __DIR__ . '/db.php';

const ACCESS_TOKEN_COOKIE = 'access_token';
const REFRESH_TOKEN_COOKIE = 'refresh_token';
const ACCESS_TOKEN_TTL = 900;
const REFRESH_TOKEN_TTL = 86400;
const SESSION_TTL = 86400;

function session_init(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => SESSION_TTL,
            'httponly' => true,
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function login(string $username, string $password): array|false {
    $pdo = DB::get();
    $stmt = $pdo->prepare("
        SELECT u.id_usuario, u.username, u.email, u.password_hash, u.email_verificado,
               u.id_status, s.nombre AS status,
               u.id_rol, r.nombre AS rol
        FROM usuarios u
        JOIN cat_status s ON s.id_status = u.id_status
        JOIN roles r ON r.id_rol = u.id_rol
        WHERE u.username = ? OR u.email = ?
        LIMIT 1
    ");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();

    if (!$user || $user['status'] !== 'activo') {
        return false;
    }

    if (!password_verify($password, $user['password_hash'])) {
        return false;
    }

    $pdo->prepare("UPDATE usuarios SET fecha_ultimo_acceso = NOW() WHERE id_usuario = ?")
        ->execute([$user['id_usuario']]);

    audit((int)$user['id_usuario'], 'LOGIN', 'Inicio de sesion exitoso');
    return hydrate_user_session($user);
}

function hydrate_user_session(array $user): array {
    return [
        'id_usuario' => (int)$user['id_usuario'],
        'username' => $user['username'],
        'email' => $user['email'],
        'rol' => $user['rol'],
        'id_rol' => (int)$user['id_rol'],
        'email_verificado' => (int)($user['email_verificado'] ?? 0),
    ];
}

function start_authenticated_session(array $user): void {
    session_init();
    $_SESSION['user'] = $user;
    $_SESSION['permissions'] = load_permissions_for_role((int)$user['id_rol']);
    issue_auth_tokens((int)$user['id_usuario']);
}

function logout(bool $redirect = false): void {
    session_init();
    $idUsuario = (int)($_SESSION['user']['id_usuario'] ?? 0);
    if ($idUsuario > 0) {
        audit($idUsuario, 'LOGOUT', 'Cierre de sesion');
    }
    revoke_current_tokens($idUsuario, 'Logout manual');
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'] ?? '/',
            $params['domain'] ?? '',
            (bool)($params['secure'] ?? false),
            (bool)($params['httponly'] ?? true)
        );
    }
    session_destroy();

    if ($redirect) {
        header('Location: /login.php');
        exit;
    }
}

function require_auth(): void {
    session_init();
    if (empty($_SESSION['user']) && !restore_session_from_refresh()) {
        force_reauth();
    }

    $idUsuario = (int)($_SESSION['user']['id_usuario'] ?? 0);
    if ($idUsuario <= 0) {
        force_reauth();
    }

    if (!has_valid_access_token($idUsuario) && !refresh_access_token_for_user($idUsuario)) {
        force_reauth();
    }

    refresh_current_user_snapshot();
    touch_current_session();
}

function current_user(): array {
    return $_SESSION['user'] ?? [];
}

function current_permissions(): array {
    session_init();
    if (!empty($_SESSION['user']['id_rol'])) {
        $_SESSION['permissions'] = load_permissions_for_role((int)$_SESSION['user']['id_rol']);
    }
    return $_SESSION['permissions'] ?? [];
}

function has_permission(string $resource, string $action): bool {
    $permissions = current_permissions();
    if (!$permissions) {
        return false;
    }
    $key = strtolower($resource) . ':' . strtoupper($action);
    return in_array($key, $permissions, true);
}

function require_permission(string $resource, string $action): void {
    require_auth();
    if (!has_permission($resource, $action)) {
        audit((int)(current_user()['id_usuario'] ?? 0), 'ACCESS_DENIED', "Intento sin permiso a {$resource}:{$action}");
        header('Location: ' . denied_redirect_path());
        exit;
    }
}

function require_role(string ...$roles): void {
    require_auth();
    $role = strtolower((string)(current_user()['rol'] ?? ''));
    foreach ($roles as $allowed) {
        if ($role === strtolower($allowed)) {
            return;
        }
    }
    header('Location: ' . denied_redirect_path());
    exit;
}

function load_permissions_for_role(int $roleId): array {
    $stmt = DB::get()->prepare("
        SELECT CONCAT(LOWER(cr.nombre), ':', UPPER(ca.nombre)) AS permission_key
        FROM permisos p
        JOIN cat_recurso cr ON cr.id_recurso = p.id_recurso
        JOIN cat_accion ca ON ca.id_accion = p.id_accion
        WHERE p.id_rol = ?
    ");
    $stmt->execute([$roleId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
}

function fetch_user_auth_snapshot(int $userId): array|false {
    $stmt = DB::get()->prepare("
        SELECT u.id_usuario, u.username, u.email, u.email_verificado,
               u.id_rol, r.nombre AS rol, s.nombre AS status
        FROM usuarios u
        JOIN roles r ON r.id_rol = u.id_rol
        JOIN cat_status s ON s.id_status = u.id_status
        WHERE u.id_usuario = ?
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

function refresh_current_user_snapshot(): void {
    $userId = (int)($_SESSION['user']['id_usuario'] ?? 0);
    if ($userId <= 0) {
        force_reauth();
    }

    $user = fetch_user_auth_snapshot($userId);
    if (!$user || $user['status'] !== 'activo') {
        force_reauth();
    }

    $_SESSION['user'] = hydrate_user_session($user);
    $_SESSION['permissions'] = load_permissions_for_role((int)$user['id_rol']);
}

function audit(int $idUsuario, string $action, string $detail = ''): void {
    try {
        DB::get()->prepare("
            INSERT INTO auditoria (id_usuario, accion, detalle, ip_address)
            VALUES (?, ?, ?, ?)
        ")->execute([
            $idUsuario > 0 ? $idUsuario : null,
            $action,
            $detail,
            client_ip(),
        ]);
    } catch (Throwable) {
    }
}

function denied_redirect_path(): string {
    $candidates = [
        ['href' => '/dashboard/overview.php', 'resource' => 'reportes', 'action' => 'READ'],
        ['href' => '/dashboard/users.php', 'resource' => 'usuarios', 'action' => 'READ'],
        ['href' => '/dashboard/profile.php', 'resource' => 'usuarios', 'action' => 'READ'],
        ['href' => '/dashboard/resources.php', 'resource' => 'configuracion', 'action' => 'READ'],
        ['href' => '/dashboard/actions.php', 'resource' => 'configuracion', 'action' => 'READ'],
        ['href' => '/dashboard/roles.php', 'resource' => 'configuracion', 'action' => 'READ'],
        ['href' => '/dashboard/statuses.php', 'resource' => 'configuracion', 'action' => 'READ'],
        ['href' => '/dashboard/sessions.php', 'resource' => 'reportes', 'action' => 'READ'],
        ['href' => '/dashboard/audit.php', 'resource' => 'reportes', 'action' => 'READ'],
    ];

    foreach ($candidates as $candidate) {
        if (has_permission($candidate['resource'], $candidate['action'])) {
            return $candidate['href'] . '?denied=1';
        }
    }

    return '/login.php?expired=1';
}

function force_reauth(): void {
    revoke_current_tokens((int)($_SESSION['user']['id_usuario'] ?? 0), 'Sesion expirada');
    $_SESSION = [];
    header('Location: /login.php?expired=1');
    exit;
}

function client_ip(): string {
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function client_user_agent(): string {
    return substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 255);
}

function token_hash(string $rawToken): string {
    return hash('sha256', $rawToken);
}

function set_token_cookie(string $name, string $value, int $ttl): void {
    $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    setcookie($name, $value, [
        'expires' => time() + $ttl,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function clear_token_cookie(string $name): void {
    setcookie($name, '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function issue_auth_tokens(int $idUsuario): void {
    $pdo = DB::get();
    $refreshToken = bin2hex(random_bytes(32));
    $accessToken = bin2hex(random_bytes(32));
    $refreshHash = token_hash($refreshToken);
    $accessHash = token_hash($accessToken);
    $ip = client_ip();
    $userAgent = client_user_agent();

    try {
        $pdo->beginTransaction();

        $pdo->prepare("
            UPDATE sesiones
            SET activa = 0, fecha_revocacion = NOW(), motivo_revocacion = 'Nueva sesion'
            WHERE id_usuario = ? AND activa = 1
        ")->execute([$idUsuario]);

        $pdo->prepare("
            INSERT INTO sesiones (id_usuario, token_hash, ip_address, user_agent, fecha_expiracion)
            VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))
        ")->execute([$idUsuario, $refreshHash, $ip, $userAgent]);
        $sessionId = (int)$pdo->lastInsertId();

        $pdo->prepare("
            UPDATE refresh_tokens
            SET activa = 0, fecha_revocacion = NOW()
            WHERE id_usuario = ? AND activa = 1
        ")->execute([$idUsuario]);

        $pdo->prepare("
            INSERT INTO refresh_tokens (id_sesion, id_usuario, token_hash, ip_address, user_agent, fecha_expiracion)
            VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))
        ")->execute([$sessionId, $idUsuario, $refreshHash, $ip, $userAgent]);
        $refreshId = (int)$pdo->lastInsertId();

        $pdo->prepare("
            INSERT INTO access_tokens (id_refresh_token, id_usuario, token_hash, ip_address, fecha_expiracion)
            VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE))
        ")->execute([$refreshId, $idUsuario, $accessHash, $ip]);

        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }

    $_SESSION['id_sesion'] = $sessionId;
    set_token_cookie(REFRESH_TOKEN_COOKIE, $refreshToken, REFRESH_TOKEN_TTL);
    set_token_cookie(ACCESS_TOKEN_COOKIE, $accessToken, ACCESS_TOKEN_TTL);
}

function get_valid_refresh_token(?int $idUsuario = null): array|false {
    $token = $_COOKIE[REFRESH_TOKEN_COOKIE] ?? '';
    if ($token === '') {
        return false;
    }

    $sql = "
        SELECT rt.id_refresh_token, rt.id_sesion, rt.id_usuario, s.activa AS sesion_activa
        FROM refresh_tokens rt
        JOIN sesiones s ON s.id_sesion = rt.id_sesion
        WHERE rt.token_hash = ?
          AND rt.activa = 1
          AND rt.fecha_expiracion > NOW()
          AND s.activa = 1
          AND s.fecha_expiracion > NOW()
    ";
    $params = [token_hash($token)];

    if ($idUsuario !== null) {
        $sql .= " AND rt.id_usuario = ?";
        $params[] = $idUsuario;
    }

    $sql .= " LIMIT 1";
    $stmt = DB::get()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch();
}

function has_valid_access_token(int $idUsuario): bool {
    $token = $_COOKIE[ACCESS_TOKEN_COOKIE] ?? '';
    if ($token === '') {
        return false;
    }

    $stmt = DB::get()->prepare("
        SELECT 1
        FROM access_tokens at
        JOIN refresh_tokens rt ON rt.id_refresh_token = at.id_refresh_token
        JOIN sesiones s ON s.id_sesion = rt.id_sesion
        WHERE at.id_usuario = ?
          AND at.token_hash = ?
          AND at.fecha_expiracion > NOW()
          AND rt.activa = 1
          AND s.activa = 1
        LIMIT 1
    ");
    $stmt->execute([$idUsuario, token_hash($token)]);
    return (bool)$stmt->fetchColumn();
}

function refresh_access_token_for_user(int $idUsuario): bool {
    $refresh = get_valid_refresh_token($idUsuario);
    if (!$refresh) {
        return false;
    }

    $newAccess = bin2hex(random_bytes(32));
    DB::get()->prepare("
        INSERT INTO access_tokens (id_refresh_token, id_usuario, token_hash, ip_address, fecha_expiracion)
        VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE))
    ")->execute([
        (int)$refresh['id_refresh_token'],
        $idUsuario,
        token_hash($newAccess),
        client_ip(),
    ]);

    $_SESSION['id_sesion'] = (int)$refresh['id_sesion'];
    touch_session((int)$refresh['id_sesion']);
    set_token_cookie(ACCESS_TOKEN_COOKIE, $newAccess, ACCESS_TOKEN_TTL);
    audit($idUsuario, 'ACCESS_REFRESH', 'Access token renovado');
    return true;
}

function restore_session_from_refresh(): bool {
    $refresh = get_valid_refresh_token();
    if (!$refresh) {
        return false;
    }

    $user = fetch_user_auth_snapshot((int)$refresh['id_usuario']);

    if (!$user || $user['status'] !== 'activo') {
        return false;
    }

    $_SESSION['user'] = hydrate_user_session($user);
    $_SESSION['permissions'] = load_permissions_for_role((int)$user['id_rol']);
    $_SESSION['id_sesion'] = (int)$refresh['id_sesion'];

    refresh_access_token_for_user((int)$user['id_usuario']);
    audit((int)$user['id_usuario'], 'LOGIN_REFRESH', 'Sesion restaurada por refresh token');
    return true;
}

function touch_current_session(): void {
    $sessionId = (int)($_SESSION['id_sesion'] ?? 0);
    if ($sessionId > 0) {
        touch_session($sessionId);
    }
}

function touch_session(int $sessionId): void {
    DB::get()->prepare("
        UPDATE sesiones
        SET fecha_ultima_actividad = NOW()
        WHERE id_sesion = ?
    ")->execute([$sessionId]);
}

function revoke_current_tokens(int $idUsuario, string $reason = 'Revocada'): void {
    $pdo = DB::get();
    $refreshToken = $_COOKIE[REFRESH_TOKEN_COOKIE] ?? '';
    $accessToken = $_COOKIE[ACCESS_TOKEN_COOKIE] ?? '';
    $sessionId = (int)($_SESSION['id_sesion'] ?? 0);

    if ($accessToken !== '') {
        $pdo->prepare("
            UPDATE access_tokens
            SET fecha_expiracion = NOW()
            WHERE token_hash = ?
        ")->execute([token_hash($accessToken)]);
    }

    if ($refreshToken !== '') {
        $pdo->prepare("
            UPDATE refresh_tokens
            SET activa = 0, fecha_revocacion = NOW()
            WHERE token_hash = ?
        ")->execute([token_hash($refreshToken)]);
    }

    if ($sessionId > 0) {
        $pdo->prepare("
            UPDATE sesiones
            SET activa = 0, fecha_revocacion = NOW(), motivo_revocacion = ?
            WHERE id_sesion = ?
        ")->execute([$reason, $sessionId]);
    } elseif ($idUsuario > 0) {
        $pdo->prepare("
            UPDATE sesiones
            SET activa = 0, fecha_revocacion = NOW(), motivo_revocacion = ?
            WHERE id_usuario = ? AND activa = 1
        ")->execute([$reason, $idUsuario]);
    }

    clear_token_cookie(ACCESS_TOKEN_COOKIE);
    clear_token_cookie(REFRESH_TOKEN_COOKIE);
}
