<?php
declare(strict_types=1);

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $path = APP_ROOT . '/storage/cms.sqlite';
        $fresh = !file_exists($path);
        if (!is_dir(dirname($path))) mkdir(dirname($path), 0775, true);
        $pdo = new PDO('sqlite:' . $path, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA journal_mode = WAL');
        $pdo->exec('PRAGMA foreign_keys = ON');
        if ($fresh) db_install($pdo);
        db_migrate($pdo);
    }
    return $pdo;
}

/** Idempotens migrációk már létező adatbázisokhoz */
function db_migrate(PDO $db): void {
    $pageCols = array_column($db->query('PRAGMA table_info(pages)')->fetchAll(), 'name');
    if (!in_array('builder', $pageCols, true)) $db->exec('ALTER TABLE pages ADD COLUMN builder INTEGER NOT NULL DEFAULT 0');
    if (!in_array('blocks', $pageCols, true)) $db->exec("ALTER TABLE pages ADD COLUMN blocks TEXT NOT NULL DEFAULT '[]'");

    $db->exec("CREATE TABLE IF NOT EXISTS menu_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        label TEXT NOT NULL,
        url TEXT NOT NULL,
        location TEXT NOT NULL DEFAULT 'header',
        sort_order INTEGER NOT NULL DEFAULT 0,
        new_tab INTEGER NOT NULL DEFAULT 0
    )");
    $seeded = $db->query("SELECT value FROM settings WHERE key='menu_seeded'")->fetchColumn();
    if (!$seeded) {
        $st = $db->prepare('INSERT INTO menu_items (label, url, location, sort_order) VALUES (?,?,?,?)');
        $i = 0;
        foreach ($db->query("SELECT title, slug FROM pages WHERE status='published' AND show_in_menu=1 ORDER BY menu_order, title") as $p) {
            $st->execute([$p['title'], $p['slug'], 'header', ++$i]);
        }
        $db->exec("INSERT INTO settings (key, value) VALUES ('menu_seeded','1')");
    }

    $db->exec("CREATE TABLE IF NOT EXISTS redirects (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        from_path TEXT NOT NULL UNIQUE,
        to_url TEXT NOT NULL,
        code INTEGER NOT NULL DEFAULT 301,
        hits INTEGER NOT NULL DEFAULT 0,
        last_hit TEXT,
        created_at TEXT NOT NULL DEFAULT (datetime('now','localtime'))
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS templates (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        data TEXT NOT NULL DEFAULT '{}',
        created_at TEXT NOT NULL DEFAULT (datetime('now','localtime'))
    )");
    if (!(int)$db->query('SELECT COUNT(*) FROM templates')->fetchColumn()) {
        $presets = [
            ['Aurora', ['accent'=>'#6d5bfa','accent2'=>'#f43f8e','bg'=>'#fbfaf8','surface'=>'#ffffff','ink'=>'#16161d','radius'=>18,'font_display'=>'Sora','font_body'=>'Inter']],
            ['Smaragd', ['accent'=>'#10b981','accent2'=>'#0ea5e9','bg'=>'#f6faf8','surface'=>'#ffffff','ink'=>'#132019','radius'=>14,'font_display'=>'Space Grotesk','font_body'=>'Inter']],
            ['Naplemente', ['accent'=>'#f97316','accent2'=>'#e11d48','bg'=>'#fdf9f4','surface'=>'#ffffff','ink'=>'#201613','radius'=>22,'font_display'=>'Poppins','font_body'=>'DM Sans']],
            ['Tinta', ['accent'=>'#2563eb','accent2'=>'#7c3aed','bg'=>'#f5f7fb','surface'=>'#ffffff','ink'=>'#101623','radius'=>10,'font_display'=>'Manrope','font_body'=>'Inter']],
            ['Rozé', ['accent'=>'#be185d','accent2'=>'#9333ea','bg'=>'#fdf7f9','surface'=>'#ffffff','ink'=>'#22131b','radius'=>20,'font_display'=>'Playfair Display','font_body'=>'Lora']],
        ];
        $st = $db->prepare('INSERT INTO templates (name, data) VALUES (?,?)');
        foreach ($presets as [$name, $data]) $st->execute([$name, json_encode($data)]);
        $db->exec("INSERT OR REPLACE INTO settings (key, value) VALUES ('active_template','1')");
    }
}

function db_install(PDO $db): void {
    $db->exec(<<<SQL
    CREATE TABLE users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        role TEXT NOT NULL DEFAULT 'editor',
        created_at TEXT NOT NULL DEFAULT (datetime('now','localtime'))
    );
    CREATE TABLE categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        slug TEXT NOT NULL UNIQUE,
        description TEXT DEFAULT '',
        color TEXT DEFAULT '#6366f1'
    );
    CREATE TABLE posts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        slug TEXT NOT NULL UNIQUE,
        excerpt TEXT DEFAULT '',
        content TEXT DEFAULT '',
        featured_image TEXT DEFAULT '',
        category_id INTEGER REFERENCES categories(id) ON DELETE SET NULL,
        author_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
        status TEXT NOT NULL DEFAULT 'draft',
        views INTEGER NOT NULL DEFAULT 0,
        published_at TEXT,
        created_at TEXT NOT NULL DEFAULT (datetime('now','localtime')),
        updated_at TEXT NOT NULL DEFAULT (datetime('now','localtime'))
    );
    CREATE INDEX idx_posts_status ON posts(status, published_at DESC);
    CREATE INDEX idx_posts_cat ON posts(category_id);
    CREATE TABLE pages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        slug TEXT NOT NULL UNIQUE,
        content TEXT DEFAULT '',
        status TEXT NOT NULL DEFAULT 'published',
        show_in_menu INTEGER NOT NULL DEFAULT 1,
        menu_order INTEGER NOT NULL DEFAULT 0,
        created_at TEXT NOT NULL DEFAULT (datetime('now','localtime')),
        updated_at TEXT NOT NULL DEFAULT (datetime('now','localtime'))
    );
    CREATE TABLE media (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        filename TEXT NOT NULL,
        path TEXT NOT NULL,
        thumb TEXT DEFAULT '',
        mime TEXT DEFAULT '',
        size INTEGER DEFAULT 0,
        width INTEGER DEFAULT 0,
        height INTEGER DEFAULT 0,
        user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
        created_at TEXT NOT NULL DEFAULT (datetime('now','localtime'))
    );
    CREATE TABLE settings (
        key TEXT PRIMARY KEY,
        value TEXT DEFAULT ''
    );
    SQL);

    // Seed
    $db->prepare('INSERT INTO users (name, email, password, role) VALUES (?,?,?,?)')
       ->execute(['Adminisztrátor', 'admin@cms.local', password_hash('admin123', PASSWORD_DEFAULT), 'admin']);

    $settings = [
        'site_name' => 'Aurora CMS',
        'tagline' => 'Modern, gyors és gyönyörű tartalomkezelés',
        'description' => 'Egy villámgyors, modern CMS rendszer.',
        'posts_per_page' => '9',
        'footer_text' => '© ' . date('Y') . ' Aurora CMS. Minden jog fenntartva.',
    ];
    $st = $db->prepare('INSERT INTO settings (key, value) VALUES (?,?)');
    foreach ($settings as $k => $v) $st->execute([$k, $v]);

    $cats = [
        ['Technológia', 'technologia', 'A legfrissebb tech hírek és elemzések', '#6366f1'],
        ['Dizájn', 'dizajn', 'Vizuális kultúra, UI/UX és kreativitás', '#ec4899'],
        ['Életmód', 'eletmod', 'Inspiráció a mindennapokhoz', '#10b981'],
    ];
    $st = $db->prepare('INSERT INTO categories (name, slug, description, color) VALUES (?,?,?,?)');
    foreach ($cats as $c) $st->execute($c);

    $posts = [
        ['Üdvözöl az Aurora CMS!', 'udvozol-az-aurora-cms',
         'Ismerd meg az új tartalomkezelő rendszered: gyors, modern és gyönyörű.',
         '<h2>Minden, amire szükséged van</h2><p>Az Aurora CMS egy villámgyors, modern tartalomkezelő rendszer. Posztok, oldalak, kategóriák, médiakezelő és felhasználókezelés — mindez egy letisztult, elegáns admin felületen.</p><h3>Első lépések</h3><p>Jelentkezz be az admin felületre a <strong>/admin</strong> címen, és kezdd el feltölteni a saját tartalmaidat. A kezdő belépési adatok: <code>admin@cms.local</code> / <code>admin123</code> — az első belépés után mindenképp változtasd meg a jelszót!</p><blockquote>A jó tartalom a jó eszközökkel kezdődik.</blockquote><p>Jó munkát kívánunk!</p>',
         1, 'published'],
        ['A minimalista dizájn ereje', 'a-minimalista-dizajn-ereje',
         'Miért működik a kevesebb több elve a webes felületeken?',
         '<p>A minimalizmus nem a díszítés hiánya, hanem a lényeg kiemelése. Egy jól megtervezett felület minden eleme okkal van ott.</p><h2>Fókusz a tartalomra</h2><p>A tipográfia, a fehér tér és a finom színek együtt teremtik meg azt az élményt, amiben a tartalom ragyoghat.</p><p>Az Aurora CMS frontend témája is erre az elvre épül: gyors betöltés, olvasható betűk, elegáns részletek.</p>',
         2, 'published'],
        ['Sebesség mint alapelv', 'sebesseg-mint-alapelv',
         'Hogyan lehet egy CMS egyszerre gazdag funkciójú és villámgyors?',
         '<p>Az Aurora CMS SQLite adatbázisra és minimális függőségekre épül — nincs nehéz keretrendszer, nincs több száz lekérdezés oldalanként.</p><h2>Mitől gyors?</h2><ul><li>SQLite WAL móddal — memóriasebességű olvasás</li><li>Nulla külső függőség a frontenden</li><li>Optimalizált képek, automatikus bélyegképek</li></ul><p>Az eredmény: oldalbetöltés ezredmásodpercek alatt.</p>',
         1, 'published'],
    ];
    $st = $db->prepare("INSERT INTO posts (title, slug, excerpt, content, category_id, author_id, status, published_at)
                        VALUES (?,?,?,?,?,1,?,datetime('now','localtime'))");
    foreach ($posts as $p) $st->execute($p);

    $db->prepare("INSERT INTO pages (title, slug, content, menu_order) VALUES (?,?,?,?)")
       ->execute(['Rólunk', 'rolunk',
        '<p>Az Aurora CMS egy modern tartalomkezelő rendszer, amely a sebességet és a szép dizájnt helyezi előtérbe.</p><p>Ezt az oldalt az admin felületen, az <strong>Oldalak</strong> menüpont alatt szerkesztheted.</p>', 1]);
    $db->prepare("INSERT INTO pages (title, slug, content, menu_order) VALUES (?,?,?,?)")
       ->execute(['Kapcsolat', 'kapcsolat',
        '<p>Vedd fel velünk a kapcsolatot: <a href="mailto:hello@example.com">hello@example.com</a></p>', 2]);
}
