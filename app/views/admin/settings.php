<header class="page-head">
    <div>
        <h1>Beállítások</h1>
        <p class="muted">Az oldal általános beállításai</p>
    </div>
</header>

<div class="panel side-panel settings-panel">
    <form method="post" action="<?= base_url('admin/settings/save') ?>">
        <?= csrf_field() ?>
        <label class="field">
            <span>Oldal neve</span>
            <input class="input" type="text" name="site_name" value="<?= e(setting('site_name')) ?>" required>
        </label>
        <label class="field">
            <span>Szlogen</span>
            <input class="input" type="text" name="tagline" value="<?= e(setting('tagline')) ?>">
        </label>
        <label class="field">
            <span>Meta leírás <em class="muted">(keresőmotoroknak)</em></span>
            <textarea class="input" name="description" rows="2"><?= e(setting('description')) ?></textarea>
        </label>
        <label class="field">
            <span>Posztok száma oldalanként</span>
            <input class="input" type="number" name="posts_per_page" min="1" max="50" value="<?= e(setting('posts_per_page', '9')) ?>">
        </label>
        <label class="field">
            <span>Lábléc szöveg</span>
            <input class="input" type="text" name="footer_text" value="<?= e(setting('footer_text')) ?>">
        </label>
        <button class="btn btn-primary" type="submit">Mentés</button>
    </form>
</div>
