<header class="page-head">
    <div>
        <h1>Médiatár</h1>
        <p class="muted"><?= count($items) ?> fájl</p>
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

<div class="media-grid" id="mediaGrid">
    <?php foreach ($items as $m): ?>
    <figure class="media-item" data-id="<?= (int)$m['id'] ?>">
        <?php if (str_starts_with($m['mime'], 'image/')): ?>
            <img src="<?= base_url(e($m['thumb'] ?: $m['path'])) ?>" alt="<?= e($m['filename']) ?>" loading="lazy">
        <?php else: ?>
            <div class="media-file">📄</div>
        <?php endif; ?>
        <figcaption>
            <strong title="<?= e($m['filename']) ?>"><?= e($m['filename']) ?></strong>
            <span class="muted"><?= human_size((int)$m['size']) ?><?= $m['width'] ? ' · ' . $m['width'] . '×' . $m['height'] : '' ?></span>
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

<script>
window.CMS_BASE = <?= json_encode(base_url('/')) ?>;
window.CSRF = <?= json_encode(csrf_token()) ?>;
</script>
