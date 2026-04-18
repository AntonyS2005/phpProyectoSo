<?php
// ⚠️ SOLO PARA PRUEBAS — eliminar antes de entregar
require_once __DIR__ . '/includes/db.php';

echo "<style>
body { font-family: monospace; padding: 2rem; background: #1a1a2e; color: #eee; }
h2 { color: #3498db; margin-top: 2rem; }
.ok  { color: #2ecc71; font-weight: bold; }
.err { color: #e74c3c; font-weight: bold; }
.box { background: #16213e; padding: 1rem; border-radius: 8px; margin: .5rem 0; }
table { border-collapse: collapse; width: 100%; }
th, td { padding: .4rem .8rem; border: 1px solid #333; text-align: left; }
th { background: #0f3460; }
</style>";

echo "<h1>🔍 Debug — Login as a Service</h1>";

// ── 1. Variables de entorno ───────────────────────────────────
echo "<h2>1. Variables de entorno</h2><div class='box'>";
echo "DB_HOST: <strong>" . (getenv('DB_HOST') ?: '❌ no definido') . "</strong><br>";
echo "DB_PORT: <strong>" . (getenv('DB_PORT') ?: '❌ no definido') . "</strong><br>";
echo "DB_NAME: <strong>" . (getenv('DB_NAME') ?: '❌ no definido') . "</strong><br>";
echo "DB_USER: <strong>" . (getenv('DB_USER') ?: '❌ no definido') . "</strong><br>";
echo "DB_PASS: <strong>" . (getenv('DB_PASS') ? '****** (definido)' : '❌ no definido') . "</strong><br>";
echo "</div>";

// ── 2. Conexión a la BD ───────────────────────────────────────
echo "<h2>2. Conexión a la base de datos</h2><div class='box'>";
try {
    $pdo = DB::get();
    echo "<span class='ok'>✅ Conexión exitosa</span>";
} catch (Throwable $e) {
    echo "<span class='err'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</span>";
    echo "</div>";
    die("<br><br><em>Sin conexión no se puede continuar.</em>");
}
echo "</div>";

// ── 3. Tablas existentes ──────────────────────────────────────
echo "<h2>3. Tablas en la base de datos</h2><div class='box'>";
$tablas = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
if ($tablas) {
    foreach ($tablas as $t) echo "📋 $t<br>";
} else {
    echo "<span class='err'>❌ No hay tablas — ¿corriste el SQL de creación?</span>";
}
echo "</div>";

// ── 4. Usuarios en la BD ──────────────────────────────────────
echo "<h2>4. Usuarios encontrados</h2><div class='box'>";
try {
    $usuarios = $pdo->query("
        SELECT u.id_usuario, u.username, u.email,
               s.nombre AS status, r.nombre AS rol,
               LEFT(u.password_hash, 30) AS hash_preview
        FROM usuarios u
        JOIN cat_status s ON s.id_status = u.id_status
        JOIN roles      r ON r.id_rol    = u.id_rol
    ")->fetchAll();

    if ($usuarios) {
        echo "<table>
            <tr><th>#</th><th>Username</th><th>Email</th><th>Status</th><th>Rol</th><th>Hash (primeros 30 chars)</th></tr>";
        foreach ($usuarios as $u) {
            echo "<tr>
                <td>{$u['id_usuario']}</td>
                <td>{$u['username']}</td>
                <td>{$u['email']}</td>
                <td>{$u['status']}</td>
                <td>{$u['rol']}</td>
                <td>{$u['hash_preview']}...</td>
            </tr>";
        }
        echo "</table>";
    } else {
        echo "<span class='err'>❌ No hay usuarios — ¿corriste el seed.sql?</span>";
    }
} catch (Throwable $e) {
    echo "<span class='err'>❌ " . htmlspecialchars($e->getMessage()) . "</span>";
}
echo "</div>";

// ── 5. Probar contraseña manualmente ─────────────────────────
echo "<h2>5. Probar contraseña</h2><div class='box'>";
$test_user = $_GET['user'] ?? 'admin';
$test_pass = $_GET['pass'] ?? 'Test1234!';

try {
    $stmt = $pdo->prepare("SELECT password_hash, id_status FROM usuarios WHERE username = ?");
    $stmt->execute([$test_user]);
    $row = $stmt->fetch();

    if (!$row) {
        echo "<span class='err'>❌ Usuario '$test_user' NO existe en la BD</span>";
    } else {
        echo "Usuario: <strong>$test_user</strong><br>";
        echo "Status id: <strong>{$row['id_status']}</strong><br>";
        echo "Hash completo: <code>" . htmlspecialchars($row['password_hash']) . "</code><br><br>";

        $ok = password_verify($test_pass, $row['password_hash']);
        if ($ok) {
            echo "<span class='ok'>✅ Contraseña '$test_pass' es CORRECTA</span>";
        } else {
            echo "<span class='err'>❌ Contraseña '$test_pass' es INCORRECTA para este hash</span>";
        }
    }
} catch (Throwable $e) {
    echo "<span class='err'>❌ " . htmlspecialchars($e->getMessage()) . "</span>";
}
echo "</div>";

echo "<br><p style='color:#888'>
    Cambia usuario/contraseña a probar:<br>
    <a style='color:#3498db' href='?user=admin&pass=Test1234!'>admin / Test1234!</a> |
    <a style='color:#3498db' href='?user=consulta&pass=Test1234!'>consulta / Test1234!</a> |
    <a style='color:#3498db' href='?user=ingreso&pass=Test1234!'>ingreso / Test1234!</a>
</p>";
