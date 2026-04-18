<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/db.php';

require_role('admin');

$pdo = DB::get();
$msg = '';
$msg_type = 'success';

// ── POST Actions ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

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
                $msg = "Usuario '$username' creado correctamente.";
            } catch (PDOException $e) {
                $msg = str_contains($e->getMessage(), 'Duplicate')
                    ? 'El username o email ya existe.'
                    : 'Error al crear usuario.';
                $msg_type = 'error';
            }
        }
    }

    if ($action === 'editar') {
        $id        = (int)($_POST['id_usuario'] ?? 0);
        $email     = trim($_POST['email'] ?? '');
        $id_rol    = (int)($_POST['id_rol'] ?? 0);
        $id_status = (int)($_POST['id_status'] ?? 1);
        $nombre    = trim($_POST['nombre'] ?? '');
        $apellido  = trim($_POST['apellido'] ?? '');
        $password  = $_POST['password'] ?? '';

        try {
            if ($password) {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $pdo->prepare("UPDATE usuarios SET email=?, id_rol=?, id_status=?, password_hash=? WHERE id_usuario=?")
                    ->execute([$email, $id_rol, $id_status, $hash, $id]);
            } else {
                $pdo->prepare("UPDATE usuarios SET email=?, id_rol=?, id_status=? WHERE id_usuario=?")
                    ->execute([$email, $id_rol, $id_status, $id]);
            }
            $pdo->prepare("
                INSERT INTO usuarios_perfil (id_usuario, nombre, apellido) VALUES (?,?,?)
                ON DUPLICATE KEY UPDATE nombre=VALUES(nombre), apellido=VALUES(apellido)
            ")->execute([$id, $nombre, $apellido]);
            audit(current_user()['id_usuario'], 'UPDATE_USER', "Editó usuario id: $id");
            $msg = 'Usuario actualizado correctamente.';
        } catch (PDOException) {
            $msg = 'Error al actualizar el usuario.';
            $msg_type = 'error';
        }
    }

    if ($action === 'eliminar') {
        $id = (int)($_POST['id_usuario'] ?? 0);
        if ($id === current_user()['id_usuario']) {
            $msg = 'No puedes eliminar tu propio usuario.';
            $msg_type = 'error';
        } else {
            try {
                $pdo->prepare("DELETE FROM usuarios WHERE id_usuario = ?")->execute([$id]);
                audit(current_user()['id_usuario'], 'DELETE_USER', "Eliminó usuario id: $id");
                $msg = 'Usuario eliminado correctamente.';
            } catch (PDOException) {
                $msg = 'Error al eliminar el usuario.';
                $msg_type = 'error';
            }
        }
    }
}

// ── Data ──────────────────────────────────────────────────────
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

$roles    = $pdo->query("SELECT * FROM roles ORDER BY nombre")->fetchAll();
$statuses = $pdo->query("SELECT * FROM cat_status ORDER BY nombre")->fetchAll();

// ── Stats ─────────────────────────────────────────────────────
$total    = count($usuarios);
$activos  = count(array_filter($usuarios, fn($u) => strtolower($u['status']) === 'activo'));
$inactivos = $total - $activos;
$hoy      = date('Y-m-d');
$acceso_hoy = count(array_filter($usuarios, fn($u) =>
    $u['fecha_ultimo_acceso'] && str_starts_with($u['fecha_ultimo_acceso'], $hoy)
));

layout_head('Panel de Administración');
?>

<div class="admin-shell">

  <!-- ── Sidebar ───────────────────────────────────────────── -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
      <div class="logo-mark">A</div>
      <span class="logo-text">AdminPanel</span>
    </div>
    <nav class="sidebar-nav">
      <a class="nav-item active" href="#">
        <span class="nav-icon">&#9783;</span>
        <span class="nav-label">Usuarios</span>
      </a>
      <a class="nav-item" href="#">
        <span class="nav-icon">&#9881;</span>
        <span class="nav-label">Roles y permisos</span>
      </a>
      <a class="nav-item" href="#">
        <span class="nav-icon">&#9741;</span>
        <span class="nav-label">Auditoría</span>
      </a>
      <a class="nav-item" href="#">
        <span class="nav-icon">&#9723;</span>
        <span class="nav-label">Configuración</span>
      </a>
    </nav>
    <div class="sidebar-footer">
      <button class="toggle-btn" onclick="toggleSidebar()" title="Contraer menú">
        <span class="toggle-arrow">&#8592;</span>
        <span class="toggle-label">Contraer</span>
      </button>
    </div>
  </aside>

  <!-- ── Main ─────────────────────────────────────────────── -->
  <div class="main-area">

    <!-- Topbar -->
    <header class="topbar">
      <span class="topbar-title">Gestión de usuarios</span>
      <div class="topbar-right">
        <span class="topbar-user"><?= htmlspecialchars(current_user()['username']) ?></span>
        <div class="avatar"><?= strtoupper(substr(current_user()['username'], 0, 2)) ?></div>
      </div>
    </header>

    <div class="page-content">

      <!-- Alerta -->
      <?php if ($msg): ?>
      <div class="alert alert-<?= $msg_type ?>">
        <?= htmlspecialchars($msg) ?>
      </div>
      <?php endif; ?>

      <!-- Stats -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-label">Total usuarios</div>
          <div class="stat-value"><?= $total ?></div>
          <div class="stat-sub">Registrados en el sistema</div>
        </div>
        <div class="stat-card stat-green">
          <div class="stat-label">Activos</div>
          <div class="stat-value"><?= $activos ?></div>
          <div class="stat-sub"><?= $total ? round($activos / $total * 100) : 0 ?>% del total</div>
        </div>
        <div class="stat-card stat-red">
          <div class="stat-label">Inactivos</div>
          <div class="stat-value"><?= $inactivos ?></div>
          <div class="stat-sub"><?= $total ? round($inactivos / $total * 100) : 0 ?>% del total</div>
        </div>
        <div class="stat-card stat-blue">
          <div class="stat-label">Accedieron hoy</div>
          <div class="stat-value"><?= $acceso_hoy ?></div>
          <div class="stat-sub">Sesiones activas</div>
        </div>
      </div>

      <!-- Tabla de usuarios -->
      <div class="card">
        <div class="card-header">
          <div class="card-title">
            Usuarios registrados
            <span class="count-pill"><?= $total ?></span>
          </div>
          <button class="btn btn-primary" onclick="openModal('modal-crear')">+ Nuevo usuario</button>
        </div>
        <div class="table-search">
          <input type="text" id="search-input" placeholder="Buscar por usuario, nombre o email..."
                 oninput="filterTable(this.value)">
        </div>
        <div class="table-wrap">
          <table id="users-table">
            <thead>
              <tr>
                <th style="width:48px">#</th>
                <th>Usuario</th>
                <th>Nombre</th>
                <th>Email</th>
                <th style="width:110px">Rol</th>
                <th style="width:90px">Status</th>
                <th style="width:140px">Último acceso</th>
                <th style="width:130px">Acciones</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($usuarios as $u):
              $nombre_completo = trim($u['p_nombre'] . ' ' . $u['p_apellido']);
              $status_class = strtolower($u['status']) === 'activo' ? 'badge-active' : 'badge-inactive';
            ?>
            <tr>
              <td class="td-muted"><?= $u['id_usuario'] ?></td>
              <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
              <td><?= $nombre_completo ? htmlspecialchars($nombre_completo) : '<span class="td-muted">—</span>' ?></td>
              <td class="td-email"><?= htmlspecialchars($u['email']) ?></td>
              <td><span class="badge badge-rol"><?= htmlspecialchars($u['rol']) ?></span></td>
              <td><span class="badge <?= $status_class ?>"><?= ucfirst($u['status']) ?></span></td>
              <td class="td-muted">
                <?= $u['fecha_ultimo_acceso'] ? date('d/m/Y H:i', strtotime($u['fecha_ultimo_acceso'])) : '—' ?>
              </td>
              <td>
                <div class="action-btns">
                  <button class="btn btn-sm btn-secondary"
                    onclick='openEdit(<?= htmlspecialchars(json_encode($u)) ?>)'>Editar</button>
                  <?php if ($u['id_usuario'] !== current_user()['id_usuario']): ?>
                  <form method="POST" style="display:inline"
                        onsubmit="return confirm('¿Eliminar a <?= htmlspecialchars($u['username']) ?>?')">
                    <input type="hidden" name="action" value="eliminar">
                    <input type="hidden" name="id_usuario" value="<?= $u['id_usuario'] ?>">
                    <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                  </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$usuarios): ?>
            <tr><td colspan="8" class="empty-row">Sin usuarios registrados.</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div><!-- /page-content -->
  </div><!-- /main-area -->
</div><!-- /admin-shell -->

<!-- ── Modal Crear ────────────────────────────────────────────── -->
<div class="modal-bg" id="modal-crear">
  <div class="modal">
    <div class="modal-header">
      <h2 class="modal-title">Nuevo usuario</h2>
      <button class="modal-close" onclick="closeModal('modal-crear')">&#10005;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="crear">
      <div class="form-row">
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
        <label>Username <span class="required">*</span></label>
        <input type="text" name="username" required placeholder="juanperez">
      </div>
      <div class="form-group">
        <label>Email <span class="required">*</span></label>
        <input type="email" name="email" required placeholder="juan@email.com">
      </div>
      <div class="form-group">
        <label>Contraseña <span class="required">*</span></label>
        <input type="password" name="password" required placeholder="Mínimo 8 caracteres">
      </div>
      <div class="form-group">
        <label>Rol <span class="required">*</span></label>
        <select name="id_rol" required>
          <option value="">— Seleccionar —</option>
          <?php foreach ($roles as $r): ?>
          <option value="<?= $r['id_rol'] ?>"><?= htmlspecialchars($r['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" onclick="closeModal('modal-crear')">Cancelar</button>
        <button type="submit" class="btn btn-primary">Crear usuario</button>
      </div>
    </form>
  </div>
</div>

<!-- ── Modal Editar ───────────────────────────────────────────── -->
<div class="modal-bg" id="modal-editar">
  <div class="modal">
    <div class="modal-header">
      <h2 class="modal-title">Editar: <span id="edit-username"></span></h2>
      <button class="modal-close" onclick="closeModal('modal-editar')">&#10005;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="editar">
      <input type="hidden" name="id_usuario" id="edit-id">
      <div class="form-row">
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
        <label>Email <span class="required">*</span></label>
        <input type="email" name="email" id="edit-email" required>
      </div>
      <div class="form-group">
        <label>Nueva contraseña <small>— dejar vacío para no cambiar</small></label>
        <input type="password" name="password" placeholder="••••••••">
      </div>
      <div class="form-row">
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

<style>
/* ── Shell layout ──────────────────────────────────────────── */
.admin-shell { display: flex; min-height: 100vh; background: var(--bg-page, #f5f5f4); }

/* ── Sidebar ───────────────────────────────────────────────── */
.sidebar {
  width: 220px; min-width: 220px;
  background: #fff; border-right: 1px solid #e5e7eb;
  display: flex; flex-direction: column;
  transition: width .25s ease, min-width .25s ease;
  overflow: hidden; position: sticky; top: 0; height: 100vh;
}
.sidebar.collapsed { width: 56px; min-width: 56px; }
.sidebar-logo {
  display: flex; align-items: center; gap: 10px;
  padding: 14px; height: 56px; border-bottom: 1px solid #e5e7eb;
}
.logo-mark {
  width: 28px; height: 28px; min-width: 28px; border-radius: 7px;
  background: #2563eb; color: #fff; display: flex;
  align-items: center; justify-content: center;
  font-size: 13px; font-weight: 500; flex-shrink: 0;
}
.logo-text { font-size: 14px; font-weight: 500; white-space: nowrap; }
.sidebar.collapsed .logo-text,
.sidebar.collapsed .nav-label,
.sidebar.collapsed .toggle-label { display: none; }
.sidebar-nav { flex: 1; padding: 8px 0; }
.nav-item {
  display: flex; align-items: center; gap: 10px;
  padding: 9px 14px; font-size: 13px; color: #6b7280;
  text-decoration: none; transition: background .15s;
}
.nav-item:hover { background: #f9fafb; color: #111; }
.nav-item.active { color: #2563eb; background: #eff6ff; }
.nav-icon { font-size: 15px; min-width: 18px; text-align: center; }
.sidebar-footer { border-top: 1px solid #e5e7eb; padding: 10px; }
.toggle-btn {
  display: flex; align-items: center; gap: 8px; width: 100%;
  background: none; border: 1px solid #e5e7eb; border-radius: 6px;
  padding: 7px 10px; font-size: 12px; color: #6b7280; cursor: pointer;
  transition: background .15s;
}
.toggle-btn:hover { background: #f9fafb; }
.toggle-arrow { font-size: 11px; transition: transform .25s; }
.sidebar.collapsed .toggle-arrow { transform: rotate(180deg); }
.sidebar.collapsed .toggle-btn { justify-content: center; padding: 7px; }

/* ── Main area ─────────────────────────────────────────────── */
.main-area { flex: 1; display: flex; flex-direction: column; min-width: 0; }
.topbar {
  height: 56px; background: #fff; border-bottom: 1px solid #e5e7eb;
  display: flex; align-items: center; justify-content: space-between;
  padding: 0 24px; position: sticky; top: 0; z-index: 10;
}
.topbar-title { font-size: 14px; font-weight: 500; }
.topbar-right { display: flex; align-items: center; gap: 10px; }
.topbar-user { font-size: 13px; color: #6b7280; }
.avatar {
  width: 30px; height: 30px; border-radius: 50%;
  background: #eff6ff; color: #2563eb;
  display: flex; align-items: center; justify-content: center;
  font-size: 11px; font-weight: 500; border: 1px solid #bfdbfe;
}
.page-content { padding: 24px; }

/* ── Alert ─────────────────────────────────────────────────── */
.alert { padding: 12px 16px; border-radius: 8px; font-size: 13px; margin-bottom: 20px; }
.alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
.alert-error   { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

/* ── Stats ─────────────────────────────────────────────────── */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: 12px; margin-bottom: 24px;
}
.stat-card { background: #f9fafb; border-radius: 8px; padding: 16px; }
.stat-label { font-size: 12px; color: #6b7280; margin-bottom: 6px; }
.stat-value { font-size: 26px; font-weight: 500; line-height: 1; }
.stat-sub   { font-size: 11px; color: #9ca3af; margin-top: 4px; }
.stat-green .stat-value { color: #15803d; }
.stat-red   .stat-value { color: #b91c1c; }
.stat-blue  .stat-value { color: #1d4ed8; }

/* ── Card / tabla ──────────────────────────────────────────── */
.card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; }
.card-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 14px 18px; border-bottom: 1px solid #e5e7eb;
}
.card-title { font-size: 14px; font-weight: 500; display: flex; align-items: center; gap: 8px; }
.count-pill {
  font-size: 12px; background: #f3f4f6; border: 1px solid #e5e7eb;
  border-radius: 20px; padding: 2px 10px; color: #6b7280; font-weight: 400;
}
.table-search { padding: 10px 18px; border-bottom: 1px solid #e5e7eb; }
.table-search input {
  width: 100%; border: 1px solid #e5e7eb; border-radius: 7px;
  padding: 8px 12px; font-size: 13px; background: #f9fafb; outline: none;
}
.table-search input:focus { border-color: #93c5fd; }
.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; min-width: 700px; }
th {
  font-size: 11px; font-weight: 500; color: #6b7280; text-align: left;
  padding: 10px 16px; background: #f9fafb; border-bottom: 1px solid #e5e7eb;
  white-space: nowrap;
}
td { font-size: 13px; padding: 11px 16px; border-bottom: 1px solid #f3f4f6; vertical-align: middle; }
tr:last-child td { border-bottom: none; }
tbody tr:hover td { background: #fafafa; }
.td-muted { color: #9ca3af; font-size: 12px; }
.td-email { color: #4b5563; font-size: 12px; }
.empty-row { text-align: center; color: #9ca3af; padding: 2rem; }

/* ── Badges ────────────────────────────────────────────────── */
.badge { font-size: 11px; padding: 3px 9px; border-radius: 20px; font-weight: 500; display: inline-block; }
.badge-active   { background: #f0fdf4; color: #15803d; }
.badge-inactive { background: #fef2f2; color: #b91c1c; }
.badge-rol      { background: #eff6ff; color: #1d4ed8; }

/* ── Buttons ───────────────────────────────────────────────── */
.btn { border-radius: 7px; padding: 8px 16px; font-size: 13px; cursor: pointer; font-weight: 500; border: none; transition: opacity .15s; }
.btn:hover { opacity: .85; }
.btn-primary   { background: #2563eb; color: #fff; }
.btn-secondary { background: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; }
.btn-danger    { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
.btn-sm        { padding: 5px 11px; font-size: 12px; border-radius: 5px; }
.action-btns   { display: flex; gap: 6px; }

/* ── Modals ────────────────────────────────────────────────── */
.modal-bg {
  display: none; position: fixed; inset: 0;
  background: rgba(0,0,0,.4); z-index: 999;
  align-items: center; justify-content: center;
}
.modal-bg.open { display: flex; }
.modal {
  background: #fff; border-radius: 12px; padding: 24px;
  width: 100%; max-width: 500px; max-height: 90vh; overflow-y: auto;
}
.modal-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
.modal-title  { font-size: 16px; font-weight: 500; }
.modal-close  { background: none; border: none; font-size: 18px; cursor: pointer; color: #9ca3af; line-height: 1; }
.modal-close:hover { color: #374151; }
.modal-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 20px; }

/* ── Forms ─────────────────────────────────────────────────── */
.form-row     { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.form-group   { display: flex; flex-direction: column; gap: 5px; margin-bottom: 14px; }
.form-group label { font-size: 13px; font-weight: 500; color: #374151; }
.form-group input,
.form-group select {
  border: 1px solid #e5e7eb; border-radius: 7px;
  padding: 8px 12px; font-size: 13px; outline: none;
  transition: border-color .15s;
}
.form-group input:focus,
.form-group select:focus { border-color: #93c5fd; }
.required { color: #ef4444; }
</style>

<script>
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

function openEdit(u) {
  document.getElementById('edit-id').value        = u.id_usuario;
  document.getElementById('edit-username').textContent = u.username;
  document.getElementById('edit-email').value     = u.email;
  document.getElementById('edit-nombre').value    = u.p_nombre  || '';
  document.getElementById('edit-apellido').value  = u.p_apellido || '';
  document.getElementById('edit-rol').value       = u.id_rol;
  document.getElementById('edit-status').value    = u.id_status;
  openModal('modal-editar');
}

document.querySelectorAll('.modal-bg').forEach(bg => {
  bg.addEventListener('click', e => { if (e.target === bg) bg.classList.remove('open'); });
});

function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('collapsed');
}

function filterTable(q) {
  q = q.toLowerCase();
  document.querySelectorAll('#users-table tbody tr').forEach(tr => {
    const text = tr.textContent.toLowerCase();
    tr.style.display = text.includes(q) ? '' : 'none';
  });
}
</script>

<?php layout_foot(); ?>