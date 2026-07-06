<header class="page-head">
    <div>
        <h1>Menük</h1>
        <p class="muted">Fejléc- és láblécmenü — húzd az elemeket a sorrend módosításához</p>
    </div>
</header>

<div class="dash-grid">
    <div class="menu-columns">
        <?php foreach (['header' => 'Fejléc menü', 'footer' => 'Lábléc menü'] as $loc => $locLabel): ?>
        <section class="panel">
            <div class="panel-head"><h2><?= $locLabel ?></h2></div>
            <?php if ($items[$loc]): ?>
            <ul class="menu-list" data-location="<?= $loc ?>">
                <?php foreach ($items[$loc] as $mi): ?>
                <li class="menu-item" draggable="true" data-id="<?= (int)$mi['id'] ?>">
                    <span class="drag-handle" title="Húzd a sorrendhez">
                        <svg viewBox="0 0 24 24" fill="currentColor"><circle cx="9" cy="6" r="1.6"/><circle cx="15" cy="6" r="1.6"/><circle cx="9" cy="12" r="1.6"/><circle cx="15" cy="12" r="1.6"/><circle cx="9" cy="18" r="1.6"/><circle cx="15" cy="18" r="1.6"/></svg>
                    </span>
                    <span class="menu-item-label">
                        <strong><?= e($mi['label']) ?></strong>
                        <span class="muted"><?= e($mi['url']) ?><?= $mi['new_tab'] ? ' ↗' : '' ?></span>
                    </span>
                    <span class="row-actions">
                        <button class="icon-btn" type="button" title="Szerkesztés"
                                onclick='editMenuItem(<?= json_encode(['id'=>$mi['id'],'label'=>$mi['label'],'url'=>$mi['url'],'location'=>$mi['location'],'new_tab'=>(int)$mi['new_tab']], JSON_HEX_APOS) ?>)'>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17 3a2.8 2.8 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5z"/></svg>
                        </button>
                        <form method="post" action="<?= base_url('admin/menu/delete') ?>" data-confirm="Törlöd a menüelemet?">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int)$mi['id'] ?>">
                            <button class="icon-btn danger" type="submit" title="Törlés">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 11v6M14 11v6"/></svg>
                            </button>
                        </form>
                    </span>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php else: ?>
            <p class="muted pad">Ez a menü még üres. Adj hozzá elemet a jobb oldali űrlappal!</p>
            <?php endif; ?>
        </section>
        <?php endforeach; ?>
    </div>

    <div class="panel side-panel">
        <h3 id="menuFormTitle">Új menüelem</h3>
        <form method="post" action="<?= base_url('admin/menu/save') ?>" id="menuForm">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="miId" value="0">
            <label class="field">
                <span>Gyorsválasztás <em class="muted">(kitölti a mezőket)</em></span>
                <select class="input" id="miQuick" onchange="quickPick(this)">
                    <option value="">— Válassz tartalmat —</option>
                    <option value='{"label":"Kezdőlap","url":"/"}'>Kezdőlap</option>
                    <optgroup label="Oldalak">
                        <?php foreach ($pages as $p): ?>
                        <option value='<?= e(json_encode(['label'=>$p['title'],'url'=>$p['slug']], JSON_HEX_APOS|JSON_UNESCAPED_UNICODE)) ?>'><?= e($p['title']) ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                    <optgroup label="Kategóriák">
                        <?php foreach ($cats as $c): ?>
                        <option value='<?= e(json_encode(['label'=>$c['name'],'url'=>'category/'.$c['slug']], JSON_HEX_APOS|JSON_UNESCAPED_UNICODE)) ?>'><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                </select>
            </label>
            <label class="field">
                <span>Felirat</span>
                <input class="input" type="text" name="label" id="miLabel" required>
            </label>
            <label class="field">
                <span>URL <em class="muted">(belső útvonal vagy https://…)</em></span>
                <input class="input" type="text" name="url" id="miUrl" required placeholder="pl. rolunk vagy https://example.com">
            </label>
            <label class="field">
                <span>Hely</span>
                <select class="input" name="location" id="miLocation">
                    <option value="header">Fejléc</option>
                    <option value="footer">Lábléc</option>
                </select>
            </label>
            <label class="check-field">
                <input type="checkbox" name="new_tab" id="miNewTab">
                <span>Megnyitás új lapon</span>
            </label>
            <div class="btn-row">
                <button class="btn btn-primary" type="submit">Mentés</button>
                <button class="btn btn-ghost" type="button" onclick="resetMenuForm()" id="miCancel" hidden>Mégse</button>
            </div>
        </form>
    </div>
</div>

<script>
function quickPick(sel) {
    if (!sel.value) return;
    const d = JSON.parse(sel.value);
    document.getElementById('miLabel').value = d.label;
    document.getElementById('miUrl').value = d.url;
}
function editMenuItem(mi) {
    document.getElementById('menuFormTitle').textContent = 'Menüelem szerkesztése';
    document.getElementById('miId').value = mi.id;
    document.getElementById('miLabel').value = mi.label;
    document.getElementById('miUrl').value = mi.url;
    document.getElementById('miLocation').value = mi.location;
    document.getElementById('miNewTab').checked = !!mi.new_tab;
    document.getElementById('miCancel').hidden = false;
    document.getElementById('miLabel').focus();
}
function resetMenuForm() {
    document.getElementById('menuFormTitle').textContent = 'Új menüelem';
    document.getElementById('menuForm').reset();
    document.getElementById('miId').value = 0;
    document.getElementById('miCancel').hidden = true;
}

// Drag & drop sorrendezés
document.querySelectorAll('.menu-list').forEach(list => {
    let dragged = null;
    list.addEventListener('dragstart', e => {
        dragged = e.target.closest('.menu-item');
        dragged.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
    });
    list.addEventListener('dragover', e => {
        e.preventDefault();
        const over = e.target.closest('.menu-item');
        if (!over || over === dragged || !dragged) return;
        const rect = over.getBoundingClientRect();
        const before = e.clientY < rect.top + rect.height / 2;
        list.insertBefore(dragged, before ? over : over.nextSibling);
    });
    list.addEventListener('dragend', async () => {
        if (!dragged) return;
        dragged.classList.remove('dragging');
        dragged = null;
        const ids = [...list.querySelectorAll('.menu-item')].map(li => li.dataset.id);
        await fetch(window.CMS_BASE + 'admin/menu/reorder', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF': window.CSRF},
            body: JSON.stringify({ids}),
        });
    });
});
window.CMS_BASE = <?= json_encode(base_url('/')) ?>;
window.CSRF = <?= json_encode(csrf_token()) ?>;
</script>
