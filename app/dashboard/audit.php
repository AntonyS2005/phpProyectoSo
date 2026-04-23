<?php
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/repository.php';

require_permission('reportes', 'READ');

$filters = [
    'user' => query_string('user'),
    'action' => query_string('action'),
    'ip' => query_string('ip'),
    'from' => query_string('from'),
    'to' => query_string('to'),
];

$events = fetch_audit($filters);

layout_head('Auditoria', ['subtitle' => 'Bitacora read-only con filtros por usuario, accion, IP y fecha.']);
?>
<article class="glass-panel overflow-hidden">
    <form method="GET" class="grid gap-3 border-b border-base-300/60 px-5 py-4 md:grid-cols-5">
        <input type="text" name="user" value="<?= h($filters['user']) ?>" class="input input-bordered" placeholder="Usuario">
        <input type="text" name="action" value="<?= h($filters['action']) ?>" class="input input-bordered" placeholder="Accion">
        <input type="text" name="ip" value="<?= h($filters['ip']) ?>" class="input input-bordered" placeholder="IP">
        <input type="date" name="from" value="<?= h($filters['from']) ?>" class="input input-bordered">
        <input type="date" name="to" value="<?= h($filters['to']) ?>" class="input input-bordered">
        <div class="md:col-span-5 flex gap-2">
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="/dashboard/audit.php" class="btn btn-ghost">Limpiar</a>
        </div>
    </form>
    <div class="overflow-x-auto">
        <table class="table table-zebra">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Usuario</th>
                    <th>Accion</th>
                    <th>Detalle</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($events as $event): ?>
                <tr>
                    <td><?= h(date('d/m/Y H:i:s', strtotime($event['fecha_evento']))) ?></td>
                    <td><?= h($event['username']) ?></td>
                    <td><span class="badge badge-outline"><?= h($event['accion']) ?></span></td>
                    <td><?= h((string)$event['detalle']) ?></td>
                    <td class="font-mono text-xs"><?= h((string)$event['ip_address']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$events): ?>
                <tr><td colspan="5" class="text-center text-base-content/60">No hay eventos con ese filtro.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</article>
<?php layout_foot(); ?>
