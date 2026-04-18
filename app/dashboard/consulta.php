<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/db.php';

require_role('consulta', 'admin');   // admin también puede ver esta vista

$pdo = DB::get();

$usuarios = $pdo->query("
    SELECT u.id_usuario, u.username, u.email, u.fecha_registro, u.fecha_ultimo_acceso,
           s.nombre AS status, r.nombre AS rol,
           p.nombre AS p_nombre, p.apellido AS p_apellido
    FROM usuarios u
    JOIN cat_status s ON s.id_status = u.id_status
    JOIN roles      r ON r.id_rol    = u.id_rol
    LEFT JOIN usuarios_perfil p ON p.id_usuario = u.id_usuario
    ORDER BY u.fecha_registro DESC
")->fetchAll();

$total_activos   = count(array_filter($usuarios, fn($u) => $u['status'] === 'activo'));
$total_inactivos = count($usuarios) - $total_activos;

layout_head('Panel de Consulta');
?>

<div class="alert alert-info">
    📋 Modo <strong>solo lectura</strong> — Puedes visualizar la información pero no realizar cambios.
</div>

<!-- Resumen estadístico -->
<div class="info-grid" style="margin-bottom:1.5rem">
    <div class="card" style="margin:0;text-align:center">
        <div style="font-size:2rem;font-weight:800;color:#1a1a2e"><?= count($usuarios) ?></div>
        <div style="color:#7f8c8d;font-size:.88rem;margin-top:.25rem">Total usuarios</div>
    </div>
    <div class="card" style="margin:0;text-align:center">
        <div style="font-size:2rem;font-weight:800;color:#27ae60"><?= $total_activos ?></div>
        <div style="color:#7f8c8d;font-size:.88rem;margin-top:.25rem">Activos</div>
    </div>
    <div class="card" style="margin:0;text-align:center">
        <div style="font-size:2rem;font-weight:800;color:#e74c3c"><?= $total_inactivos ?></div>
        <div style="color:#7f8c8d;font-size:.88rem;margin-top:.25rem">Inactivos / Bloqueados</div>
    </div>
</div>

<div class="card">
    <h2>👥 Listado de usuarios</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Usuario</th>
                    <th>Nombre completo</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Status</th>
                    <th>Registrado</th>
                    <th>Último acceso</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($usuarios as $u): ?>
            <tr>
                <td><?= $u['id_usuario'] ?></td>
                <td><?= htmlspecialchars($u['username']) ?></td>
                <td><?= htmlspecialchars(trim($u['p_nombre'] . ' ' . $u['p_apellido'])) ?: '—' ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><span class="badge" style="background:#34495e"><?= htmlspecialchars($u['rol']) ?></span></td>
                <td>
                    <span class="status-<?= $u['status'] ?>">
                        <?= ucfirst($u['status']) ?>
                    </span>
                </td>
                <td><?= date('d/m/Y', strtotime($u['fecha_registro'])) ?></td>
                <td><?= $u['fecha_ultimo_acceso'] ? date('d/m/Y H:i', strtotime($u['fecha_ultimo_acceso'])) : '—' ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php layout_foot(); ?>
