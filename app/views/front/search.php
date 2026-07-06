<section class="container">
    <header class="archive-head">
        <span class="archive-label">Keresés</span>
        <h1><?= $q !== '' ? '„' . e($q) . '”' : 'Keresés' ?></h1>
        <?php if ($q !== ''): ?><p><?= count($posts) ?> találat</p><?php endif; ?>
    </header>
    <?php if ($posts): ?>
        <div class="card-grid">
            <?php foreach ($posts as $p) require __DIR__ . '/_card.php'; ?>
        </div>
    <?php elseif ($q !== ''): ?>
        <p class="empty-note">Nincs találat a keresésre. Próbálj más kulcsszót!</p>
    <?php endif; ?>
</section>
