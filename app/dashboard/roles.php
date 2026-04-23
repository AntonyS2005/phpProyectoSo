<?php
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/repository.php';
require_once __DIR__ . '/../includes/services.php';

require_permission('configuracion', 'READ');

if (is_post()) {
    try {
        if (($_POST['form_action'] ?? '') === 'delete') {
            require_permission('configuracion', 'DELETE');
            $id = posted_int('id_rol');
            $stmt = DB::get()->prepare("SELECT COUNT(*) FROM usuarios WHERE id_rol = ?");
            $stmt->execute([$id]);
            if ((int)$stmt->fetchColumn() > 0) {
                throw new RuntimeException('No puedes eliminar un rol asignado a usuarios.');
            }
            delete_catalog_record('roles', 'id_rol', $id);
            audit((int)current_user()['id_usuario'], 'DELETE_ROLE', "Eliminacion de rol {$id}");
            flash_set('success', 'Rol eliminado.');
        } else {
            $id = posted_int('id_rol');
            $nombre = posted_string('nombre');
            $descripcion = posted_string('descripcion');
            if ($nombre === '') {
                throw new RuntimeException('El nombre del rol es obligatorio.');
            }
            if ($id > 0) {
                require_permission('configuracion', 'UPDATE');
                save_catalog_record('roles', 'id_rol', $id, ['nombre' => $nombre, 'descripcion' => $descripcion ?: null]);
                audit((int)current_user()['id_usuario'], 'UPDATE_ROLE', "Actualizacion de rol {$id}");
                flash_set('success', 'Rol actualizado.');
            } else {
                require_permission('configuracion', 'CREATE');
                save_catalog_record('roles', 'id_rol', null, ['nombre' => $nombre, 'descripcion' => $descripcion ?: null]);
                audit((int)current_user()['id_usuario'], 'CREATE_ROLE', "Creacion de rol {$nombre}");
                flash_set('success', 'Rol creado.');
            }
        }
    } catch (Throwable $exception) {
        flash_set('error', $exception->getMessage());
    }
    redirect_to('/dashboard/roles.php');
}

$roles = fetch_all_roles();
$editing = null;
if (query_int('edit') > 0) {
    $stmt = DB::get()->prepare("SELECT * FROM roles WHERE id_rol = ?");
    $stmt->execute([query_int('edit')]);
    $editing = $stmt->fetch();
}

layout_head('Roles', ['subtitle' => 'Catalogo de roles y resumen de impacto por permisos y usuarios.']);
?>
<section class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
    <article class="glass-panel overflow-hidden">
        <div class="border-b border-base-300/70 px-5 py-4">
            <h2 class="display-title text-xl font-bold">Roles registrados</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="table table-zebra">
                <thead><tr><th>Rol</th><th>Descripcion</th><th>Permisos</th><th>Usuarios</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($roles as $role): ?>
                <tr>
                    <td><span class="badge <?= h(role_badge_class($role['nombre'])) ?>"><?= h($role['nombre']) ?></span></td>
                    <td><?= h((string)$role['descripcion']) ?></td>
                    <td><?= h((string)$role['total_permisos']) ?></td>
                    <td><?= h((string)$role['total_usuarios']) ?></td>
                    <td>
                        <?php if (has_permission('configuracion', 'UPDATE')): ?><a class="btn btn-xs btn-outline" href="/dashboard/roles.php?edit=<?= h((string)$role['id_rol']) ?>">Editar</a><?php endif; ?>
                        <?php if (has_permission('configuracion', 'DELETE') && (int)$role['total_usuarios'] === 0): ?>
                        <form method="POST" class="inline" onsubmit="return confirm('Eliminar rol?')">
                            <input type="hidden" name="form_action" value="delete">
                            <input type="hidden" name="id_rol" value="<?= h((string)$role['id_rol']) ?>">
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
        <h2 class="display-title text-xl font-bold"><?= $editing ? 'Editar rol' : 'Nuevo rol' ?></h2>
        <form method="POST" class="mt-5 space-y-4">
            <input type="hidden" name="id_rol" value="<?= h((string)($editing['id_rol'] ?? 0)) ?>">
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
                <?php if ($editing): ?><a href="/dashboard/roles.php" class="btn btn-ghost">Cancelar</a><?php endif; ?>
            </div>
        </form>
    </article>
</section>
<?php layout_foot(); ?>
