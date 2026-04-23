<?php
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/repository.php';
require_once __DIR__ . '/../includes/services.php';

require_permission('configuracion', 'READ');

if (is_post()) {
    try {
        if (($_POST['form_action'] ?? '') === 'delete') {
            require_permission('configuracion', 'DELETE');
            $id = posted_int('id_status');
            $stmt = DB::get()->prepare("SELECT COUNT(*) FROM usuarios WHERE id_status = ?");
            $stmt->execute([$id]);
            if ((int)$stmt->fetchColumn() > 0) {
                throw new RuntimeException('No puedes eliminar un estado que ya esta asignado a usuarios.');
            }
            delete_catalog_record('cat_status', 'id_status', $id);
            audit((int)current_user()['id_usuario'], 'DELETE_STATUS', "Eliminacion de estado {$id}");
            flash_set('success', 'Estado eliminado.');
        } else {
            $id = posted_int('id_status');
            $nombre = posted_string('nombre');
            if ($nombre === '') {
                throw new RuntimeException('El nombre del estado es obligatorio.');
            }
            if ($id > 0) {
                require_permission('configuracion', 'UPDATE');
                save_catalog_record('cat_status', 'id_status', $id, ['nombre' => $nombre]);
                audit((int)current_user()['id_usuario'], 'UPDATE_STATUS', "Actualizacion de estado {$id}");
                flash_set('success', 'Estado actualizado.');
            } else {
                require_permission('configuracion', 'CREATE');
                save_catalog_record('cat_status', 'id_status', null, ['nombre' => $nombre]);
                audit((int)current_user()['id_usuario'], 'CREATE_STATUS', "Creacion de estado {$nombre}");
                flash_set('success', 'Estado creado.');
            }
        }
    } catch (Throwable $exception) {
        flash_set('error', $exception->getMessage());
    }
    redirect_to('/dashboard/statuses.php');
}

$statuses = DB::get()->query("
    SELECT s.*, COUNT(u.id_usuario) AS total_usuarios
    FROM cat_status s
    LEFT JOIN usuarios u ON u.id_status = s.id_status
    GROUP BY s.id_status
    ORDER BY s.nombre
")->fetchAll();
$editing = query_int('edit') > 0 ? DB::get()->prepare("SELECT * FROM cat_status WHERE id_status = ?") : null;
if ($editing) {
    $editing->execute([query_int('edit')]);
    $editing = $editing->fetch();
}

layout_head('Estados', ['subtitle' => 'Catalogo de estados de usuario y reglas de uso.']);
?>

<section class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
    <article class="glass-panel overflow-hidden">
        <div class="border-b border-base-300/70 px-5 py-4">
            <h2 class="display-title text-xl font-bold">Estados registrados</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="table table-zebra">
                <thead><tr><th>Nombre</th><th>Usuarios</th><th>Acciones</th></tr></thead>
                <tbody>
                <?php foreach ($statuses as $status): ?>
                <tr>
                    <td><span class="badge <?= h(status_badge_class($status['nombre'])) ?>"><?= h($status['nombre']) ?></span></td>
                    <td><?= h((string)$status['total_usuarios']) ?></td>
                    <td>
                        <?php if (has_permission('configuracion', 'UPDATE')): ?>
                        <a href="/dashboard/statuses.php?edit=<?= h((string)$status['id_status']) ?>" class="btn btn-xs btn-outline">Editar</a>
                        <?php endif; ?>
                        <?php if (has_permission('configuracion', 'DELETE') && (int)$status['total_usuarios'] === 0): ?>
                        <form method="POST" class="inline" onsubmit="return confirm('Eliminar estado?')">
                            <input type="hidden" name="form_action" value="delete">
                            <input type="hidden" name="id_status" value="<?= h((string)$status['id_status']) ?>">
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
        <h2 class="display-title text-xl font-bold"><?= $editing ? 'Editar estado' : 'Nuevo estado' ?></h2>
        <form method="POST" class="mt-5 space-y-4">
            <input type="hidden" name="id_status" value="<?= h((string)($editing['id_status'] ?? 0)) ?>">
            <label class="form-control">
                <div class="label"><span class="label-text">Nombre</span></div>
                <input type="text" name="nombre" class="input input-bordered" value="<?= h($editing['nombre'] ?? '') ?>" required>
            </label>
            <div class="flex gap-2">
                <button class="btn btn-primary" type="submit"><?= $editing ? 'Guardar' : 'Crear' ?></button>
                <?php if ($editing): ?><a href="/dashboard/statuses.php" class="btn btn-ghost">Cancelar</a><?php endif; ?>
            </div>
        </form>
    </article>
</section>

<?php layout_foot(); ?>
