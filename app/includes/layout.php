<?php
// ── Helper: abrir layout ──────────────────────────────────────
function layout_head(string $title): void {
    $u = current_user();
    $rol = $u['rol'] ?? '';
    $username = htmlspecialchars($u['username'] ?? '');

    $badge_color = match(strtolower($rol)) {
        'admin'    => '#e74c3c',
        'consulta' => '#2980b9',
        default    => '#27ae60',
    };
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> — Login as a Service</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
<?php if ($u): ?>
<nav class="navbar">
    <span class="brand">🔐 Login as a Service</span>
    <div class="nav-right">
        <span class="badge" style="background:<?= $badge_color ?>">
            <?= strtoupper(htmlspecialchars($rol)) ?>
        </span>
        <span class="nav-user">👤 <?= $username ?></span>
        <a href="/logout.php" class="btn-logout">Salir</a>
    </div>
</nav>
<?php endif; ?>
<main class="container">
    <h1><?= htmlspecialchars($title) ?></h1>
    <?php
}

function layout_foot(): void {
    ?>
</main>
<footer class="footer">Login as a Service &copy; <?= date('Y') ?></footer>
</body>
</html>
    <?php
}
