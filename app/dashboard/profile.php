<?php
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/repository.php';

require_permission('usuarios', 'READ');

$userId = (int)current_user()['id_usuario'];

if (is_post()) {
    try {
        $pdo = DB::get();
        $pdo->beginTransaction();

        $email = posted_string('email');
        $nombre = posted_string('nombre');
        $apellido = posted_string('apellido');
        $telefono = posted_string('telefono');
        $fechaNacimiento = posted_nullable_date('fecha_nacimiento');
        $newPassword = (string)($_POST['password'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('El correo no tiene un formato valido.');
        }

        if ($newPassword !== '') {
            $pdo->prepare("UPDATE usuarios SET email = ?, password_hash = ? WHERE id_usuario = ?")
                ->execute([$email, password_hash($newPassword, PASSWORD_BCRYPT), $userId]);
            audit($userId, 'CHANGE_PASSWORD', 'Cambio de contrasena desde perfil');
        } else {
            $pdo->prepare("UPDATE usuarios SET email = ? WHERE id_usuario = ?")
                ->execute([$email, $userId]);
        }

        $pdo->prepare("
            INSERT INTO usuarios_perfil (id_usuario, nombre, apellido, telefono, fecha_nacimiento)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                nombre = VALUES(nombre),
                apellido = VALUES(apellido),
                telefono = VALUES(telefono),
                fecha_nacimiento = VALUES(fecha_nacimiento)
        ")->execute([$userId, $nombre ?: null, $apellido ?: null, $telefono ?: null, $fechaNacimiento]);

        $pdo->commit();
        $_SESSION['user']['email'] = $email;
        flash_set('success', 'Perfil actualizado correctamente.');
    } catch (Throwable $exception) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        flash_set('error', $exception->getMessage());
    }
    redirect_to('/dashboard/profile.php');
}

$profile = fetch_user_by_id($userId);
$mySessions = array_values(array_filter(fetch_sessions(), fn($session) => (int)$session['id_usuario'] === $userId));

layout_head('Mi Perfil', ['subtitle' => 'Gestion personal de datos, contrasena y sesiones propias.']);
?>
<section class="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
    <article class="glass-panel">
        <h2 class="display-title text-xl font-bold">Datos personales</h2>
        <form method="POST" class="mt-5 space-y-4">
            <div class="grid gap-4 md:grid-cols-2">
                <label class="form-control">
                    <div class="label"><span class="label-text">Nombre</span></div>
                    <input type="text" name="nombre" class="input input-bordered" value="<?= h($profile['p_nombre'] ?? '') ?>">
                </label>
                <label class="form-control">
                    <div class="label"><span class="label-text">Apellido</span></div>
                    <input type="text" name="apellido" class="input input-bordered" value="<?= h($profile['p_apellido'] ?? '') ?>">
                </label>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <label class="form-control">
                    <div class="label"><span class="label-text">Email</span></div>
                    <input type="email" name="email" class="input input-bordered" value="<?= h($profile['email'] ?? '') ?>" required>
                </label>
                <label class="form-control">
                    <div class="label"><span class="label-text">Telefono</span></div>
                    <input type="text" name="telefono" class="input input-bordered" value="<?= h($profile['telefono'] ?? '') ?>">
                </label>
            </div>
            <label class="form-control">
                <div class="label"><span class="label-text">Fecha nacimiento</span></div>
                <input type="date" name="fecha_nacimiento" class="input input-bordered" value="<?= h($profile['fecha_nacimiento'] ?? '') ?>">
            </label>
            <label class="form-control">
                <div class="label"><span class="label-text">Nueva contrasena</span></div>
                <input type="password" name="password" class="input input-bordered">
            </label>
            <button class="btn btn-primary" type="submit">Guardar perfil</button>
        </form>
    </article>

    <article class="glass-panel overflow-hidden">
        <div class="border-b border-base-300/70 px-5 py-4">
            <h2 class="display-title text-xl font-bold">Mis sesiones</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="table table-zebra">
                <thead>
                    <tr>
                        <th>IP</th>
                        <th>Inicio</th>
                        <th>Actividad</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($mySessions as $session): ?>
                <tr>
                    <td class="font-mono text-xs"><?= h($session['ip_address']) ?></td>
                    <td><?= h(date('d/m/Y H:i', strtotime($session['fecha_inicio']))) ?></td>
                    <td><?= h(date('d/m/Y H:i', strtotime($session['fecha_ultima_actividad']))) ?></td>
                    <td><span class="badge <?= $session['activa'] ? 'badge-success' : 'badge-error' ?>"><?= $session['activa'] ? 'Activa' : 'Revocada' ?></span></td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$mySessions): ?>
                <tr><td colspan="4" class="text-center text-base-content/60">No hay sesiones registradas.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>
<?php layout_foot(); ?>
