<form method="post" action="<?= base_url('admin/posts/save') ?>" class="edit-layout" id="editForm">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int)($post['id'] ?? 0) ?>">

    <div class="edit-main">
        <header class="page-head">
            <div>
                <a class="back-link" href="<?= base_url('admin/posts') ?>">← Posztok</a>
                <h1><?= e($title) ?></h1>
            </div>
        </header>

        <input class="input input-title" type="text" name="title" placeholder="A poszt címe…" required
               value="<?= e($post['title'] ?? '') ?>">

        <div class="editor-wrap panel">
            <div id="editor"><?= $post['content'] ?? '' ?></div>
            <textarea name="content" id="contentField" hidden></textarea>
        </div>

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
<script>
const quill = new Quill('#editor', {
    theme: 'snow',
    placeholder: 'Kezdj el írni…',
    modules: {
        toolbar: {
            container: [
                [{ header: [2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                ['blockquote', 'code-block'],
                [{ list: 'ordered' }, { list: 'bullet' }],
                ['link', 'image'],
                ['clean'],
            ],
            handlers: {
                image() {
                    openMediaPicker(url => {
                        const range = quill.getSelection(true);
                        quill.insertEmbed(range.index, 'image', url);
                    }, true);
                },
            },
        },
    },
});
document.getElementById('editForm').addEventListener('submit', () => {
    // getSemanticHTML() a szóközöket &nbsp;-re cseréli — visszaalakítjuk
    document.getElementById('contentField').value = quill.getSemanticHTML().replaceAll('&nbsp;', ' ');
});
</script>
