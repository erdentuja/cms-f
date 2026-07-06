<header class="page-head">
    <div>
        <h1>Bővítmények</h1>
        <p class="muted"><?= count($plugins) ?> telepített bővítmény</p>
    </div>
    <form method="post" action="<?= base_url('admin/plugins/upload') ?>" enctype="multipart/form-data" id="pluginUploadForm">
        <?= csrf_field() ?>
        <label class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12"/></svg>
            Bővítmény feltöltése (.zip)
            <input type="file" name="file" accept=".zip" hidden onchange="document.getElementById('pluginUploadForm').submit()">
        </label>
    </form>
</header>

<?php foreach ($errors as $slug => $msg): ?>
<div class="flash flash-error">A(z) „<?= e($slug) ?>” bővítmény hibát dobott betöltéskor: <?= e($msg) ?></div>
<?php endforeach; ?>

<?php if ($plugins): ?>
<div class="plugin-grid">
    <?php foreach ($plugins as $slug => $p): ?>
    <div class="panel plugin-card <?= $p['active'] ? 'active' : '' ?>">
        <div class="plugin-head">
            <h2><?= e($p['name']) ?></h2>
            <span class="badge <?= $p['active'] ? 'badge-green' : 'badge-gray' ?>"><?= $p['active'] ? 'Bekapcsolva' : 'Kikapcsolva' ?></span>
        </div>
        <p class="plugin-desc"><?= e($p['description']) ?></p>
        <p class="muted plugin-meta">
            <?= $p['version'] ? 'v' . e($p['version']) : '' ?>
            <?= $p['author'] ? ' · ' . e($p['author']) : '' ?>
            · <code><?= e($slug) ?></code>
        </p>
        <?php if (!$p['has_entry']): ?>
        <p class="muted">⚠ Hiányzik a plugin.php — a bővítmény nem tölthető be.</p>
        <?php endif; ?>
        <div class="btn-row">
            <form method="post" action="<?= base_url('admin/plugins/toggle') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="slug" value="<?= e($slug) ?>">
                <button class="btn <?= $p['active'] ? 'btn-ghost' : 'btn-primary' ?> btn-sm" type="submit" <?= $p['has_entry'] ? '' : 'disabled' ?>>
                    <?= $p['active'] ? 'Kikapcsolás' : 'Bekapcsolás' ?>
                </button>
            </form>
            <?php if (!$p['active']): ?>
            <form method="post" action="<?= base_url('admin/plugins/delete') ?>"
                  data-confirm="Törlöd a(z) „<?= e($p['name']) ?>” bővítményt? A fájljai véglegesen törlődnek.">
                <?= csrf_field() ?>
                <input type="hidden" name="slug" value="<?= e($slug) ?>">
                <button class="btn btn-ghost danger btn-sm" type="submit">Törlés</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div class="empty-state">
    <p>Még nincs telepített bővítmény. Tölts fel egy zip csomagot a fenti gombbal!</p>
    <p class="muted">Egy bővítmény felépítése: <code>plugin.json</code> (name, description, version, author) + <code>plugin.php</code> belépési pont.</p>
</div>
<?php endif; ?>
