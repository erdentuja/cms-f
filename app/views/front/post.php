<?php if (!empty($preview)): ?><div class="preview-bar">Előnézet — a módosítások nincsenek elmentve</div><?php endif; ?>
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
