<form method="post" action="<?= base_url('admin/pages/save') ?>" class="edit-layout" id="editForm">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int)($page['id'] ?? 0) ?>">

    <div class="edit-main">
        <header class="page-head">
            <div>
                <a class="back-link" href="<?= base_url('admin/pages') ?>">← Oldalak</a>
                <h1><?= e($title) ?></h1>
            </div>
        </header>

        <input class="input input-title" type="text" name="title" placeholder="Az oldal címe…" required
               value="<?= e($page['title'] ?? '') ?>">

        <div class="editor-wrap panel">
            <div id="editor"><?= $page['content'] ?? '' ?></div>
            <textarea name="content" id="contentField" hidden></textarea>
        </div>
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
            <label class="check-field">
                <input type="checkbox" name="show_in_menu" <?= ($page['show_in_menu'] ?? 1) ? 'checked' : '' ?>>
                <span>Megjelenítés a menüben</span>
            </label>
            <label class="field">
                <span>Sorrend a menüben</span>
                <input class="input" type="number" name="menu_order" value="<?= (int)($page['menu_order'] ?? 0) ?>">
            </label>
            <button class="btn btn-primary btn-block" type="submit">Mentés</button>
            <?php if (($page['status'] ?? '') === 'published' && !empty($page['slug'])): ?>
                <a class="link center-link" href="<?= base_url(e($page['slug'])) ?>" target="_blank">Megtekintés az oldalon →</a>
            <?php endif; ?>
        </div>
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
