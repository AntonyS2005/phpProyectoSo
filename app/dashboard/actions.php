<?php
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/repository.php';
require_once __DIR__ . '/../includes/services.php';

require_permission('configuracion', 'READ');

if (is_post()) {
    try {
        if (($_POST['form_action'] ?? '') === 'delete') {
            require_permission('configuracion', 'DELETE');
            $id = posted_int('id_accion');
            $stmt = DB::get()->prepare("SELECT COUNT(*) FROM recurso_accion WHERE id_accion = ?");
            $stmt->execute([$id]);
            if ((int)$stmt->fetchColumn() > 0) {
                throw new RuntimeException('No puedes eliminar una accion asignada a recursos.');
            }
            $stmt = DB::get()->prepare("SELECT COUNT(*) FROM permisos WHERE id_accion = ?");
            $stmt->execute([$id]);
            if ((int)$stmt->fetchColumn() > 0) {
                throw new RuntimeException('No puedes eliminar una accion asociada a permisos.');
            }
            delete_catalog_record('cat_accion', 'id_accion', $id);
            audit((int)current_user()['id_usuario'], 'DELETE_ACTION', "Eliminacion de accion {$id}");
            flash_set('success', 'Accion eliminada.');
        } else {
            $id = posted_int('id_accion');
            $nombre = strtoupper(posted_string('nombre'));
            $descripcion = posted_string('descripcion');
            if ($nombre === '') {
                throw new RuntimeException('El nombre de la accion es obligatorio.');
            }
            if ($id > 0) {
                require_permission('configuracion', 'UPDATE');
                save_catalog_record('cat_accion', 'id_accion', $id, ['nombre' => $nombre, 'descripcion' => $descripcion ?: null]);
                audit((int)current_user()['id_usuario'], 'UPDATE_ACTION', "Actualizacion de accion {$id}");
                flash_set('success', 'Accion actualizada.');
            } else {
                require_permission('configuracion', 'CREATE');
                save_catalog_record('cat_accion', 'id_accion', null, ['nombre' => $nombre, 'descripcion' => $descripcion ?: null]);
                audit((int)current_user()['id_usuario'], 'CREATE_ACTION', "Creacion de accion {$nombre}");
                flash_set('success', 'Accion creada.');
            }
        }
    } catch (Throwable $exception) {
        flash_set('error', $exception->getMessage());
    }
    redirect_to('/dashboard/actions.php');
}

$actions = fetch_all_actions();
$editing = null;
if (query_int('edit') > 0) {
    $stmt = DB::get()->prepare("SELECT * FROM cat_accion WHERE id_accion = ?");
    $stmt->execute([query_int('edit')]);
    $editing = $stmt->fetch();
}

layout_head('Acciones', ['subtitle' => 'Operaciones permitidas sobre cada recurso del sistema.']);
?>
<section class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
    <article class="glass-panel overflow-hidden">
        <div class="border-b border-base-300/70 px-5 py-4">
            <h2 class="display-title text-xl font-bold">Acciones</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="table table-zebra">
                <thead><tr><th>Accion</th><th>Descripcion</th><th>Recursos</th><th>Permisos</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($actions as $action): ?>
                <tr>
                    <td><span class="badge badge-outline"><?= h($action['nombre']) ?></span></td>
                    <td><?= h((string)$action['descripcion']) ?></td>
                    <td><?= h((string)$action['total_recursos']) ?></td>
                    <td><?= h((string)$action['total_permisos']) ?></td>
                    <td>
                        <?php if (has_permission('configuracion', 'UPDATE')): ?><a class="btn btn-xs btn-outline" href="/dashboard/actions.php?edit=<?= h((string)$action['id_accion']) ?>">Editar</a><?php endif; ?>
                        <?php if (has_permission('configuracion', 'DELETE') && (int)$action['total_permisos'] === 0 && (int)$action['total_recursos'] === 0): ?>
                        <form method="POST" class="inline" onsubmit="return confirm('Eliminar accion?')">
                            <input type="hidden" name="form_action" value="delete">
                            <input type="hidden" name="id_accion" value="<?= h((string)$action['id_accion']) ?>">
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
        <h2 class="display-title text-xl font-bold"><?= $editing ? 'Editar accion' : 'Nueva accion' ?></h2>
        <form method="POST" class="mt-5 space-y-4">
            <input type="hidden" name="id_accion" value="<?= h((string)($editing['id_accion'] ?? 0)) ?>">
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
                <?php if ($editing): ?><a href="/dashboard/actions.php" class="btn btn-ghost">Cancelar</a><?php endif; ?>
            </div>
        </form>
    </article>
</section>
<?php layout_foot(); ?>
