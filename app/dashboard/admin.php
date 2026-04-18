<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/db.php';

require_role('admin');

$pdo = DB::get();
$msg = '';
$msg_type = 'success';

// ── Procesar acciones POST ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // CREAR usuario
    if ($action === 'crear') {
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $id_rol   = (int)($_POST['id_rol'] ?? 0);
        $nombre   = trim($_POST['nombre'] ?? '');
        $apellido = trim($_POST['apellido'] ?? '');

        if (!$username || !$email || !$password || !$id_rol) {
            $msg = 'Todos los campos obligatorios deben completarse.';
            $msg_type = 'error';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $msg = 'El email no tiene un formato válido.';
            $msg_type = 'error';
        } else {
            try {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("
                    INSERT INTO usuarios (id_status, id_rol, username, email, password_hash, email_verificado)
                    VALUES (1, ?, ?, ?, ?, 1)
                ");
                $stmt->execute([$id_rol, $username, $email, $hash]);
                $new_id = $pdo->lastInsertId();

                $pdo->prepare("INSERT INTO usuarios_perfil (id_usuario, nombre, apellido) VALUES (?,?,?)")
                    ->execute([$new_id, $nombre, $apellido]);

                audit(current_user()['id_usuario'], 'CREATE_USER', "Creó usuario: $username");
                $msg = "✅ Usuario '$username' creado correctamente.";
            } catch (PDOException $e) {
                $msg = str_contains($e->getMessage(), 'Duplicate')
                    ? '⚠️ El username o email ya existe.'
                    : '❌ Error al crear usuario.';
                $msg_type = 'error';
            }
        }
    }

    // EDITAR usuario
    if ($action === 'editar') {
        $id       = (int)($_POST['id_usuario'] ?? 0);
        $email    = trim($_POST['email'] ?? '');
        $id_rol   = (int)($_POST['id_rol'] ?? 0);
        $id_status= (int)($_POST['id_status'] ?? 1);
        $nombre   = trim($_POST['nombre'] ?? '');
        $apellido = trim($_POST['apellido'] ?? '');
        $password = $_POST['password'] ?? '';

        try {
            if ($password) {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $pdo->prepare("UPDATE usuarios SET email=?, id_rol=?, id_status=?, password_hash=? WHERE id_usuario=?")
                    ->execute([$email, $id_rol, $id_status, $hash, $id]);
            } else {
                $pdo->prepare("UPDATE usuarios SET email=?, id_rol=?, id_status=? WHERE id_usuario=?")
                    ->execute([$email, $id_rol, $id_status, $id]);
            }

            // Upsert perfil
            $pdo->prepare("
                INSERT INTO usuarios_perfil (id_usuario, nombre, apellido)
                VALUES (?,?,?)
                ON DUPLICATE KEY UPDATE nombre=VALUES(nombre), apellido=VALUES(apellido)
            ")->execute([$id, $nombre, $apellido]);

            audit(current_user()['id_usuario'], 'UPDATE_USER', "Editó usuario id: $id");
            $msg = '✅ Usuario actualizado correctamente.';
        } catch (PDOException) {
            $msg = '❌ Error al actualizar el usuario.'; $msg_type = 'error';
        }
    }

    // ELIMINAR usuario
    if ($action === 'eliminar') {
        $id = (int)($_POST['id_usuario'] ?? 0);
        if ($id === current_user()['id_usuario']) {
            $msg = '⚠️ No puedes eliminar tu propio usuario.'; $msg_type = 'error';
        } else {
            try {
                $pdo->prepare("DELETE FROM usuarios WHERE id_usuario = ?")->execute([$id]);
                audit(current_user()['id_usuario'], 'DELETE_USER', "Eliminó usuario id: $id");
                $msg = '✅ Usuario eliminado correctamente.';
            } catch (PDOException) {
                $msg = '❌ Error al eliminar el usuario.'; $msg_type = 'error';
            }
        }
    }
}

// ── Cargar datos ──────────────────────────────────────────────
$usuarios = $pdo->query("
    SELECT u.id_usuario, u.username, u.email, u.fecha_registro, u.fecha_ultimo_acceso,
           s.nombre AS status, r.nombre AS rol, u.id_status, u.id_rol,
           p.nombre AS p_nombre, p.apellido AS p_apellido
    FROM usuarios u
    JOIN cat_status s ON s.id_status = u.id_status
    JOIN roles      r ON r.id_rol    = u.id_rol
    LEFT JOIN usuarios_perfil p ON p.id_usuario = u.id_usuario
    ORDER BY u.fecha_registro DESC
")->fetchAll();

$roles     = $pdo->query("SELECT * FROM roles ORDER BY nombre")->fetchAll();
$statuses  = $pdo->query("SELECT * FROM cat_status ORDER BY nombre")->fetchAll();

// ── Render ────────────────────────────────────────────────────
layout_head('Panel de Administración');
?>

<?php if ($msg): ?>
    <div class="alert alert-<?= $msg_type === 'error' ? 'error' : 'success' ?>">
        <?= htmlspecialchars($msg) ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="action-bar">
        <h2>👥 Usuarios registrados (<?= count($usuarios) ?>)</h2>
        <button class="btn btn-success" onclick="openModal('modal-crear')">+ Nuevo usuario</button>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th><th>Usuario</th><th>Nombre</th><th>Email</th>
                    <th>Rol</th><th>Status</th><th>Último acceso</th><th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($usuarios as $u): ?>
            <tr>
                <td><?= $u['id_usuario'] ?></td>
                <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
                <td><?= htmlspecialchars(trim($u['p_nombre'] . ' ' . $u['p_apellido'])) ?: '—' ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><span class="badge" style="background:#1a1a2e"><?= htmlspecialchars($u['rol']) ?></span></td>
                <td>
                    <span class="status-<?= $u['status'] ?>">
                        <?= ucfirst($u['status']) ?>
                    </span>
                </td>
                <td><?= $u['fecha_ultimo_acceso'] ? date('d/m/Y H:i', strtotime($u['fecha_ultimo_acceso'])) : '—' ?></td>
                <td style="white-space:nowrap">
                    <button class="btn btn-secondary btn-sm"
                        onclick="openEdit(<?= htmlspecialchars(json_encode($u)) ?>)">
                        ✏️ Editar
                    </button>
                    <?php if ($u['id_usuario'] !== current_user()['id_usuario']): ?>
                    <form method="POST" style="display:inline"
                          onsubmit="return confirm('¿Eliminar al usuario <?= htmlspecialchars($u['username']) ?>?')">
                        <input type="hidden" name="action" value="eliminar">
                        <input type="hidden" name="id_usuario" value="<?= $u['id_usuario'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm">🗑️ Eliminar</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$usuarios): ?>
            <tr><td colspan="8" style="text-align:center;color:#aaa;padding:2rem">Sin usuarios registrados.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ── Modal: Crear usuario ──────────────────────────────────── -->
<div class="modal-bg" id="modal-crear">
    <div class="modal">
        <h2>➕ Nuevo usuario</h2>
        <form method="POST">
            <input type="hidden" name="action" value="crear">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
                <div class="form-group">
                    <label>Nombre</label>
                    <input type="text" name="nombre" placeholder="Juan">
                </div>
                <div class="form-group">
                    <label>Apellido</label>
                    <input type="text" name="apellido" placeholder="Pérez">
                </div>
            </div>
            <div class="form-group">
                <label>Username *</label>
                <input type="text" name="username" required placeholder="juanperez">
            </div>
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" required placeholder="juan@email.com">
            </div>
            <div class="form-group">
                <label>Contraseña *</label>
                <input type="password" name="password" required placeholder="Mínimo 8 caracteres">
            </div>
            <div class="form-group">
                <label>Rol *</label>
                <select name="id_rol" required>
                    <option value="">— Seleccionar —</option>
                    <?php foreach ($roles as $r): ?>
                    <option value="<?= $r['id_rol'] ?>"><?= htmlspecialchars($r['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modal-crear')">Cancelar</button>
                <button type="submit" class="btn btn-success">Crear usuario</button>
            </div>
        </form>
    </div>
</div>

<!-- ── Modal: Editar usuario ─────────────────────────────────── -->
<div class="modal-bg" id="modal-editar">
    <div class="modal">
        <h2>✏️ Editar usuario: <span id="edit-username"></span></h2>
        <form method="POST">
            <input type="hidden" name="action" value="editar">
            <input type="hidden" name="id_usuario" id="edit-id">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
                <div class="form-group">
                    <label>Nombre</label>
                    <input type="text" name="nombre" id="edit-nombre">
                </div>
                <div class="form-group">
                    <label>Apellido</label>
                    <input type="text" name="apellido" id="edit-apellido">
                </div>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" id="edit-email" required>
            </div>
            <div class="form-group">
                <label>Nueva contraseña <small>(dejar vacío para no cambiar)</small></label>
                <input type="password" name="password" placeholder="••••••••">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
                <div class="form-group">
                    <label>Rol</label>
                    <select name="id_rol" id="edit-rol">
                        <?php foreach ($roles as $r): ?>
                        <option value="<?= $r['id_rol'] ?>"><?= htmlspecialchars($r['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="id_status" id="edit-status">
                        <?php foreach ($statuses as $s): ?>
                        <option value="<?= $s['id_status'] ?>"><?= htmlspecialchars($s['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modal-editar')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar cambios</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

function openEdit(u) {
    document.getElementById('edit-id').value       = u.id_usuario;
    document.getElementById('edit-username').textContent = u.username;
    document.getElementById('edit-email').value    = u.email;
    document.getElementById('edit-nombre').value   = u.p_nombre || '';
    document.getElementById('edit-apellido').value = u.p_apellido || '';
    document.getElementById('edit-rol').value      = u.id_rol;
    document.getElementById('edit-status').value   = u.id_status;
    openModal('modal-editar');
}

// Cerrar modal al hacer click en el fondo
document.querySelectorAll('.modal-bg').forEach(bg => {
    bg.addEventListener('click', e => { if (e.target === bg) bg.classList.remove('open'); });
});
</script>

<?php layout_foot(); ?>
