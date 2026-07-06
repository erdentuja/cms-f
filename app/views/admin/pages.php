<header class="page-head">
    <div>
        <h1>Oldalak</h1>
        <p class="muted"><?= count($pages) ?> statikus oldal</p>
    </div>
    <a class="btn btn-primary" href="<?= base_url('admin/pages/new') ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
        Új oldal
    </a>
</header>

<div class="panel">
    <?php if ($pages): ?>
    <table class="table">
        <thead><tr><th>Cím</th><th>URL</th><th>Státusz</th><th>Módosítva</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($pages as $p): ?>
            <tr>
                <td><a class="row-title" href="<?= base_url('admin/pages/' . $p['id']) ?>"><?= e($p['title']) ?></a></td>
                <td class="muted">/<?= e($p['slug']) ?></td>
                <td><span class="badge <?= $p['status'] === 'published' ? 'badge-green' : 'badge-gray' ?>"><?= $p['status'] === 'published' ? 'Publikált' : 'Vázlat' ?></span></td>
                <td class="muted"><?= e(substr($p['updated_at'], 0, 16)) ?></td>
                <td class="row-actions">
                    <a class="icon-btn" href="<?= base_url(e($p['slug'])) ?>" target="_blank" title="Megtekintés">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </a>
                    <form method="post" action="<?= base_url('admin/pages/delete') ?>" data-confirm="Biztosan törlöd az oldalt?">
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
        <p>Még nincs oldal.</p>
        <a class="btn btn-primary" href="<?= base_url('admin/pages/new') ?>">Új oldal létrehozása</a>
    </div>
    <?php endif; ?>
</div>
