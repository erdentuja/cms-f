<header class="page-head">
    <div>
        <h1>Posztok</h1>
        <p class="muted"><?= count($posts) ?> bejegyzés</p>
    </div>
    <a class="btn btn-primary" href="<?= base_url('admin/posts/new') ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
        Új poszt
    </a>
</header>

<form class="toolbar" method="get" action="<?= base_url('admin/posts') ?>">
    <input class="input" type="search" name="q" placeholder="Keresés cím szerint…" value="<?= e($q) ?>">
    <select class="input" name="status" onchange="this.form.submit()">
        <option value="">Minden státusz</option>
        <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Publikált</option>
        <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Vázlat</option>
    </select>
    <button class="btn btn-ghost" type="submit">Szűrés</button>
</form>

<div class="panel">
    <?php if ($posts): ?>
    <table class="table">
        <thead>
            <tr><th>Cím</th><th>Kategória</th><th>Szerző</th><th>Státusz</th><th>Megtekintés</th><th>Módosítva</th><th></th></tr>
        </thead>
        <tbody>
            <?php foreach ($posts as $p): ?>
            <tr>
                <td><a class="row-title" href="<?= base_url('admin/posts/' . $p['id']) ?>"><?= e($p['title']) ?></a></td>
                <td><?php if ($p['cat_name']): ?><span class="cat-dot" style="--c:<?= e($p['cat_color']) ?>"><?= e($p['cat_name']) ?></span><?php else: ?><span class="muted">—</span><?php endif; ?></td>
                <td><?= e($p['author'] ?? '—') ?></td>
                <td><span class="badge <?= $p['status'] === 'published' ? 'badge-green' : 'badge-gray' ?>"><?= $p['status'] === 'published' ? 'Publikált' : 'Vázlat' ?></span></td>
                <td class="muted"><?= (int)$p['views'] ?></td>
                <td class="muted"><?= e(substr($p['updated_at'], 0, 16)) ?></td>
                <td class="row-actions">
                    <?php if ($p['status'] === 'published'): ?>
                    <a class="icon-btn" href="<?= base_url('post/' . e($p['slug'])) ?>" target="_blank" title="Megtekintés">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </a>
                    <?php endif; ?>
                    <form method="post" action="<?= base_url('admin/posts/delete') ?>" data-confirm="Biztosan törlöd a posztot?">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                        <button class="icon-btn danger" type="submit" title="Törlés">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 11v6M14 11v6"/></svg>
                        </button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="empty-state">
        <p>Nincs a szűrésnek megfelelő poszt.</p>
        <a class="btn btn-primary" href="<?= base_url('admin/posts/new') ?>">Új poszt létrehozása</a>
    </div>
    <?php endif; ?>
</div>
