<section class="container">
    <header class="archive-head" style="--pill:<?= e($cat['color']) ?>">
        <span class="archive-label">Kategória</span>
        <h1><?= e($cat['name']) ?></h1>
        <?php if ($cat['description']): ?><p><?= e($cat['description']) ?></p><?php endif; ?>
    </header>
    <?php if ($posts): ?>
        <div class="card-grid">
            <?php foreach ($posts as $p) require __DIR__ . '/_card.php'; ?>
        </div>
    <?php else: ?>
        <p class="empty-note">Ebben a kategóriában még nincs poszt.</p>
    <?php endif; ?>
</section>
