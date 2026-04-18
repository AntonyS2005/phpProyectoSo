<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';
session_init();

// Si ya hay sesión, redirigir al dashboard
if (!empty($_SESSION['user'])) {
    $rol = strtolower($_SESSION['user']['rol']);
    header("Location: /dashboard/{$rol}.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$username || !$password) {
        $error = 'Por favor completa todos los campos.';
    } else {
        $user = login($username, $password);

        if ($user === false) {
            $error = '❌ Credenciales incorrectas o usuario inactivo.';
            // Auditar intento fallido sin id de usuario
            audit(0, 'LOGIN_FAIL', "Intento fallido para: $username");
        } else {
            $_SESSION['user'] = [
                'id_usuario' => $user['id_usuario'],
                'username'   => $user['username'],
                'email'      => $user['email'],
                'rol'        => $user['rol'],
                'id_rol'     => $user['id_rol'],
            ];
            $rol = strtolower($user['rol']);
            header("Location: /dashboard/{$rol}.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Login as a Service</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
<div class="container login-wrap">
    <div class="login-logo">🔐</div>
    <h1 style="text-align:center;margin-bottom:.25rem">Login as a Service</h1>
    <p class="login-subtitle">Sistema de autenticación y privilegios</p>

    <div class="card">
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="/login.php">
            <div class="form-group">
                <label for="username">Usuario o Email</label>
                <input type="text" id="username" name="username"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                       placeholder="usuario o email@ejemplo.com"
                       autocomplete="username" required>
            </div>
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password"
                       placeholder="••••••••"
                       autocomplete="current-password" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;margin-top:.5rem">
                Iniciar sesión
            </button>
        </form>
    </div>

    <p style="text-align:center;font-size:.8rem;color:#aaa;margin-top:1rem">
        Usuarios de prueba — contraseña: <strong>Test1234!</strong><br>
        admin | consulta | ingreso
    </p>
</div>
<footer class="footer">Login as a Service &copy; <?= date('Y') ?></footer>
</body>
</html>
