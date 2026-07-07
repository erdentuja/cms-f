<header class="page-head">
    <div>
        <h1>Médiatár</h1>
        <p class="muted"><?= count($items) ?> fájl<?= $orphans ? ', ebből ' . $orphans . ' nem használt' : '' ?></p>
    </div>
    <label class="btn btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12"/></svg>
        Feltöltés
        <input type="file" id="mediaUpload" multiple accept="image/*,application/pdf" hidden>
    </label>
</header>

<div class="dropzone" id="dropzone">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12"/></svg>
    <p>Húzd ide a fájlokat, vagy kattints a Feltöltés gombra</p>
    <span class="muted">JPG, PNG, GIF, WebP, SVG, PDF — max. 20 MB</span>
    <div class="upload-progress" id="uploadProgress" hidden><div></div></div>
</div>

<?php if ($items): ?>
<div class="toolbar media-toolbar">
    <input class="input" type="search" id="mediaSearch" placeholder="Keresés fájlnév szerint…">
    <div class="seg" id="mediaFilters">
        <button class="btn btn-ghost btn-sm active" type="button" data-filter="all">Mind</button>
        <button class="btn btn-ghost btn-sm" type="button" data-filter="used">Használt</button>
        <button class="btn btn-ghost btn-sm" type="button" data-filter="orphan">Árva</button>
    </div>
    <?php if ($orphans): ?>
    <form method="post" action="<?= base_url('admin/media/delete-orphans') ?>"
          data-confirm="Törlöd mind a(z) <?= $orphans ?> árva fájlt? Ez nem visszavonható.">
        <?= csrf_field() ?>
        <button class="btn btn-ghost danger btn-sm" type="submit">Árvák törlése (<?= $orphans ?>)</button>
    </form>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="media-grid" id="mediaGrid">
    <?php foreach ($items as $m): $u = $usages[(int)$m['id']] ?? []; ?>
    <figure class="media-item" data-id="<?= (int)$m['id'] ?>"
            data-name="<?= e(mb_strtolower($m['filename'])) ?>" data-used="<?= $u ? 1 : 0 ?>">
        <?php if (str_starts_with($m['mime'], 'image/')): ?>
            <img src="<?= base_url(e($m['thumb'] ?: $m['path'])) ?>" alt="<?= e($m['filename']) ?>" loading="lazy">
        <?php else: ?>
            <div class="media-file">📄</div>
        <?php endif; ?>
        <figcaption>
            <strong title="<?= e($m['filename']) ?>"><?= e($m['filename']) ?></strong>
            <span class="muted"><?= human_size((int)$m['size']) ?><?= $m['width'] ? ' · ' . $m['width'] . '×' . $m['height'] : '' ?></span>
            <?php if ($u): ?>
            <details class="usage">
                <summary><span class="badge badge-green"><?= count($u) ?> helyen használt</span></summary>
                <ul class="usage-list">
                    <?php foreach ($u as $x): ?>
                    <li>
                        <a class="link" href="<?= base_url($x['url'] ?? (($x['type'] === 'post' ? 'admin/posts/' : 'admin/pages/') . $x['id'])) ?>"><?= e($x['title']) ?></a>
                        <span class="muted">(<?= e($x['kind'] ?? ($x['type'] === 'post' ? 'poszt' : 'oldal')) ?> — <?= e($x['where']) ?>)</span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </details>
            <?php else: ?>
            <span class="badge badge-orange" title="Sehol sem szerepel a tartalmakban">Nem használt</span>
            <?php endif; ?>
        </figcaption>
        <div class="media-actions">
            <button class="icon-btn" type="button" title="URL másolása" data-copy="<?= base_url(e($m['path'])) ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
            </button>
            <a class="icon-btn" href="<?= base_url(e($m['path'])) ?>" target="_blank" title="Megnyitás">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6M15 3h6v6M10 14 21 3"/></svg>
            </a>
            <form method="post" action="<?= base_url('admin/media/delete') ?>" data-confirm="Törlöd a fájlt? Ez nem visszavonható.">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                <button class="icon-btn danger" type="submit" title="Törlés">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 11v6M14 11v6"/></svg>
                </button>
            </form>
        </div>
    </figure>
    <?php endforeach; ?>
</div>

<?php if (!$items): ?>
<div class="empty-state" id="mediaEmpty">
    <p>A médiatár még üres. Tölts fel képeket a fenti gombbal vagy húzd ide őket!</p>
</div>
<?php endif; ?>

<p class="muted pad" id="noMatch" hidden>Nincs a szűrésnek megfelelő fájl.</p>

<script>
window.CMS_BASE = <?= json_encode(base_url('/')) ?>;
window.CSRF = <?= json_encode(csrf_token()) ?>;

// Kliensoldali keresés + használat szerinti szűrés
(function () {
    const search = document.getElementById('mediaSearch');
    const filters = document.getElementById('mediaFilters');
    if (!search) return;
    let filter = 'all';
    function apply() {
        const q = search.value.trim().toLowerCase();
        let visible = 0;
        document.querySelectorAll('.media-item').forEach(it => {
            const okQ = !q || it.dataset.name.includes(q);
            const okF = filter === 'all' || (filter === 'used') === (it.dataset.used === '1');
            it.hidden = !(okQ && okF);
            if (!it.hidden) visible++;
        });
        document.getElementById('noMatch').hidden = visible > 0;
    }
    search.addEventListener('input', apply);
    filters.addEventListener('click', e => {
        const btn = e.target.closest('[data-filter]');
        if (!btn) return;
        filter = btn.dataset.filter;
        filters.querySelectorAll('.btn').forEach(b => b.classList.toggle('active', b === btn));
        apply();
    });
})();
</script>
