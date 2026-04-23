<?php
require_once __DIR__ . '/db.php';

function fetch_all_statuses(): array {
    return DB::get()->query("SELECT * FROM cat_status ORDER BY nombre")->fetchAll();
}

function fetch_all_roles(): array {
    return DB::get()->query("
        SELECT r.*,
               COUNT(DISTINCT p.id_permiso) AS total_permisos,
               COUNT(DISTINCT u.id_usuario) AS total_usuarios
        FROM roles r
        LEFT JOIN permisos p ON p.id_rol = r.id_rol
        LEFT JOIN usuarios u ON u.id_rol = r.id_rol
        GROUP BY r.id_rol
        ORDER BY r.nombre
    ")->fetchAll();
}

function fetch_role_options(): array {
    return DB::get()->query("SELECT id_rol, nombre FROM roles ORDER BY nombre")->fetchAll();
}

function fetch_role_names(): array {
    return DB::get()->query("SELECT nombre FROM roles ORDER BY nombre")->fetchAll(PDO::FETCH_COLUMN) ?: [];
}

function role_exists_by_id(int $roleId): bool {
    $stmt = DB::get()->prepare("SELECT 1 FROM roles WHERE id_rol = ? LIMIT 1");
    $stmt->execute([$roleId]);
    return (bool)$stmt->fetchColumn();
}

function status_exists_by_id(int $statusId): bool {
    $stmt = DB::get()->prepare("SELECT 1 FROM cat_status WHERE id_status = ? LIMIT 1");
    $stmt->execute([$statusId]);
    return (bool)$stmt->fetchColumn();
}

function fetch_all_resources(): array {
    return DB::get()->query("
        SELECT cr.*, COUNT(DISTINCT p.id_permiso) AS total_permisos, COUNT(DISTINCT ra.id_recurso_accion) AS total_acciones
        FROM cat_recurso cr
        LEFT JOIN permisos p ON p.id_recurso = cr.id_recurso
        LEFT JOIN recurso_accion ra ON ra.id_recurso = cr.id_recurso
        GROUP BY cr.id_recurso
        ORDER BY cr.nombre
    ")->fetchAll();
}

function fetch_all_actions(): array {
    return DB::get()->query("
        SELECT ca.*, COUNT(DISTINCT p.id_permiso) AS total_permisos, COUNT(DISTINCT ra.id_recurso_accion) AS total_recursos
        FROM cat_accion ca
        LEFT JOIN permisos p ON p.id_accion = ca.id_accion
        LEFT JOIN recurso_accion ra ON ra.id_accion = ca.id_accion
        GROUP BY ca.id_accion
        ORDER BY ca.nombre
    ")->fetchAll();
}

function fetch_resource_actions_map(): array {
    $rows = DB::get()->query("SELECT id_recurso, id_accion FROM recurso_accion")->fetchAll();
    $map = [];
    foreach ($rows as $row) {
        $key = $row['id_recurso'] . ':' . $row['id_accion'];
        $map[$key] = true;
    }
    return $map;
}

function fetch_actions_for_resource(int $resourceId): array {
    $stmt = DB::get()->prepare("
        SELECT ca.*
        FROM recurso_accion ra
        JOIN cat_accion ca ON ca.id_accion = ra.id_accion
        WHERE ra.id_recurso = ?
        ORDER BY ca.nombre
    ");
    $stmt->execute([$resourceId]);
    return $stmt->fetchAll();
}

function fetch_permission_matrix(): array {
    $roles = DB::get()->query("SELECT id_rol, nombre FROM roles ORDER BY nombre")->fetchAll();
    $resources = DB::get()->query("SELECT id_recurso, nombre FROM cat_recurso ORDER BY nombre")->fetchAll();
    $actions = DB::get()->query("SELECT id_accion, nombre FROM cat_accion ORDER BY nombre")->fetchAll();
    $permissions = DB::get()->query("SELECT id_rol, id_recurso, id_accion FROM permisos")->fetchAll();
    $resourceActionMap = fetch_resource_actions_map();

    $matrix = [];
    foreach ($permissions as $permission) {
        $key = $permission['id_rol'] . ':' . $permission['id_recurso'] . ':' . $permission['id_accion'];
        $matrix[$key] = true;
    }

    return [
        'roles' => $roles,
        'resources' => $resources,
        'actions' => $actions,
        'matrix' => $matrix,
        'resource_action_map' => $resourceActionMap,
    ];
}

function resource_action_exists(int $resourceId, int $actionId): bool {
    $stmt = DB::get()->prepare("
        SELECT 1
        FROM recurso_accion
        WHERE id_recurso = ? AND id_accion = ?
        LIMIT 1
    ");
    $stmt->execute([$resourceId, $actionId]);
    return (bool)$stmt->fetchColumn();
}

function fetch_users(array $filters = []): array {
    $sql = "
        SELECT u.id_usuario, u.username, u.email, u.email_verificado, u.fecha_registro, u.fecha_ultimo_acceso,
               s.nombre AS status, r.nombre AS rol, u.id_status, u.id_rol,
               p.nombre AS p_nombre, p.apellido AS p_apellido, p.telefono, p.fecha_nacimiento
        FROM usuarios u
        JOIN cat_status s ON s.id_status = u.id_status
        JOIN roles r ON r.id_rol = u.id_rol
        LEFT JOIN usuarios_perfil p ON p.id_usuario = u.id_usuario
        WHERE 1 = 1
    ";
    $params = [];

    if (($filters['search'] ?? '') !== '') {
        $sql .= " AND (u.username LIKE ? OR u.email LIKE ? OR p.nombre LIKE ? OR p.apellido LIKE ?)";
        $term = '%' . $filters['search'] . '%';
        $params = array_merge($params, [$term, $term, $term, $term]);
    }

    if (($filters['status'] ?? '') !== '') {
        $sql .= " AND s.nombre = ?";
        $params[] = $filters['status'];
    }

    if (($filters['role'] ?? '') !== '') {
        $sql .= " AND r.nombre = ?";
        $params[] = $filters['role'];
    }

    $sql .= " ORDER BY u.fecha_registro DESC";
    $stmt = DB::get()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function fetch_user_by_id(int $userId): array|false {
    $stmt = DB::get()->prepare("
        SELECT u.id_usuario, u.username, u.email, u.email_verificado, u.fecha_registro, u.fecha_ultimo_acceso,
               s.nombre AS status, r.nombre AS rol, u.id_status, u.id_rol,
               p.nombre AS p_nombre, p.apellido AS p_apellido, p.telefono, p.fecha_nacimiento
        FROM usuarios u
        JOIN cat_status s ON s.id_status = u.id_status
        JOIN roles r ON r.id_rol = u.id_rol
        LEFT JOIN usuarios_perfil p ON p.id_usuario = u.id_usuario
        WHERE u.id_usuario = ?
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

function fetch_overview_metrics(): array {
    $pdo = DB::get();
    return [
        'usuarios' => (int)$pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn(),
        'usuarios_activos' => (int)$pdo->query("
            SELECT COUNT(*)
            FROM usuarios u
            JOIN cat_status s ON s.id_status = u.id_status
            WHERE s.nombre = 'activo'
        ")->fetchColumn(),
        'roles' => (int)$pdo->query("SELECT COUNT(*) FROM roles")->fetchColumn(),
        'permisos' => (int)$pdo->query("SELECT COUNT(*) FROM permisos")->fetchColumn(),
        'sesiones_activas' => (int)$pdo->query("SELECT COUNT(*) FROM sesiones WHERE activa = 1 AND fecha_expiracion > NOW()")->fetchColumn(),
        'eventos_hoy' => (int)$pdo->query("SELECT COUNT(*) FROM auditoria WHERE DATE(fecha_evento) = CURDATE()")->fetchColumn(),
    ];
}

function fetch_recent_audit(int $limit = 12): array {
    $stmt = DB::get()->prepare("
        SELECT a.*, COALESCE(u.username, 'sistema') AS username
        FROM auditoria a
        LEFT JOIN usuarios u ON u.id_usuario = a.id_usuario
        ORDER BY a.fecha_evento DESC
        LIMIT ?
    ");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function fetch_recent_users(int $limit = 8): array {
    $stmt = DB::get()->prepare("
        SELECT username, email, fecha_registro
        FROM usuarios
        ORDER BY fecha_registro DESC
        LIMIT ?
    ");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function fetch_sessions(array $filters = []): array {
    $sql = "
        SELECT s.id_sesion, s.id_usuario, s.ip_address, s.user_agent, s.activa,
               s.fecha_inicio, s.fecha_ultima_actividad, s.fecha_expiracion, s.fecha_revocacion, s.motivo_revocacion,
               u.username, r.id_refresh_token, r.fecha_expiracion AS refresh_expira,
               COUNT(DISTINCT a.id_access_token) AS access_emitidos
        FROM sesiones s
        JOIN usuarios u ON u.id_usuario = s.id_usuario
        LEFT JOIN refresh_tokens r ON r.id_sesion = s.id_sesion
        LEFT JOIN access_tokens a ON a.id_refresh_token = r.id_refresh_token
        WHERE 1 = 1
    ";
    $params = [];

    if (($filters['user'] ?? '') !== '') {
        $sql .= " AND u.username LIKE ?";
        $params[] = '%' . $filters['user'] . '%';
    }

    if (($filters['active_only'] ?? false) === true) {
        $sql .= " AND s.activa = 1";
    }

    $sql .= "
        GROUP BY s.id_sesion, s.id_usuario, s.ip_address, s.user_agent, s.activa,
                 s.fecha_inicio, s.fecha_ultima_actividad, s.fecha_expiracion, s.fecha_revocacion, s.motivo_revocacion,
                 u.username, r.id_refresh_token, r.fecha_expiracion
        ORDER BY s.fecha_inicio DESC
    ";

    $stmt = DB::get()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function fetch_audit(array $filters = []): array {
    $sql = "
        SELECT a.*, COALESCE(u.username, 'sistema') AS username
        FROM auditoria a
        LEFT JOIN usuarios u ON u.id_usuario = a.id_usuario
        WHERE 1 = 1
    ";
    $params = [];

    if (($filters['user'] ?? '') !== '') {
        $sql .= " AND COALESCE(u.username, '') LIKE ?";
        $params[] = '%' . $filters['user'] . '%';
    }

    if (($filters['action'] ?? '') !== '') {
        $sql .= " AND a.accion LIKE ?";
        $params[] = '%' . $filters['action'] . '%';
    }

    if (($filters['ip'] ?? '') !== '') {
        $sql .= " AND COALESCE(a.ip_address, '') LIKE ?";
        $params[] = '%' . $filters['ip'] . '%';
    }

    if (($filters['from'] ?? '') !== '') {
        $sql .= " AND a.fecha_evento >= ?";
        $params[] = $filters['from'] . ' 00:00:00';
    }

    if (($filters['to'] ?? '') !== '') {
        $sql .= " AND a.fecha_evento <= ?";
        $params[] = $filters['to'] . ' 23:59:59';
    }

    $sql .= " ORDER BY a.fecha_evento DESC LIMIT 150";
    $stmt = DB::get()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}
