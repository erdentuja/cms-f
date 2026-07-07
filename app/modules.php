<?php
declare(strict_types=1);

/* ===== Beépített modulok =====
 * A tartalomba shortcode-dal beszúrható elemek. Új modul: shortcode_register().
 * A csere a 'content' filteren fut, így posztban, oldalon és blokkban is működik.
 */

add_filter('content', 'shortcodes_apply');

/* ---- [kereses] — keresőmező ---- */

shortcode_register('kereses', function (array $a): string {
    $ph = trim((string)($a['szoveg'] ?? 'Keresés a tartalomban…'));
    return '<form class="mod-search" action="' . e(base_url('search')) . '" method="get" role="search">'
         . '<input type="search" name="q" placeholder="' . e($ph) . '" aria-label="Keresés">'
         . '<button type="submit" class="btn btn-primary">Keresés</button></form>';
}, '[kereses]', 'Keresőmező — a szöveg módosítható: [kereses szoveg="Mit keresel?"]');

/* ---- [friss-posztok] és [nepszeru] — posztlisták ---- */

function module_post_list(string $order, int $limit): string {
    $limit = max(1, min(20, $limit));
    // $order fix belső érték (nem felhasználói adat)
    $rows = db()->query("SELECT p.title, p.slug, p.published_at FROM posts p
                         WHERE p.status='published' ORDER BY {$order} LIMIT {$limit}")->fetchAll();
    if (!$rows) return '';
    $li = '';
    foreach ($rows as $r) {
        $li .= '<li><a href="' . e(base_url('post/' . $r['slug'])) . '">' . e($r['title']) . '</a>'
             . '<span class="mod-meta">' . e(hu_date($r['published_at'])) . '</span></li>';
    }
    return '<ul class="mod-posts">' . $li . '</ul>';
}

shortcode_register('friss-posztok',
    fn(array $a) => module_post_list('p.published_at DESC', (int)($a['limit'] ?? 5)),
    '[friss-posztok limit=5]', 'A legfrissebb posztok listája');

shortcode_register('nepszeru',
    fn(array $a) => module_post_list('p.views DESC', (int)($a['limit'] ?? 5)),
    '[nepszeru limit=5]', 'A legolvasottabb posztok listája');

/* ---- [kategoriak] — kategóriafelhő ---- */

shortcode_register('kategoriak', function (array $a): string {
    $rows = db()->query("SELECT c.name, c.slug, c.color, COUNT(p.id) AS cnt FROM categories c
                         LEFT JOIN posts p ON p.category_id = c.id AND p.status='published'
                         GROUP BY c.id ORDER BY c.name")->fetchAll();
    if (!$rows) return '';
    $out = '';
    foreach ($rows as $r) {
        $out .= '<a class="mod-cat" style="--c:' . e($r['color']) . '" href="' . e(base_url('category/' . $r['slug'])) . '">'
              . e($r['name']) . ' <span>' . (int)$r['cnt'] . '</span></a>';
    }
    return '<div class="mod-cats">' . $out . '</div>';
}, '[kategoriak]', 'Kategóriafelhő a posztok számával');
