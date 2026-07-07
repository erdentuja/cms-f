<header class="page-head">
    <div>
        <h1>Átirányítások</h1>
        <p class="muted"><?= count($redirects) ?> átirányítás — a régi URL-ek a megadott célra irányítanak</p>
    </div>
</header>

<div class="dash-grid">
    <div class="panel">
        <?php if ($redirects): ?>
        <table class="table">
            <thead><tr><th>Forrás</th><th>Cél</th><th>Kód</th><th>Találat</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($redirects as $r): ?>
                <tr>
                    <td><code><?= e($r['from_path']) ?></code></td>
                    <td class="muted" style="max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= e($r['to_url']) ?>"><?= e($r['to_url']) ?></td>
                    <td><span class="badge <?= (int)$r['code'] === 301 ? 'badge-green' : 'badge-orange' ?>"><?= (int)$r['code'] ?></span></td>
                    <td class="muted" title="<?= $r['last_hit'] ? 'Utoljára: ' . e($r['last_hit']) : 'Még nem volt találat' ?>"><?= (int)$r['hits'] ?></td>
                    <td class="row-actions">
                        <button class="icon-btn" type="button" title="Szerkesztés"
                                onclick='editRedirect(<?= json_encode(['id'=>$r['id'],'from_path'=>$r['from_path'],'to_url'=>$r['to_url'],'code'=>$r['code']], JSON_HEX_APOS) ?>)'>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17 3a2.8 2.8 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5z"/></svg>
                        </button>
                        <form method="post" action="<?= base_url('admin/redirects/delete') ?>" data-confirm="Törlöd az átirányítást?">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                            <button class="icon-btn danger" type="submit" title="Törlés">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 11v6M14 11v6"/></svg>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?><div class="empty-state"><p>Még nincs átirányítás. Hozz létre egyet, ha egy régi URL új helyre költözött.</p></div><?php endif; ?>
    </div>

    <div class="panel side-panel">
        <h3 id="redirFormTitle">Új átirányítás</h3>
        <form method="post" action="<?= base_url('admin/redirects/save') ?>" id="redirForm">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="redirId" value="0">
            <label class="field">
                <span>Forrás útvonal <em class="muted">(pl. /regi-oldal)</em></span>
                <input class="input" type="text" name="from_path" id="redirFrom" placeholder="/regi-oldal" required>
            </label>
            <label class="field">
                <span>Cél <em class="muted">(útvonal vagy teljes URL)</em></span>
                <input class="input" type="text" name="to_url" id="redirTo" placeholder="/uj-oldal vagy https://…" required>
            </label>
            <label class="field">
                <span>Típus</span>
                <select class="input" name="code" id="redirCode">
                    <option value="301">301 — végleges</option>
                    <option value="302">302 — ideiglenes</option>
                </select>
            </label>
            <div class="btn-row">
                <button class="btn btn-primary" type="submit">Mentés</button>
                <button class="btn btn-ghost" type="button" onclick="resetRedirectForm()" id="redirCancel" hidden>Mégse</button>
            </div>
        </form>
    </div>
</div>

<script>
function editRedirect(r) {
    document.getElementById('redirFormTitle').textContent = 'Átirányítás szerkesztése';
    document.getElementById('redirId').value = r.id;
    document.getElementById('redirFrom').value = r.from_path;
    document.getElementById('redirTo').value = r.to_url;
    document.getElementById('redirCode').value = r.code;
    document.getElementById('redirCancel').hidden = false;
    document.getElementById('redirFrom').focus();
}
function resetRedirectForm() {
    document.getElementById('redirFormTitle').textContent = 'Új átirányítás';
    document.getElementById('redirForm').reset();
    document.getElementById('redirId').value = 0;
    document.getElementById('redirCancel').hidden = true;
}
</script>
