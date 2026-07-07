<!DOCTYPE html>
<html lang="hu">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php
$siteName = setting('site_name');
$seoTitle = trim((string)($seoTitle ?? ''));
$pageTitle = $seoTitle !== '' ? $seoTitle : trim((string)($title ?? $siteName));
$htmlTitle = $pageTitle === $siteName ? $siteName : $pageTitle . ' — ' . $siteName;
$metaDescription = seo_text((string)($metaDescription ?? setting('description')), 160);
$ogUrl = trim((string)($canonical ?? '')) ?: site_url(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');
$ogUrl = absolute_url($ogUrl);
$robots = seo_robots_value((string)($robots ?? setting('seo_robots', 'index,follow')));
$ogImageUrl = !empty($ogImage) ? absolute_url((string)$ogImage) : '';
$twitterSite = trim((string)setting('twitter_site'));
?>
<title><?= e($htmlTitle) ?></title>
<meta name="description" content="<?= e($metaDescription) ?>">
<meta name="robots" content="<?= e($robots) ?>">
<link rel="canonical" href="<?= e($ogUrl) ?>">
<meta property="og:site_name" content="<?= e(setting('site_name')) ?>">
<meta property="og:title" content="<?= e($pageTitle) ?>">
<meta property="og:description" content="<?= e($metaDescription) ?>">
<meta property="og:type" content="<?= e($ogType ?? 'website') ?>">
<meta property="og:url" content="<?= e($ogUrl) ?>">
<meta property="og:locale" content="hu_HU">
<?php if ($ogImageUrl !== ''): ?>
<meta property="og:image" content="<?= e($ogImageUrl) ?>">
<meta property="og:image:secure_url" content="<?= e($ogImageUrl) ?>">
<meta name="twitter:card" content="summary_large_image">
<?php else: ?>
<meta name="twitter:card" content="summary">
<?php endif; ?>
<meta name="twitter:title" content="<?= e($pageTitle) ?>">
<meta name="twitter:description" content="<?= e($metaDescription) ?>">
<?php if ($ogImageUrl !== ''): ?><meta name="twitter:image" content="<?= e($ogImageUrl) ?>"><?php endif; ?>
<?php if ($twitterSite !== ''): ?><meta name="twitter:site" content="<?= e($twitterSite) ?>"><?php endif; ?>
<link rel="alternate" type="application/rss+xml" title="<?= e(setting('site_name')) ?> RSS" href="<?= base_url('rss.xml') ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<?php $tpl = active_template(); ?>
<link href="<?= e(template_fonts_href($tpl)) ?>" rel="stylesheet">
<link rel="stylesheet" href="<?= base_url('assets/css/front.css') ?>?v=5">
<style>
:root {
    --accent: <?= $tpl['accent'] ?>;
    --accent-2: <?= $tpl['accent2'] ?>;
    --bg: <?= $tpl['bg'] ?>;
    --surface: <?= $tpl['surface'] ?>;
    --ink: <?= $tpl['ink'] ?>;
    --radius: <?= $tpl['radius'] ?>px;
    --font-display: '<?= $tpl['font_display'] ?>', system-ui, sans-serif;
    --font-body: '<?= $tpl['font_body'] ?>', system-ui, sans-serif;
}
html[data-theme="dark"] {
    --accent: <?= $tpl['accent'] ?>;
    --accent-2: <?= $tpl['accent2'] ?>;
    --bg: #101017; --surface: #191922; --ink: #f0eff4;
}
</style>
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>✦</text></svg>">
<script>
(function () {
    var t = localStorage.getItem('theme');
    if (!t) t = matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    document.documentElement.dataset.theme = t;
})();
</script>
<?php do_action('front_head'); ?>
<?= setting('head_code') ?>
</head>
<body>
<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="<?= base_url('/') ?>">
            <span class="brand-mark">✦</span> <?= e(setting('site_name')) ?>
        </a>
        <nav class="main-nav" id="mainNav">
            <a href="<?= base_url('/') ?>">Kezdőlap</a>
            <?php foreach ($navItems ?? [] as $mi): ?>
                <a href="<?= e(menu_href($mi['url'])) ?>"<?= $mi['new_tab'] ? ' target="_blank" rel="noopener"' : '' ?>><?= e($mi['label']) ?></a>
            <?php endforeach; ?>
        </nav>
        <form class="search-form" action="<?= base_url('search') ?>" method="get">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input type="search" name="q" placeholder="Keresés…" value="<?= e($_GET['q'] ?? '') ?>">
        </form>
        <button class="theme-toggle" aria-label="Téma váltása"
                onclick="const h=document.documentElement;h.dataset.theme=h.dataset.theme==='dark'?'light':'dark';localStorage.setItem('theme',h.dataset.theme)">
            <svg class="ico-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.9 4.9l1.4 1.4m11.4 11.4 1.4 1.4M2 12h2m16 0h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
            <svg class="ico-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
        </button>
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
        <?php if (!empty($footerItems)): ?>
        <nav class="footer-nav">
            <?php foreach ($footerItems as $mi): ?>
                <a href="<?= e(menu_href($mi['url'])) ?>"<?= $mi['new_tab'] ? ' target="_blank" rel="noopener"' : '' ?>><?= e($mi['label']) ?></a>
            <?php endforeach; ?>
        </nav>
        <?php endif; ?>
        <p class="footer-copy"><?= e(setting('footer_text')) ?></p>
    </div>
</footer>
<?php do_action('front_footer'); ?>
<?= setting('footer_code') ?>
</body>
</html>
