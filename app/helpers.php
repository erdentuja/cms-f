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

/* ---------- Átirányítások ---------- */

/** Útvonal normalizálása átirányításhoz: mindig / prefixszel, záró / nélkül */
function redirect_normalize_path(string $p): string {
    $p = trim($p);
    if (preg_match('#^https?://#i', $p)) $p = parse_url($p, PHP_URL_PATH) ?: '/';
    $p = strtok($p, '?#') ?: '/';
    return '/' . trim($p, '/');
}

/** Ha van átirányítás a kért útvonalra, végrehajtja (301/302) és kilép */
function redirects_apply(string $uri): void {
    $st = db()->prepare('SELECT * FROM redirects WHERE from_path = ?');
    $st->execute([$uri]);
    $r = $st->fetch();
    if (!$r) return;

    $target = trim($r['to_url']);
    // Hurokvédelem: ha a cél ugyanaz az útvonal, nem irányítunk át
    if (redirect_normalize_path($target) === $r['from_path'] && !preg_match('#^https?://#i', $target)) return;

    db()->prepare("UPDATE redirects SET hits = hits + 1, last_hit = datetime('now','localtime') WHERE id = ?")
        ->execute([$r['id']]);

    $location = preg_match('#^https?://#i', $target) ? $target : base_url($target);
    // Az eredeti query stringet továbbvisszük, ha a célban nincs sajátja
    $qs = $_SERVER['QUERY_STRING'] ?? '';
    if ($qs !== '' && !str_contains($location, '?')) $location .= '?' . $qs;

    http_response_code((int)$r['code'] === 302 ? 302 : 301);
    header('Location: ' . $location);
    exit;
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

/* ---------- Blokkszerkesztő (oldalak és posztok is használják) ---------- */

/** Blokktípusok az admin felülethez: [type => felirat] */
function block_types(): array {
    return [
        'heading' => 'Címsor',
        'text'    => 'Szöveg',
        'image'   => 'Kép',
        'button'  => 'Gomb',
        'columns' => 'Kép + szöveg',
        'gallery' => 'Galéria',
        'quote'   => 'Idézet / vélemény',
        'faq'     => 'GYIK (lenyíló)',
        'counters'=> 'Számláló-sáv',
        'video'   => 'Videó',
        'map'     => 'Térkép',
        'spacer'  => 'Elválasztó',
        'html'    => 'Egyéni HTML',
    ];
}

/** Blokktípus-ikonok az admin felülethez: [type => SVG path] (24×24, stroke stílus) */
function block_icons(): array {
    return [
        'heading'  => 'M6 4v16M18 4v16M6 12h12',
        'text'     => 'M4 6h16M4 10h16M4 14h16M4 18h9',
        'image'    => 'M3 5h18v14H3zM3 15l5-5 4 4 3-3 6 6M8.5 9a1 1 0 1 0 0-.01',
        'button'   => 'M2 10a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2zM8 12h8',
        'columns'  => 'M3 5h7v14H3zM14 5h7v14h-7z',
        'gallery'  => 'M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z',
        'quote'    => 'M10 7H6v5h4zM10 12c0 3-1 4-3 5M18 7h-4v5h4zM18 12c0 3-1 4-3 5',
        'faq'      => 'M12 21a9 9 0 1 0-9-9 9 9 0 0 0 9 9M9.5 9.5A2.5 2.5 0 1 1 12 12v1.5M12 17h.01',
        'counters' => 'M4 20v-6M9 20V10M14 20V6M19 20V3',
        'video'    => 'M3 5h18v14H3zM10 9l5 3-5 3z',
        'map'      => 'M12 21s-7-5.3-7-11a7 7 0 0 1 14 0c0 5.7-7 11-7 11zM12 12a2 2 0 1 0 0-.01',
        'spacer'   => 'M12 4v16M8 8l4-4 4 4M8 16l4 4 4-4',
        'html'     => 'M9 6 3 12l6 6M15 6l6 6-6 6',
    ];
}

/** Alapértelmezett elrendezés blokktípusonként */
function block_layout_defaults(string $type): array {
    $wide = in_array($type, ['columns', 'gallery', 'html', 'counters'], true);
    return ['w' => $wide ? 'wide' : 'normal', 'bg' => 'none', 'pad' => 'none'];
}

/** YouTube/Vimeo URL → beágyazható iframe src (üres, ha nem ismerhető fel) */
function video_embed_src(string $url): string {
    if (preg_match('#(?:youtube\.com/(?:watch\?.*?v=|shorts/|embed/)|youtu\.be/)([A-Za-z0-9_-]{6,20})#', $url, $m)) {
        return 'https://www.youtube-nocookie.com/embed/' . $m[1];
    }
    if (preg_match('#vimeo\.com/(?:video/)?(\d+)#', $url, $m)) {
        return 'https://player.vimeo.com/video/' . $m[1];
    }
    return '';
}

/** Blokklista szigorú validálása mentés előtt */
function blocks_sanitize(array $blocks): array {
    $out = [];
    foreach ($blocks as $b) {
        if (!is_array($b) || !isset($b['type']) || !array_key_exists($b['type'], block_types())) continue;
        $type = $b['type'];
        $clean = ['type' => $type];
        $def = block_layout_defaults($type);
        $clean['w'] = in_array($b['w'] ?? '', ['normal', 'wide', 'full'], true) ? $b['w'] : $def['w'];
        $clean['bg'] = ($b['bg'] ?? '') === 'soft' ? 'soft' : 'none';
        $clean['pad'] = in_array($b['pad'] ?? '', ['sm', 'md', 'lg'], true) ? $b['pad'] : 'none';
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
            case 'quote':
                $clean['text'] = trim((string)($b['text'] ?? ''));
                if ($clean['text'] === '') continue 2;
                $clean['author'] = trim((string)($b['author'] ?? ''));
                $clean['role'] = trim((string)($b['role'] ?? ''));
                $clean['image'] = trim((string)($b['image'] ?? ''));
                break;
            case 'faq':
                $items = [];
                foreach ((array)($b['items'] ?? []) as $it) {
                    if (!is_array($it)) continue;
                    $q = trim((string)($it['q'] ?? ''));
                    if ($q === '') continue;
                    $items[] = ['q' => $q, 'a' => trim((string)($it['a'] ?? ''))];
                }
                if (!$items) continue 2;
                $clean['items'] = $items;
                break;
            case 'counters':
                $items = [];
                foreach ((array)($b['items'] ?? []) as $it) {
                    if (!is_array($it)) continue;
                    $v = trim((string)($it['value'] ?? ''));
                    if ($v === '') continue;
                    $items[] = ['value' => $v, 'label' => trim((string)($it['label'] ?? ''))];
                }
                if (!$items) continue 2;
                $clean['items'] = $items;
                break;
            case 'video':
                $clean['url'] = trim((string)($b['url'] ?? ''));
                if (video_embed_src($clean['url']) === '') continue 2;
                break;
            case 'map':
                $embed = trim((string)($b['embed'] ?? ''));
                if (!preg_match('#^https://(www\.google\.com/maps/embed|maps\.google\.com/maps|www\.openstreetmap\.org/export/embed\.html)#', $embed)) continue 2;
                $clean['embed'] = $embed;
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

/** Egy blokk belső tartalma (a szélesség/háttér wrappert a blocks_render adja) */
function block_render_one(array $b): string {
    switch ($b['type'] ?? '') {
        case 'heading':
            $level = (int)($b['level'] ?? 2) === 3 ? 3 : 2;
            $align = ($b['align'] ?? 'left') === 'center' ? ' class="ta-center"' : '';
            return "<h{$level}{$align}>" . e((string)($b['text'] ?? '')) . "</h{$level}>";
        case 'text':
            return '<div class="prose">' . (string)($b['html'] ?? '') . '</div>';
        case 'image':
            $url = (string)($b['url'] ?? '');
            if ($url === '') return '';
            $cap = trim((string)($b['caption'] ?? ''));
            $img = '<img src="' . e(base_url($url)) . '" alt="' . e($cap) . '" loading="lazy">';
            $figcap = $cap !== '' ? '<figcaption>' . e($cap) . '</figcaption>' : '';
            return "<figure>{$img}{$figcap}</figure>";
        case 'button':
            $label = trim((string)($b['label'] ?? ''));
            $url = trim((string)($b['url'] ?? ''));
            if ($label === '' || $url === '') return '';
            $cls = ($b['style'] ?? 'primary') === 'outline' ? 'btn-outline' : 'btn-primary';
            $href = preg_match('#^https?://#i', $url) ? $url : base_url($url);
            $target = !empty($b['new_tab']) ? ' target="_blank" rel="noopener"' : '';
            return '<a class="btn ' . $cls . '" href="' . e($href) . '"' . $target . '>' . e($label) . '</a>';
        case 'columns':
            $img = trim((string)($b['image'] ?? ''));
            $side = ($b['image_side'] ?? 'left') === 'right' ? ' image-right' : '';
            $imgHtml = $img !== '' ? '<img src="' . e(base_url($img)) . '" alt="" loading="lazy">' : '';
            return "<div class=\"cols{$side}\"><div class=\"col-media\">{$imgHtml}</div><div class=\"col-text prose\">" . (string)($b['html'] ?? '') . '</div></div>';
        case 'gallery':
            $images = (array)($b['images'] ?? []);
            if (!$images) return '';
            $items = '';
            foreach ($images as $u) $items .= '<img src="' . e(base_url((string)$u)) . '" alt="" loading="lazy">';
            return "<div class=\"gal\">{$items}</div>";
        case 'quote':
            $img = trim((string)($b['image'] ?? ''));
            $avatar = $img !== '' ? '<img class="tstm-avatar" src="' . e(base_url($img)) . '" alt="' . e((string)($b['author'] ?? '')) . '" loading="lazy">' : '';
            $who = '';
            if (($b['author'] ?? '') !== '' || ($b['role'] ?? '') !== '') {
                $who = '<figcaption class="tstm-who"><strong>' . e((string)($b['author'] ?? '')) . '</strong>'
                     . (($b['role'] ?? '') !== '' ? '<span>' . e((string)$b['role']) . '</span>' : '')
                     . '</figcaption>';
            }
            return '<figure class="tstm">' . $avatar . '<blockquote>' . e((string)($b['text'] ?? '')) . '</blockquote>' . $who . '</figure>';
        case 'faq':
            $items = '';
            foreach ((array)($b['items'] ?? []) as $it) {
                if (!is_array($it) || trim((string)($it['q'] ?? '')) === '') continue;
                $items .= '<details class="faq-item"><summary>' . e((string)$it['q']) . '</summary><div class="faq-a">' . nl2br(e((string)($it['a'] ?? ''))) . '</div></details>';
            }
            return $items === '' ? '' : '<div class="faq">' . $items . '</div>';
        case 'counters':
            $items = '';
            foreach ((array)($b['items'] ?? []) as $it) {
                if (!is_array($it) || trim((string)($it['value'] ?? '')) === '') continue;
                $items .= '<div class="cnt"><div class="cnt-v" data-v="' . e((string)$it['value']) . '">' . e((string)$it['value']) . '</div>'
                        . '<div class="cnt-l">' . e((string)($it['label'] ?? '')) . '</div></div>';
            }
            if ($items === '') return '';
            static $cntScript = false;
            $script = '';
            if (!$cntScript) {
                $cntScript = true;
                $script = <<<'HTML'
<script>
(function () {
    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (en) {
            if (!en.isIntersecting) return;
            io.unobserve(en.target);
            var raw = en.target.dataset.v || '', m = raw.match(/\d+/);
            if (!m) return;
            var num = parseInt(m[0], 10), pre = raw.slice(0, m.index), suf = raw.slice(m.index + m[0].length);
            var t0 = performance.now(), dur = 1100;
            (function tick(t) {
                var p = Math.min(1, (t - t0) / dur), ease = 1 - Math.pow(1 - p, 3);
                en.target.textContent = pre + Math.round(num * ease) + suf;
                if (p < 1) requestAnimationFrame(tick);
            })(t0);
        });
    }, { threshold: 0.4 });
    document.querySelectorAll('.cnt-v').forEach(function (el) { io.observe(el); });
})();
</script>
HTML;
            }
            return '<div class="counters">' . $items . '</div>' . $script;
        case 'video':
            $src = video_embed_src((string)($b['url'] ?? ''));
            if ($src === '') return '';
            return '<div class="video-embed"><iframe src="' . e($src) . '" title="Videó" loading="lazy" allowfullscreen allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"></iframe></div>';
        case 'map':
            $embed = (string)($b['embed'] ?? '');
            if ($embed === '') return '';
            return '<div class="map-embed"><iframe src="' . e($embed) . '" title="Térkép" loading="lazy" allowfullscreen referrerpolicy="no-referrer-when-downgrade"></iframe></div>';
        case 'spacer':
            $size = in_array($b['size'] ?? '', ['sm', 'md', 'lg'], true) ? $b['size'] : 'md';
            return "<div class=\"spacer-{$size}\"></div>";
        case 'html':
            return (string)($b['code'] ?? '');
        default:
            return '';
    }
}

/** Teljes blokklista kirajzolása szélesség/háttér/térköz wrapperrel */
function blocks_render(array $blocks): string {
    $out = '';
    foreach ($blocks as $b) {
        if (!is_array($b)) continue;
        $inner = block_render_one($b);
        if ($inner === '') continue;
        $type = (string)($b['type'] ?? '');
        $def = block_layout_defaults($type);
        $w = in_array($b['w'] ?? '', ['normal', 'wide', 'full'], true) ? $b['w'] : $def['w'];
        $cls = 'blk-row';
        if (($b['bg'] ?? '') === 'soft') $cls .= ' bg-soft';
        if (in_array($b['pad'] ?? '', ['sm', 'md', 'lg'], true)) $cls .= ' pad-' . $b['pad'];
        $out .= '<section class="' . $cls . '"><div class="blk w-' . $w . ' block-' . e($type) . '">' . $inner . '</div></section>';
    }
    return $out;
}

function json_out(array $data, int $code = 200): never {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
