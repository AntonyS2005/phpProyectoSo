<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/db.php';

require_role('ingreso', 'admin', 'consulta');   // cualquier usuario autenticado

$pdo = DB::get();
$me  = current_user();

// Cargar datos propios del usuario
$perfil = $pdo->prepare("
    SELECT u.username, u.email, u.fecha_registro, u.fecha_ultimo_acceso,
           s.nombre AS status, r.nombre AS rol,
           p.nombre AS p_nombre, p.apellido AS p_apellido, p.telefono, p.fecha_nacimiento
    FROM usuarios u
    JOIN cat_status s ON s.id_status = u.id_status
    JOIN roles      r ON r.id_rol    = u.id_rol
    LEFT JOIN usuarios_perfil p ON p.id_usuario = u.id_usuario
    WHERE u.id_usuario = ?
");
$perfil->execute([$me['id_usuario']]);
$info = $perfil->fetch();

layout_head('Mi perfil');
?>

<div class="alert alert-success">
    ✅ Acceso permitido. Bienvenido, <strong><?= htmlspecialchars($info['username']) ?></strong>.
</div>

<div class="card">
    <h2>👤 Información de mi cuenta</h2>
    <div class="info-grid">
        <div class="info-item">
            <label>Usuario</label>
            <span><?= htmlspecialchars($info['username']) ?></span>
        </div>
        <div class="info-item">
            <label>Nombre</label>
            <span><?= htmlspecialchars(trim($info['p_nombre'] . ' ' . $info['p_apellido'])) ?: '—' ?></span>
        </div>
        <div class="info-item">
            <label>Email</label>
            <span><?= htmlspecialchars($info['email']) ?></span>
        </div>
        <div class="info-item">
            <label>Teléfono</label>
            <span><?= htmlspecialchars($info['telefono'] ?? '—') ?></span>
        </div>
        <div class="info-item">
            <label>Rol asignado</label>
            <span>
                <span class="badge" style="background:#27ae60;font-size:.8rem">
                    <?= strtoupper(htmlspecialchars($info['rol'])) ?>
                </span>
            </span>
        </div>
        <div class="info-item">
            <label>Estado de cuenta</label>
            <span class="status-<?= $info['status'] ?>"><?= ucfirst($info['status']) ?></span>
        </div>
        <div class="info-item">
            <label>Fecha de registro</label>
            <span><?= date('d/m/Y H:i', strtotime($info['fecha_registro'])) ?></span>
        </div>
        <div class="info-item">
            <label>Último acceso anterior</label>
            <span><?= $info['fecha_ultimo_acceso'] ? date('d/m/Y H:i', strtotime($info['fecha_ultimo_acceso'])) : 'Primera sesión' ?></span>
        </div>
    </div>
</div>

<div class="card">
    <h2>🔒 Mis permisos</h2>
    <div class="alert alert-info">
        Tu rol de <strong><?= htmlspecialchars($info['rol']) ?></strong> te permite
        iniciar sesión y consultar tu información personal.
        Para acceso adicional, contacta a un administrador.
    </div>
</div>

<?php layout_foot(); ?>
