<?php
declare(strict_types=1);

function auth_user(): ?array {
    static $user = false;
    if ($user === false) {
        $id = $_SESSION['user_id'] ?? 0;
        if (!$id) return $user = null;
        $st = db()->prepare('SELECT id, name, email, role FROM users WHERE id = ?');
        $st->execute([$id]);
        $user = $st->fetch() ?: null;
    }
    return $user;
}

function auth_attempt(string $email, string $password): bool {
    $st = db()->prepare('SELECT * FROM users WHERE email = ?');
    $st->execute([trim($email)]);
    $u = $st->fetch();
    if ($u && password_verify($password, $u['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$u['id'];
        return true;
    }
    return false;
}

function auth_logout(): void {
    unset($_SESSION['user_id']);
    session_regenerate_id(true);
}

function require_login(): array {
    $u = auth_user();
    if (!$u) redirect('admin/login');
    return $u;
}

function require_admin(): array {
    $u = require_login();
    if (!in_array($u['role'], ['admin', 'superadmin'], true)) {
        flash_set('error', 'Ehhez a művelethez adminisztrátori jogosultság szükséges.');
        redirect('admin');
    }
    return $u;
}

function require_superadmin(): array {
    $u = require_login();
    if (($u['role'] ?? '') !== 'superadmin') {
        flash_set('error', 'Ehhez a művelethez szuperadmin jogosultság szükséges.');
        redirect('admin');
    }
    return $u;
}

function is_admin_role(?string $role): bool {
    return in_array($role, ['admin', 'superadmin'], true);
}

function is_superadmin_role(?string $role): bool {
    return $role === 'superadmin';
}

function role_label(?string $role): string {
    return match ($role) {
        'superadmin' => 'Szuperadmin',
        'admin' => 'Adminisztrátor',
        default => 'Szerkesztő',
    };
}
