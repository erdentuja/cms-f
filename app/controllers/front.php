<?php
declare(strict_types=1);

function front_nav_pages(): array {
    return db()->query("SELECT title, slug FROM pages WHERE status='published' AND show_in_menu=1 ORDER BY menu_order, title")->fetchAll();
}

function front_categories(): array {
    return db()->query("SELECT c.*, (SELECT COUNT(*) FROM posts p WHERE p.category_id=c.id AND p.status='published') AS cnt
                        FROM categories c ORDER BY c.name")->fetchAll();
}

function front_render(string $tpl, array $data = []): void {
    $data['navPages'] = front_nav_pages();
    $data['content'] = view('front/' . $tpl, $data);
    echo view('front/layout', $data);
}

function front_home(): void {
    $perPage = max(1, (int)setting('posts_per_page', '9'));
    $page = max(1, (int)($_GET['p'] ?? 1));
    $offset = ($page - 1) * $perPage;

    $total = (int)db()->query("SELECT COUNT(*) FROM posts WHERE status='published'")->fetchColumn();
    $st = db()->prepare("SELECT p.*, c.name AS cat_name, c.slug AS cat_slug, c.color AS cat_color, u.name AS author
                         FROM posts p LEFT JOIN categories c ON c.id=p.category_id LEFT JOIN users u ON u.id=p.author_id
                         WHERE p.status='published' ORDER BY p.published_at DESC LIMIT ? OFFSET ?");
    $st->execute([$perPage, $offset]);
    $posts = $st->fetchAll();

    $hero = ($page === 1 && $posts) ? array_shift($posts) : null;

    front_render('home', [
        'title' => setting('site_name'),
        'hero' => $hero,
        'posts' => $posts,
        'categories' => front_categories(),
        'page' => $page,
        'pages_total' => (int)ceil($total / $perPage),
    ]);
}

function front_post(string $slug): void {
    $st = db()->prepare("SELECT p.*, c.name AS cat_name, c.slug AS cat_slug, c.color AS cat_color, u.name AS author
                         FROM posts p LEFT JOIN categories c ON c.id=p.category_id LEFT JOIN users u ON u.id=p.author_id
                         WHERE p.slug=? AND p.status='published'");
    $st->execute([$slug]);
    $post = $st->fetch();
    if (!$post) { http_response_code(404); echo view('front/404'); return; }

    db()->prepare('UPDATE posts SET views = views + 1 WHERE id = ?')->execute([$post['id']]);

    $st = db()->prepare("SELECT p.title, p.slug, p.featured_image, p.published_at FROM posts p
                         WHERE p.status='published' AND p.id != ? AND (p.category_id = ? OR ? IS NULL)
                         ORDER BY p.published_at DESC LIMIT 3");
    $st->execute([$post['id'], $post['category_id'], $post['category_id']]);

    front_render('post', [
        'title' => $post['title'],
        'post' => $post,
        'related' => $st->fetchAll(),
        'metaDescription' => $post['excerpt'] ?: excerpt_of($post['content']),
        'ogType' => 'article',
        'ogImage' => $post['featured_image'] ?: null,
    ]);
}

function front_page(string $slug): void {
    $st = db()->prepare("SELECT * FROM pages WHERE slug=? AND status='published'");
    $st->execute([$slug]);
    $page = $st->fetch();
    if (!$page) { http_response_code(404); echo view('front/404'); return; }
    front_render('page', ['title' => $page['title'], 'page' => $page]);
}

function front_category(string $slug): void {
    $st = db()->prepare('SELECT * FROM categories WHERE slug=?');
    $st->execute([$slug]);
    $cat = $st->fetch();
    if (!$cat) { http_response_code(404); echo view('front/404'); return; }

    $st = db()->prepare("SELECT p.*, c.name AS cat_name, c.slug AS cat_slug, c.color AS cat_color, u.name AS author
                         FROM posts p LEFT JOIN categories c ON c.id=p.category_id LEFT JOIN users u ON u.id=p.author_id
                         WHERE p.status='published' AND p.category_id=? ORDER BY p.published_at DESC");
    $st->execute([$cat['id']]);

    front_render('category', ['title' => $cat['name'], 'cat' => $cat, 'posts' => $st->fetchAll()]);
}

function front_sitemap(): void {
    header('Content-Type: application/xml; charset=utf-8');
    $out = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $out .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    $add = function (string $loc, string $lastmod = '', string $prio = '0.6') use (&$out) {
        $out .= "  <url><loc>" . e($loc) . "</loc>";
        if ($lastmod) $out .= '<lastmod>' . date('Y-m-d', strtotime($lastmod)) . '</lastmod>';
        $out .= "<priority>$prio</priority></url>\n";
    };
    $add(site_url('/'), '', '1.0');
    foreach (db()->query("SELECT slug, updated_at FROM pages WHERE status='published'") as $p) {
        $add(site_url($p['slug']), $p['updated_at'], '0.7');
    }
    foreach (db()->query("SELECT slug, updated_at FROM posts WHERE status='published'") as $p) {
        $add(site_url('post/' . $p['slug']), $p['updated_at'], '0.8');
    }
    foreach (db()->query('SELECT slug FROM categories') as $c) {
        $add(site_url('category/' . $c['slug']), '', '0.5');
    }
    echo $out . '</urlset>';
}

function front_rss(): void {
    header('Content-Type: application/rss+xml; charset=utf-8');
    $posts = db()->query("SELECT * FROM posts WHERE status='published' ORDER BY published_at DESC LIMIT 20")->fetchAll();
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<rss version="2.0"><channel>';
    echo '<title>' . e(setting('site_name')) . '</title>';
    echo '<link>' . e(site_url('/')) . '</link>';
    echo '<description>' . e(setting('description')) . '</description>';
    echo '<language>hu</language>';
    foreach ($posts as $p) {
        echo '<item>';
        echo '<title>' . e($p['title']) . '</title>';
        echo '<link>' . e(site_url('post/' . $p['slug'])) . '</link>';
        echo '<guid>' . e(site_url('post/' . $p['slug'])) . '</guid>';
        echo '<pubDate>' . date(DATE_RSS, strtotime($p['published_at'])) . '</pubDate>';
        echo '<description>' . e($p['excerpt'] ?: excerpt_of($p['content'])) . '</description>';
        echo '</item>';
    }
    echo '</channel></rss>';
}

function front_search(): void {
    $q = trim((string)($_GET['q'] ?? ''));
    $posts = [];
    if ($q !== '') {
        $st = db()->prepare("SELECT p.*, c.name AS cat_name, c.slug AS cat_slug, c.color AS cat_color, u.name AS author
                             FROM posts p LEFT JOIN categories c ON c.id=p.category_id LEFT JOIN users u ON u.id=p.author_id
                             WHERE p.status='published' AND (p.title LIKE ? OR p.content LIKE ? OR p.excerpt LIKE ?)
                             ORDER BY p.published_at DESC LIMIT 30");
        $like = '%' . $q . '%';
        $st->execute([$like, $like, $like]);
        $posts = $st->fetchAll();
    }
    front_render('search', ['title' => 'Keresés', 'q' => $q, 'posts' => $posts]);
}
