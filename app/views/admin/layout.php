<?php
$nav = [
    ['admin',            'Vezérlőpult', 'M3 12 12 3l9 9M5 10v10h5v-6h4v6h5V10'],
    ['admin/posts',      'Posztok',     'M4 4h16v16H4zM8 8h8M8 12h8M8 16h5'],
    ['admin/pages',      'Oldalak',     'M6 2h9l5 5v15H6zM14 2v6h6'],
    ['admin/categories', 'Kategóriák',  'M3 6h18M3 12h12M3 18h6'],
    ['admin/menu',       'Menük',       'M4 6h16M4 12h16M4 18h10M20 15l-3 3 3 3'],
    ['admin/media',      'Médiatár',    'M3 5h18v14H3zM3 15l5-5 4 4 3-3 6 6M8.5 9a1 1 0 1 0 0-.01'],
];
if (($user['role'] ?? '') === 'admin') {
    $nav[] = ['admin/templates', 'Sablonok', 'M12 22a10 10 0 1 1 10-10c0 1.8-1.2 3-3 3h-2.5a2.5 2.5 0 0 0-1.9 4.1c.6.8.1 2.9-2.6 2.9zM7.5 11a1 1 0 1 0 0-.01M12 7.5a1 1 0 1 0 0-.01M16.5 11a1 1 0 1 0 0-.01'];
    $nav[] = ['admin/plugins',  'Bővítmények', 'M9 2v4M15 2v4M6 6h12v5a6 6 0 0 1-6 6 6 6 0 0 1-6-6zM12 17v5'];
    $nav[] = ['admin/users',    'Felhasználók', 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75'];
    $nav[] = ['admin/settings', 'Beállítások',  'M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33h0a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51h0a1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82v0a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z'];
}
$nav = apply_filters('admin_nav', $nav, $user);
$currentPath = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$base = trim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
if ($base && str_starts_with($currentPath, $base)) $currentPath = trim(substr($currentPath, strlen($base)), '/');
?>
<!DOCTYPE html>
<html lang="hu">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'Admin') ?> — <?= e(setting('site_name')) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= base_url('assets/css/admin.css') ?>?v=1">
</head>
<body>
<div class="admin-shell">
    <aside class="sidebar">
        <a class="sidebar-brand" href="<?= base_url('admin') ?>">
            <span class="brand-mark">✦</span>
            <span><?= e(setting('site_name')) ?></span>
        </a>
        <nav class="sidebar-nav">
            <?php foreach ($nav as [$path, $label, $icon]):
                $active = $path === 'admin' ? $currentPath === 'admin' : str_starts_with($currentPath, $path); ?>
            <a class="<?= $active ? 'active' : '' ?>" href="<?= base_url($path) ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="<?= $icon ?>"/></svg>
                <?= $label ?>
            </a>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-footer">
            <a class="view-site" href="<?= base_url('/') ?>" target="_blank">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a15 15 0 0 1 0 18 15 15 0 0 1 0-18"/></svg>
                Weboldal megtekintése
            </a>
            <div class="user-box">
                <div class="avatar"><?= e(mb_strtoupper(mb_substr($user['name'] ?? '?', 0, 1))) ?></div>
                <div class="user-info">
                    <strong><?= e($user['name'] ?? '') ?></strong>
                    <span><?= ($user['role'] ?? '') === 'admin' ? 'Adminisztrátor' : 'Szerkesztő' ?></span>
                </div>
                <form method="post" action="<?= base_url('admin/logout') ?>" title="Kijelentkezés">
                    <?= csrf_field() ?>
                    <button type="submit" aria-label="Kijelentkezés">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <main class="admin-main">
        <?php if (!empty($flash)): ?>
            <div class="flash flash-<?= e($flash['type']) ?>" id="flash"><?= e($flash['msg']) ?></div>
        <?php endif; ?>
        <?= $content ?>
    </main>
</div>
<script src="<?= base_url('assets/js/admin.js') ?>?v=1"></script>
</body>
</html>
