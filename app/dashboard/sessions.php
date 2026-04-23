<?php
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/repository.php';
require_once __DIR__ . '/../includes/services.php';

require_permission('reportes', 'READ');

if (is_post()) {
    try {
        require_permission('reportes', 'UPDATE');
        revoke_session(posted_int('id_sesion'));
        flash_set('success', 'Sesion revocada.');
    } catch (Throwable $exception) {
        flash_set('error', $exception->getMessage());
    }
    redirect_to('/dashboard/sessions.php');
}

$filters = [
    'user' => query_string('user'),
    'active_only' => query_string('active') === '1',
];
$sessions = fetch_sessions($filters);

layout_head('Sesiones', ['subtitle' => 'Centro operativo de sesiones maestras, refresh tokens y access emitidos.']);
?>
<article class="glass-panel overflow-hidden">
    <form method="GET" class="grid gap-3 border-b border-base-300/60 px-5 py-4 md:grid-cols-3">
        <input type="text" name="user" value="<?= h($filters['user']) ?>" class="input input-bordered" placeholder="Buscar usuario">
        <label class="label cursor-pointer justify-start gap-3">
            <input type="checkbox" name="active" value="1" class="checkbox checkbox-primary" <?= $filters['active_only'] ? 'checked' : '' ?>>
            <span class="label-text">Solo activas</span>
        </label>
        <div class="flex gap-2">
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="/dashboard/sessions.php" class="btn btn-ghost">Limpiar</a>
        </div>
    </form>
    <div class="overflow-x-auto">
        <table class="table table-zebra">
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Cliente</th>
                    <th>Inicio</th>
                    <th>Actividad</th>
                    <th>Estado</th>
                    <th>Refresh</th>
                    <th>Access</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($sessions as $session): ?>
                <tr>
                    <td>
                        <p class="font-semibold"><?= h($session['username']) ?></p>
                        <p class="font-mono text-xs text-base-content/60"><?= h($session['ip_address']) ?></p>
                    </td>
                    <td class="max-w-xs truncate text-xs"><?= h((string)$session['user_agent']) ?></td>
                    <td><?= h(date('d/m H:i', strtotime($session['fecha_inicio']))) ?></td>
                    <td><?= h(date('d/m H:i', strtotime($session['fecha_ultima_actividad']))) ?></td>
                    <td><span class="badge <?= $session['activa'] ? 'badge-success' : 'badge-error' ?>"><?= $session['activa'] ? 'Activa' : 'Revocada' ?></span></td>
                    <td><?= $session['refresh_expira'] ? h(date('d/m H:i', strtotime($session['refresh_expira']))) : '-' ?></td>
                    <td><?= h((string)$session['access_emitidos']) ?></td>
                    <td>
                        <?php if ($session['activa'] && has_permission('reportes', 'UPDATE')): ?>
                        <form method="POST" onsubmit="return confirm('Revocar sesion?')">
                            <input type="hidden" name="id_sesion" value="<?= h((string)$session['id_sesion']) ?>">
                            <button type="submit" class="btn btn-xs btn-error">Revocar</button>
                        </form>
                        <?php else: ?>
                        <span class="text-xs text-base-content/50"><?= h((string)($session['motivo_revocacion'] ?? '-')) ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$sessions): ?>
                <tr><td colspan="8" class="text-center text-base-content/60">No hay sesiones con ese filtro.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</article>
<?php layout_foot(); ?>
