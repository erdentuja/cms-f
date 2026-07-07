<?php $builderMode = !empty($page['builder']); ?>
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

        <div class="editor-tabs">
            <button type="button" class="tab-btn" data-tab="classic">Klasszikus szerkesztő</button>
            <button type="button" class="tab-btn" data-tab="builder">Blokk-alapú oldalépítő</button>
        </div>

        <div class="editor-wrap panel" id="classicPane">
            <div id="editor"><?= $page['content'] ?? '' ?></div>
            <textarea name="content" id="contentField" hidden></textarea>
        </div>

        <div class="panel builder-pane" id="builderPane">
            <div class="builder-toolbar">
                <span class="muted">Blokk hozzáadása:</span>
                <div class="block-add-buttons">
                    <?php foreach ($blockTypes as $type => $label): ?>
                        <button type="button" class="btn btn-ghost btn-sm" data-add="<?= e($type) ?>"><?= e($label) ?></button>
                    <?php endforeach; ?>
                </div>
            </div>
            <ul class="block-list" id="blockList"></ul>
            <p class="muted pad" id="blockEmpty">Még nincs blokk. Adj hozzá egyet a fenti gombokkal!</p>
        </div>
        <textarea name="blocks" id="blocksField" hidden></textarea>
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

/* ---------- Blokk-alapú oldalépítő ---------- */
const initialBlocks = <?= json_encode(json_decode($page['blocks'] ?? '[]', true) ?: [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const BLOCK_LABELS = <?= json_encode($blockTypes, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const blockList = document.getElementById('blockList');
const blockEmpty = document.getElementById('blockEmpty');
const quillMap = new WeakMap();
const DRAG_ICON = '<svg viewBox="0 0 24 24" fill="currentColor"><circle cx="9" cy="6" r="1.6"/><circle cx="15" cy="6" r="1.6"/><circle cx="9" cy="12" r="1.6"/><circle cx="15" cy="12" r="1.6"/><circle cx="9" cy="18" r="1.6"/><circle cx="15" cy="18" r="1.6"/></svg>';
const TRASH_ICON = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 11v6M14 11v6"/></svg>';

function escAttr(s) {
    const d = document.createElement('div');
    d.textContent = s ?? '';
    return d.innerHTML;
}
function relUrl(url) {
    return url.startsWith(window.CMS_BASE) ? url.slice(window.CMS_BASE.length) : url;
}

function imagePickerField(key, url) {
    url = url || '';
    return `
        <div class="block-img-field">
            <input type="hidden" data-k="${key}" value="${escAttr(url)}">
            <div class="block-img-preview ${url ? '' : 'empty'}">
                ${url ? `<img src="${window.CMS_BASE + url}" alt="">` : '<span>Nincs kép kiválasztva</span>'}
            </div>
            <div class="btn-row">
                <button type="button" class="btn btn-ghost btn-sm" data-pick-img="${key}">Kép választása</button>
                <button type="button" class="btn btn-ghost danger btn-sm" data-clear-img="${key}" ${url ? '' : 'hidden'}>Eltávolítás</button>
            </div>
        </div>`;
}

function repRow(type, it) {
    it = it || {};
    if (type === 'faq') return `
        <div class="rep-row">
            <input class="input" data-i="q" placeholder="Kérdés" value="${escAttr(it.q)}">
            <textarea class="input" data-i="a" rows="2" placeholder="Válasz">${escAttr(it.a)}</textarea>
            <button type="button" class="icon-btn danger" data-remove-row title="Sor törlése">${TRASH_ICON}</button>
        </div>`;
    return `
        <div class="rep-row">
            <input class="input" data-i="value" placeholder="Érték (pl. 120+)" value="${escAttr(it.value)}">
            <input class="input" data-i="label" placeholder="Felirat (pl. elégedett ügyfél)" value="${escAttr(it.label)}">
            <button type="button" class="icon-btn danger" data-remove-row title="Sor törlése">${TRASH_ICON}</button>
        </div>`;
}

function layoutFields(type, d) {
    const defW = ['columns', 'gallery', 'html', 'counters'].includes(type) ? 'wide' : 'normal';
    const w = d.w || defW, bg = d.bg || 'none', pad = d.pad || 'none';
    return `
        <details class="blk-layout">
            <summary>Elrendezés</summary>
            <div class="tpl-form-grid cols-3">
                <label class="field"><span>Szélesség</span><select class="input" data-k="w">
                    <option value="normal" ${w === 'normal' ? 'selected' : ''}>Normál</option>
                    <option value="wide" ${w === 'wide' ? 'selected' : ''}>Széles</option>
                    <option value="full" ${w === 'full' ? 'selected' : ''}>Teljes</option>
                </select></label>
                <label class="field"><span>Háttérsáv</span><select class="input" data-k="bg">
                    <option value="none" ${bg === 'none' ? 'selected' : ''}>Nincs</option>
                    <option value="soft" ${bg === 'soft' ? 'selected' : ''}>Színezett</option>
                </select></label>
                <label class="field"><span>Függőleges térköz</span><select class="input" data-k="pad">
                    <option value="none" ${pad === 'none' ? 'selected' : ''}>Nincs</option>
                    <option value="sm" ${pad === 'sm' ? 'selected' : ''}>Kicsi</option>
                    <option value="md" ${pad === 'md' ? 'selected' : ''}>Közepes</option>
                    <option value="lg" ${pad === 'lg' ? 'selected' : ''}>Nagy</option>
                </select></label>
            </div>
        </details>`;
}

function buildFields(type, data) {
    data = data || {};
    switch (type) {
        case 'heading':
            return `
                <label class="field"><span>Szöveg</span><input class="input" type="text" data-k="text" value="${escAttr(data.text)}" placeholder="Címsor szövege"></label>
                <div class="tpl-form-grid">
                    <label class="field"><span>Szint</span><select class="input" data-k="level">
                        <option value="2" ${data.level == 3 ? '' : 'selected'}>H2 (nagy)</option>
                        <option value="3" ${data.level == 3 ? 'selected' : ''}>H3 (kis)</option>
                    </select></label>
                    <label class="field"><span>Igazítás</span><select class="input" data-k="align">
                        <option value="left" ${data.align === 'center' ? '' : 'selected'}>Balra</option>
                        <option value="center" ${data.align === 'center' ? 'selected' : ''}>Középre</option>
                    </select></label>
                </div>`;
        case 'text':
            return `<div class="richtext" data-k="html" data-quill="1"></div>`;
        case 'image':
            return imagePickerField('url', data.url) + `
                <label class="field"><span>Felirat <em class="muted">(opcionális)</em></span><input class="input" type="text" data-k="caption" value="${escAttr(data.caption)}"></label>`;
        case 'button':
            return `
                <label class="field"><span>Felirat</span><input class="input" type="text" data-k="label" value="${escAttr(data.label)}"></label>
                <label class="field"><span>URL</span><input class="input" type="text" data-k="url" value="${escAttr(data.url)}" placeholder="pl. kapcsolat vagy https://…"></label>
                <div class="tpl-form-grid">
                    <label class="field"><span>Stílus</span><select class="input" data-k="style">
                        <option value="primary" ${data.style === 'outline' ? '' : 'selected'}>Kiemelt</option>
                        <option value="outline" ${data.style === 'outline' ? 'selected' : ''}>Körvonalas</option>
                    </select></label>
                    <label class="check-field" style="margin-top:26px"><input type="checkbox" data-k="new_tab" ${data.new_tab ? 'checked' : ''}><span>Új lapon</span></label>
                </div>`;
        case 'columns':
            return imagePickerField('image', data.image) + `
                <label class="field"><span>Kép oldala</span><select class="input" data-k="image_side">
                    <option value="left" ${data.image_side === 'right' ? '' : 'selected'}>Balra</option>
                    <option value="right" ${data.image_side === 'right' ? 'selected' : ''}>Jobbra</option>
                </select></label>
                <div class="richtext" data-k="html" data-quill="1"></div>`;
        case 'gallery':
            return `
                <div class="gallery-grid" data-gallery></div>
                <input type="hidden" data-k="images" data-json="1" value="[]">
                <button type="button" class="btn btn-ghost btn-sm" data-add-gallery-img>Kép hozzáadása</button>`;
        case 'quote':
            return `
                <label class="field"><span>Idézet szövege</span><textarea class="input" data-k="text" rows="3">${escAttr(data.text)}</textarea></label>
                <div class="tpl-form-grid">
                    <label class="field"><span>Név</span><input class="input" type="text" data-k="author" value="${escAttr(data.author)}"></label>
                    <label class="field"><span>Titulus <em class="muted">(opcionális)</em></span><input class="input" type="text" data-k="role" value="${escAttr(data.role)}"></label>
                </div>` + imagePickerField('image', data.image);
        case 'faq': {
            const rows = ((data.items && data.items.length ? data.items : [{}])).map(it => repRow('faq', it)).join('');
            return `<div class="rep-list" data-rep="faq">${rows}</div>
                <button type="button" class="btn btn-ghost btn-sm" data-add-row="faq">+ Kérdés hozzáadása</button>`;
        }
        case 'counters': {
            const rows = ((data.items && data.items.length ? data.items : [{}])).map(it => repRow('counters', it)).join('');
            return `<div class="rep-list" data-rep="counters">${rows}</div>
                <button type="button" class="btn btn-ghost btn-sm" data-add-row="counters">+ Számláló hozzáadása</button>`;
        }
        case 'video':
            return `
                <label class="field"><span>Videó URL</span><input class="input" type="text" data-k="url" value="${escAttr(data.url)}" placeholder="https://www.youtube.com/watch?v=… vagy https://vimeo.com/…"></label>
                <p class="muted">YouTube és Vimeo linkeket ismer fel, adatvédelmi (nocookie) beágyazással.</p>`;
        case 'map':
            return `
                <label class="field"><span>Térkép beágyazási URL</span><input class="input" type="text" data-k="embed" value="${escAttr(data.embed)}" placeholder="https://www.google.com/maps/embed?pb=… vagy OpenStreetMap embed"></label>
                <p class="muted">Google Maps: Megosztás → Térkép beágyazása → másold az iframe <code>src</code> értékét. OpenStreetMap: Megosztás → HTML.</p>`;
        case 'spacer':
            return `<label class="field"><span>Magasság</span><select class="input" data-k="size">
                    <option value="sm" ${data.size === 'sm' ? 'selected' : ''}>Kicsi</option>
                    <option value="md" ${!data.size || data.size === 'md' ? 'selected' : ''}>Közepes</option>
                    <option value="lg" ${data.size === 'lg' ? 'selected' : ''}>Nagy</option>
                </select></label>`;
        case 'html':
            return `<textarea class="input mono" data-k="code" rows="6" placeholder="Egyéni HTML kód…">${escAttr(data.code)}</textarea>`;
        default:
            return '';
    }
}

function toggleEmpty() {
    blockEmpty.hidden = blockList.children.length > 0;
    updateModeHint();
}

/* A látogatók a mentett mód tartalmát látják — mindig jelezzük, melyik az */
function updateModeHint() {
    const hint = document.getElementById('modeHint');
    const builder = document.getElementById('builderMode').value === '1';
    const hasBlocks = document.getElementById('blockList').children.length > 0;
    const trap = !builder && hasBlocks;
    hint.textContent = builder
        ? 'Mentéskor az oldalépítő tartalma kerül az oldalra.'
        : 'Mentéskor a klasszikus szerkesztő tartalma kerül az oldalra.'
          + (trap ? ' Az oldalépítő blokkjai rejtve maradnak!' : '');
    hint.classList.toggle('warn', trap);
}

function galleryImages(card) {
    try { return JSON.parse(card.querySelector('[data-k="images"]').value || '[]'); } catch { return []; }
}
function setGalleryImages(card, arr) {
    card.querySelector('[data-k="images"]').value = JSON.stringify(arr);
    card.querySelector('[data-gallery]').innerHTML = arr.map((u, i) => `
        <div class="gallery-thumb">
            <img src="${window.CMS_BASE + u}" alt="">
            <button type="button" class="icon-btn danger" data-remove-gallery-img="${i}" title="Törlés">${TRASH_ICON}</button>
        </div>`).join('');
}

function addBlock(type, data) {
    const li = document.createElement('li');
    li.className = 'block-card';
    li.dataset.type = type;
    li.innerHTML = `
        <div class="block-card-head">
            <span class="drag-handle" draggable="true" title="Húzd a sorrendhez">${DRAG_ICON}</span>
            <strong class="block-card-label">${BLOCK_LABELS[type] || type}</strong>
            <button type="button" class="icon-btn danger" data-remove-block title="Blokk törlése">${TRASH_ICON}</button>
        </div>
        <div class="block-card-body">${buildFields(type, data)}${layoutFields(type, data)}</div>`;
    blockList.appendChild(li);

    if (type === 'gallery') setGalleryImages(li, (data && data.images) || []);

    li.querySelectorAll('[data-quill]').forEach(el => {
        const q = new Quill(el, {
            theme: 'snow',
            modules: { toolbar: [['bold', 'italic', 'underline'], ['link'], [{ list: 'ordered' }, { list: 'bullet' }], ['clean']] },
        });
        if (data && data.html) q.clipboard.dangerouslyPasteHTML(data.html);
        quillMap.set(el, q);
    });

    toggleEmpty();
}

function serializeBlock(li) {
    const obj = { type: li.dataset.type };
    const rep = li.querySelector('[data-rep]');
    if (rep) {
        obj.items = [...rep.querySelectorAll('.rep-row')].map(r => {
            const o = {};
            r.querySelectorAll('[data-i]').forEach(f => o[f.dataset.i] = f.value);
            return o;
        });
    }
    li.querySelectorAll('[data-k]').forEach(el => {
        const k = el.dataset.k;
        if (el.dataset.quill) {
            const q = quillMap.get(el);
            obj[k] = q ? q.getSemanticHTML().replaceAll('&nbsp;', ' ') : '';
        } else if (el.type === 'checkbox') {
            obj[k] = el.checked;
        } else if (el.dataset.json) {
            try { obj[k] = JSON.parse(el.value || '[]'); } catch { obj[k] = []; }
        } else {
            obj[k] = el.value;
        }
    });
    return obj;
}

document.querySelectorAll('[data-add]').forEach(btn => {
    btn.addEventListener('click', () => addBlock(btn.dataset.add, {}));
});

blockList.addEventListener('click', e => {
    const pickBtn = e.target.closest('[data-pick-img]');
    if (pickBtn) {
        const key = pickBtn.dataset.pickImg;
        const wrap = pickBtn.closest('.block-img-field');
        openMediaPicker(url => {
            wrap.querySelector(`[data-k="${key}"]`).value = relUrl(url);
            const prev = wrap.querySelector('.block-img-preview');
            prev.classList.remove('empty');
            prev.innerHTML = `<img src="${url}" alt="">`;
            wrap.querySelector(`[data-clear-img="${key}"]`).hidden = false;
        });
        return;
    }
    const clearBtn = e.target.closest('[data-clear-img]');
    if (clearBtn) {
        const key = clearBtn.dataset.clearImg;
        const wrap = clearBtn.closest('.block-img-field');
        wrap.querySelector(`[data-k="${key}"]`).value = '';
        const prev = wrap.querySelector('.block-img-preview');
        prev.classList.add('empty');
        prev.innerHTML = '<span>Nincs kép kiválasztva</span>';
        clearBtn.hidden = true;
        return;
    }
    const addGalleryBtn = e.target.closest('[data-add-gallery-img]');
    if (addGalleryBtn) {
        const card = addGalleryBtn.closest('.block-card');
        openMediaPicker(url => setGalleryImages(card, [...galleryImages(card), relUrl(url)]));
        return;
    }
    const removeGalleryBtn = e.target.closest('[data-remove-gallery-img]');
    if (removeGalleryBtn) {
        const card = removeGalleryBtn.closest('.block-card');
        const idx = parseInt(removeGalleryBtn.dataset.removeGalleryImg, 10);
        const arr = galleryImages(card);
        arr.splice(idx, 1);
        setGalleryImages(card, arr);
        return;
    }
    const addRowBtn = e.target.closest('[data-add-row]');
    if (addRowBtn) {
        addRowBtn.closest('.block-card-body').querySelector('[data-rep]')
            .insertAdjacentHTML('beforeend', repRow(addRowBtn.dataset.addRow));
        return;
    }
    const removeRowBtn = e.target.closest('[data-remove-row]');
    if (removeRowBtn) {
        removeRowBtn.closest('.rep-row').remove();
        return;
    }
    const removeBtn = e.target.closest('[data-remove-block]');
    if (removeBtn) {
        if (confirm('Törlöd ezt a blokkot?')) removeBtn.closest('.block-card').remove();
        toggleEmpty();
    }
});

// Drag & drop sorrendezés (csak a fogantyúról indítható)
(function () {
    let dragged = null;
    blockList.addEventListener('dragstart', e => {
        const handle = e.target.closest('.drag-handle');
        if (!handle) return;
        dragged = handle.closest('.block-card');
        dragged.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setDragImage(dragged, 20, 20);
    });
    blockList.addEventListener('dragover', e => {
        e.preventDefault();
        if (!dragged) return;
        const over = e.target.closest('.block-card');
        if (!over || over === dragged) return;
        const rect = over.getBoundingClientRect();
        const before = e.clientY < rect.top + rect.height / 2;
        blockList.insertBefore(dragged, before ? over : over.nextSibling);
    });
    blockList.addEventListener('dragend', () => {
        dragged?.classList.remove('dragging');
        dragged = null;
    });
})();

initialBlocks.forEach(b => addBlock(b.type, b));
toggleEmpty();

// Szerkesztő mód fül
const tabBtns = document.querySelectorAll('.tab-btn');
const classicPane = document.getElementById('classicPane');
const builderPane = document.getElementById('builderPane');
const builderModeInput = document.getElementById('builderMode');
const previewBtn = document.getElementById('previewBtn');
function setTab(tab) {
    tabBtns.forEach(b => b.classList.toggle('active', b.dataset.tab === tab));
    classicPane.hidden = tab !== 'classic';
    builderPane.hidden = tab !== 'builder';
    builderModeInput.value = tab === 'builder' ? '1' : '0';
    previewBtn.hidden = tab !== 'builder';
    updateModeHint();
}
tabBtns.forEach(b => b.addEventListener('click', () => setTab(b.dataset.tab)));
setTab(<?= json_encode($builderMode ? 'builder' : 'classic') ?>);

// Élő előnézet: a blokkokat mentés nélkül POST-oljuk egy új lapra
previewBtn.addEventListener('click', () => {
    const f = document.createElement('form');
    f.method = 'post';
    f.action = <?= json_encode(base_url('admin/pages/preview')) ?>;
    f.target = '_blank';
    const add = (n, v) => {
        const i = document.createElement('input');
        i.type = 'hidden'; i.name = n; i.value = v;
        f.appendChild(i);
    };
    add('_csrf', window.CSRF);
    add('title', document.querySelector('[name=title]').value || 'Előnézet');
    add('blocks', JSON.stringify([...blockList.querySelectorAll('.block-card')].map(serializeBlock)));
    document.body.appendChild(f);
    f.submit();
    f.remove();
});

document.getElementById('editForm').addEventListener('submit', () => {
    // getSemanticHTML() a szóközöket &nbsp;-re cseréli — visszaalakítjuk
    document.getElementById('contentField').value = quill.getSemanticHTML().replaceAll('&nbsp;', ' ');
    const blocks = [...blockList.querySelectorAll('.block-card')].map(serializeBlock);
    document.getElementById('blocksField').value = JSON.stringify(blocks);
});
</script>
