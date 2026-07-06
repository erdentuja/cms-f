<?php if (!empty($hero)): ?>
<section class="hero">
    <div class="container hero-grid">
        <div class="hero-text">
            <?php if (!empty($hero['cat_name'])): ?>
                <a class="cat-pill" style="--pill:<?= e($hero['cat_color'] ?: '#6366f1') ?>" href="<?= base_url('category/' . e($hero['cat_slug'])) ?>"><?= e($hero['cat_name']) ?></a>
            <?php endif; ?>
            <h1><a href="<?= base_url('post/' . e($hero['slug'])) ?>"><?= e($hero['title']) ?></a></h1>
            <p><?= e($hero['excerpt'] ?: excerpt_of($hero['content'], 200)) ?></p>
            <div class="card-meta">
                <span><?= e($hero['author'] ?? '') ?></span>
                <span class="dot">·</span>
                <time><?= hu_date($hero['published_at']) ?></time>
                <span class="dot">·</span>
                <span><?= reading_time($hero['content']) ?> perc olvasás</span>
            </div>
            <a class="btn btn-primary" href="<?= base_url('post/' . e($hero['slug'])) ?>">Elolvasom
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
            </a>
        </div>
        <a class="hero-media" href="<?= base_url('post/' . e($hero['slug'])) ?>">
            <?php if ($hero['featured_image']): ?>
                <img src="<?= base_url(e($hero['featured_image'])) ?>" alt="<?= e($hero['title']) ?>">
            <?php else: ?>
                <div class="hero-placeholder" aria-hidden="true">✦</div>
            <?php endif; ?>
        </a>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($categories)): ?>
<section class="container cat-strip">
    <?php foreach ($categories as $c): if (!$c['cnt']) continue; ?>
        <a class="cat-chip" style="--pill:<?= e($c['color']) ?>" href="<?= base_url('category/' . e($c['slug'])) ?>">
            <?= e($c['name']) ?> <span><?= (int)$c['cnt'] ?></span>
        </a>
    <?php endforeach; ?>
</section>
<?php endif; ?>

<section class="container">
    <div class="section-head">
        <h2>Legfrissebb írások</h2>
    </div>
    <?php if ($posts): ?>
        <div class="card-grid">
            <?php foreach ($posts as $p) require __DIR__ . '/_card.php'; ?>
        </div>
    <?php elseif (empty($hero)): ?>
        <p class="empty-note">Még nincs publikált poszt. Hozz létre egyet az <a href="<?= base_url('admin') ?>">admin felületen</a>!</p>
    <?php endif; ?>

    <?php if (($pages_total ?? 1) > 1): ?>
    <nav class="pagination">
        <?php for ($i = 1; $i <= $pages_total; $i++): ?>
            <a class="<?= $i === $page ? 'active' : '' ?>" href="<?= base_url('/?p=' . $i) ?>"><?= $i ?></a>
        <?php endfor; ?>
    </nav>
    <?php endif; ?>
</section>
