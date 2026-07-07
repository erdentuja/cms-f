/* ===== Aurora CMS — Admin JS ===== */

// Confirm dialogs on forms
document.addEventListener('submit', e => {
    const msg = e.target.dataset?.confirm;
    if (msg && !confirm(msg)) e.preventDefault();
});

// Copy-to-clipboard buttons
document.addEventListener('click', e => {
    const btn = e.target.closest('[data-copy]');
    if (!btn) return;
    navigator.clipboard.writeText(btn.dataset.copy).then(() => {
        btn.classList.add('copied');
        const old = btn.title;
        btn.title = 'Másolva!';
        setTimeout(() => { btn.title = old; btn.classList.remove('copied'); }, 1200);
    });
});

// Auto-hide flash messages
const flash = document.getElementById('flash');
if (flash) setTimeout(() => { flash.style.transition = 'opacity .4s'; flash.style.opacity = '0'; setTimeout(() => flash.remove(), 450); }, 3500);

/* ---------- Upload helper ---------- */
async function uploadFile(file, onProgress) {
    const fd = new FormData();
    fd.append('file', file);
    fd.append('_csrf', window.CSRF);
    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', window.CMS_BASE + 'admin/media/upload');
        xhr.upload.onprogress = ev => { if (ev.lengthComputable && onProgress) onProgress(ev.loaded / ev.total); };
        xhr.onload = () => {
            try {
                const data = JSON.parse(xhr.responseText);
                xhr.status < 400 ? resolve(data) : reject(new Error(data.error || 'Hiba a feltöltéskor'));
            } catch { reject(new Error('Váratlan válasz a szervertől')); }
        };
        xhr.onerror = () => reject(new Error('Hálózati hiba'));
        xhr.send(fd);
    });
}

/* ---------- Media library page ---------- */
const dropzone = document.getElementById('dropzone');
const mediaUpload = document.getElementById('mediaUpload');
if (dropzone) {
    const bar = document.querySelector('#uploadProgress div');
    const progressBox = document.getElementById('uploadProgress');

    async function handleFiles(files) {
        if (!files.length) return;
        progressBox.hidden = false;
        let done = 0;
        for (const file of files) {
            try {
                await uploadFile(file, p => { bar.style.width = ((done + p) / files.length * 100) + '%'; });
            } catch (err) {
                alert(file.name + ': ' + err.message);
            }
            done++;
            bar.style.width = (done / files.length * 100) + '%';
        }
        location.reload();
    }

    ['dragenter', 'dragover'].forEach(ev => dropzone.addEventListener(ev, e => { e.preventDefault(); dropzone.classList.add('dragover'); }));
    ['dragleave', 'drop'].forEach(ev => dropzone.addEventListener(ev, e => { e.preventDefault(); dropzone.classList.remove('dragover'); }));
    dropzone.addEventListener('drop', e => handleFiles([...e.dataTransfer.files]));
    dropzone.addEventListener('click', () => mediaUpload?.click());
    mediaUpload?.addEventListener('change', () => handleFiles([...mediaUpload.files]));
}

/* ---------- Media picker modal ---------- */
let pickerCallback = null;

async function openMediaPicker(cb) {
    pickerCallback = cb;
    const modal = document.getElementById('mediaPicker');
    if (!modal) return;
    modal.hidden = false;
    document.body.style.overflow = 'hidden';
    await refreshPickerGrid();
}

function closeMediaPicker() {
    const modal = document.getElementById('mediaPicker');
    if (modal) modal.hidden = true;
    document.body.style.overflow = '';
    pickerCallback = null;
}

async function refreshPickerGrid() {
    const grid = document.getElementById('pickerGrid');
    grid.innerHTML = '<p class="muted pad">Betöltés…</p>';
    try {
        const res = await fetch(window.CMS_BASE + 'admin/media/list');
        const data = await res.json();
        if (!data.items.length) {
            grid.innerHTML = '<p class="muted pad">Még nincs kép a médiatárban. Tölts fel egyet a fenti gombbal!</p>';
            return;
        }
        grid.innerHTML = '';
        for (const item of data.items) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'pick';
            btn.innerHTML = `<img src="${item.thumb_url}" alt="" loading="lazy" title="${item.filename}">`;
            btn.addEventListener('click', () => {
                const cb = pickerCallback;
                closeMediaPicker();
                cb?.(item.url, item);
            });
            grid.appendChild(btn);
        }
    } catch {
        grid.innerHTML = '<p class="muted pad">Nem sikerült betölteni a médiatárat.</p>';
    }
}

document.getElementById('mediaPicker')?.addEventListener('click', e => {
    if (e.target.id === 'mediaPicker') closeMediaPicker();
});
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeMediaPicker();
});
document.getElementById('pickerUpload')?.addEventListener('change', async e => {
    const file = e.target.files[0];
    if (!file) return;
    try {
        await uploadFile(file);
        await refreshPickerGrid();
    } catch (err) { alert(err.message); }
    e.target.value = '';
});

/* ---------- Featured image ---------- */
function setFeatured(url) {
    const input = document.getElementById('featuredInput');
    const preview = document.getElementById('featuredPreview');
    if (!input || !preview) return;
    const base = window.CMS_BASE;
    input.value = url.startsWith(base) ? url.slice(base.length) : url;
    preview.classList.remove('empty');
    preview.innerHTML = `<img src="${url}" alt="">`;
    document.getElementById('clearFeaturedBtn').hidden = false;
}

/* ---------- Szerkesztő-oldalsáv: összecsukható panelek ---------- */
/* A panelek húzását/áthelyezését a _panel-layout-scripts.php végzi; ez a rész
   csak az összecsukás gombot adja hozzá és a nyitott/zárt állapotot jegyzi meg. */
(function () {
    const form = document.getElementById('editForm');
    if (!form) return;
    const panels = [...form.querySelectorAll('.side-panel')];
    if (!panels.length) return;

    const storeKey = 'aurora-panels-closed:' + location.pathname.replace(/\/(\d+|new)$/, '');
    const idOf = (p, i) => p.dataset.panelId || (p.querySelector('h3')?.textContent || 'panel-' + i)
        .toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[^a-z0-9]+/g, '-');

    let closed = [];
    try { closed = JSON.parse(localStorage.getItem(storeKey)) || []; } catch { /* sérült állapot: alapértelmezés */ }

    function save() {
        localStorage.setItem(storeKey, JSON.stringify(panels.filter(p => p.classList.contains('closed')).map(idOf)));
    }

    panels.forEach((p, i) => {
        const h = p.querySelector('h3');
        if (!h) return;
        p.classList.add('sortable-panel');
        if (closed.includes(idOf(p, i))) p.classList.add('closed');

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'panel-toggle';
        btn.setAttribute('aria-label', 'Összecsukás / kinyitás');
        btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>';
        btn.addEventListener('click', () => { p.classList.toggle('closed'); save(); });
        h.appendChild(btn);
    });
})();

function clearFeatured() {
    const input = document.getElementById('featuredInput');
    const preview = document.getElementById('featuredPreview');
    if (!input || !preview) return;
    input.value = '';
    preview.classList.add('empty');
    preview.innerHTML = '<span>Nincs kép kiválasztva</span>';
    document.getElementById('clearFeaturedBtn').hidden = true;
}
