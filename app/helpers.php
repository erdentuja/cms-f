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

/* ---------- Dizájnsablonok ---------- */

function template_fonts(): array {
    return ['Sora', 'Inter', 'Space Grotesk', 'Manrope', 'Poppins', 'DM Sans', 'Playfair Display', 'Lora'];
}

function template_defaults(): array {
    return [
        'accent' => '#6d5bfa', 'accent2' => '#f43f8e',
        'bg' => '#fbfaf8', 'surface' => '#ffffff', 'ink' => '#16161d',
        'radius' => 18,
        'font_display' => 'Sora', 'font_body' => 'Inter',
    ];
}

/** Sablonadatok szigorú validálása (importnál és kirajzolásnál is fut) */
function template_sanitize(array $in): array {
    $def = template_defaults();
    $out = [];
    foreach (['accent', 'accent2', 'bg', 'surface', 'ink'] as $k) {
        $v = (string)($in[$k] ?? '');
        $out[$k] = preg_match('/^#[0-9a-fA-F]{6}$/', $v) ? strtolower($v) : $def[$k];
    }
    $out['radius'] = min(32, max(0, (int)($in['radius'] ?? $def['radius'])));
    foreach (['font_display', 'font_body'] as $k) {
        $v = (string)($in[$k] ?? '');
        $out[$k] = in_array($v, template_fonts(), true) ? $v : $def[$k];
    }
    return $out;
}

/** Az aktív sablon adatai (kérésenként gyorsítótárazva) */
function active_template(): array {
    static $tpl = null;
    if ($tpl === null) {
        $data = [];
        if ($id = (int)setting('active_template')) {
            $st = db()->prepare('SELECT data FROM templates WHERE id=?');
            $st->execute([$id]);
            if ($row = $st->fetch()) $data = json_decode($row['data'], true) ?: [];
        }
        $tpl = template_sanitize($data);
    }
    return $tpl;
}

/** Google Fonts link href az aktív sablon betűtípusaihoz */
function template_fonts_href(array $tpl): string {
    $fams = array_unique([$tpl['font_display'], $tpl['font_body']]);
    $parts = array_map(fn($f) => 'family=' . str_replace(' ', '+', $f) . ':wght@400;500;600;700;800', $fams);
    return 'https://fonts.googleapis.com/css2?' . implode('&', $parts) . '&display=swap';
}

/* ---------- Blokk-alapú oldalépítő ---------- */

/** Blokktípusok az admin felülethez: [type => felirat] */
function block_types(): array {
    return [
        'heading' => 'Címsor',
        'text'    => 'Szöveg',
        'image'   => 'Kép',
        'button'  => 'Gomb',
        'columns' => 'Kép + szöveg',
        'gallery' => 'Galéria',
        'spacer'  => 'Elválasztó',
        'html'    => 'Egyéni HTML',
    ];
}

/** Blokklista szigorú validálása mentés előtt */
function blocks_sanitize(array $blocks): array {
    $out = [];
    foreach ($blocks as $b) {
        if (!is_array($b) || !isset($b['type']) || !array_key_exists($b['type'], block_types())) continue;
        $type = $b['type'];
        $clean = ['type' => $type];
        switch ($type) {
            case 'heading':
                $clean['text'] = trim((string)($b['text'] ?? ''));
                $clean['level'] = (int)($b['level'] ?? 2) === 3 ? 3 : 2;
                $clean['align'] = ($b['align'] ?? '') === 'center' ? 'center' : 'left';
                if ($clean['text'] === '') continue 2;
                break;
            case 'text':
                $clean['html'] = (string)($b['html'] ?? '');
                if (trim(strip_tags($clean['html'])) === '') continue 2;
                break;
            case 'image':
                $clean['url'] = trim((string)($b['url'] ?? ''));
                if ($clean['url'] === '') continue 2;
                $clean['caption'] = trim((string)($b['caption'] ?? ''));
                $clean['full'] = !empty($b['full']);
                break;
            case 'button':
                $clean['label'] = trim((string)($b['label'] ?? ''));
                $clean['url'] = trim((string)($b['url'] ?? ''));
                if ($clean['label'] === '' || $clean['url'] === '') continue 2;
                $clean['style'] = ($b['style'] ?? '') === 'outline' ? 'outline' : 'primary';
                $clean['new_tab'] = !empty($b['new_tab']);
                break;
            case 'columns':
                $clean['image'] = trim((string)($b['image'] ?? ''));
                $clean['html'] = (string)($b['html'] ?? '');
                $clean['image_side'] = ($b['image_side'] ?? '') === 'right' ? 'right' : 'left';
                if ($clean['image'] === '' && trim(strip_tags($clean['html'])) === '') continue 2;
                break;
            case 'gallery':
                $images = array_values(array_filter((array)($b['images'] ?? []), fn($u) => is_string($u) && trim($u) !== ''));
                if (!$images) continue 2;
                $clean['images'] = $images;
                break;
            case 'spacer':
                $clean['size'] = in_array($b['size'] ?? '', ['sm', 'md', 'lg'], true) ? $b['size'] : 'md';
                break;
            case 'html':
                $clean['code'] = (string)($b['code'] ?? '');
                if (trim($clean['code']) === '') continue 2;
                break;
        }
        $out[] = $clean;
    }
    return $out;
}

/** Egy blokk kirajzolása a frontenden */
function block_render_one(array $b): string {
    switch ($b['type'] ?? '') {
        case 'heading':
            $level = (int)($b['level'] ?? 2) === 3 ? 3 : 2;
            $align = ($b['align'] ?? 'left') === 'center' ? ' align-center' : '';
            return "<div class=\"block block-heading{$align}\"><h{$level}>" . e((string)($b['text'] ?? '')) . "</h{$level}></div>";
        case 'text':
            return '<div class="block block-text prose">' . (string)($b['html'] ?? '') . '</div>';
        case 'image':
            $url = (string)($b['url'] ?? '');
            if ($url === '') return '';
            $cls = 'block block-image' . (!empty($b['full']) ? ' block-wide' : '');
            $cap = trim((string)($b['caption'] ?? ''));
            $img = '<img src="' . e(base_url($url)) . '" alt="' . e($cap) . '" loading="lazy">';
            $figcap = $cap !== '' ? '<figcaption>' . e($cap) . '</figcaption>' : '';
            return "<figure class=\"{$cls}\">{$img}{$figcap}</figure>";
        case 'button':
            $label = trim((string)($b['label'] ?? ''));
            $url = trim((string)($b['url'] ?? ''));
            if ($label === '' || $url === '') return '';
            $cls = ($b['style'] ?? 'primary') === 'outline' ? 'btn-outline' : 'btn-primary';
            $href = preg_match('#^https?://#i', $url) ? $url : base_url($url);
            $target = !empty($b['new_tab']) ? ' target="_blank" rel="noopener"' : '';
            return '<div class="block block-button"><a class="btn ' . $cls . '" href="' . e($href) . '"' . $target . '>' . e($label) . '</a></div>';
        case 'columns':
            $img = trim((string)($b['image'] ?? ''));
            $side = ($b['image_side'] ?? 'left') === 'right' ? ' image-right' : '';
            $imgHtml = $img !== '' ? '<img src="' . e(base_url($img)) . '" alt="" loading="lazy">' : '';
            return "<div class=\"block block-wide block-columns{$side}\"><div class=\"block-col-media\">{$imgHtml}</div><div class=\"block-col-text prose\">" . (string)($b['html'] ?? '') . '</div></div>';
        case 'gallery':
            $images = (array)($b['images'] ?? []);
            if (!$images) return '';
            $items = '';
            foreach ($images as $u) $items .= '<img src="' . e(base_url((string)$u)) . '" alt="" loading="lazy">';
            return "<div class=\"block block-wide block-gallery\">{$items}</div>";
        case 'spacer':
            $size = in_array($b['size'] ?? '', ['sm', 'md', 'lg'], true) ? $b['size'] : 'md';
            return "<div class=\"block block-spacer size-{$size}\"></div>";
        case 'html':
            return '<div class="block block-wide block-html">' . (string)($b['code'] ?? '') . '</div>';
        default:
            return '';
    }
}

/** Teljes blokklista kirajzolása */
function blocks_render(array $blocks): string {
    $out = '';
    foreach ($blocks as $b) {
        if (is_array($b)) $out .= block_render_one($b);
    }
    return $out;
}

function json_out(array $data, int $code = 200): never {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
