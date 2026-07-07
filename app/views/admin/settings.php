<header class="page-head">
    <div>
        <h1>Beállítások</h1>
        <p class="muted">Az oldal általános beállításai</p>
    </div>
</header>

<form method="post" action="<?= base_url('admin/settings/save') ?>" class="settings-form">
    <?= csrf_field() ?>

    <div class="settings-layout">
        <div class="settings-primary">
        <section class="panel side-panel settings-section">
            <h3>Oldalsáv szerkesztő</h3>
            <div class="sidebar-editor" data-sidebar-editor>
                <div class="sidebar-shortcodes">
                    <?php
                    $sidebarSnippets = [
                        ['Keresés', "<div class=\"widget widget-search\">\n<h3>Keresés</h3>\n[kereses]\n</div>"],
                        ['Kategóriák', "<div class=\"widget widget-cats\">\n<h3>Kategóriák</h3>\n[kategoriak]\n</div>"],
                        ['Friss posztok', "<div class=\"widget\">\n<h3>Friss posztok</h3>\n[friss-posztok limit=5]\n</div>"],
                        ['Népszerű', "<div class=\"widget\">\n<h3>Népszerű</h3>\n[nepszeru limit=5]\n</div>"],
                        ['Egyedi blokk', "<div class=\"widget\">\n<h3>Cím</h3>\n<p>Szöveg...</p>\n</div>"],
                    ];
                    ?>
                    <?php foreach ($sidebarSnippets as [$label, $code]): ?>
                    <button type="button" class="sidebar-shortcode" draggable="true" data-code="<?= e($code) ?>">
                        <span><?= e($label) ?></span>
                        <code><?= e(strtok($code, "\n")) ?></code>
                    </button>
                    <?php endforeach; ?>
                </div>
                <label class="field sidebar-code-field">
                    <span>Oldalsáv tartalma <em class="muted">(húzd ide a shortcode blokkokat, vagy kattints rájuk a beszúráshoz)</em></span>
                    <textarea class="input code-input sidebar-code-input" name="post_sidebar_content" rows="18"><?= e(setting('post_sidebar_content', sidebar_content_default())) ?></textarea>
                </label>
            </div>
        </section>

        <?php if (is_superadmin_role($user['role'] ?? null)): ?>
        <section class="panel side-panel settings-section">
            <h3>Kódbeszúrás</h3>
            <label class="field">
                <span>Fejléc kód <em class="muted">(a &lt;/head&gt; elé kerül — pl. analitika, meta tagek)</em></span>
                <textarea class="input code-input" name="head_code" rows="4" spellcheck="false" placeholder="<script>…</script>"><?= e(setting('head_code')) ?></textarea>
            </label>
            <label class="field">
                <span>Lábléc kód <em class="muted">(a &lt;/body&gt; elé kerül — pl. chat widget)</em></span>
                <textarea class="input code-input" name="footer_code" rows="4" spellcheck="false"><?= e(setting('footer_code')) ?></textarea>
            </label>
            <p class="muted settings-note">A kódbeszúrás nyers HTML-ként kerül a nyilvános oldalakra — csak megbízható kódot illessz be.</p>
        </section>
        <?php endif; ?>
        </div>

        <div class="settings-secondary">
        <section class="panel side-panel settings-section">
            <h3>Alapadatok</h3>
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
        </section>

        <section class="panel side-panel settings-section">
            <h3>SEO alapok</h3>
            <label class="field">
                <span>Főoldal SEO cím <em class="muted">(max. kb. 70 karakter)</em></span>
                <input class="input" type="text" name="seo_home_title" maxlength="70" value="<?= e(setting('seo_home_title', setting('site_name'))) ?>">
            </label>
            <label class="field">
                <span>Robots alapértelmezés</span>
                <?php $robots = setting('seo_robots', 'index,follow'); ?>
                <select class="input" name="seo_robots">
                    <?php foreach (['index,follow', 'noindex,follow', 'index,nofollow', 'noindex,nofollow'] as $opt): ?>
                    <option value="<?= e($opt) ?>" <?= $robots === $opt ? 'selected' : '' ?>><?= e($opt) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="field">
                <span>Alap OG kép <em class="muted">(uploads/... vagy teljes URL)</em></span>
                <input class="input" type="text" name="default_og_image" value="<?= e(setting('default_og_image')) ?>">
            </label>
            <label class="field">
                <span>Twitter/X azonosító <em class="muted">(pl. @markanev)</em></span>
                <input class="input" type="text" name="twitter_site" value="<?= e(setting('twitter_site')) ?>">
            </label>
        </section>

        <section class="panel side-panel settings-section">
            <h3>Tartalom</h3>
            <label class="field">
                <span>Posztok száma oldalanként</span>
                <input class="input" type="number" name="posts_per_page" min="1" max="50" value="<?= e(setting('posts_per_page', '9')) ?>">
            </label>
        </section>

        <section class="panel side-panel settings-section">
            <h3>Poszt oldalsáv</h3>
            <label class="field">
                <span>Poszt-oldalsáv <em class="muted">(keresés, kategóriák, friss és népszerű posztok a poszt oldalakon)</em></span>
                <select class="input" name="post_sidebar">
                    <?php $ps = setting('post_sidebar', 'none'); ?>
                    <option value="none" <?= $ps === 'none' ? 'selected' : '' ?>>Nincs</option>
                    <option value="right" <?= $ps === 'right' ? 'selected' : '' ?>>Jobb oldalt</option>
                    <option value="left" <?= $ps === 'left' ? 'selected' : '' ?>>Bal oldalt</option>
                </select>
            </label>
            <label class="field">
                <span>Ragadós oldalsáv <em class="muted">(görgetés közben a képernyőn marad)</em></span>
                <select class="input" name="post_sidebar_sticky">
                    <?php $sticky = setting('post_sidebar_sticky', '0'); ?>
                    <option value="0" <?= $sticky !== '1' ? 'selected' : '' ?>>Kikapcsolva</option>
                    <option value="1" <?= $sticky === '1' ? 'selected' : '' ?>>Bekapcsolva</option>
                </select>
            </label>
        </section>

        <section class="panel side-panel settings-section">
            <h3>Lábléc</h3>
            <label class="field">
                <span>Lábléc szöveg</span>
                <input class="input" type="text" name="footer_text" value="<?= e(setting('footer_text')) ?>">
            </label>
        </section>
        </div>
    </div>

    <div class="settings-actions">
        <button class="btn btn-primary" type="submit">Mentés</button>
    </div>
</form>

<script>
(() => {
    const editor = document.querySelector('[data-sidebar-editor]');
    if (!editor) return;
    const input = editor.querySelector('.sidebar-code-input');
    const insert = (code) => {
        const start = input.selectionStart ?? input.value.length;
        const end = input.selectionEnd ?? start;
        const before = input.value.slice(0, start).replace(/\s*$/, '');
        const after = input.value.slice(end).replace(/^\s*/, '');
        const next = `${before}${before ? '\n\n' : ''}${code}${after ? '\n\n' + after : ''}`;
        input.value = next;
        const pos = (before ? before.length + 2 : 0) + code.length;
        input.focus();
        input.setSelectionRange(pos, pos);
    };
    editor.querySelectorAll('.sidebar-shortcode').forEach(btn => {
        btn.addEventListener('click', () => insert(btn.dataset.code));
        btn.addEventListener('dragstart', e => {
            e.dataTransfer.setData('text/plain', btn.dataset.code);
            e.dataTransfer.effectAllowed = 'copy';
        });
    });
    input.addEventListener('dragover', e => {
        e.preventDefault();
        input.classList.add('drag-over');
    });
    input.addEventListener('dragleave', () => input.classList.remove('drag-over'));
    input.addEventListener('drop', e => {
        e.preventDefault();
        input.classList.remove('drag-over');
        insert(e.dataTransfer.getData('text/plain'));
    });
})();
</script>
