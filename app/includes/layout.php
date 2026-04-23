<?php
require_once __DIR__ . '/app.php';

function layout_head(string $title, array $options = []): void {
    $user = current_user();
    $flash = flash_get();
    $homePath = app_home_path();
    $pageActions = $options['actions'] ?? [];
    $subtitle = $options['subtitle'] ?? '';
?>
<!DOCTYPE html>
<html lang="es" data-theme="laas">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($title) ?> - Login as a Service</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="min-h-screen bg-base-100 text-base-content">
<div class="drawer lg:drawer-open">
    <input id="app-drawer" type="checkbox" class="drawer-toggle">
    <div class="drawer-content min-h-screen">
        <header class="sticky top-0 z-30 border-b border-base-300/80 bg-base-100/80 backdrop-blur-xl">
            <div class="page-shell !max-w-none !py-4">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex min-w-0 items-center gap-3">
                        <label for="app-drawer" class="btn btn-square btn-ghost rounded-2xl border border-base-300/70 bg-base-100 lg:hidden">
                            <span class="text-lg font-semibold">=</span>
                        </label>
                        <a href="<?= h($homePath) ?>" class="group min-w-0">
                            <p class="display-title truncate text-xl font-extrabold tracking-[-0.04em]">Login as a Service</p>
                            <p class="mt-1 text-[0.7rem] uppercase tracking-[0.28em] text-base-content/45">Security Console</p>
                        </a>
                    <div class="flex flex-wrap items-center gap-2 lg:justify-end">
                        <span class="badge badge-outline border-base-300 bg-base-100 px-3 py-3 text-[0.72rem] font-semibold"><?= h(strtoupper((string)($user['rol'] ?? ''))) ?></span>
                        <div class="rounded-2xl border border-base-300/80 bg-base-100 px-4 py-2 shadow-sm">
                            <p class="text-sm font-semibold"><?= h($user['username'] ?? '') ?></p>
                            <p class="text-xs text-base-content/55"><?= h($user['email'] ?? '') ?></p>
                        </div>
                        <a href="/logout.php" class="btn btn-error rounded-2xl">Salir</a>
                    </div>
                </div>
            </div>
        </header>

        <main class="page-shell">
            <section class="mb-8 flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                <div class="min-w-0">
                    <p class="kicker">Workspace</p>
                    <h1 class="display-title mt-2 text-4xl font-extrabold leading-none sm:text-5xl"><?= h($title) ?></h1>
                    <?php if ($subtitle !== ''): ?>
                    <p class="mt-3 max-w-3xl text-sm leading-6 text-base-content/70 sm:text-[0.95rem]"><?= h($subtitle) ?></p>
                    <?php endif; ?>
                </div>
                <?php if ($pageActions): ?>
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($pageActions as $action): ?>
                    <a href="<?= h($action['href']) ?>" class="btn rounded-2xl <?= h($action['class'] ?? 'btn-primary') ?>"><?= h($action['label']) ?></a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </section>

            <?php if ($flash): ?>
            <div class="alert <?= h('alert-' . $flash['type']) ?> mb-6">
                <span><?= h($flash['message']) ?></span>
            </div>
            <?php endif; ?>

            <?php if (isset($_GET['denied'])): ?>
            <div class="alert alert-error mb-6">
                <span>No tienes permisos para acceder a esa seccion.</span>
            </div>
            <?php endif; ?>
<?php
}

function layout_foot(): void {
    $user = current_user();
?>
        </main>
    </div>
    <aside class="drawer-side z-40">
        <label for="app-drawer" class="drawer-overlay"></label>
        <div class="flex min-h-full w-[21rem] flex-col bg-neutral text-neutral-content">
            <div class="border-b border-white/10 px-6 py-6">
                <div class="sidebar-card p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="display-title text-3xl font-extrabold tracking-[-0.05em]">LAAS</p>
                            <p class="mt-2 text-sm leading-6 text-neutral-content/72">Panel unificado para usuarios, permisos, sesiones y auditoria.</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/8 px-3 py-2 text-[0.72rem] uppercase tracking-[0.2em] text-neutral-content/70">PHP</div>
                    </div>
                </div>
            </div>
            <nav class="flex-1 overflow-y-auto px-4 py-5">
                <?php foreach (app_navigation() as $section): ?>
                <div class="mb-6">
                    <p class="mb-3 px-3 text-[0.68rem] font-semibold uppercase tracking-[0.24em] text-neutral-content/35"><?= h($section['title']) ?></p>
                    <ul class="space-y-1.5">
                        <?php foreach ($section['items'] as $item): ?>
                        <li>
                            <a href="<?= h($item['href']) ?>" class="nav-pill <?= current_path() === $item['href'] ? 'nav-pill-active' : 'nav-pill-idle' ?>">
                                <?= h($item['label']) ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endforeach; ?>
            </nav>
            <div class="border-t border-white/10 px-6 py-5">
                <div class="sidebar-card p-4 text-xs text-neutral-content/65">
                    <p class="font-semibold text-neutral-content/80">Sesion actual</p>
                    <p class="mt-2 truncate"><?= h($user['username'] ?? '') ?></p>
                    <p class="truncate text-neutral-content/50"><?= h($user['email'] ?? '') ?></p>
                </div>
            </div>
        </div>
    </aside>
</div>
</body>
</html>
<?php
}

function render_stat_card(string $label, string $value, string $tone = 'text-primary', string $helper = ''): void {
?>
<article class="metric-card">
    <div class="absolute inset-x-5 top-0 h-px bg-gradient-to-r from-transparent via-base-content/20 to-transparent"></div>
    <p class="text-[0.68rem] uppercase tracking-[0.22em] text-base-content/45"><?= h($label) ?></p>
    <p class="display-title mt-3 text-4xl font-extrabold leading-none sm:text-5xl <?= h($tone) ?>"><?= h($value) ?></p>
    <?php if ($helper !== ''): ?>
    <p class="mt-3 max-w-[22rem] text-sm leading-6 text-base-content/60"><?= h($helper) ?></p>
    <?php endif; ?>
</article>
<?php
}
