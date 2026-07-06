<?php
declare(strict_types=1);

/* Kapcsolat űrlap — [kapcsolat] shortcode + üzenetek admin lista */

db()->exec("CREATE TABLE IF NOT EXISTS contact_messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL,
    message TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT (datetime('now','localtime'))
)");

/* ---- Shortcode a tartalomban ---- */

add_filter('content', function (string $html): string {
    if (!str_contains($html, '[kapcsolat]')) return $html;
    return str_replace('[kapcsolat]', kapcsolat_form_html(), $html);
});

function kapcsolat_form_html(): string {
    $sent = isset($_GET['sent']);
    $back = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/', '/');
    ob_start(); ?>
<div class="kapcsolat-form">
    <?php if ($sent): ?>
    <p class="kf-success">Köszönjük! Az üzeneted megérkezett, hamarosan válaszolunk.</p>
    <?php else: ?>
    <form method="post" action="<?= base_url('kapcsolat-kuldes') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="_back" value="<?= e($back) ?>">
        <input type="text" name="website" class="kf-hp" tabindex="-1" autocomplete="off" aria-hidden="true">
        <div class="kf-grid">
            <label>Név<input type="text" name="name" required maxlength="120"></label>
            <label>E-mail<input type="email" name="email" required maxlength="200"></label>
        </div>
        <label>Üzenet<textarea name="message" rows="5" required maxlength="5000"></textarea></label>
        <button type="submit" class="btn btn-primary">Üzenet küldése</button>
    </form>
    <?php endif; ?>
</div>
<?php
    return (string)ob_get_clean();
}

add_action('front_head', function (): void { ?>
<style>
.kapcsolat-form form { display: grid; gap: 16px; }
.kapcsolat-form .kf-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.kapcsolat-form label { display: grid; gap: 6px; font-weight: 600; font-size: .9rem; }
.kapcsolat-form input, .kapcsolat-form textarea {
    font: inherit; padding: 11px 14px; border-radius: 11px;
    border: 1px solid color-mix(in srgb, var(--ink) 16%, transparent);
    background: var(--surface); color: var(--ink);
}
.kapcsolat-form input:focus, .kapcsolat-form textarea:focus {
    outline: 2px solid var(--accent); outline-offset: 1px; border-color: var(--accent);
}
.kapcsolat-form .btn { justify-self: start; margin-top: 0; }
.kapcsolat-form .kf-hp { position: absolute; left: -9999px; }
.kf-success {
    background: color-mix(in srgb, #18a058 12%, var(--surface));
    border: 1px solid color-mix(in srgb, #18a058 35%, transparent);
    color: #18a058; padding: 14px 18px; border-radius: 12px; font-weight: 600;
}
@media (max-width: 620px) { .kapcsolat-form .kf-grid { grid-template-columns: 1fr; } }
</style>
<?php });

/* ---- Útvonalak ---- */

add_filter('routes', function (array $routes): array {
    $routes[] = ['POST', '/kapcsolat-kuldes',       'kapcsolat_submit'];
    $routes[] = ['GET',  '/admin/uzenetek',         'kapcsolat_admin_list'];
    $routes[] = ['POST', '/admin/uzenetek/delete',  'kapcsolat_admin_delete'];
    return $routes;
});

function kapcsolat_submit(): void {
    csrf_verify();
    $back = preg_match('/^[a-z0-9\/-]*$/', (string)($_POST['_back'] ?? '')) ? (string)$_POST['_back'] : '';
    if (trim((string)($_POST['website'] ?? '')) !== '') redirect($back . '?sent=1'); // honeypot: csendben eldobjuk
    $name = trim((string)($_POST['name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $message = trim((string)($_POST['message'] ?? ''));
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $message === '') {
        redirect($back);
    }
    db()->prepare('INSERT INTO contact_messages (name, email, message) VALUES (?,?,?)')
        ->execute([mb_substr($name, 0, 120), mb_substr($email, 0, 200), mb_substr($message, 0, 5000)]);
    redirect($back . '?sent=1');
}

/* ---- Admin: üzenetek listája ---- */

add_filter('admin_nav', function (array $nav): array {
    $nav[] = ['admin/uzenetek', 'Üzenetek', 'M4 4h16v12H7l-3 3zM8 9h8M8 12h5'];
    return $nav;
});

function kapcsolat_admin_list(): void {
    require_login();
    $msgs = db()->query('SELECT * FROM contact_messages ORDER BY created_at DESC')->fetchAll();
    ob_start(); ?>
<header class="page-head">
    <div>
        <h1>Üzenetek</h1>
        <p class="muted"><?= count($msgs) ?> beérkezett üzenet — a [kapcsolat] űrlapból</p>
    </div>
</header>
<div class="panel">
    <?php if ($msgs): ?>
    <table class="table">
        <thead><tr><th>Feladó</th><th>Üzenet</th><th>Érkezett</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($msgs as $m): ?>
            <tr>
                <td>
                    <strong><?= e($m['name']) ?></strong><br>
                    <a class="link" href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a>
                </td>
                <td class="kf-msg"><?= nl2br(e($m['message'])) ?></td>
                <td class="muted"><?= e(substr($m['created_at'], 0, 16)) ?></td>
                <td class="row-actions">
                    <form method="post" action="<?= base_url('admin/uzenetek/delete') ?>" data-confirm="Törlöd az üzenetet?">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
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
        <p>Még nincs beérkezett üzenet.</p>
        <p class="muted">Illeszd be a <code>[kapcsolat]</code> kódot egy oldal tartalmába, és az űrlapról ide érkeznek az üzenetek.</p>
    </div>
    <?php endif; ?>
</div>
<style>.kf-msg { max-width: 480px; white-space: normal; }</style>
<?php
    admin_render_html('Üzenetek', (string)ob_get_clean());
}

function kapcsolat_admin_delete(): void {
    require_login();
    csrf_verify();
    db()->prepare('DELETE FROM contact_messages WHERE id=?')->execute([(int)($_POST['id'] ?? 0)]);
    flash_set('success', 'Üzenet törölve.');
    redirect('admin/uzenetek');
}
