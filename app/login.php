<?php
require_once __DIR__ . '/includes/app.php';
session_init();

if (!empty($_SESSION['user'])) {
    redirect_to(app_home_path());
}

$error = isset($_GET['expired']) ? 'Tu sesion expiro. Inicia sesion nuevamente.' : '';

if (is_post()) {
    $username = posted_string('username');
    $password = (string)($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Completa usuario y contrasena.';
    } else {
        $user = login($username, $password);
        if ($user === false) {
            $error = 'Credenciales incorrectas o usuario inactivo.';
            audit(0, 'LOGIN_FAIL', "Intento fallido para {$username}");
        } else {
            start_authenticated_session($user);
            redirect_to(app_home_path());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es" data-theme="laas">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Login as a Service</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="min-h-screen bg-base-100">
<main class="grid min-h-screen place-items-center px-4 py-10">
    <section class="grid w-full max-w-[1380px] overflow-hidden rounded-[2.2rem] border border-base-300/70 bg-base-100 shadow-[0_30px_90px_rgba(18,26,34,0.14)] lg:grid-cols-[1.15fr_0.85fr]">
        <div class="relative hidden overflow-hidden bg-neutral p-10 text-neutral-content lg:block">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_15%_20%,rgba(255,255,255,0.12),transparent_35%),radial-gradient(circle_at_85%_75%,rgba(13,148,136,0.30),transparent_30%)]"></div>
            <div class="relative z-10 flex h-full flex-col justify-between">
                <div>
                    <p class="display-title max-w-xl text-6xl font-extrabold leading-[0.9]">Seguridad, gobierno y operacion en una sola app PHP.</p>
                    <p class="mt-6 max-w-lg text-sm leading-7 text-neutral-content/80">
                        Usuarios, permisos, sesiones, tokens y auditoria con un shell responsive construido sobre la base real del sistema.
                    </p>
                </div>
                <div class="grid gap-3 text-sm text-neutral-content/75">
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                        <p class="font-semibold">Demo</p>
                        <p>Usuario: admin</p>
                        <p>Password: Test1234!</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="p-6 sm:p-10 lg:p-12 xl:p-14">
            <div class="mb-8">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-primary">Login as a Service</p>
                <h1 class="display-title mt-3 text-4xl font-extrabold">Iniciar sesion</h1>
                <p class="mt-2 max-w-md text-sm leading-6 text-base-content/70">Accede a tu panel segun permisos, sesiones activas y modulos operativos disponibles.</p>
            </div>

            <?php if ($error !== ''): ?>
            <div class="alert alert-error mb-6">
                <span><?= h($error) ?></span>
            </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <label class="form-control">
                    <div class="label"><span class="label-text font-semibold">Usuario o correo</span></div>
                    <input type="text" name="username" value="<?= old_input('username') ?>" class="input input-bordered w-full" autocomplete="username" required>
                </label>
                <label class="form-control">
                    <div class="label"><span class="label-text font-semibold">Contrasena</span></div>
                    <input type="password" name="password" class="input input-bordered w-full" autocomplete="current-password" required>
                </label>
                <button type="submit" class="btn btn-primary w-full">Entrar al sistema</button>
            </form>

            <div class="mt-8 rounded-[1.4rem] border border-base-300/70 bg-base-200/70 p-5 text-sm text-base-content/75">
                <p class="font-semibold">Usuarios de prueba</p>
                <p class="mt-1">admin, consulta, ingreso</p>
            </div>
        </div>
    </section>
</main>
</body>
</html>
