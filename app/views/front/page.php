<article class="article">
    <header class="article-header container-narrow">
        <h1><?= e($page['title']) ?></h1>
    </header>
    <?php if (!empty($page['builder'])): ?>
        <div class="blocks"><?= blocks_render($blocks) ?></div>
    <?php else: ?>
        <div class="container-narrow prose">
            <?= $page['content'] ?>
        </div>
    <?php endif; ?>
</article>
