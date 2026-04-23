<?php
require_once __DIR__ . '/auth.php';

function h(?string $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function is_post(): bool {
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function redirect_to(string $path): void {
    header("Location: $path");
    exit;
}

function flash_set(string $type, string $message): void {
    session_init();
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function flash_get(): ?array {
    session_init();
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

function old_input(string $key, string $default = ''): string {
    return h($_POST[$key] ?? $default);
}

function posted_int(string $key, int $default = 0): int {
    return (int)($_POST[$key] ?? $default);
}

function posted_string(string $key, string $default = ''): string {
    return trim((string)($_POST[$key] ?? $default));
}

function posted_nullable_date(string $key): ?string {
    $value = trim((string)($_POST[$key] ?? ''));
    return $value === '' ? null : $value;
}

function query_string(string $key, string $default = ''): string {
    return trim((string)($_GET[$key] ?? $default));
}

function query_int(string $key, int $default = 0): int {
    return (int)($_GET[$key] ?? $default);
}

function status_badge_class(string $status): string {
    return match (strtolower($status)) {
        'activo' => 'badge-success',
        'inactivo' => 'badge-warning',
        'bloqueado' => 'badge-error',
        default => 'badge-neutral',
    };
}

function role_badge_class(string $role): string {
    return match (strtolower($role)) {
        'admin' => 'badge-secondary',
        'consulta' => 'badge-info',
        'ingreso' => 'badge-primary',
        default => 'badge-neutral',
    };
}

function permission_sections(): array {
    return [
        [
            'title' => 'Resumen',
            'items' => [
                ['label' => 'Overview', 'href' => '/dashboard/overview.php', 'resource' => 'reportes', 'action' => 'READ'],
                ['label' => 'Mi Perfil', 'href' => '/dashboard/profile.php', 'resource' => 'usuarios', 'action' => 'READ'],
            ],
        ],
        [
            'title' => 'Administracion',
            'items' => [
                ['label' => 'Usuarios', 'href' => '/dashboard/users.php', 'resource' => 'usuarios', 'action' => 'READ'],
                ['label' => 'Estados', 'href' => '/dashboard/statuses.php', 'resource' => 'configuracion', 'action' => 'READ'],
                ['label' => 'Roles', 'href' => '/dashboard/roles.php', 'resource' => 'configuracion', 'action' => 'READ'],
                ['label' => 'Recursos', 'href' => '/dashboard/resources.php', 'resource' => 'configuracion', 'action' => 'READ'],
                ['label' => 'Acciones', 'href' => '/dashboard/actions.php', 'resource' => 'configuracion', 'action' => 'READ'],
                ['label' => 'Permisos', 'href' => '/dashboard/permissions.php', 'resource' => 'configuracion', 'action' => 'UPDATE'],
            ],
        ],
        [
            'title' => 'Operacion',
            'items' => [
                ['label' => 'Sesiones', 'href' => '/dashboard/sessions.php', 'resource' => 'reportes', 'action' => 'READ'],
                ['label' => 'Auditoria', 'href' => '/dashboard/audit.php', 'resource' => 'reportes', 'action' => 'READ'],
            ],
        ],
    ];
}

function app_navigation(): array {
    $sections = [];
    foreach (permission_sections() as $section) {
        $items = [];
        foreach ($section['items'] as $item) {
            if (has_permission($item['resource'], $item['action'])) {
                $items[] = $item;
            }
        }
        if ($items) {
            $sections[] = [
                'title' => $section['title'],
                'items' => $items,
            ];
        }
    }
    return $sections;
}

function app_home_path(): string {
    foreach (app_navigation() as $section) {
        if (!empty($section['items'][0]['href'])) {
            return $section['items'][0]['href'];
        }
    }
    return '/dashboard/profile.php';
}

function current_path(): string {
    return parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
}
