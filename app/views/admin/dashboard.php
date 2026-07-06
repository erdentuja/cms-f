<header class="page-head">
    <div>
        <h1>Vezérlőpult</h1>
        <p class="muted">Üdv újra, <?= e($user['name']) ?>! Így áll most az oldalad.</p>
    </div>
    <a class="btn btn-primary" href="<?= base_url('admin/posts/new') ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
        Új poszt
    </a>
</header>

<div class="stat-grid">
    <?php
    $cards = [
        ['Posztok', $stats['posts'], 'admin/posts', 'grad-1'],
        ['Oldalak', $stats['pages'], 'admin/pages', 'grad-2'],
        ['Médiafájlok', $stats['media'], 'admin/media', 'grad-3'],
        ['Megtekintések', number_format($stats['views'], 0, ',', ' '), null, 'grad-4'],
    ];
    foreach ($cards as [$label, $value, $link, $grad]): ?>
    <a class="stat-card <?= $grad ?>" <?= $link ? 'href="' . base_url($link) . '"' : '' ?>>
        <span class="stat-value"><?= $value ?></span>
        <span class="stat-label"><?= $label ?></span>
    </a>
    <?php endforeach; ?>
</div>

<div class="dash-grid">
    <section class="panel">
        <div class="panel-head">
            <h2>Legutóbb szerkesztett</h2>
            <a class="link" href="<?= base_url('admin/posts') ?>">Összes →</a>
        </div>
        <?php if ($recent): ?>
        <ul class="row-list">
            <?php foreach ($recent as $r): ?>
            <li>
                <a class="row-title" href="<?= base_url('admin/posts/' . $r['id']) ?>"><?= e($r['title']) ?></a>
                <span class="badge <?= $r['status'] === 'published' ? 'badge-green' : 'badge-gray' ?>">
                    <?= $r['status'] === 'published' ? 'Publikált' : 'Vázlat' ?>
                </span>
                <?php if ($r['cat_name']): ?><span class="cat-dot" style="--c:<?= e($r['cat_color']) ?>"><?= e($r['cat_name']) ?></span><?php endif; ?>
                <time class="muted"><?= e(substr($r['updated_at'], 0, 16)) ?></time>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php else: ?><p class="muted pad">Még nincs poszt.</p><?php endif; ?>
    </section>

    <section class="panel">
        <div class="panel-head"><h2>Legolvasottabb</h2></div>
        <?php if ($popular): ?>
        <ul class="row-list">
            <?php foreach ($popular as $i => $p): ?>
            <li>
                <span class="rank">#<?= $i + 1 ?></span>
                <a class="row-title" href="<?= base_url('post/' . e($p['slug'])) ?>" target="_blank"><?= e($p['title']) ?></a>
                <span class="muted"><?= number_format((int)$p['views'], 0, ',', ' ') ?> megtekintés</span>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php else: ?><p class="muted pad">Még nincs adat.</p><?php endif; ?>
        <?php if ($stats['drafts']): ?>
        <div class="hint-box">✏️ <?= (int)$stats['drafts'] ?> vázlat vár publikálásra.</div>
        <?php endif; ?>
    </section>
</div>
