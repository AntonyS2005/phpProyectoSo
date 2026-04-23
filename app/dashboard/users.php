<?php
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/repository.php';
require_once __DIR__ . '/../includes/services.php';

require_permission('usuarios', 'READ');

if (is_post()) {
    try {
        if (($_POST['form_action'] ?? '') === 'delete') {
            require_permission('usuarios', 'DELETE');
            delete_user(posted_int('id_usuario'));
            flash_set('success', 'Usuario eliminado correctamente.');
        } else {
            $editingId = posted_int('id_usuario');
            if ($editingId > 0) {
                require_permission('usuarios', 'UPDATE');
                save_user_from_post($editingId);
                flash_set('success', 'Usuario actualizado correctamente.');
            } else {
                require_permission('usuarios', 'CREATE');
                save_user_from_post(null);
                flash_set('success', 'Usuario creado correctamente.');
            }
        }
    } catch (Throwable $exception) {
        flash_set('error', $exception->getMessage());
    }
    redirect_to('/dashboard/users.php');
}

$filters = [
    'search' => query_string('search'),
    'status' => query_string('status'),
    'role' => query_string('role'),
];

$roles = fetch_all_roles();
$roleOptions = fetch_role_options();
$validRoleNames = fetch_role_names();
if ($filters['role'] !== '' && !in_array($filters['role'], $validRoleNames, true)) {
    $filters['role'] = '';
}

$users = fetch_users($filters);
$statuses = fetch_all_statuses();
$editingUser = query_int('edit') > 0 ? fetch_user_by_id(query_int('edit')) : null;

layout_head('Usuarios', [
    'subtitle' => 'CRUD completo de cuentas, perfil extendido y ciclo de vida del usuario.',
]);
?>

<section class="mb-8 grid gap-4 md:grid-cols-3">
    <?php render_stat_card('Total usuarios', (string)count($users), 'text-primary', 'Resultado del filtro actual'); ?>
    <?php render_stat_card('Con permisos CREATE', has_permission('usuarios', 'CREATE') ? 'Si' : 'No', 'text-secondary', 'Capacidad de alta'); ?>
    <?php render_stat_card('Con permisos DELETE', has_permission('usuarios', 'DELETE') ? 'Si' : 'No', 'text-error', 'Capacidad de baja'); ?>
</section>

<section class="grid gap-6 2xl:grid-cols-[1.25fr_0.75fr]">
    <article class="glass-panel overflow-hidden">
        <div class="panel-header">
            <div>
                <p class="kicker">Gestion</p>
                <h2 class="panel-title mt-2">Listado de usuarios</h2>
            </div>
            <span class="badge badge-outline border-base-300 bg-base-100 px-3 py-3"><?= h((string)count($users)) ?> resultados</span>
        </div>
        <form method="GET" class="grid gap-3 border-b border-base-300/60 px-6 py-5 lg:grid-cols-5">
            <input type="text" name="search" value="<?= h($filters['search']) ?>" class="input input-bordered md:col-span-2" placeholder="Buscar por usuario, correo o nombre">
            <select name="status" class="select select-bordered">
                <option value="">Todos los estados</option>
                <?php foreach ($statuses as $status): ?>
                <option value="<?= h($status['nombre']) ?>" <?= $filters['status'] === $status['nombre'] ? 'selected' : '' ?>><?= h($status['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
            <input
                type="text"
                name="role"
                value="<?= h($filters['role']) ?>"
                list="role-filter-options"
                class="input input-bordered"
                placeholder="Rol o todos"
            >
            <datalist id="role-filter-options">
                <?php foreach ($roleOptions as $roleOption): ?>
                <option value="<?= h($roleOption['nombre']) ?>"></option>
                <?php endforeach; ?>
            </datalist>
            <div class="lg:col-span-2 flex flex-wrap gap-2">
                <button class="btn btn-primary" type="submit">Filtrar</button>
                <a class="btn btn-ghost" href="/dashboard/users.php">Limpiar</a>
            </div>
        </form>
        <div class="overflow-x-auto">
            <table class="table table-zebra table-pin-rows">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Perfil</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Ultimo acceso</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td>
                        <p class="font-semibold"><?= h($user['username']) ?></p>
                        <p class="text-xs text-base-content/60"><?= h($user['email']) ?></p>
                    </td>
                    <td>
                        <p><?= h(trim(($user['p_nombre'] ?? '') . ' ' . ($user['p_apellido'] ?? ''))) ?: '-' ?></p>
                        <p class="text-xs text-base-content/60"><?= h((string)($user['telefono'] ?? '-')) ?></p>
                    </td>
                    <td><span class="badge <?= h(role_badge_class($user['rol'])) ?>"><?= h($user['rol']) ?></span></td>
                    <td><span class="badge <?= h(status_badge_class($user['status'])) ?>"><?= h($user['status']) ?></span></td>
                    <td><?= $user['fecha_ultimo_acceso'] ? h(date('d/m/Y H:i', strtotime($user['fecha_ultimo_acceso']))) : '-' ?></td>
                    <td>
                        <div class="flex flex-wrap gap-2">
                            <?php if (has_permission('usuarios', 'UPDATE')): ?>
                            <a class="btn btn-xs btn-outline" href="/dashboard/users.php?edit=<?= h((string)$user['id_usuario']) ?>">Editar</a>
                            <?php endif; ?>
                            <?php if (has_permission('usuarios', 'DELETE') && (int)$user['id_usuario'] !== (int)current_user()['id_usuario']): ?>
                            <form method="POST" onsubmit="return confirm('Eliminar usuario?')">
                                <input type="hidden" name="form_action" value="delete">
                                <input type="hidden" name="id_usuario" value="<?= h((string)$user['id_usuario']) ?>">
                                <button class="btn btn-xs btn-error" type="submit">Eliminar</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$users): ?>
                <tr><td colspan="6" class="text-center text-base-content/60">No hay usuarios con ese filtro.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>

    <article class="glass-panel">
        <div class="panel-header !border-b-0 !pb-0">
            <div>
                <p class="kicker"><?= $editingUser ? 'Actualizacion' : 'Alta' ?></p>
                <h2 class="panel-title mt-2"><?= $editingUser ? 'Editar usuario' : 'Nuevo usuario' ?></h2>
            </div>
        </div>
        <form method="POST" class="panel-body space-y-4 pt-5">
            <input type="hidden" name="id_usuario" value="<?= h((string)($editingUser['id_usuario'] ?? '0')) ?>">
            <input type="hidden" name="id_rol" id="role-id" value="<?= h((string)($editingUser['id_rol'] ?? '')) ?>">
            <div class="grid gap-4 md:grid-cols-2">
                <label class="form-control">
                    <div class="label"><span class="label-text">Nombre</span></div>
                    <input type="text" name="nombre" class="input input-bordered" value="<?= h($editingUser['p_nombre'] ?? '') ?>">
                </label>
                <label class="form-control">
                    <div class="label"><span class="label-text">Apellido</span></div>
                    <input type="text" name="apellido" class="input input-bordered" value="<?= h($editingUser['p_apellido'] ?? '') ?>">
                </label>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <label class="form-control">
                    <div class="label"><span class="label-text">Username</span></div>
                    <input type="text" name="username" class="input input-bordered" value="<?= h($editingUser['username'] ?? '') ?>" required>
                </label>
                <label class="form-control">
                    <div class="label"><span class="label-text">Email</span></div>
                    <input type="email" name="email" class="input input-bordered" value="<?= h($editingUser['email'] ?? '') ?>" required>
                </label>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <label class="form-control">
                    <div class="label"><span class="label-text">Telefono</span></div>
                    <input type="text" name="telefono" class="input input-bordered" value="<?= h($editingUser['telefono'] ?? '') ?>">
                </label>
                <label class="form-control">
                    <div class="label"><span class="label-text">Fecha nacimiento</span></div>
                    <input type="date" name="fecha_nacimiento" class="input input-bordered" value="<?= h($editingUser['fecha_nacimiento'] ?? '') ?>">
                </label>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <label class="form-control">
                    <div class="label"><span class="label-text">Rol</span></div>
                    <input
                        type="text"
                        id="role-name"
                        list="role-form-options"
                        class="input input-bordered"
                        placeholder="Escribe o selecciona un rol"
                        value="<?= h($editingUser['rol'] ?? '') ?>"
                        required
                    >
                    <datalist id="role-form-options">
                        <?php foreach ($roleOptions as $roleOption): ?>
                        <option value="<?= h($roleOption['nombre']) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                    <p class="mt-2 text-xs text-base-content/50">Solo se aceptan roles existentes en la base de datos.</p>
                </label>
                <label class="form-control">
                    <div class="label"><span class="label-text">Estado</span></div>
                    <select name="id_status" class="select select-bordered" required>
                        <?php foreach ($statuses as $status): ?>
                        <option value="<?= h((string)$status['id_status']) ?>" <?= (int)($editingUser['id_status'] ?? 1) === (int)$status['id_status'] ? 'selected' : '' ?>><?= h($status['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            <label class="form-control">
                <div class="label"><span class="label-text"><?= $editingUser ? 'Nueva contrasena (opcional)' : 'Contrasena' ?></span></div>
                <input type="password" name="password" class="input input-bordered" <?= $editingUser ? '' : 'required' ?>>
            </label>
            <label class="label cursor-pointer justify-start gap-3">
                <input type="checkbox" name="email_verificado" class="checkbox checkbox-primary" <?= (int)($editingUser['email_verificado'] ?? 0) === 1 ? 'checked' : '' ?>>
                <span class="label-text">Email verificado</span>
            </label>
            <div class="flex flex-wrap gap-2">
                <?php if (($editingUser && has_permission('usuarios', 'UPDATE')) || (!$editingUser && has_permission('usuarios', 'CREATE'))): ?>
                <button class="btn btn-primary" type="submit"><?= $editingUser ? 'Guardar cambios' : 'Crear usuario' ?></button>
                <?php endif; ?>
                <?php if ($editingUser): ?>
                <a class="btn btn-ghost" href="/dashboard/users.php">Cancelar</a>
                <?php endif; ?>
            </div>
        </form>
    </article>
</section>

<script>
const roleNameInput = document.getElementById('role-name');
const roleIdInput = document.getElementById('role-id');
const roleMap = {
<?php foreach ($roleOptions as $roleOption): ?>
  "<?= h($roleOption['nombre']) ?>": "<?= h((string)$roleOption['id_rol']) ?>",
<?php endforeach; ?>
};

if (roleNameInput && roleIdInput) {
  const syncRoleValue = () => {
    roleIdInput.value = roleMap[roleNameInput.value] ?? '';
    if (roleIdInput.value === '') {
      roleNameInput.setCustomValidity('Debes elegir un rol existente.');
    } else {
      roleNameInput.setCustomValidity('');
    }
  };

  roleNameInput.addEventListener('input', syncRoleValue);
  roleNameInput.addEventListener('change', syncRoleValue);
  syncRoleValue();
}
</script>

<?php layout_foot(); ?>
