<?php
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/repository.php';

require_permission('reportes', 'READ');

$metrics = fetch_overview_metrics();
$recentAudit = fetch_recent_audit();
$recentUsers = fetch_recent_users();
$sessions = array_slice(fetch_sessions(['active_only' => true]), 0, 8);

layout_head('Overview', [
    'subtitle' => 'Resumen operativo del sistema, actividad reciente y estado de acceso.',
]);
?>

<section class="mb-8 grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
    <?php render_stat_card('Usuarios', (string)$metrics['usuarios'], 'text-primary', 'Total registrados'); ?>
    <?php render_stat_card('Usuarios activos', (string)$metrics['usuarios_activos'], 'text-success', 'Cuentas en estado activo'); ?>
    <?php render_stat_card('Roles', (string)$metrics['roles'], 'text-secondary', 'Perfiles administrables'); ?>
    <?php render_stat_card('Permisos', (string)$metrics['permisos'], 'text-accent', 'Reglas recurso-accion'); ?>
    <?php render_stat_card('Sesiones activas', (string)$metrics['sesiones_activas'], 'text-info', 'Sesiones maestras vigentes'); ?>
    <?php render_stat_card('Eventos hoy', (string)$metrics['eventos_hoy'], 'text-warning', 'Auditoria del dia'); ?>
</section>

<section class="grid gap-6 2xl:grid-cols-[1.2fr_0.8fr]">
    <article class="glass-panel overflow-hidden">
        <div class="panel-header">
            <div>
                <p class="kicker">Operacion</p>
                <h2 class="panel-title mt-2">Sesiones activas</h2>
            </div>
            <span class="badge badge-outline border-base-300 bg-base-100 px-3 py-3"><?= h((string)count($sessions)) ?> visibles</span>
        </div>
        <div class="overflow-x-auto">
            <table class="table table-zebra table-pin-rows">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>IP</th>
                        <th>Inicio</th>
                        <th>Ultima actividad</th>
                        <th>Access</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($sessions as $session): ?>
                    <tr>
                        <td><?= h($session['username']) ?></td>
                        <td class="font-mono text-xs"><?= h($session['ip_address']) ?></td>
                        <td><?= h(date('d/m H:i', strtotime($session['fecha_inicio']))) ?></td>
                        <td><?= h(date('d/m H:i', strtotime($session['fecha_ultima_actividad']))) ?></td>
                        <td><span class="badge badge-outline"><?= h((string)$session['access_emitidos']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$sessions): ?>
                    <tr><td colspan="5" class="text-center text-sm text-base-content/60">No hay sesiones activas.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>

    <div class="grid gap-6">
        <article class="glass-panel overflow-hidden">
            <div class="panel-header">
                <div>
                    <p class="kicker">Actividad</p>
                    <h2 class="panel-title mt-2">Usuarios recientes</h2>
                </div>
            </div>
            <div class="divide-y divide-base-300/60">
                <?php foreach ($recentUsers as $user): ?>
                <div class="px-6 py-5">
                    <p class="font-semibold"><?= h($user['username']) ?></p>
                    <p class="text-sm text-base-content/65"><?= h($user['email']) ?></p>
                    <p class="mt-1 text-xs text-base-content/50"><?= h(date('d/m/Y H:i', strtotime($user['fecha_registro']))) ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </article>

        <article class="glass-panel overflow-hidden">
            <div class="panel-header">
                <div>
                    <p class="kicker">Bitacora</p>
                    <h2 class="panel-title mt-2">Auditoria reciente</h2>
                </div>
            </div>
            <div class="divide-y divide-base-300/60">
                <?php foreach ($recentAudit as $event): ?>
                <div class="px-6 py-5">
                    <div class="flex items-center justify-between gap-2">
                        <span class="badge badge-outline"><?= h($event['accion']) ?></span>
                        <span class="text-xs text-base-content/50"><?= h(date('d/m H:i:s', strtotime($event['fecha_evento']))) ?></span>
                    </div>
                    <p class="mt-3 text-sm leading-6"><strong><?= h($event['username']) ?></strong> - <?= h((string)$event['detalle']) ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </article>
    </div>
</section>

<?php layout_foot(); ?>
