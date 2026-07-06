<?php /* expects $p (post row with cat_name, cat_slug, cat_color, author) */ ?>
<article class="card">
    <a class="card-media" href="<?= base_url('post/' . e($p['slug'])) ?>">
        <?php if ($p['featured_image']): ?>
            <img src="<?= base_url(e($p['featured_image'])) ?>" alt="<?= e($p['title']) ?>" loading="lazy">
        <?php else: ?>
            <div class="card-placeholder" aria-hidden="true">✦</div>
        <?php endif; ?>
    </a>
    <div class="card-body">
        <?php if (!empty($p['cat_name'])): ?>
            <a class="cat-pill" style="--pill:<?= e($p['cat_color'] ?: '#6366f1') ?>" href="<?= base_url('category/' . e($p['cat_slug'])) ?>"><?= e($p['cat_name']) ?></a>
        <?php endif; ?>
        <h3 class="card-title"><a href="<?= base_url('post/' . e($p['slug'])) ?>"><?= e($p['title']) ?></a></h3>
        <p class="card-excerpt"><?= e($p['excerpt'] ?: excerpt_of($p['content'] ?? '', 120)) ?></p>
        <div class="card-meta">
            <span><?= e($p['author'] ?? '') ?></span>
            <span class="dot">·</span>
            <time><?= hu_date($p['published_at']) ?></time>
        </div>
    </div>
</article>
