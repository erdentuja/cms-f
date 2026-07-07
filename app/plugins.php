<?php
declare(strict_types=1);

/* ===== Aurora CMS — Bővítményrendszer =====
 * Egy bővítmény: plugins/<mappa>/plugin.json (manifest) + plugin.php (belépési pont).
 * A bekapcsolt bővítmények listája a settings 'active_plugins' kulcsában (JSON tömb).
 * A plugin.php action/filter hookokkal kapcsolódik a CMS-hez.
 */

/* ---------- Hook-rendszer ---------- */

$GLOBALS['aurora_hooks'] = [];

function add_action(string $hook, callable $fn, int $priority = 10): void {
    $GLOBALS['aurora_hooks'][$hook][] = ['fn' => $fn, 'prio' => $priority];
}

function add_filter(string $hook, callable $fn, int $priority = 10): void {
    add_action($hook, $fn, $priority);
}

/** Hook-hoz kötött callbackek prioritás szerint */
function hook_callbacks(string $hook): array {
    $list = $GLOBALS['aurora_hooks'][$hook] ?? [];
    usort($list, fn($a, $b) => $a['prio'] <=> $b['prio']);
    return array_column($list, 'fn');
}

function do_action(string $hook, ...$args): void {
    foreach (hook_callbacks($hook) as $fn) $fn(...$args);
}

function apply_filters(string $hook, mixed $value, ...$args): mixed {
    foreach (hook_callbacks($hook) as $fn) $value = $fn($value, ...$args);
    return $value;
}

/* ---------- Shortcode-ok (beszúrható modulok) ---------- */

$GLOBALS['aurora_shortcodes'] = [];

/** Modul regisztrálása: a tartalomban [tag attr=érték] formában szúrható be */
function shortcode_register(string $tag, callable $fn, string $example = '', string $desc = ''): void {
    $GLOBALS['aurora_shortcodes'][$tag] = [
        'fn' => $fn,
        'example' => $example !== '' ? $example : "[{$tag}]",
        'desc' => $desc,
    ];
}

/** Minden regisztrált modul (tag => meta), névsorban — a szerkesztő súgópaneljéhez */
function shortcodes_all(): array {
    $out = $GLOBALS['aurora_shortcodes'];
    ksort($out);
    return $out;
}

/** A regisztrált shortcode-ok kicserélése; az ismeretlen [valami] érintetlen marad */
function shortcodes_apply(string $html): string {
    if (!$GLOBALS['aurora_shortcodes'] || !str_contains($html, '[')) return $html;
    return preg_replace_callback(
        '/\[([a-z0-9\-]+)((?:\s+[a-z_]+=(?:"[^"]*"|[^\]\s]+))*)\s*\]/u',
        function (array $m): string {
            $reg = $GLOBALS['aurora_shortcodes'][$m[1]] ?? null;
            if (!$reg) return $m[0];
            $attrs = [];
            preg_match_all('/([a-z_]+)=(?:"([^"]*)"|([^\]\s]+))/u', $m[2], $am, PREG_SET_ORDER);
            foreach ($am as $a) $attrs[$a[1]] = ($a[3] ?? '') !== '' ? $a[3] : ($a[2] ?? '');
            try {
                return (string)$reg['fn']($attrs);
            } catch (\Throwable) {
                return ''; // hibás modul ne törje el az oldalt
            }
        },
        $html
    );
}

/* ---------- Bővítmények felderítése és betöltése ---------- */

function plugins_dir(): string {
    return APP_ROOT . '/plugins';
}

/** plugin.json beolvasása (UTF-8 BOM-mal is működik) */
function plugin_manifest(string $file): ?array {
    $raw = (string)@file_get_contents($file);
    $json = json_decode(preg_replace('/^\xEF\xBB\xBF/', '', $raw), true);
    return is_array($json) ? $json : null;
}

/** Minden telepített bővítmény: [mappa => manifest + active flag] */
function plugins_all(): array {
    $dir = plugins_dir();
    if (!is_dir($dir)) return [];
    $active = plugins_active();
    $out = [];
    foreach (scandir($dir) as $slug) {
        if ($slug === '.' || $slug === '..' || !is_dir("$dir/$slug")) continue;
        $manifest = plugin_manifest("$dir/$slug/plugin.json");
        if ($manifest === null) continue;
        $out[$slug] = [
            'name' => (string)($manifest['name'] ?? $slug),
            'description' => (string)($manifest['description'] ?? ''),
            'version' => (string)($manifest['version'] ?? ''),
            'author' => (string)($manifest['author'] ?? ''),
            'active' => in_array($slug, $active, true),
            'has_entry' => is_file("$dir/$slug/plugin.php"),
        ];
    }
    ksort($out);
    return $out;
}

function plugins_active(): array {
    $list = json_decode(setting('active_plugins', '[]'), true);
    return is_array($list) ? array_values(array_filter($list, 'is_string')) : [];
}

/** Bekapcsolt bővítmények betöltése; a hibásak nem törik el az oldalt */
function plugins_boot(): void {
    $GLOBALS['plugin_errors'] = [];
    foreach (plugins_active() as $slug) {
        $file = plugins_dir() . '/' . $slug . '/plugin.php';
        if (!is_file($file)) continue;
        try {
            require_once $file;
        } catch (\Throwable $e) {
            $GLOBALS['plugin_errors'][$slug] = $e->getMessage();
        }
    }
}

/* ---------- Zip kicsomagolás (ZipArchive vagy beépített fallback) ---------- */

/** Zip kicsomagolása a célmappába; Throwable-t dob hibánál */
function zip_extract(string $zipFile, string $destDir): void {
    if (!is_dir($destDir)) mkdir($destDir, 0775, true);
    if (class_exists('ZipArchive')) {
        $za = new ZipArchive();
        if ($za->open($zipFile) !== true) throw new RuntimeException('A zip nem nyitható meg.');
        for ($i = 0; $i < $za->numFiles; $i++) {
            $name = zip_safe_name((string)$za->getNameIndex($i));
            if ($name === '') continue;
            if (str_ends_with($name, '/')) { @mkdir("$destDir/$name", 0775, true); continue; }
            @mkdir(dirname("$destDir/$name"), 0775, true);
            file_put_contents("$destDir/$name", $za->getFromIndex($i));
        }
        $za->close();
        return;
    }
    zip_extract_fallback($zipFile, $destDir);
}

/** Bejegyzésnév normalizálása és útvonal-kitörés (zip slip) elleni védelem */
function zip_safe_name(string $name): string {
    $name = str_replace('\\', '/', $name);
    $name = ltrim($name, '/');
    foreach (explode('/', $name) as $part) {
        if ($part === '..') return '';
    }
    return $name;
}

/** Minimális zip-olvasó (store + deflate) a központi könyvtár alapján */
function zip_extract_fallback(string $zipFile, string $destDir): void {
    $data = file_get_contents($zipFile);
    if ($data === false) throw new RuntimeException('A zip nem olvasható.');
    // End of Central Directory rekord megkeresése a fájl végéről
    $eocd = strrpos($data, "PK\x05\x06");
    if ($eocd === false) throw new RuntimeException('Érvénytelen zip fájl.');
    $hdr = unpack('vtotal', substr($data, $eocd + 10, 2))
         + unpack('Vcdsize/Vcdoffset', substr($data, $eocd + 12, 8));
    $pos = $hdr['cdoffset'];
    for ($i = 0; $i < $hdr['total']; $i++) {
        if (substr($data, $pos, 4) !== "PK\x01\x02") throw new RuntimeException('Sérült zip központi könyvtár.');
        $e = unpack('vmethod', substr($data, $pos + 10, 2))
           + unpack('Vcsize/Vusize', substr($data, $pos + 20, 8))
           + unpack('vnamelen/vextralen/vcommentlen', substr($data, $pos + 28, 6))
           + unpack('Voffset', substr($data, $pos + 42, 4));
        $name = zip_safe_name(substr($data, $pos + 46, $e['namelen']));
        $pos += 46 + $e['namelen'] + $e['extralen'] + $e['commentlen'];
        if ($name === '') continue;

        if (str_ends_with($name, '/')) { @mkdir("$destDir/$name", 0775, true); continue; }

        // Lokális fejléc a tényleges adatpozícióhoz
        $lh = $e['offset'];
        if (substr($data, $lh, 4) !== "PK\x03\x04") throw new RuntimeException('Sérült zip bejegyzés.');
        $l = unpack('vnamelen/vextralen', substr($data, $lh + 26, 4));
        $raw = substr($data, $lh + 30 + $l['namelen'] + $l['extralen'], $e['csize']);
        $content = match ($e['method']) {
            0 => $raw,
            8 => gzinflate($raw),
            default => throw new RuntimeException('Nem támogatott zip tömörítés.'),
        };
        if ($content === false) throw new RuntimeException('A zip bejegyzés nem csomagolható ki.');
        @mkdir(dirname("$destDir/$name"), 0775, true);
        file_put_contents("$destDir/$name", $content);
    }
}

/** Mappa rekurzív törlése (csak a plugins könyvtáron belül használjuk) */
function rrmdir(string $dir): void {
    if (!is_dir($dir)) return;
    foreach (new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    ) as $f) {
        $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
    }
    rmdir($dir);
}
