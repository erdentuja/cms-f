<?php
$builderMode = !empty($page['builder']);
$content = $page['content'] ?? '';
$blocksData = json_decode($page['blocks'] ?? '[]', true) ?: [];
$previewAction = base_url('admin/pages/preview');
?>
<form method="post" action="<?= base_url('admin/pages/save') ?>" class="edit-layout" id="editForm">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int)($page['id'] ?? 0) ?>">
    <input type="hidden" name="builder" id="builderMode" value="<?= $builderMode ? 1 : 0 ?>">

    <div class="edit-main">
        <header class="page-head">
            <div>
                <a class="back-link" href="<?= base_url('admin/pages') ?>">← Oldalak</a>
                <h1><?= e($title) ?></h1>
            </div>
        </header>

        <input class="input input-title" type="text" name="title" placeholder="Az oldal címe…" required
               value="<?= e($page['title'] ?? '') ?>">

        <?php require __DIR__ . '/_editor-content.php'; ?>
    </div>

    <aside class="edit-side">
        <div class="panel side-panel">
            <h3>Publikálás</h3>
            <label class="field">
                <span>Státusz</span>
                <select class="input" name="status">
                    <option value="published" <?= ($page['status'] ?? 'published') === 'published' ? 'selected' : '' ?>>Publikált</option>
                    <option value="draft" <?= ($page['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Vázlat</option>
                </select>
            </label>
            <label class="field">
                <span>URL (slug)</span>
                <input class="input" type="text" name="slug" value="<?= e($page['slug'] ?? '') ?>" placeholder="automatikus a címből">
            </label>
            <button class="btn btn-primary btn-block" type="submit">Mentés</button>
            <button class="btn btn-ghost btn-block" type="button" id="previewBtn" hidden>Előnézet új lapon</button>
            <p class="mode-hint" id="modeHint"></p>
            <p class="muted side-hint">A menübe a <a class="link" href="<?= base_url('admin/menu') ?>">Menük</a> oldalon tudod felvenni.</p>
            <?php if (($page['status'] ?? '') === 'published' && !empty($page['slug'])): ?>
                <a class="link center-link" href="<?= base_url(e($page['slug'])) ?>" target="_blank">Megtekintés az oldalon →</a>
            <?php endif; ?>
        </div>
        <?php require __DIR__ . '/_modules-help.php'; ?>
    </aside>
</form>

<?php require __DIR__ . '/_media-picker.php'; ?>
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
<?php require __DIR__ . '/_editor-scripts.php'; ?>
