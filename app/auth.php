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

function auth_rate_ip(): string {
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? 'cli'), 0, 80);
}

function auth_too_many_attempts(string $email): bool {
    $email = mb_strtolower(trim($email));
    db()->exec("DELETE FROM login_attempts WHERE created_at < datetime('now','localtime','-1 day')");
    $st = db()->prepare("SELECT COUNT(*) FROM login_attempts WHERE email=? AND ip=? AND created_at >= datetime('now','localtime','-15 minutes')");
    $st->execute([$email, auth_rate_ip()]);
    return (int)$st->fetchColumn() >= 8;
}

function auth_record_failure(string $email): void {
    db()->prepare('INSERT INTO login_attempts (email, ip) VALUES (?,?)')
        ->execute([mb_strtolower(trim($email)), auth_rate_ip()]);
}

function auth_clear_failures(string $email): void {
    db()->prepare('DELETE FROM login_attempts WHERE email=? AND ip=?')
        ->execute([mb_strtolower(trim($email)), auth_rate_ip()]);
}

function auth_attempt(string $email, string $password): bool {
    if (auth_too_many_attempts($email)) {
        return false;
    }
    $st = db()->prepare('SELECT * FROM users WHERE email = ?');
    $st->execute([trim($email)]);
    $u = $st->fetch();
    if ($u && password_verify($password, $u['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$u['id'];
        auth_clear_failures($email);
        return true;
    }
    auth_record_failure($email);
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
