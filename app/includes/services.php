<?php
require_once __DIR__ . '/app.php';
require_once __DIR__ . '/repository.php';

function save_user_from_post(?int $userId = null): void {
    $pdo = DB::get();
    $username = posted_string('username');
    $email = posted_string('email');
    $password = (string)($_POST['password'] ?? '');
    $idRol = posted_int('id_rol');
    $idStatus = posted_int('id_status', 1);
    $emailVerificado = isset($_POST['email_verificado']) ? 1 : 0;
    $nombre = posted_string('nombre');
    $apellido = posted_string('apellido');
    $telefono = posted_string('telefono');
    $fechaNacimiento = posted_nullable_date('fecha_nacimiento');

    if ($username === '' || $email === '' || $idRol <= 0 || $idStatus <= 0) {
        throw new RuntimeException('Completa los campos obligatorios del usuario.');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('El correo no tiene un formato valido.');
    }
    if (!role_exists_by_id($idRol)) {
        throw new RuntimeException('Selecciona un rol valido de la base de datos.');
    }
    if (!status_exists_by_id($idStatus)) {
        throw new RuntimeException('Selecciona un estado valido de la base de datos.');
    }
    if ($userId === null && $password === '') {
        throw new RuntimeException('La contrasena es obligatoria al crear un usuario.');
    }

    try {
        $pdo->beginTransaction();

        if ($userId === null) {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("
                INSERT INTO usuarios (id_status, id_rol, username, email, password_hash, email_verificado)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$idStatus, $idRol, $username, $email, $hash, $emailVerificado]);
            $userId = (int)$pdo->lastInsertId();
            audit((int)current_user()['id_usuario'], 'CREATE_USER', "Creacion de usuario {$username}");
        } else {
            if ($password !== '') {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("
                    UPDATE usuarios
                    SET id_status = ?, id_rol = ?, username = ?, email = ?, password_hash = ?, email_verificado = ?
                    WHERE id_usuario = ?
                ");
                $stmt->execute([$idStatus, $idRol, $username, $email, $hash, $emailVerificado, $userId]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE usuarios
                    SET id_status = ?, id_rol = ?, username = ?, email = ?, email_verificado = ?
                    WHERE id_usuario = ?
                ");
                $stmt->execute([$idStatus, $idRol, $username, $email, $emailVerificado, $userId]);
            }
            audit((int)current_user()['id_usuario'], 'UPDATE_USER', "Actualizacion de usuario {$userId}");
        }

        $pdo->prepare("
            INSERT INTO usuarios_perfil (id_usuario, nombre, apellido, telefono, fecha_nacimiento)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                nombre = VALUES(nombre),
                apellido = VALUES(apellido),
                telefono = VALUES(telefono),
                fecha_nacimiento = VALUES(fecha_nacimiento)
        ")->execute([$userId, $nombre ?: null, $apellido ?: null, $telefono ?: null, $fechaNacimiento]);

        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ($exception instanceof PDOException && str_contains($exception->getMessage(), 'Duplicate')) {
            throw new RuntimeException('El username o email ya existe.');
        }
        throw $exception;
    }
}

function delete_user(int $userId): void {
    if ($userId === (int)current_user()['id_usuario']) {
        throw new RuntimeException('No puedes eliminar tu propio usuario.');
    }
    DB::get()->prepare("DELETE FROM usuarios WHERE id_usuario = ?")->execute([$userId]);
    audit((int)current_user()['id_usuario'], 'DELETE_USER', "Eliminacion de usuario {$userId}");
}

function save_catalog_record(string $table, string $idColumn, ?int $id = null, array $columns = []): void {
    $pdo = DB::get();
    $fields = array_keys($columns);
    $values = array_values($columns);

    if ($id === null) {
        $placeholders = implode(', ', array_fill(0, count($fields), '?'));
        $sql = "INSERT INTO {$table} (" . implode(', ', $fields) . ") VALUES ({$placeholders})";
        $pdo->prepare($sql)->execute($values);
    } else {
        $assignments = implode(', ', array_map(fn($field) => "{$field} = ?", $fields));
        $values[] = $id;
        $sql = "UPDATE {$table} SET {$assignments} WHERE {$idColumn} = ?";
        $pdo->prepare($sql)->execute($values);
    }
}

function delete_catalog_record(string $table, string $idColumn, int $id): void {
    DB::get()->prepare("DELETE FROM {$table} WHERE {$idColumn} = ?")->execute([$id]);
}

function save_permission_matrix(array $selectedKeys): void {
    $pdo = DB::get();
    $pdo->beginTransaction();
    try {
        $pdo->exec("DELETE FROM permisos");
        if ($selectedKeys) {
            $stmt = $pdo->prepare("
                INSERT INTO permisos (id_rol, id_recurso, id_accion)
                VALUES (?, ?, ?)
            ");
            foreach ($selectedKeys as $key) {
                [$idRol, $idRecurso, $idAccion] = array_map('intval', explode(':', $key));
                if (!resource_action_exists($idRecurso, $idAccion)) {
                    throw new RuntimeException('Intentaste guardar un permiso con una accion no permitida para ese recurso.');
                }
                $stmt->execute([$idRol, $idRecurso, $idAccion]);
            }
        }
        $pdo->commit();
        $_SESSION['permissions'] = load_permissions_for_role((int)current_user()['id_rol']);
        audit((int)current_user()['id_usuario'], 'UPDATE_PERMISSIONS', 'Actualizacion global de matriz de permisos');
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
}

function save_resource_actions(int $resourceId, array $actionIds): void {
    $pdo = DB::get();
    $pdo->beginTransaction();
    try {
        $pdo->prepare("DELETE FROM recurso_accion WHERE id_recurso = ?")->execute([$resourceId]);

        if ($actionIds) {
            $stmt = $pdo->prepare("
                INSERT INTO recurso_accion (id_recurso, id_accion)
                VALUES (?, ?)
            ");
            foreach ($actionIds as $actionId) {
                $stmt->execute([$resourceId, (int)$actionId]);
            }
        }

        $pdo->prepare("
            DELETE p
            FROM permisos p
            LEFT JOIN recurso_accion ra
              ON ra.id_recurso = p.id_recurso
             AND ra.id_accion = p.id_accion
            WHERE p.id_recurso = ?
              AND ra.id_recurso_accion IS NULL
        ")->execute([$resourceId]);

        $pdo->commit();
        audit((int)current_user()['id_usuario'], 'UPDATE_RESOURCE_ACTIONS', "Actualizacion de acciones para recurso {$resourceId}");
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
}

function revoke_session(int $sessionId, string $reason = 'Revocada por administrador'): void {
    $pdo = DB::get();
    $pdo->beginTransaction();
    try {
        $pdo->prepare("
            UPDATE sesiones
            SET activa = 0, fecha_revocacion = NOW(), motivo_revocacion = ?
            WHERE id_sesion = ?
        ")->execute([$reason, $sessionId]);

        $pdo->prepare("
            UPDATE refresh_tokens
            SET activa = 0, fecha_revocacion = NOW()
            WHERE id_sesion = ?
        ")->execute([$sessionId]);

        $pdo->prepare("
            UPDATE access_tokens at
            JOIN refresh_tokens rt ON rt.id_refresh_token = at.id_refresh_token
            SET at.fecha_expiracion = NOW()
            WHERE rt.id_sesion = ?
        ")->execute([$sessionId]);

        $pdo->commit();
        audit((int)current_user()['id_usuario'], 'REVOKE_SESSION', "Revocacion de sesion {$sessionId}");
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
}
