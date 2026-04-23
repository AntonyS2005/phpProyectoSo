<?php
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/repository.php';
require_once __DIR__ . '/../includes/services.php';

require_permission('configuracion', 'READ');

if (is_post()) {
    try {
        if (($_POST['form_action'] ?? '') === 'delete') {
            require_permission('configuracion', 'DELETE');
            $id = posted_int('id_recurso');
            $stmt = DB::get()->prepare("SELECT COUNT(*) FROM permisos WHERE id_recurso = ?");
            $stmt->execute([$id]);
            if ((int)$stmt->fetchColumn() > 0) {
                throw new RuntimeException('No puedes eliminar un recurso con permisos asociados.');
            }
            delete_catalog_record('cat_recurso', 'id_recurso', $id);
            audit((int)current_user()['id_usuario'], 'DELETE_RESOURCE', "Eliminacion de recurso {$id}");
            flash_set('success', 'Recurso eliminado.');
        } elseif (($_POST['form_action'] ?? '') === 'actions') {
            require_permission('configuracion', 'UPDATE');
            $id = posted_int('id_recurso');
            save_resource_actions($id, $_POST['action_ids'] ?? []);
            flash_set('success', 'Acciones del recurso actualizadas.');
        } else {
            $id = posted_int('id_recurso');
            $nombre = posted_string('nombre');
            $descripcion = posted_string('descripcion');
            if ($nombre === '') {
                throw new RuntimeException('El nombre del recurso es obligatorio.');
            }
            if ($id > 0) {
                require_permission('configuracion', 'UPDATE');
                save_catalog_record('cat_recurso', 'id_recurso', $id, ['nombre' => $nombre, 'descripcion' => $descripcion ?: null]);
                audit((int)current_user()['id_usuario'], 'UPDATE_RESOURCE', "Actualizacion de recurso {$id}");
                flash_set('success', 'Recurso actualizado.');
            } else {
                require_permission('configuracion', 'CREATE');
                save_catalog_record('cat_recurso', 'id_recurso', null, ['nombre' => $nombre, 'descripcion' => $descripcion ?: null]);
                audit((int)current_user()['id_usuario'], 'CREATE_RESOURCE', "Creacion de recurso {$nombre}");
                flash_set('success', 'Recurso creado.');
            }
        }
    } catch (Throwable $exception) {
        flash_set('error', $exception->getMessage());
    }
    redirect_to('/dashboard/resources.php');
}

$resources = fetch_all_resources();
$actions = fetch_all_actions();
$editing = null;
$editingActions = [];
if (query_int('edit') > 0) {
    $stmt = DB::get()->prepare("SELECT * FROM cat_recurso WHERE id_recurso = ?");
    $stmt->execute([query_int('edit')]);
    $editing = $stmt->fetch();
    if ($editing) {
        $editingActions = array_column(fetch_actions_for_resource((int)$editing['id_recurso']), 'id_accion');
    }
}

layout_head('Recursos', ['subtitle' => 'Objetos funcionales del sistema usados por la matriz de permisos.']);
?>
<section class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
    <article class="glass-panel overflow-hidden">
        <div class="border-b border-base-300/70 px-5 py-4">
            <h2 class="display-title text-xl font-bold">Recursos</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="table table-zebra">
                <thead><tr><th>Nombre</th><th>Descripcion</th><th>Acciones</th><th>Permisos</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($resources as $resource): ?>
                <tr>
                    <td><?= h($resource['nombre']) ?></td>
                    <td><?= h((string)$resource['descripcion']) ?></td>
                    <td><?= h((string)$resource['total_acciones']) ?></td>
                    <td><?= h((string)$resource['total_permisos']) ?></td>
                    <td>
                        <?php if (has_permission('configuracion', 'UPDATE')): ?><a class="btn btn-xs btn-outline" href="/dashboard/resources.php?edit=<?= h((string)$resource['id_recurso']) ?>">Editar</a><?php endif; ?>
                        <?php if (has_permission('configuracion', 'DELETE') && (int)$resource['total_permisos'] === 0): ?>
                        <form method="POST" class="inline" onsubmit="return confirm('Eliminar recurso?')">
                            <input type="hidden" name="form_action" value="delete">
                            <input type="hidden" name="id_recurso" value="<?= h((string)$resource['id_recurso']) ?>">
                            <button class="btn btn-xs btn-error" type="submit">Eliminar</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>
    <article class="glass-panel">
        <h2 class="display-title text-xl font-bold"><?= $editing ? 'Editar recurso' : 'Nuevo recurso' ?></h2>
        <form method="POST" class="mt-5 space-y-4">
            <input type="hidden" name="id_recurso" value="<?= h((string)($editing['id_recurso'] ?? 0)) ?>">
            <label class="form-control">
                <div class="label"><span class="label-text">Nombre</span></div>
                <input type="text" name="nombre" class="input input-bordered" value="<?= h($editing['nombre'] ?? '') ?>" required>
            </label>
            <label class="form-control">
                <div class="label"><span class="label-text">Descripcion</span></div>
                <textarea name="descripcion" class="textarea textarea-bordered"><?= h($editing['descripcion'] ?? '') ?></textarea>
            </label>
            <div class="flex gap-2">
                <button class="btn btn-primary" type="submit"><?= $editing ? 'Guardar' : 'Crear' ?></button>
                <?php if ($editing): ?><a href="/dashboard/resources.php" class="btn btn-ghost">Cancelar</a><?php endif; ?>
            </div>
        </form>

        <?php if ($editing): ?>
        <div class="mt-8 border-t border-base-300/70 pt-6">
            <h3 class="display-title text-lg font-bold">Acciones permitidas para este recurso</h3>
            <p class="mt-2 text-sm leading-6 text-base-content/65">
                Aqui defines que acciones son validas para este recurso. La pantalla de permisos solo mostrara estas combinaciones.
            </p>
            <div class="mt-4 flex flex-wrap gap-2">
                <button class="btn btn-xs btn-ghost rounded-xl" type="button" onclick="toggleResourceActionCheckboxes(true)">Marcar todo</button>
                <button class="btn btn-xs btn-ghost rounded-xl" type="button" onclick="toggleResourceActionCheckboxes(false)">Limpiar</button>
            </div>
            <form method="POST" class="mt-5 space-y-3">
                <input type="hidden" name="form_action" value="actions">
                <input type="hidden" name="id_recurso" value="<?= h((string)$editing['id_recurso']) ?>">
                <?php foreach ($actions as $action): ?>
                <label class="permission-toggle flex items-center justify-between gap-3 rounded-2xl border border-base-300/70 bg-base-200/35 px-4 py-3 transition hover:border-primary/35 hover:bg-base-200/55">
                    <div>
                        <p class="text-sm font-semibold"><?= h($action['nombre']) ?></p>
                        <p class="text-xs text-base-content/50"><?= h((string)$action['descripcion']) ?></p>
                    </div>
                    <input
                        type="checkbox"
                        name="action_ids[]"
                        value="<?= h((string)$action['id_accion']) ?>"
                        class="checkbox checkbox-primary"
                        <?= in_array((int)$action['id_accion'], array_map('intval', $editingActions), true) ? 'checked' : '' ?>
                    >
                </label>
                <?php endforeach; ?>
                <div class="flex gap-2 pt-2">
                    <button class="btn btn-secondary" type="submit">Guardar acciones</button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </article>
</section>
<?php if ($editing): ?>
<script>
function toggleResourceActionCheckboxes(checked) {
  document.querySelectorAll('input[name="action_ids[]"]').forEach((checkbox) => {
    checkbox.checked = checked;
  });
}
</script>
<?php endif; ?>
<?php layout_foot(); ?>
