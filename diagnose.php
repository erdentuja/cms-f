<?php
/* Aurora CMS — tárhely-diagnosztika.
   Töltsd fel az index.php mellé, nyisd meg böngészőben (pl. https://domain.hu/mappa/diagnose.php),
   és minden követelményt kipipál vagy megjelöl. HASZNÁLAT UTÁN TÖRÖLD! */
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', '1');

function sor($nev, $ok, $reszlet) {
    $szin = $ok ? '#18a058' : '#d03050';
    $jel = $ok ? '&#10004;' : '&#10008;';
    echo '<tr><td style="padding:6px 14px 6px 0;white-space:nowrap">' . $nev . '</td>';
    echo '<td style="padding:6px 14px;color:' . $szin . ';font-weight:bold">' . $jel . '</td>';
    echo '<td style="padding:6px 0;color:#555">' . $reszlet . '</td></tr>';
}

echo '<!DOCTYPE html><html lang="hu"><head><meta charset="utf-8"><title>Aurora CMS diagnosztika</title></head>';
echo '<body style="font-family:sans-serif;max-width:860px;margin:40px auto;line-height:1.5">';
echo '<h1>Aurora CMS — tárhely-diagnosztika</h1>';
echo '<table style="border-collapse:collapse">';

$phpOk = PHP_VERSION_ID >= 80100;
sor('PHP verzió (8.1+ kell)', $phpOk, 'Jelenleg: PHP ' . PHP_VERSION . ($phpOk ? '' : ' — a tárhely vezérlőpultjában válts 8.1+ (ajánlott: 8.3) verzióra!'));

sor('pdo_sqlite bővítmény (adatbázis)', extension_loaded('pdo_sqlite'), extension_loaded('pdo_sqlite') ? 'rendben' : 'HIÁNYZIK — enélkül a CMS nem indul; kapcsold be a PHP-beállításoknál');
sor('mbstring bővítmény (szövegkezelés)', extension_loaded('mbstring'), extension_loaded('mbstring') ? 'rendben' : 'HIÁNYZIK — enélkül a CMS nem indul');
sor('gd bővítmény (bélyegképek)', extension_loaded('gd'), extension_loaded('gd') ? (function_exists('imagewebp') ? 'rendben, WebP támogatással' : 'van GD, de WebP nélkül — a bélyegképek kimaradhatnak') : 'HIÁNYZIK — a képfeltöltés bélyegképei nem készülnek el');
sor('fileinfo bővítmény (feltöltés-ellenőrzés)', extension_loaded('fileinfo'), extension_loaded('fileinfo') ? 'rendben' : 'HIÁNYZIK — a médiafeltöltés nem fog működni');
sor('dom bővítmény (HTML-tisztítás)', extension_loaded('dom'), extension_loaded('dom') ? 'rendben' : 'hiányzik — működik, de egyszerűsített tartalom-tisztítással');
sor('zip bővítmény (bővítmény-telepítés)', extension_loaded('zip'), extension_loaded('zip') ? 'rendben' : 'hiányzik — nem gond, van beépített tartalék zip-olvasó');

$dir = __DIR__;
$storageOk = is_dir($dir . '/storage') ? is_writable($dir . '/storage') : @mkdir($dir . '/storage', 0775, true);
sor('storage/ mappa írható', (bool)$storageOk, $storageOk ? 'rendben' : 'NEM ÍRHATÓ — adj írási jogot (755/775, megfelelő tulajdonos); enélkül a CMS nem indul');
$uploadsOk = is_dir($dir . '/uploads') ? is_writable($dir . '/uploads') : @mkdir($dir . '/uploads', 0775, true);
sor('uploads/ mappa írható', (bool)$uploadsOk, $uploadsOk ? 'rendben' : 'NEM ÍRHATÓ — a médiafeltöltés nem fog működni');

$dbFile = $dir . '/storage/cms.sqlite';
sor('Adatbázis-fájl', true, file_exists($dbFile) ? 'megvan (' . round(filesize($dbFile) / 1024) . ' KB) — a meglévő tartalom és belépés él' : 'nincs — első nyitáskor frissen települ, a kezdeti jelszó a storage/initial-admin.txt fájlba kerül');

if (function_exists('apache_get_modules')) {
    $rewriteOk = in_array('mod_rewrite', apache_get_modules());
    sor('mod_rewrite (szép URL-ek)', $rewriteOk, $rewriteOk ? 'rendben' : 'HIÁNYZIK — az al-oldalak 404-et adnak majd');
} else {
    sor('mod_rewrite (szép URL-ek)', true, 'innen nem megállapítható — ha ez az oldal megnyílt, de a főoldal 500-at ad, NEM a rewrite a hiba oka');
}

if ($phpOk && extension_loaded('pdo_sqlite') && $storageOk) {
    $bootOk = true;
    $bootMsg = '';
    try {
        require $dir . '/app/bootstrap.php';
        $bootMsg = 'a CMS magja hiba nélkül elindult';
    } catch (Throwable $e) {
        $bootOk = false;
        $bootMsg = 'HIBA: ' . htmlspecialchars($e->getMessage()) . ' (' . htmlspecialchars(basename($e->getFile())) . ':' . $e->getLine() . ')';
    }
    sor('Próbaindítás (bootstrap)', $bootOk, $bootMsg);
} else {
    sor('Próbaindítás (bootstrap)', false, 'kihagyva — előbb a fenti pirosakat kell rendezni');
}

echo '</table>';
echo '<p style="margin-top:24px;padding:12px 16px;background:#fff3cd;border:1px solid #ffc107;border-radius:8px"><strong>Fontos:</strong> ellenőrzés után töröld ezt a fájlt (diagnose.php) a szerverről!</p>';
echo '</body></html>';
