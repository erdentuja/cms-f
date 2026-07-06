<?php
declare(strict_types=1);

/** HTML escape */
function e(?string $s): string {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

/** Base URL of the app (works in subfolder and vhost too) */
function base_url(string $path = ''): string {
    static $base = null;
    if ($base === null) {
        $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
        $base = $dir === '' ? '' : $dir;
    }
    return $base . '/' . ltrim($path, '/');
}

/** Absolute URL (scheme + host) — sitemap-hez, OG tagekhez */
function site_url(string $path = ''): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . base_url($path);
}

function redirect(string $path): never {
    header('Location: ' . base_url($path));
    exit;
}

/** Slug generation with Hungarian accent transliteration */
function slugify(string $text): string {
    $map = [
        'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ö'=>'o','ő'=>'o','ú'=>'u','ü'=>'u','ű'=>'u',
        'Á'=>'a','É'=>'e','Í'=>'i','Ó'=>'o','Ö'=>'o','Ő'=>'o','Ú'=>'u','Ü'=>'u','Ű'=>'u',
    ];
    $text = strtr($text, $map);
    $text = mb_strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/u', '-', $text);
    return trim($text, '-') ?: 'n-a';
}

/** Ensure a slug is unique in a table (appends -2, -3, ...) */
function unique_slug(PDO $db, string $table, string $slug, int $ignoreId = 0): string {
    $base = $slug; $i = 1;
    while (true) {
        $st = $db->prepare("SELECT COUNT(*) FROM {$table} WHERE slug = ? AND id != ?");
        $st->execute([$slug, $ignoreId]);
        if ((int)$st->fetchColumn() === 0) return $slug;
        $slug = $base . '-' . (++$i);
    }
}

/** Flash messages */
function flash_set(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}
function flash_get(): ?array {
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

/** CSRF */
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_field(): string {
    return '<input type="hidden" name="_csrf" value="' . csrf_token() . '">';
}
function csrf_verify(): void {
    $t = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', $t)) {
        http_response_code(419);
        exit('Érvénytelen kérés (CSRF).');
    }
}

/** Render a view into the buffer and return it */
function view(string $name, array $data = []): string {
    extract($data, EXTR_SKIP);
    ob_start();
    require APP_ROOT . '/app/views/' . $name . '.php';
    return ob_get_clean();
}

/** Settings (cached per request) */
function setting(string $key, string $default = ''): string {
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (db()->query('SELECT key, value FROM settings') as $row) {
            $cache[$row['key']] = $row['value'];
        }
    }
    return $cache[$key] ?? $default;
}

/** Hungarian date format */
function hu_date(?string $dt): string {
    if (!$dt) return '';
    $months = ['','január','február','március','április','május','június','július','augusztus','szeptember','október','november','december'];
    $t = strtotime($dt);
    return date('Y. ', $t) . $months[(int)date('n', $t)] . date(' j.', $t);
}

function excerpt_of(string $html, int $len = 160): string {
    $text = trim(preg_replace('/\s+/u', ' ', strip_tags($html)));
    return mb_strlen($text) > $len ? mb_substr($text, 0, $len) . '…' : $text;
}

/** Human file size */
function human_size(int $bytes): string {
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    $n = (float)$bytes;
    while ($n >= 1024 && $i < 3) { $n /= 1024; $i++; }
    return round($n, $i ? 1 : 0) . ' ' . $units[$i];
}

/** Reading time estimate (minutes) */
function reading_time(string $html): int {
    $words = str_word_count(strip_tags($html));
    return max(1, (int)ceil($words / 200));
}

function json_out(array $data, int $code = 200): never {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
