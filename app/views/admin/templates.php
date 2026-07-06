<link href="<?= e('https://fonts.googleapis.com/css2?' . implode('&', array_map(fn($f) => 'family=' . str_replace(' ', '+', $f) . ':wght@700', template_fonts())) . '&display=swap') ?>" rel="stylesheet">
<header class="page-head">
    <div>
        <h1>Sablonok</h1>
        <p class="muted">Dizájnsablonok — színek, betűtípusok, formák. Exportálhatod és importálhatod őket JSON-ként.</p>
    </div>
    <form method="post" action="<?= base_url('admin/templates/import') ?>" enctype="multipart/form-data" id="importForm">
        <?= csrf_field() ?>
        <label class="btn btn-ghost">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
            Sablon importálása
            <input type="file" name="file" accept="application/json,.json" hidden onchange="document.getElementById('importForm').submit()">
        </label>
    </form>
</header>

<div class="tpl-grid">
    <?php foreach ($tpls as $t): $d = $t['tpl']; $isActive = (int)$t['id'] === $activeId; ?>
    <div class="tpl-card panel <?= $isActive ? 'active' : '' ?>">
        <div class="tpl-preview" style="background:<?= e($d['bg']) ?>">
            <div class="tpl-mini" style="background:<?= e($d['surface']) ?>; border-radius:<?= (int)$d['radius'] ?>px">
                <span class="tpl-aa" style="color:<?= e($d['ink']) ?>; font-family:'<?= e($d['font_display']) ?>', sans-serif">Aa</span>
                <span class="tpl-bar" style="background:linear-gradient(90deg, <?= e($d['accent']) ?>, <?= e($d['accent2']) ?>)"></span>
            </div>
            <div class="tpl-swatches">
                <?php foreach ([$d['accent'], $d['accent2'], $d['ink'], $d['bg']] as $c): ?>
                <span style="background:<?= e($c) ?>"></span>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="tpl-body">
            <div class="tpl-title">
                <strong><?= e($t['name']) ?></strong>
                <?php if ($isActive): ?><span class="badge badge-green">Aktív</span><?php endif; ?>
            </div>
            <span class="muted"><?= e($d['font_display']) ?> · <?= e($d['font_body']) ?> · <?= (int)$d['radius'] ?>px</span>
            <div class="tpl-actions">
                <?php if (!$isActive): ?>
                <form method="post" action="<?= base_url('admin/templates/activate') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                    <button class="btn btn-primary btn-sm" type="submit">Aktiválás</button>
                </form>
                <?php endif; ?>
                <button class="btn btn-ghost btn-sm" type="button"
                        onclick='editTemplate(<?= json_encode(['id'=>$t['id'],'name'=>$t['name']] + $d, JSON_HEX_APOS|JSON_UNESCAPED_UNICODE) ?>)'>Szerkesztés</button>
                <button class="btn btn-ghost btn-sm" type="button"
                        onclick='duplicateTemplate(<?= json_encode(['name'=>$t['name']] + $d, JSON_HEX_APOS|JSON_UNESCAPED_UNICODE) ?>)'>Másolat</button>
                <a class="icon-btn" href="<?= base_url('admin/templates/export/' . (int)$t['id']) ?>" title="Exportálás JSON-ként" download>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 10l-5-5-5 5M12 5v12"/></svg>
                </a>
                <?php if (!$isActive): ?>
                <form method="post" action="<?= base_url('admin/templates/delete') ?>" data-confirm="Törlöd ezt a sablont?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                    <button class="icon-btn danger" type="submit" title="Törlés">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 11v6M14 11v6"/></svg>
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="panel side-panel tpl-form-panel">
    <h3 id="tplFormTitle">Új sablon</h3>
    <form method="post" action="<?= base_url('admin/templates/save') ?>" id="tplForm">
        <?= csrf_field() ?>
        <input type="hidden" name="id" id="tId" value="0">
        <div class="tpl-form-grid">
            <label class="field span-2">
                <span>Név</span>
                <input class="input" type="text" name="name" id="tName" required>
            </label>
            <label class="field">
                <span>Elsődleges szín</span>
                <input class="input input-color" type="color" name="accent" id="tAccent" value="#6d5bfa">
            </label>
            <label class="field">
                <span>Másodlagos szín</span>
                <input class="input input-color" type="color" name="accent2" id="tAccent2" value="#f43f8e">
            </label>
            <label class="field">
                <span>Háttér</span>
                <input class="input input-color" type="color" name="bg" id="tBg" value="#fbfaf8">
            </label>
            <label class="field">
                <span>Kártyák háttere</span>
                <input class="input input-color" type="color" name="surface" id="tSurface" value="#ffffff">
            </label>
            <label class="field">
                <span>Szövegszín</span>
                <input class="input input-color" type="color" name="ink" id="tInk" value="#16161d">
            </label>
            <label class="field">
                <span>Lekerekítés: <output id="radiusOut">18</output>px</span>
                <input class="input" type="range" name="radius" id="tRadius" min="0" max="32" value="18"
                       oninput="document.getElementById('radiusOut').textContent = this.value">
            </label>
            <label class="field">
                <span>Címsor betűtípus</span>
                <select class="input" name="font_display" id="tFontDisplay">
                    <?php foreach (template_fonts() as $f): ?><option><?= e($f) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label class="field">
                <span>Szöveg betűtípus</span>
                <select class="input" name="font_body" id="tFontBody">
                    <?php foreach (template_fonts() as $f): ?><option><?= e($f) ?></option><?php endforeach; ?>
                </select>
            </label>
        </div>
        <div class="btn-row">
            <button class="btn btn-primary" type="submit">Mentés</button>
            <button class="btn btn-ghost" type="button" onclick="resetTplForm()" id="tCancel" hidden>Mégse</button>
        </div>
    </form>
</div>

<script>
const tplFields = ['accent', 'accent2', 'bg', 'surface', 'ink'];
function fillTplForm(t) {
    tplFields.forEach(k => document.getElementById('t' + k.charAt(0).toUpperCase() + k.slice(1)).value = t[k]);
    document.getElementById('tRadius').value = t.radius;
    document.getElementById('radiusOut').textContent = t.radius;
    document.getElementById('tFontDisplay').value = t.font_display;
    document.getElementById('tFontBody').value = t.font_body;
}
function editTemplate(t) {
    document.getElementById('tplFormTitle').textContent = 'Sablon szerkesztése: ' + t.name;
    document.getElementById('tId').value = t.id;
    document.getElementById('tName').value = t.name;
    fillTplForm(t);
    document.getElementById('tCancel').hidden = false;
    document.querySelector('.tpl-form-panel').scrollIntoView({behavior: 'smooth'});
}
function duplicateTemplate(t) {
    document.getElementById('tplFormTitle').textContent = 'Új sablon (másolat)';
    document.getElementById('tId').value = 0;
    document.getElementById('tName').value = t.name + ' másolat';
    fillTplForm(t);
    document.getElementById('tCancel').hidden = false;
    document.querySelector('.tpl-form-panel').scrollIntoView({behavior: 'smooth'});
}
function resetTplForm() {
    document.getElementById('tplFormTitle').textContent = 'Új sablon';
    document.getElementById('tplForm').reset();
    document.getElementById('tId').value = 0;
    document.getElementById('radiusOut').textContent = document.getElementById('tRadius').value;
    document.getElementById('tCancel').hidden = true;
}
</script>
