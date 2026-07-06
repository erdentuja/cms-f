<?php
declare(strict_types=1);

function admin_render(string $tpl, array $data = []): void {
    $data['user'] = auth_user();
    $data['flash'] = flash_get();
    $data['content'] = view('admin/' . $tpl, $data);
    echo view('admin/layout', $data);
}

/* ---------- Auth ---------- */

function admin_login_form(): void {
    if (auth_user()) redirect('admin');
    echo view('admin/login', ['flash' => flash_get()]);
}

function admin_login(): void {
    csrf_verify();
    if (auth_attempt($_POST['email'] ?? '', $_POST['password'] ?? '')) {
        redirect('admin');
    }
    flash_set('error', 'Hibás e-mail cím vagy jelszó.');
    redirect('admin/login');
}

function admin_logout(): void {
    csrf_verify();
    auth_logout();
    redirect('admin/login');
}

/* ---------- Dashboard ---------- */

function admin_dashboard(): void {
    require_login();
    $db = db();
    $stats = [
        'posts' => (int)$db->query('SELECT COUNT(*) FROM posts')->fetchColumn(),
        'pages' => (int)$db->query('SELECT COUNT(*) FROM pages')->fetchColumn(),
        'media' => (int)$db->query('SELECT COUNT(*) FROM media')->fetchColumn(),
        'users' => (int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn(),
        'views' => (int)$db->query('SELECT COALESCE(SUM(views),0) FROM posts')->fetchColumn(),
        'drafts' => (int)$db->query("SELECT COUNT(*) FROM posts WHERE status='draft'")->fetchColumn(),
    ];
    $recent = $db->query("SELECT p.*, c.name AS cat_name, c.color AS cat_color FROM posts p
                          LEFT JOIN categories c ON c.id=p.category_id
                          ORDER BY p.updated_at DESC LIMIT 6")->fetchAll();
    $popular = $db->query("SELECT title, slug, views FROM posts WHERE status='published' ORDER BY views DESC LIMIT 5")->fetchAll();
    admin_render('dashboard', ['title' => 'Vezérlőpult', 'stats' => $stats, 'recent' => $recent, 'popular' => $popular]);
}

/* ---------- Posts ---------- */

function admin_posts(): void {
    require_login();
    $q = trim((string)($_GET['q'] ?? ''));
    $status = $_GET['status'] ?? '';
    $sql = "SELECT p.*, c.name AS cat_name, c.color AS cat_color, u.name AS author FROM posts p
            LEFT JOIN categories c ON c.id=p.category_id LEFT JOIN users u ON u.id=p.author_id WHERE 1=1";
    $params = [];
    if ($q !== '')      { $sql .= ' AND p.title LIKE ?'; $params[] = "%$q%"; }
    if ($status !== '') { $sql .= ' AND p.status = ?';   $params[] = $status; }
    $sql .= ' ORDER BY p.updated_at DESC';
    $st = db()->prepare($sql);
    $st->execute($params);
    admin_render('posts', ['title' => 'Posztok', 'posts' => $st->fetchAll(), 'q' => $q, 'status' => $status]);
}

function admin_post_form(string $id = ''): void {
    require_login();
    $post = null;
    if ($id !== '') {
        $st = db()->prepare('SELECT * FROM posts WHERE id=?');
        $st->execute([(int)$id]);
        $post = $st->fetch();
        if (!$post) { flash_set('error', 'A poszt nem található.'); redirect('admin/posts'); }
    }
    $cats = db()->query('SELECT * FROM categories ORDER BY name')->fetchAll();
    admin_render('post-edit', ['title' => $post ? 'Poszt szerkesztése' : 'Új poszt', 'post' => $post, 'cats' => $cats]);
}

function admin_post_save(): void {
    $user = require_login();
    csrf_verify();
    $id = (int)($_POST['id'] ?? 0);
    $title = trim((string)($_POST['title'] ?? ''));
    if ($title === '') { flash_set('error', 'A cím megadása kötelező.'); redirect($id ? "admin/posts/$id" : 'admin/posts/new'); }

    $slug = trim((string)($_POST['slug'] ?? ''));
    $slug = unique_slug(db(), 'posts', slugify($slug !== '' ? $slug : $title), $id);
    $status = in_array($_POST['status'] ?? '', ['published', 'draft'], true) ? $_POST['status'] : 'draft';
    $catId = (int)($_POST['category_id'] ?? 0) ?: null;

    $data = [
        $title, $slug, trim((string)($_POST['excerpt'] ?? '')), (string)($_POST['content'] ?? ''),
        trim((string)($_POST['featured_image'] ?? '')), $catId, $status,
    ];

    if ($id) {
        $st = db()->prepare("UPDATE posts SET title=?, slug=?, excerpt=?, content=?, featured_image=?, category_id=?, status=?,
                             updated_at=datetime('now','localtime'),
                             published_at=CASE WHEN ?='published' AND published_at IS NULL THEN datetime('now','localtime') ELSE published_at END
                             WHERE id=?");
        $st->execute([...$data, $status, $id]);
    } else {
        $st = db()->prepare("INSERT INTO posts (title, slug, excerpt, content, featured_image, category_id, status, author_id, published_at)
                             VALUES (?,?,?,?,?,?,?,?, CASE WHEN ?='published' THEN datetime('now','localtime') ELSE NULL END)");
        $st->execute([...$data, $user['id'], $status]);
        $id = (int)db()->lastInsertId();
    }
    flash_set('success', 'A poszt mentve.');
    redirect("admin/posts/$id");
}

function admin_post_delete(): void {
    require_login();
    csrf_verify();
    db()->prepare('DELETE FROM posts WHERE id=?')->execute([(int)($_POST['id'] ?? 0)]);
    flash_set('success', 'A poszt törölve.');
    redirect('admin/posts');
}

/* ---------- Pages ---------- */

function admin_pages(): void {
    require_login();
    $pages = db()->query('SELECT * FROM pages ORDER BY menu_order, title')->fetchAll();
    admin_render('pages', ['title' => 'Oldalak', 'pages' => $pages]);
}

function admin_page_form(string $id = ''): void {
    require_login();
    $page = null;
    if ($id !== '') {
        $st = db()->prepare('SELECT * FROM pages WHERE id=?');
        $st->execute([(int)$id]);
        $page = $st->fetch();
        if (!$page) { flash_set('error', 'Az oldal nem található.'); redirect('admin/pages'); }
    }
    admin_render('page-edit', ['title' => $page ? 'Oldal szerkesztése' : 'Új oldal', 'page' => $page]);
}

function admin_page_save(): void {
    require_login();
    csrf_verify();
    $id = (int)($_POST['id'] ?? 0);
    $title = trim((string)($_POST['title'] ?? ''));
    if ($title === '') { flash_set('error', 'A cím megadása kötelező.'); redirect($id ? "admin/pages/$id" : 'admin/pages/new'); }

    $slug = trim((string)($_POST['slug'] ?? ''));
    $slug = unique_slug(db(), 'pages', slugify($slug !== '' ? $slug : $title), $id);
    $data = [
        $title, $slug, (string)($_POST['content'] ?? ''),
        in_array($_POST['status'] ?? '', ['published', 'draft'], true) ? $_POST['status'] : 'published',
    ];
    if ($id) {
        $st = db()->prepare("UPDATE pages SET title=?, slug=?, content=?, status=?, updated_at=datetime('now','localtime') WHERE id=?");
        $st->execute([...$data, $id]);
    } else {
        $st = db()->prepare('INSERT INTO pages (title, slug, content, status) VALUES (?,?,?,?)');
        $st->execute($data);
        $id = (int)db()->lastInsertId();
    }
    flash_set('success', 'Az oldal mentve.');
    redirect("admin/pages/$id");
}

function admin_page_delete(): void {
    require_login();
    csrf_verify();
    db()->prepare('DELETE FROM pages WHERE id=?')->execute([(int)($_POST['id'] ?? 0)]);
    flash_set('success', 'Az oldal törölve.');
    redirect('admin/pages');
}

/* ---------- Categories ---------- */

function admin_categories(): void {
    require_login();
    $cats = db()->query("SELECT c.*, (SELECT COUNT(*) FROM posts p WHERE p.category_id=c.id) AS cnt
                         FROM categories c ORDER BY c.name")->fetchAll();
    admin_render('categories', ['title' => 'Kategóriák', 'cats' => $cats]);
}

function admin_category_save(): void {
    require_login();
    csrf_verify();
    $id = (int)($_POST['id'] ?? 0);
    $name = trim((string)($_POST['name'] ?? ''));
    if ($name === '') { flash_set('error', 'A név megadása kötelező.'); redirect('admin/categories'); }
    $slug = unique_slug(db(), 'categories', slugify($name), $id);
    $color = preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['color'] ?? '') ? $_POST['color'] : '#6366f1';
    $desc = trim((string)($_POST['description'] ?? ''));
    if ($id) {
        db()->prepare('UPDATE categories SET name=?, slug=?, description=?, color=? WHERE id=?')->execute([$name, $slug, $desc, $color, $id]);
    } else {
        db()->prepare('INSERT INTO categories (name, slug, description, color) VALUES (?,?,?,?)')->execute([$name, $slug, $desc, $color]);
    }
    flash_set('success', 'Kategória mentve.');
    redirect('admin/categories');
}

function admin_category_delete(): void {
    require_login();
    csrf_verify();
    db()->prepare('DELETE FROM categories WHERE id=?')->execute([(int)($_POST['id'] ?? 0)]);
    flash_set('success', 'Kategória törölve.');
    redirect('admin/categories');
}

/* ---------- Menu ---------- */

function admin_menu(): void {
    require_login();
    $items = ['header' => [], 'footer' => []];
    foreach (db()->query('SELECT * FROM menu_items ORDER BY sort_order, id') as $mi) {
        $items[$mi['location']][] = $mi;
    }
    $pages = db()->query("SELECT title, slug FROM pages WHERE status='published' ORDER BY title")->fetchAll();
    $cats = db()->query('SELECT name, slug FROM categories ORDER BY name')->fetchAll();
    admin_render('menu', ['title' => 'Menük', 'items' => $items, 'pages' => $pages, 'cats' => $cats]);
}

function admin_menu_save(): void {
    require_login();
    csrf_verify();
    $id = (int)($_POST['id'] ?? 0);
    $label = trim((string)($_POST['label'] ?? ''));
    $url = trim((string)($_POST['url'] ?? ''));
    if ($label === '' || $url === '') {
        flash_set('error', 'A felirat és az URL megadása kötelező.');
        redirect('admin/menu');
    }
    if (!preg_match('#^https?://#i', $url)) $url = ltrim($url, '/');
    $location = ($_POST['location'] ?? '') === 'footer' ? 'footer' : 'header';
    $newTab = isset($_POST['new_tab']) ? 1 : 0;

    if ($id) {
        db()->prepare('UPDATE menu_items SET label=?, url=?, location=?, new_tab=? WHERE id=?')
            ->execute([$label, $url, $location, $newTab, $id]);
    } else {
        $max = (int)db()->query("SELECT COALESCE(MAX(sort_order),0) FROM menu_items WHERE location=" . db()->quote($location))->fetchColumn();
        db()->prepare('INSERT INTO menu_items (label, url, location, sort_order, new_tab) VALUES (?,?,?,?,?)')
            ->execute([$label, $url, $location, $max + 1, $newTab]);
    }
    flash_set('success', 'Menüelem mentve.');
    redirect('admin/menu');
}

function admin_menu_delete(): void {
    require_login();
    csrf_verify();
    db()->prepare('DELETE FROM menu_items WHERE id=?')->execute([(int)($_POST['id'] ?? 0)]);
    flash_set('success', 'Menüelem törölve.');
    redirect('admin/menu');
}

function admin_menu_reorder(): void {
    require_login();
    csrf_verify();
    $data = json_decode(file_get_contents('php://input'), true) ?? [];
    $ids = array_map('intval', $data['ids'] ?? []);
    $st = db()->prepare('UPDATE menu_items SET sort_order=? WHERE id=?');
    foreach ($ids as $i => $id) $st->execute([$i + 1, $id]);
    json_out(['ok' => true]);
}

/* ---------- Media ---------- */

function admin_media(): void {
    require_login();
    $items = db()->query('SELECT m.*, u.name AS uploader FROM media m LEFT JOIN users u ON u.id=m.user_id ORDER BY m.created_at DESC')->fetchAll();
    admin_render('media', ['title' => 'Médiatár', 'items' => $items]);
}

function admin_media_list(): void {
    require_login();
    $items = db()->query("SELECT id, filename, path, thumb, mime FROM media WHERE mime LIKE 'image/%' ORDER BY created_at DESC LIMIT 200")->fetchAll();
    foreach ($items as &$it) {
        $it['url'] = base_url($it['path']);
        $it['thumb_url'] = base_url($it['thumb'] ?: $it['path']);
    }
    json_out(['items' => $items]);
}

function admin_media_upload(): void {
    $user = require_login();
    csrf_verify();
    if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        json_out(['error' => 'A feltöltés sikertelen.'], 400);
    }
    $f = $_FILES['file'];
    if ($f['size'] > 20 * 1024 * 1024) json_out(['error' => 'A fájl túl nagy (max. 20 MB).'], 400);

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']) ?: '';
    $allowed = [
        'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif',
        'image/webp' => 'webp', 'image/svg+xml' => 'svg', 'application/pdf' => 'pdf',
    ];
    if (!isset($allowed[$mime])) json_out(['error' => 'Nem támogatott fájltípus: ' . $mime], 400);

    $dir = 'uploads/' . date('Y/m');
    $abs = APP_ROOT . '/' . $dir;
    if (!is_dir($abs)) mkdir($abs, 0775, true);

    $baseName = slugify(pathinfo($f['name'], PATHINFO_FILENAME));
    $name = $baseName . '-' . substr(bin2hex(random_bytes(4)), 0, 6) . '.' . $allowed[$mime];
    if (!move_uploaded_file($f['tmp_name'], "$abs/$name")) json_out(['error' => 'A fájl mentése sikertelen.'], 500);

    $w = $h = 0; $thumb = '';
    if (str_starts_with($mime, 'image/') && $mime !== 'image/svg+xml') {
        [$w, $h] = getimagesize("$abs/$name") ?: [0, 0];
        $thumb = media_make_thumb("$abs/$name", $mime, $dir, $name);
    }

    $st = db()->prepare('INSERT INTO media (filename, path, thumb, mime, size, width, height, user_id) VALUES (?,?,?,?,?,?,?,?)');
    $st->execute([$f['name'], "$dir/$name", $thumb, $mime, (int)$f['size'], $w, $h, $user['id']]);

    json_out([
        'id' => (int)db()->lastInsertId(),
        'url' => base_url("$dir/$name"),
        'thumb_url' => base_url($thumb ?: "$dir/$name"),
        'filename' => $f['name'],
        'mime' => $mime,
    ]);
}

/** Create a max-560px wide WebP thumbnail; returns relative path or ''. */
function media_make_thumb(string $absFile, string $mime, string $dir, string $name): string {
    $create = match ($mime) {
        'image/jpeg' => 'imagecreatefromjpeg',
        'image/png'  => 'imagecreatefrompng',
        'image/gif'  => 'imagecreatefromgif',
        'image/webp' => 'imagecreatefromwebp',
        default => null,
    };
    if (!$create || !function_exists($create)) return '';
    $src = @$create($absFile);
    if (!$src) return '';
    $w = imagesx($src); $h = imagesy($src);
    if ($w <= 560) { imagedestroy($src); return ''; }
    $nw = 560; $nh = (int)round($h * $nw / $w);
    $dst = imagecreatetruecolor($nw, $nh);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
    $thumbDir = APP_ROOT . "/$dir/thumbs";
    if (!is_dir($thumbDir)) mkdir($thumbDir, 0775, true);
    $thumbName = preg_replace('/\.[a-z]+$/', '.webp', $name);
    imagewebp($dst, "$thumbDir/$thumbName", 82);
    imagedestroy($src); imagedestroy($dst);
    return "$dir/thumbs/$thumbName";
}

function admin_media_delete(): void {
    require_login();
    csrf_verify();
    $id = (int)($_POST['id'] ?? 0);
    $st = db()->prepare('SELECT * FROM media WHERE id=?');
    $st->execute([$id]);
    if ($m = $st->fetch()) {
        foreach ([$m['path'], $m['thumb']] as $p) {
            if ($p && is_file(APP_ROOT . '/' . $p)) @unlink(APP_ROOT . '/' . $p);
        }
        db()->prepare('DELETE FROM media WHERE id=?')->execute([$id]);
    }
    if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch') json_out(['ok' => true]);
    flash_set('success', 'A fájl törölve.');
    redirect('admin/media');
}

/* ---------- Users ---------- */

function admin_users(): void {
    require_admin();
    $users = db()->query('SELECT id, name, email, role, created_at FROM users ORDER BY name')->fetchAll();
    admin_render('users', ['title' => 'Felhasználók', 'users' => $users]);
}

function admin_user_save(): void {
    $me = require_admin();
    csrf_verify();
    $id = (int)($_POST['id'] ?? 0);
    $name = trim((string)($_POST['name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $role = in_array($_POST['role'] ?? '', ['admin', 'editor'], true) ? $_POST['role'] : 'editor';
    $pass = (string)($_POST['password'] ?? '');

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash_set('error', 'Név és érvényes e-mail cím megadása kötelező.');
        redirect('admin/users');
    }
    $st = db()->prepare('SELECT id FROM users WHERE email=? AND id!=?');
    $st->execute([$email, $id]);
    if ($st->fetch()) { flash_set('error', 'Ez az e-mail cím már foglalt.'); redirect('admin/users'); }

    if ($id) {
        if ($id === (int)$me['id']) $role = 'admin'; // saját admin jog nem vonható el
        db()->prepare('UPDATE users SET name=?, email=?, role=? WHERE id=?')->execute([$name, $email, $role, $id]);
        if ($pass !== '') {
            if (mb_strlen($pass) < 8) { flash_set('error', 'A jelszó legalább 8 karakter legyen.'); redirect('admin/users'); }
            db()->prepare('UPDATE users SET password=? WHERE id=?')->execute([password_hash($pass, PASSWORD_DEFAULT), $id]);
        }
    } else {
        if (mb_strlen($pass) < 8) { flash_set('error', 'A jelszó legalább 8 karakter legyen.'); redirect('admin/users'); }
        db()->prepare('INSERT INTO users (name, email, password, role) VALUES (?,?,?,?)')
            ->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT), $role]);
    }
    flash_set('success', 'Felhasználó mentve.');
    redirect('admin/users');
}

function admin_user_delete(): void {
    $me = require_admin();
    csrf_verify();
    $id = (int)($_POST['id'] ?? 0);
    if ($id === (int)$me['id']) { flash_set('error', 'Saját magadat nem törölheted.'); redirect('admin/users'); }
    db()->prepare('DELETE FROM users WHERE id=?')->execute([$id]);
    flash_set('success', 'Felhasználó törölve.');
    redirect('admin/users');
}

/* ---------- Settings ---------- */

function admin_settings(): void {
    require_admin();
    admin_render('settings', ['title' => 'Beállítások']);
}

function admin_settings_save(): void {
    require_admin();
    csrf_verify();
    $keys = ['site_name', 'tagline', 'description', 'posts_per_page', 'footer_text'];
    $st = db()->prepare('INSERT INTO settings (key, value) VALUES (?,?) ON CONFLICT(key) DO UPDATE SET value=excluded.value');
    foreach ($keys as $k) {
        if (isset($_POST[$k])) $st->execute([$k, trim((string)$_POST[$k])]);
    }
    flash_set('success', 'Beállítások mentve.');
    redirect('admin/settings');
}
