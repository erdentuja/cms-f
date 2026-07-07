<?php if (!empty($preview)): ?><div class="preview-bar">Előnézet — a módosítások nincsenek elmentve</div><?php endif; ?>
<?php $sidebar = $sidebar ?? 'none'; ?>
<?php if ($sidebar !== 'none'): ?>
<div class="container post-layout<?= $sidebar === 'left' ? ' sidebar-left' : '' ?>">
<div class="post-main">
<?php endif; ?>
<article class="article">
    <header class="article-header container-narrow">
        <?php if (!empty($post['cat_name'])): ?>
            <a class="cat-pill" style="--pill:<?= e($post['cat_color'] ?: '#6366f1') ?>" href="<?= base_url('category/' . e($post['cat_slug'])) ?>"><?= e($post['cat_name']) ?></a>
        <?php endif; ?>
        <h1><?= e($post['title']) ?></h1>
        <div class="card-meta">
            <span><?= e($post['author'] ?? '') ?></span>
            <span class="dot">·</span>
            <time><?= hu_date($post['published_at']) ?></time>
            <span class="dot">·</span>
            <span><?= reading_time(!empty($post['builder']) ? blocks_render($blocks) : $post['content']) ?> perc olvasás</span>
        </div>
    </header>

    <?php if (!empty($post['featured_image'])): ?>
        <div class="container article-cover">
            <img src="<?= base_url(e($post['featured_image'])) ?>" alt="<?= e($post['title']) ?>">
        </div>
    <?php endif; ?>

    <?php if (!empty($post['builder'])): ?>
        <div class="blocks"><?= apply_filters('content', blocks_render($blocks)) ?></div>
    <?php else: ?>
        <div class="container-narrow prose">
            <?= apply_filters('content', $post['content']) ?>
        </div>
    <?php endif; ?>
</article>
<?php if ($sidebar !== 'none'): ?>
</div>
<aside class="post-sidebar">
    <div class="widget widget-search">
        <h3>Keresés</h3>
        <form action="<?= base_url('search') ?>" method="get">
            <input type="search" name="q" placeholder="Keresés…">
            <button class="btn btn-primary" type="submit" aria-label="Keresés">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            </button>
        </form>
    </div>
    <?php if (!empty($sideData['categories'])): ?>
    <div class="widget widget-cats">
        <h3>Kategóriák</h3>
        <ul>
            <?php foreach ($sideData['categories'] as $c): if (!$c['cnt']) continue; ?>
            <li><a href="<?= base_url('category/' . e($c['slug'])) ?>"><?= e($c['name']) ?> <span class="cnt"><?= (int)$c['cnt'] ?></span></a></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    <?php if (!empty($sideData['recent'])): ?>
    <div class="widget">
        <h3>Friss posztok</h3>
        <ul>
            <?php foreach ($sideData['recent'] as $r): ?>
            <li>
                <a href="<?= base_url('post/' . e($r['slug'])) ?>"><?= e($r['title']) ?></a>
                <span class="muted-line"><?= hu_date($r['published_at']) ?></span>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    <?php if (!empty($sideData['popular'])): ?>
    <div class="widget">
        <h3>Népszerű</h3>
        <ul>
            <?php foreach ($sideData['popular'] as $p): ?>
            <li>
                <a href="<?= base_url('post/' . e($p['slug'])) ?>"><?= e($p['title']) ?></a>
                <span class="muted-line"><?= (int)$p['views'] ?> megtekintés</span>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
</aside>
</div>
<?php endif; ?>

<?php if (!empty($related)): ?>
<section class="container related">
    <h2>Ezeket is ajánljuk</h2>
    <div class="related-grid">
        <?php foreach ($related as $r): ?>
            <a class="related-card" href="<?= base_url('post/' . e($r['slug'])) ?>">
                <?php if ($r['featured_image']): ?>
                    <img src="<?= base_url(e($r['featured_image'])) ?>" alt="" loading="lazy">
                <?php else: ?>
                    <div class="card-placeholder small" aria-hidden="true">✦</div>
                <?php endif; ?>
                <div>
                    <h3><?= e($r['title']) ?></h3>
                    <time><?= hu_date($r['published_at']) ?></time>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
