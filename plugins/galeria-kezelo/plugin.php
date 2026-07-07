<?php
declare(strict_types=1);

/* Galéria kezelő — névvel ellátott galériák a médiatárból, [galeria id=N] shortcode-dal */

db()->exec("CREATE TABLE IF NOT EXISTS galleries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    images TEXT NOT NULL DEFAULT '[]',
    created_at TEXT NOT NULL DEFAULT (datetime('now','localtime'))
)");
$cols = array_column(db()->query('PRAGMA table_info(galleries)')->fetchAll(), 'name');
if (!in_array('layout', $cols, true)) db()->exec("ALTER TABLE galleries ADD COLUMN layout TEXT NOT NULL DEFAULT 'grid'");

/* ---- Shortcode a tartalomban ---- */

shortcode_register('galeria',
    fn(array $a) => galeria_html((int)($a['id'] ?? 0), (string)($a['layout'] ?? '')),
    '[galeria id=1]', 'A Galériák menüben összeállított galéria — opcionális: [galeria id=1 layout=mosaic]');

function galeria_layouts(): array {
    return [
        'grid' => ['label' => 'Rács', 'icon' => ['111', '111', '111']],
        'squares' => ['label' => 'Négyzetek', 'icon' => ['11', '11']],
        'masonry' => ['label' => 'Masonry', 'icon' => ['121', '212']],
        'mosaic' => ['label' => 'Mozaik', 'icon' => ['211', '112', '111']],
        'featured' => ['label' => 'Kiemelt', 'icon' => ['211', '111']],
    ];
}

function galeria_layout_key(string $layout): string {
    return array_key_exists($layout, galeria_layouts()) ? $layout : 'grid';
}

function galeria_images_of(string $json): array {
    $imgs = json_decode($json, true);
    return is_array($imgs) ? array_values(array_filter($imgs, fn($u) => is_string($u) && trim($u) !== '')) : [];
}

function galeria_html(int $id, string $layoutOverride = ''): string {
    $st = db()->prepare('SELECT images, layout FROM galleries WHERE id=?');
    $st->execute([$id]);
    $row = $st->fetch();
    if (!$row) return '';
    $images = galeria_images_of((string)$row['images']);
    if (!$images) return '';
    $layout = galeria_layout_key($layoutOverride !== '' ? $layoutOverride : (string)($row['layout'] ?? 'grid'));
    $items = '';
    // A galéria blokkal azonos markup: a front .gal stílusa és a lightbox is érvényes rá
    foreach ($images as $u) $items .= '<img src="' . e(base_url($u)) . '" alt="" loading="lazy">';
    return '<div class="gal gk-gallery gk-layout-' . e($layout) . '">' . $items . '</div>';
}

add_action('front_head', function (): void { ?>
<style>
.gk-gallery { --gk-gap: 14px; gap: var(--gk-gap); }
.gk-gallery img { width: 100%; height: 100%; object-fit: cover; border-radius: var(--radius); }
.gk-layout-grid { grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); }
.gk-layout-squares { grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); }
.gk-layout-squares img { aspect-ratio: 1; }
.gk-layout-masonry { grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); align-items: start; }
.gk-layout-masonry img:nth-child(3n+1) { aspect-ratio: 4 / 5; }
.gk-layout-masonry img:nth-child(3n+2) { aspect-ratio: 1; }
.gk-layout-masonry img:nth-child(3n) { aspect-ratio: 5 / 4; }
.gk-layout-mosaic { grid-template-columns: repeat(6, 1fr); grid-auto-flow: dense; }
.gk-layout-mosaic img { min-height: 150px; }
.gk-layout-mosaic img:nth-child(5n+1) { grid-column: span 3; grid-row: span 2; }
.gk-layout-mosaic img:nth-child(5n+2), .gk-layout-mosaic img:nth-child(5n+3) { grid-column: span 3; }
.gk-layout-mosaic img:nth-child(5n+4), .gk-layout-mosaic img:nth-child(5n) { grid-column: span 2; }
.gk-layout-featured { grid-template-columns: repeat(4, 1fr); grid-auto-flow: dense; }
.gk-layout-featured img { min-height: 150px; }
.gk-layout-featured img:first-child { grid-column: span 2; grid-row: span 2; }
.gk-layout-featured img:not(:first-child) { grid-column: span 1; }
@media (max-width: 760px) {
    .gk-layout-mosaic, .gk-layout-featured { grid-template-columns: 1fr 1fr; }
    .gk-layout-mosaic img, .gk-layout-featured img, .gk-layout-featured img:first-child { grid-column: span 1; grid-row: span 1; }
}
</style>
<?php });

/* ---- Médiatár: a galériában használt kép ne számítson árvának ---- */

add_filter('media_usage', function (array $usages, array $media): array {
    static $galleries = null;
    if ($galleries === null) $galleries = db()->query('SELECT id, name, images FROM galleries')->fetchAll();
    $jsonNeedle = str_replace('/', '\\/', $media['path']); // az images JSON escape-eli a perjeleket
    foreach ($galleries as $g) {
        if (str_contains($g['images'], $media['path']) || str_contains($g['images'], $jsonNeedle)) {
            $usages[] = ['type' => 'gallery', 'id' => (int)$g['id'], 'title' => $g['name'],
                         'where' => 'képek', 'kind' => 'galéria', 'url' => 'admin/galeriak'];
        }
    }
    return $usages;
});

/* ---- Útvonalak és admin menü ---- */

add_filter('routes', function (array $routes): array {
    $routes[] = ['GET',  '/admin/galeriak',        'galeria_admin'];
    $routes[] = ['POST', '/admin/galeriak/save',   'galeria_admin_save'];
    $routes[] = ['POST', '/admin/galeriak/delete', 'galeria_admin_delete'];
    return $routes;
});

add_filter('admin_nav', function (array $nav): array {
    $nav[] = ['admin/galeriak', 'Galériák', 'M8 3h13v13M3 8h13v13H3zM3 16l4-4 3 3 2-2 4 4M7.5 11.5a1 1 0 1 0 0-.01'];
    return $nav;
});

/* ---- Admin: galériák kezelése ---- */

function galeria_admin(): void {
    require_login();
    $galleries = db()->query('SELECT * FROM galleries ORDER BY name')->fetchAll();
    ob_start(); ?>
<header class="page-head">
    <div>
        <h1>Galériák</h1>
        <p class="muted"><?= count($galleries) ?> galéria — beszúrás a <code>[galeria id=N]</code> shortcode-dal</p>
    </div>
</header>

<div class="dash-grid">
    <div class="panel">
        <?php if ($galleries): ?>
        <table class="table">
            <thead><tr><th>Galéria</th><th>Képek</th><th>Layout</th><th>Shortcode</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($galleries as $g): $imgs = galeria_images_of($g['images']); $layout = galeria_layout_key((string)($g['layout'] ?? 'grid')); ?>
                <tr>
                    <td>
                        <strong><?= e($g['name']) ?></strong>
                        <div class="gk-thumbs">
                            <?php foreach (array_slice($imgs, 0, 4) as $u): ?>
                                <img src="<?= e(base_url($u)) ?>" alt="" loading="lazy">
                            <?php endforeach; ?>
                            <?php if (count($imgs) > 4): ?><span class="muted">+<?= count($imgs) - 4 ?></span><?php endif; ?>
                        </div>
                    </td>
                    <td class="muted"><?= count($imgs) ?></td>
                    <td><span class="badge badge-gray"><?= e(galeria_layouts()[$layout]['label']) ?></span></td>
                    <td>
                        <button class="btn btn-ghost btn-sm gk-copy" type="button"
                                data-code="[galeria id=<?= (int)$g['id'] ?>]"
                                title="Másolás vágólapra"><code>[galeria id=<?= (int)$g['id'] ?>]</code></button>
                    </td>
                    <td class="row-actions">
                        <button class="icon-btn" type="button" title="Szerkesztés"
                                onclick='editGallery(<?= json_encode(['id'=>$g['id'],'name'=>$g['name'],'images'=>$imgs,'layout'=>$layout], JSON_HEX_APOS) ?>)'>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17 3a2.8 2.8 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5z"/></svg>
                        </button>
                        <form method="post" action="<?= base_url('admin/galeriak/delete') ?>" data-confirm="Törlöd a galériát? Ahol be van szúrva, ott üres helyet hagy.">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int)$g['id'] ?>">
                            <button class="icon-btn danger" type="submit" title="Törlés">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 11v6M14 11v6"/></svg>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <p>Még nincs galéria.</p>
            <p class="muted">Hozz létre egyet a jobb oldali űrlappal, majd illeszd be a <code>[galeria id=N]</code> shortcode-ot egy oldal vagy poszt tartalmába — akár a blokkszerkesztő szöveg- vagy HTML-blokkjába is.</p>
        </div>
        <?php endif; ?>
    </div>

    <div class="panel side-panel">
        <h3 id="gkFormTitle">Új galéria</h3>
        <form method="post" action="<?= base_url('admin/galeriak/save') ?>" id="gkForm">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="gkId" value="0">
            <input type="hidden" name="images" id="gkImages" value="[]">
            <input type="hidden" name="layout" id="gkLayout" value="grid">
            <label class="field">
                <span>Név</span>
                <input class="input" type="text" name="name" id="gkName" required maxlength="120">
            </label>
            <div class="field">
                <span>Grid layout</span>
                <div class="gk-layout-picker">
                    <?php foreach (galeria_layouts() as $key => $layout): ?>
                    <button class="gk-layout-opt" type="button" data-layout="<?= e($key) ?>" title="<?= e($layout['label']) ?>">
                        <span class="gk-layout-icon">
                            <?php foreach ($layout['icon'] as $row): ?>
                            <span>
                                <?php foreach (str_split($row) as $cell): ?>
                                <i class="<?= $cell === '2' ? 'wide' : '' ?>"></i>
                                <?php endforeach; ?>
                            </span>
                            <?php endforeach; ?>
                        </span>
                        <strong><?= e($layout['label']) ?></strong>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="field">
                <span>Képek <em class="muted">(húzással átrendezhető)</em></span>
                <div class="gk-editor" id="gkEditor"></div>
                <button class="btn btn-ghost btn-sm" type="button" onclick="gkAddImage()">+ Kép hozzáadása a médiatárból</button>
            </div>
            <div class="btn-row">
                <button class="btn btn-primary" type="submit">Mentés</button>
                <button class="btn btn-ghost" type="button" onclick="resetGalleryForm()" id="gkCancel" hidden>Mégse</button>
            </div>
        </form>
    </div>
</div>

<style>
.gk-thumbs { display: flex; gap: 6px; margin-top: 8px; align-items: center; }
.gk-thumbs img { width: 42px; height: 42px; object-fit: cover; border-radius: 8px; border: 1px solid var(--line); }
.gk-copy { padding: 6px 10px; }
.gk-layout-picker { display: grid; grid-template-columns: repeat(auto-fit, minmax(86px, 1fr)); gap: 8px; margin-top: 8px; }
.gk-layout-opt {
    border: 1px solid var(--line); border-radius: 10px; background: var(--bg);
    padding: 9px 8px; display: grid; justify-items: center; gap: 7px;
    cursor: pointer; transition: border-color .15s, color .15s, background .15s;
}
.gk-layout-opt:hover, .gk-layout-opt.active { border-color: var(--accent); color: var(--accent); background: var(--accent-soft); }
.gk-layout-opt strong { font-size: .72rem; line-height: 1; }
.gk-layout-icon { width: 42px; height: 32px; display: grid; gap: 3px; }
.gk-layout-icon span { display: grid; grid-auto-flow: column; grid-auto-columns: 1fr; gap: 3px; }
.gk-layout-icon i { display: block; min-width: 0; border-radius: 3px; background: currentColor; opacity: .55; }
.gk-layout-icon i.wide { grid-column: span 2; opacity: .85; }
.gk-editor { display: flex; flex-wrap: wrap; gap: 8px; margin: 8px 0 10px; }
.gk-editor .gk-item { position: relative; cursor: grab; }
.gk-editor .gk-item.dragging { opacity: .4; }
.gk-editor img { width: 64px; height: 64px; object-fit: cover; border-radius: 10px; border: 1px solid var(--line); display: block; }
.gk-editor .gk-rm {
    position: absolute; top: -6px; right: -6px; width: 20px; height: 20px;
    border: 0; border-radius: 50%; background: #d03050; color: #fff;
    font-size: .75rem; line-height: 1; cursor: pointer; display: grid; place-items: center;
}
</style>
<script>
let gkList = [];
let gkLayout = 'grid';

function gkSetLayout(layout) {
    gkLayout = layout || 'grid';
    document.getElementById('gkLayout').value = gkLayout;
    document.querySelectorAll('.gk-layout-opt').forEach(b => b.classList.toggle('active', b.dataset.layout === gkLayout));
}

function gkRender() {
    const box = document.getElementById('gkEditor');
    box.innerHTML = '';
    gkList.forEach((u, i) => {
        const d = document.createElement('div');
        d.className = 'gk-item';
        d.draggable = true;
        d.dataset.i = i;
        d.innerHTML = `<img src="${window.CMS_BASE}${u}" alt=""><button type="button" class="gk-rm" title="Eltávolítás">✕</button>`;
        d.querySelector('.gk-rm').addEventListener('click', () => { gkList.splice(i, 1); gkRender(); });
        d.addEventListener('dragstart', () => d.classList.add('dragging'));
        d.addEventListener('dragend', () => d.classList.remove('dragging'));
        d.addEventListener('dragover', e => {
            e.preventDefault();
            const from = +document.querySelector('.gk-item.dragging')?.dataset.i;
            const to = +d.dataset.i;
            if (isNaN(from) || from === to) return;
            gkList.splice(to, 0, gkList.splice(from, 1)[0]);
            gkRender();
            document.querySelector(`.gk-item[data-i="${to}"]`)?.classList.add('dragging');
        });
        box.appendChild(d);
    });
    document.getElementById('gkImages').value = JSON.stringify(gkList);
}

function gkAddImage() {
    openMediaPicker(url => {
        // a picker teljes URL-t ad — relatív uploads/ útvonalként tároljuk
        const rel = url.startsWith(window.CMS_BASE) ? url.slice(window.CMS_BASE.length) : url;
        gkList.push(rel.replace(/^\/+/, ''));
        gkRender();
    });
}

function editGallery(g) {
    document.getElementById('gkFormTitle').textContent = 'Galéria szerkesztése';
    document.getElementById('gkId').value = g.id;
    document.getElementById('gkName').value = g.name;
    gkSetLayout(g.layout || 'grid');
    gkList = g.images.slice();
    gkRender();
    document.getElementById('gkCancel').hidden = false;
    document.getElementById('gkName').focus();
}

function resetGalleryForm() {
    document.getElementById('gkFormTitle').textContent = 'Új galéria';
    document.getElementById('gkForm').reset();
    document.getElementById('gkId').value = 0;
    gkSetLayout('grid');
    gkList = [];
    gkRender();
    document.getElementById('gkCancel').hidden = true;
}

document.querySelectorAll('.gk-copy').forEach(b => b.addEventListener('click', async () => {
    await navigator.clipboard.writeText(b.dataset.code);
    const c = b.querySelector('code'), t = c.textContent;
    c.textContent = 'Másolva!';
    setTimeout(() => { c.textContent = t; }, 1200);
}));

document.querySelectorAll('.gk-layout-opt').forEach(b => b.addEventListener('click', () => gkSetLayout(b.dataset.layout)));
gkSetLayout('grid');
gkRender();
</script>
<?php
    $html = (string)ob_get_clean() . view('admin/_media-picker');
    admin_render_html('Galériák', $html);
}

function galeria_admin_save(): void {
    require_login();
    csrf_verify();
    $id = (int)($_POST['id'] ?? 0);
    $name = trim((string)($_POST['name'] ?? ''));
    if ($name === '') { flash_set('error', 'A galéria nevének megadása kötelező.'); redirect('admin/galeriak'); }
    $imgs = json_decode((string)($_POST['images'] ?? '[]'), true);
    $imgs = is_array($imgs)
        ? array_values(array_filter($imgs, fn($u) => is_string($u) && preg_match('#^uploads/[\w\-./]+$#', $u) && !str_contains($u, '..')))
        : [];
    $layout = galeria_layout_key((string)($_POST['layout'] ?? 'grid'));
    if ($id) {
        db()->prepare('UPDATE galleries SET name=?, images=?, layout=? WHERE id=?')->execute([mb_substr($name, 0, 120), json_encode($imgs), $layout, $id]);
    } else {
        db()->prepare('INSERT INTO galleries (name, images, layout) VALUES (?,?,?)')->execute([mb_substr($name, 0, 120), json_encode($imgs), $layout]);
        $id = (int)db()->lastInsertId();
    }
    flash_set('success', "Galéria mentve — shortcode: [galeria id={$id}]");
    redirect('admin/galeriak');
}

function galeria_admin_delete(): void {
    require_login();
    csrf_verify();
    db()->prepare('DELETE FROM galleries WHERE id=?')->execute([(int)($_POST['id'] ?? 0)]);
    flash_set('success', 'Galéria törölve.');
    redirect('admin/galeriak');
}
