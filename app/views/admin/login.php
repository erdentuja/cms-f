<!DOCTYPE html>
<html lang="hu">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Bejelentkezés — <?= e(setting('site_name')) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@600;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= base_url('assets/css/admin.css') ?>?v=1">
</head>
<body class="login-body">
<div class="login-card">
    <div class="login-brand">
        <span class="brand-mark">✦</span>
        <h1><?= e(setting('site_name')) ?></h1>
        <p>Jelentkezz be az admin felületre</p>
    </div>
    <?php if ($flash): ?>
        <div class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
    <?php endif; ?>
    <form method="post" action="<?= base_url('admin/login') ?>">
        <?= csrf_field() ?>
        <label class="field">
            <span>E-mail cím</span>
            <input class="input" type="email" name="email" required autofocus placeholder="admin@cms.local">
        </label>
        <label class="field">
            <span>Jelszó</span>
            <input class="input" type="password" name="password" required placeholder="••••••••">
        </label>
        <button class="btn btn-primary btn-block" type="submit">Bejelentkezés</button>
    </form>
    <p class="login-hint">Alapértelmezett: admin@cms.local / admin123</p>
</div>
</body>
</html>
