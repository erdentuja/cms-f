<header class="page-head">
    <div>
        <h1>Felhasználók</h1>
        <p class="muted"><?= count($users) ?> fiók</p>
    </div>
</header>

<div class="dash-grid">
    <div class="panel">
        <table class="table">
            <thead><tr><th>Név</th><th>E-mail</th><th>Szerepkör</th><th>Létrehozva</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <?php $canManageUser = is_superadmin_role($user['role'] ?? null) || $u['role'] !== 'superadmin'; ?>
                    <td>
                        <span class="user-cell">
                            <span class="avatar sm"><?= e(mb_strtoupper(mb_substr($u['name'], 0, 1))) ?></span>
                            <strong><?= e($u['name']) ?></strong>
                            <?php if ((int)$u['id'] === (int)$user['id']): ?><span class="muted">(te)</span><?php endif; ?>
                        </span>
                    </td>
                    <td class="muted"><?= e($u['email']) ?></td>
                    <td><span class="badge <?= $u['role'] === 'editor' ? 'badge-gray' : 'badge-purple' ?>"><?= e(role_label($u['role'])) ?></span></td>
                    <td class="muted"><?= e(substr($u['created_at'], 0, 10)) ?></td>
                    <td class="row-actions">
                        <?php if ($canManageUser): ?>
                        <button class="icon-btn" type="button" title="Szerkesztés"
                                onclick='editUser(<?= json_encode(['id'=>$u['id'],'name'=>$u['name'],'email'=>$u['email'],'role'=>$u['role']], JSON_HEX_APOS) ?>)'>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17 3a2.8 2.8 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5z"/></svg>
                        </button>
                        <?php if ((int)$u['id'] !== (int)$user['id']): ?>
                        <form method="post" action="<?= base_url('admin/users/delete') ?>" data-confirm="Biztosan törlöd a felhasználót?">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                            <button class="icon-btn danger" type="submit" title="Törlés">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 11v6M14 11v6"/></svg>
                            </button>
                        </form>
                        <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="panel side-panel">
        <h3 id="userFormTitle">Új felhasználó</h3>
        <form method="post" action="<?= base_url('admin/users/save') ?>" id="userForm">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="userId" value="0">
            <label class="field">
                <span>Név</span>
                <input class="input" type="text" name="name" id="userName" required>
            </label>
            <label class="field">
                <span>E-mail cím</span>
                <input class="input" type="email" name="email" id="userEmail" required>
            </label>
            <label class="field">
                <span>Szerepkör</span>
                <select class="input" name="role" id="userRole">
                    <option value="editor">Szerkesztő</option>
                    <option value="admin">Adminisztrátor</option>
                    <?php if (is_superadmin_role($user['role'] ?? null)): ?>
                    <option value="superadmin">Szuperadmin</option>
                    <?php endif; ?>
                </select>
            </label>
            <label class="field">
                <span>Jelszó <em class="muted" id="passHint">(min. 8 karakter)</em></span>
                <input class="input" type="password" name="password" id="userPass" minlength="8">
            </label>
            <div class="btn-row">
                <button class="btn btn-primary" type="submit">Mentés</button>
                <button class="btn btn-ghost" type="button" onclick="resetUserForm()" id="userCancel" hidden>Mégse</button>
            </div>
        </form>
    </div>
</div>

<script>
function editUser(u) {
    document.getElementById('userFormTitle').textContent = 'Felhasználó szerkesztése';
    document.getElementById('userId').value = u.id;
    document.getElementById('userName').value = u.name;
    document.getElementById('userEmail').value = u.email;
    document.getElementById('userRole').value = u.role;
    document.getElementById('userPass').required = false;
    document.getElementById('passHint').textContent = '(üresen hagyva nem változik)';
    document.getElementById('userCancel').hidden = false;
    document.getElementById('userName').focus();
}
function resetUserForm() {
    document.getElementById('userFormTitle').textContent = 'Új felhasználó';
    document.getElementById('userForm').reset();
    document.getElementById('userId').value = 0;
    document.getElementById('userPass').required = true;
    document.getElementById('passHint').textContent = '(min. 8 karakter)';
    document.getElementById('userCancel').hidden = true;
}
document.getElementById('userPass').required = true;
</script>
