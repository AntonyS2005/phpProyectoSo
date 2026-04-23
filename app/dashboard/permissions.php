<?php
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/repository.php';
require_once __DIR__ . '/../includes/services.php';

require_permission('configuracion', 'READ');

if (is_post()) {
    try {
        require_permission('configuracion', 'UPDATE');
        save_permission_matrix($_POST['permissions'] ?? []);
        flash_set('success', 'Matriz de permisos actualizada.');
    } catch (Throwable $exception) {
        flash_set('error', $exception->getMessage());
    }
    redirect_to('/dashboard/permissions.php');
}

$data = fetch_permission_matrix();
$roleSummaries = [];
foreach ($data['roles'] as $role) {
    $count = 0;
    foreach ($data['resources'] as $resource) {
        foreach ($data['actions'] as $action) {
            $resourceActionKey = $resource['id_recurso'] . ':' . $action['id_accion'];
            if (!isset($data['resource_action_map'][$resourceActionKey])) {
                continue;
            }
            $key = $role['id_rol'] . ':' . $resource['id_recurso'] . ':' . $action['id_accion'];
            if (isset($data['matrix'][$key])) {
                $count++;
            }
        }
    }
    $roleSummaries[$role['id_rol']] = $count;
}

layout_head('Permisos', ['subtitle' => 'Matriz rol x recurso x accion con enforcement real en navegacion y backend.']);
?>
<form method="POST" class="space-y-6">
    <section class="grid gap-4 lg:grid-cols-[1.05fr_0.95fr]">
        <article class="glass-panel panel-body">
            <p class="kicker">Editor</p>
            <h2 class="panel-title mt-2">Matriz de permisos por rol</h2>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-base-content/65">
                En lugar de una tabla gigante, aqui cada rol se edita por bloques de recurso. Eso hace mucho mas facil
                entender que puede hacer cada perfil sin perder el control fino por accion.
            </p>
            <div class="mt-5 flex flex-wrap gap-2">
                <a class="btn btn-outline rounded-2xl" href="/dashboard/resources.php">Editar acciones por recurso</a>
                <a class="btn btn-ghost rounded-2xl" href="/dashboard/actions.php">Administrar catalogo de acciones</a>
            </div>
        </article>
        <article class="glass-panel panel-body">
            <p class="kicker">Resumen</p>
            <div class="mt-3 grid gap-3 sm:grid-cols-3">
                <?php foreach ($data['roles'] as $role): ?>
                <div class="rounded-[1.3rem] border border-base-300/70 bg-base-100/80 p-4">
                    <p class="text-sm font-semibold"><?= h($role['nombre']) ?></p>
                    <p class="mt-2 display-title text-3xl font-extrabold"><?= h((string)$roleSummaries[$role['id_rol']]) ?></p>
                    <p class="mt-1 text-xs uppercase tracking-[0.18em] text-base-content/45">permisos activos</p>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if (has_permission('configuracion', 'UPDATE')): ?>
            <div class="mt-5 flex flex-wrap gap-2">
                <button class="btn btn-primary rounded-2xl" type="submit">Guardar cambios</button>
                <button class="btn btn-outline rounded-2xl" type="reset">Deshacer cambios</button>
                <button class="btn btn-ghost rounded-2xl" type="button" onclick="window.location.reload()">Restaurar desde BD</button>
                <button class="btn btn-ghost rounded-2xl" type="button" onclick="toggleAllPermissionCheckboxes(true)">Activar todo</button>
                <button class="btn btn-ghost rounded-2xl" type="button" onclick="toggleAllPermissionCheckboxes(false)">Limpiar todo</button>
            </div>
            <?php endif; ?>
        </article>
    </section>

    <section class="flex flex-wrap gap-2">
        <?php foreach ($data['roles'] as $role): ?>
        <a href="#role-<?= h((string)$role['id_rol']) ?>" class="btn btn-outline rounded-2xl"><?= h($role['nombre']) ?></a>
        <?php endforeach; ?>
    </section>

    <?php foreach ($data['roles'] as $role): ?>
    <section id="role-<?= h((string)$role['id_rol']) ?>" class="glass-panel overflow-hidden">
        <div class="panel-header">
            <div>
                <p class="kicker">Rol</p>
                <h2 class="panel-title mt-2"><?= h($role['nombre']) ?></h2>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="badge badge-outline border-base-300 bg-base-100 px-3 py-3"><?= h((string)$roleSummaries[$role['id_rol']]) ?> activos</span>
                <button class="btn btn-sm btn-ghost rounded-2xl" type="button" onclick="toggleRolePermissionCheckboxes(<?= h((string)$role['id_rol']) ?>, true)">Marcar rol</button>
                <button class="btn btn-sm btn-ghost rounded-2xl" type="button" onclick="toggleRolePermissionCheckboxes(<?= h((string)$role['id_rol']) ?>, false)">Limpiar rol</button>
            </div>
        </div>

        <div class="grid gap-4 p-5 xl:grid-cols-2 2xl:grid-cols-3">
            <?php foreach ($data['resources'] as $resource): ?>
            <article class="rounded-[1.45rem] border border-base-300/70 bg-base-100/80 p-5 shadow-sm">
                <div class="mb-4 flex items-start justify-between gap-3">
                    <div>
                        <p class="text-lg font-semibold"><?= h($resource['nombre']) ?></p>
                        <p class="mt-1 text-xs uppercase tracking-[0.18em] text-base-content/45">recurso</p>
                    </div>
                    <button
                        class="btn btn-xs btn-ghost rounded-xl"
                        type="button"
                        onclick="toggleResourcePermissionCheckboxes(<?= h((string)$role['id_rol']) ?>, <?= h((string)$resource['id_recurso']) ?>, true)"
                    >
                        Todo
                    </button>
                </div>

                <div class="space-y-3">
                    <?php foreach ($data['actions'] as $action): ?>
                    <?php $resourceActionKey = $resource['id_recurso'] . ':' . $action['id_accion']; ?>
                    <?php if (!isset($data['resource_action_map'][$resourceActionKey])) continue; ?>
                    <?php $key = $role['id_rol'] . ':' . $resource['id_recurso'] . ':' . $action['id_accion']; ?>
                    <label class="permission-toggle flex items-center justify-between gap-3 rounded-2xl border border-base-300/70 bg-base-200/35 px-4 py-3 transition hover:border-primary/35 hover:bg-base-200/55">
                        <div>
                            <p class="text-sm font-semibold"><?= h($action['nombre']) ?></p>
                            <p class="text-xs uppercase tracking-[0.16em] text-base-content/45"><?= h($resource['nombre']) ?></p>
                        </div>
                        <input
                            type="checkbox"
                            name="permissions[]"
                            value="<?= h($key) ?>"
                            data-role-id="<?= h((string)$role['id_rol']) ?>"
                            data-resource-id="<?= h((string)$resource['id_recurso']) ?>"
                            class="checkbox checkbox-primary permission-checkbox"
                            <?= isset($data['matrix'][$key]) ? 'checked' : '' ?>
                            <?= has_permission('configuracion', 'UPDATE') ? '' : 'disabled' ?>
                        >
                    </label>
                    <?php endforeach; ?>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endforeach; ?>
</form>

<script>
function permissionCheckboxes() {
  return Array.from(document.querySelectorAll('.permission-checkbox'));
}

function toggleAllPermissionCheckboxes(checked) {
  permissionCheckboxes().forEach((checkbox) => {
    if (checkbox.disabled) return;
    checkbox.checked = checked;
  });
}

function toggleRolePermissionCheckboxes(roleId, checked) {
  permissionCheckboxes()
    .filter((checkbox) => checkbox.dataset.roleId === String(roleId))
    .forEach((checkbox) => {
      if (checkbox.disabled) return;
      checkbox.checked = checked;
    });
}

function toggleResourcePermissionCheckboxes(roleId, resourceId, checked) {
  permissionCheckboxes()
    .filter((checkbox) =>
      checkbox.dataset.roleId === String(roleId) &&
      checkbox.dataset.resourceId === String(resourceId)
    )
    .forEach((checkbox) => {
      if (checkbox.disabled) return;
      checkbox.checked = checked;
    });
}
</script>
<?php layout_foot(); ?>
