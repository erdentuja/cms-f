<header class="page-head">
    <div>
        <h1>Kategóriák</h1>
        <p class="muted"><?= count($cats) ?> kategória</p>
    </div>
</header>

<div class="dash-grid">
    <div class="panel">
        <?php if ($cats): ?>
        <table class="table">
            <thead><tr><th>Név</th><th>URL</th><th>Posztok</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($cats as $c): ?>
                <tr>
                    <td><span class="cat-dot" style="--c:<?= e($c['color']) ?>"><strong><?= e($c['name']) ?></strong></span></td>
                    <td class="muted">/category/<?= e($c['slug']) ?></td>
                    <td class="muted"><?= (int)$c['cnt'] ?></td>
                    <td class="row-actions">
                        <button class="icon-btn" type="button" title="Szerkesztés"
                                onclick='editCategory(<?= json_encode(['id'=>$c['id'],'name'=>$c['name'],'description'=>$c['description'],'color'=>$c['color'],'seo_title'=>$c['seo_title'] ?? '','seo_description'=>$c['seo_description'] ?? '','seo_image'=>$c['seo_image'] ?? '','seo_canonical'=>$c['seo_canonical'] ?? '','seo_robots'=>$c['seo_robots'] ?? 'index,follow'], JSON_HEX_APOS|JSON_UNESCAPED_UNICODE) ?>)'>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17 3a2.8 2.8 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5z"/></svg>
                        </button>
                        <form method="post" action="<?= base_url('admin/categories/delete') ?>" data-confirm="Törlöd a kategóriát? A posztok kategória nélkül maradnak.">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                            <button class="icon-btn danger" type="submit" title="Törlés">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 11v6M14 11v6"/></svg>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?><div class="empty-state"><p>Még nincs kategória.</p></div><?php endif; ?>
    </div>

    <div class="panel side-panel">
        <h3 id="catFormTitle">Új kategória</h3>
        <form method="post" action="<?= base_url('admin/categories/save') ?>" id="catForm">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="catId" value="0">
            <label class="field">
                <span>Név</span>
                <input class="input" type="text" name="name" id="catName" required>
            </label>
            <label class="field">
                <span>Leírás</span>
                <textarea class="input" name="description" id="catDesc" rows="2"></textarea>
            </label>
            <label class="field">
                <span>Szín</span>
                <input class="input input-color" type="color" name="color" id="catColor" value="#6366f1">
            </label>
            <label class="field">
                <span>SEO cím</span>
                <input class="input" type="text" name="seo_title" id="catSeoTitle" maxlength="70">
            </label>
            <label class="field">
                <span>Meta leírás</span>
                <textarea class="input" name="seo_description" id="catSeoDesc" rows="2" maxlength="160"></textarea>
            </label>
            <label class="field">
                <span>OG kép</span>
                <input class="input" type="text" name="seo_image" id="catSeoImage" placeholder="uploads/... vagy https://...">
            </label>
            <label class="field">
                <span>Canonical URL</span>
                <input class="input" type="url" name="seo_canonical" id="catSeoCanonical">
            </label>
            <label class="field">
                <span>Robots</span>
                <select class="input" name="seo_robots" id="catSeoRobots">
                    <?php foreach (['index,follow', 'noindex,follow', 'index,nofollow', 'noindex,nofollow'] as $opt): ?>
                    <option value="<?= e($opt) ?>"><?= e($opt) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <div class="btn-row">
                <button class="btn btn-primary" type="submit">Mentés</button>
                <button class="btn btn-ghost" type="button" onclick="resetCategoryForm()" id="catCancel" hidden>Mégse</button>
            </div>
        </form>
    </div>
</div>

<script>
function editCategory(c) {
    document.getElementById('catFormTitle').textContent = 'Kategória szerkesztése';
    document.getElementById('catId').value = c.id;
    document.getElementById('catName').value = c.name;
    document.getElementById('catDesc').value = c.description || '';
    document.getElementById('catColor').value = c.color || '#6366f1';
    document.getElementById('catSeoTitle').value = c.seo_title || '';
    document.getElementById('catSeoDesc').value = c.seo_description || '';
    document.getElementById('catSeoImage').value = c.seo_image || '';
    document.getElementById('catSeoCanonical').value = c.seo_canonical || '';
    document.getElementById('catSeoRobots').value = c.seo_robots || 'index,follow';
    document.getElementById('catCancel').hidden = false;
    document.getElementById('catName').focus();
}
function resetCategoryForm() {
    document.getElementById('catFormTitle').textContent = 'Új kategória';
    document.getElementById('catForm').reset();
    document.getElementById('catId').value = 0;
    document.getElementById('catCancel').hidden = true;
}
</script>
