<?php
$builderMode = !empty($post['builder']);
$content = $post['content'] ?? '';
$blocksData = json_decode($post['blocks'] ?? '[]', true) ?: [];
$previewAction = base_url('admin/posts/preview');
?>
<form method="post" action="<?= base_url('admin/posts/save') ?>" class="edit-layout" id="editForm">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int)($post['id'] ?? 0) ?>">
    <input type="hidden" name="builder" id="builderMode" value="<?= $builderMode ? 1 : 0 ?>">

    <div class="edit-main">
        <header class="page-head">
            <div>
                <a class="back-link" href="<?= base_url('admin/posts') ?>">← Posztok</a>
                <h1><?= e($title) ?></h1>
            </div>
        </header>

        <input class="input input-title" type="text" name="title" placeholder="A poszt címe…" required
               value="<?= e($post['title'] ?? '') ?>">

        <?php require __DIR__ . '/_editor-content.php'; ?>

        <label class="field">
            <span>Kivonat <em class="muted">(a listákban és a keresőknek megjelenő rövid leírás)</em></span>
            <textarea class="input" name="excerpt" rows="3"><?= e($post['excerpt'] ?? '') ?></textarea>
        </label>
    </div>

    <aside class="edit-side">
        <div class="panel side-panel">
            <h3>Publikálás</h3>
            <label class="field">
                <span>Státusz</span>
                <select class="input" name="status">
                    <option value="draft" <?= ($post['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Vázlat</option>
                    <option value="published" <?= ($post['status'] ?? '') === 'published' ? 'selected' : '' ?>>Publikált</option>
                </select>
            </label>
            <label class="field">
                <span>URL (slug)</span>
                <input class="input" type="text" name="slug" value="<?= e($post['slug'] ?? '') ?>" placeholder="automatikus a címből">
            </label>
            <button class="btn btn-primary btn-block" type="submit">Mentés</button>
            <button class="btn btn-ghost btn-block" type="button" id="previewBtn" hidden>Előnézet új lapon</button>
            <p class="mode-hint" id="modeHint"></p>
            <?php if (($post['status'] ?? '') === 'published'): ?>
                <a class="link center-link" href="<?= base_url('post/' . e($post['slug'])) ?>" target="_blank">Megtekintés az oldalon →</a>
            <?php endif; ?>
        </div>

        <div class="panel side-panel">
            <h3>Kategória</h3>
            <select class="input" name="category_id">
                <option value="">— Nincs —</option>
                <?php foreach ($cats as $c): ?>
                <option value="<?= (int)$c['id'] ?>" <?= (int)($post['category_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="panel side-panel">
            <h3>Oldalsáv <em class="muted side-hint-inline">felülbírálja a globálisat</em></h3>
            <?php $sb = $post['sidebar'] ?? ''; ?>
            <div class="sidebar-picker">
                <label class="sidebar-opt" title="Globális beállítás követése">
                    <input type="radio" name="sidebar" value="" <?= $sb === '' ? 'checked' : '' ?>>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a15 15 0 0 1 0 18 15 15 0 0 1 0-18"/></svg>
                    <span>Alapért.</span>
                </label>
                <label class="sidebar-opt" title="Nincs oldalsáv">
                    <input type="radio" name="sidebar" value="none" <?= $sb === 'none' ? 'checked' : '' ?>>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="14" rx="2"/></svg>
                    <span>Nincs</span>
                </label>
                <label class="sidebar-opt" title="Oldalsáv balra">
                    <input type="radio" name="sidebar" value="left" <?= $sb === 'left' ? 'checked' : '' ?>>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="6" height="14" rx="1.2" fill="currentColor" stroke="none"/><rect x="11" y="5" width="10" height="14" rx="1.2"/></svg>
                    <span>Bal</span>
                </label>
                <label class="sidebar-opt" title="Oldalsáv jobbra">
                    <input type="radio" name="sidebar" value="right" <?= $sb === 'right' ? 'checked' : '' ?>>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="10" height="14" rx="1.2"/><rect x="15" y="5" width="6" height="14" rx="1.2" fill="currentColor" stroke="none"/></svg>
                    <span>Jobb</span>
                </label>
            </div>

            <?php $sbs = $post['sidebar_sticky'] ?? ''; ?>
            <h3 class="sub-panel-title">Ragadós viselkedés</h3>
            <div class="sidebar-picker sticky-picker">
                <label class="sidebar-opt" title="Globális ragadós beállítás követése">
                    <input type="radio" name="sidebar_sticky" value="" <?= $sbs === '' ? 'checked' : '' ?>>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a15 15 0 0 1 0 18 15 15 0 0 1 0-18"/></svg>
                    <span>Alapért.</span>
                </label>
                <label class="sidebar-opt" title="Ne legyen ragadós">
                    <input type="radio" name="sidebar_sticky" value="0" <?= $sbs === '0' ? 'checked' : '' ?>>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="5" y="4" width="14" height="16" rx="2"/><path d="M8 9h8M8 13h5"/></svg>
                    <span>Ki</span>
                </label>
                <label class="sidebar-opt" title="Görgetés közben ragadjon">
                    <input type="radio" name="sidebar_sticky" value="1" <?= $sbs === '1' ? 'checked' : '' ?>>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="5" y="4" width="14" height="16" rx="2"/><path d="M8 8h8M8 12h8"/><path d="M17 3v4M17 17v4"/></svg>
                    <span>Be</span>
                </label>
            </div>
        </div>

        <div class="panel side-panel">
            <h3>Kiemelt kép</h3>
            <input type="hidden" name="featured_image" id="featuredInput" value="<?= e($post['featured_image'] ?? '') ?>">
            <div class="featured-preview <?= empty($post['featured_image']) ? 'empty' : '' ?>" id="featuredPreview"
                 data-base="<?= base_url('/') ?>">
                <?php if (!empty($post['featured_image'])): ?>
                    <img src="<?= base_url(e($post['featured_image'])) ?>" alt="">
                <?php else: ?>
                    <span>Nincs kép kiválasztva</span>
                <?php endif; ?>
            </div>
            <div class="btn-row">
                <button class="btn btn-ghost" type="button" onclick="openMediaPicker(setFeatured)">Kép választása</button>
                <button class="btn btn-ghost danger" type="button" onclick="clearFeatured()" id="clearFeaturedBtn"
                        <?= empty($post['featured_image']) ? 'hidden' : '' ?>>Eltávolítás</button>
            </div>
        </div>
        <?php require __DIR__ . '/_modules-help.php'; ?>
    </aside>
</form>

<?php require __DIR__ . '/_media-picker.php'; ?>
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
<?php require __DIR__ . '/_editor-scripts.php'; ?>
