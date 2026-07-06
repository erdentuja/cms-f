<!DOCTYPE html>
<html lang="hu">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? '') ?> — <?= e(setting('site_name')) ?></title>
<meta name="description" content="<?= e($metaDescription ?? setting('description')) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= base_url('assets/css/front.css') ?>?v=1">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>✦</text></svg>">
</head>
<body>
<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="<?= base_url('/') ?>">
            <span class="brand-mark">✦</span> <?= e(setting('site_name')) ?>
        </a>
        <nav class="main-nav" id="mainNav">
            <a href="<?= base_url('/') ?>">Kezdőlap</a>
            <?php foreach ($navPages ?? [] as $np): ?>
                <a href="<?= base_url(e($np['slug'])) ?>"><?= e($np['title']) ?></a>
            <?php endforeach; ?>
        </nav>
        <form class="search-form" action="<?= base_url('search') ?>" method="get">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input type="search" name="q" placeholder="Keresés…" value="<?= e($_GET['q'] ?? '') ?>">
        </form>
        <button class="nav-toggle" onclick="document.getElementById('mainNav').classList.toggle('open')" aria-label="Menü">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
        </button>
    </div>
</header>

<main>
<?= $content ?>
</main>

<footer class="site-footer">
    <div class="container footer-inner">
        <div>
            <a class="brand" href="<?= base_url('/') ?>"><span class="brand-mark">✦</span> <?= e(setting('site_name')) ?></a>
            <p class="footer-tagline"><?= e(setting('tagline')) ?></p>
        </div>
        <p class="footer-copy"><?= e(setting('footer_text')) ?></p>
    </div>
</footer>
</body>
</html>
