<?php
declare(strict_types=1);

// Static file passthrough for the PHP built-in server (php -S ... index.php)
if (PHP_SAPI === 'cli-server') {
    $p = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $file = __DIR__ . $p;
    if ($p !== '/' && file_exists($file) && !is_dir($file)) return false;
}

require __DIR__ . '/app/bootstrap.php';
require APP_ROOT . '/app/controllers/front.php';
require APP_ROOT . '/app/controllers/admin.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
if ($base !== '' && str_starts_with($uri, $base)) $uri = substr($uri, strlen($base));
$uri = '/' . trim(rawurldecode($uri), '/');
$method = $_SERVER['REQUEST_METHOD'];

// 301/302 átirányítások — csak frontend GET kérésekre, az admin útvonalakat nem érinti
if ($method === 'GET' && !str_starts_with($uri, '/admin')) {
    redirects_apply($uri);
}

/** Route table: [method, pattern, handler]. {x} captures a segment. */
$routes = [
    // Frontend
    ['GET',  '/',                       'front_home'],
    ['GET',  '/post/{slug}',            'front_post'],
    ['GET',  '/category/{slug}',        'front_category'],
    ['GET',  '/search',                 'front_search'],
    ['GET',  '/sitemap.xml',            'front_sitemap'],
    ['GET',  '/rss.xml',                'front_rss'],

    // Admin auth
    ['GET',  '/admin/login',            'admin_login_form'],
    ['POST', '/admin/login',            'admin_login'],
    ['POST', '/admin/logout',           'admin_logout'],

    // Admin
    ['GET',  '/admin',                  'admin_dashboard'],
    ['GET',  '/admin/posts',            'admin_posts'],
    ['GET',  '/admin/posts/new',        'admin_post_form'],
    ['GET',  '/admin/posts/{id}',       'admin_post_form'],
    ['POST', '/admin/posts/save',       'admin_post_save'],
    ['POST', '/admin/posts/preview',    'admin_post_preview'],
    ['POST', '/admin/posts/delete',     'admin_post_delete'],

    ['GET',  '/admin/pages',            'admin_pages'],
    ['GET',  '/admin/pages/new',        'admin_page_form'],
    ['GET',  '/admin/pages/{id}',       'admin_page_form'],
    ['POST', '/admin/pages/save',       'admin_page_save'],
    ['POST', '/admin/pages/preview',    'admin_page_preview'],
    ['POST', '/admin/pages/delete',     'admin_page_delete'],

    ['GET',  '/admin/categories',       'admin_categories'],
    ['POST', '/admin/categories/save',  'admin_category_save'],
    ['POST', '/admin/categories/delete','admin_category_delete'],

    ['GET',  '/admin/menu',             'admin_menu'],
    ['POST', '/admin/menu/save',        'admin_menu_save'],
    ['POST', '/admin/menu/delete',      'admin_menu_delete'],
    ['POST', '/admin/menu/reorder',     'admin_menu_reorder'],

    ['GET',  '/admin/media',            'admin_media'],
    ['GET',  '/admin/media/list',       'admin_media_list'],
    ['POST', '/admin/media/upload',     'admin_media_upload'],
    ['POST', '/admin/media/delete',     'admin_media_delete'],
    ['POST', '/admin/media/delete-orphans', 'admin_media_orphans_delete'],

    ['GET',  '/admin/templates',        'admin_templates'],
    ['POST', '/admin/templates/save',   'admin_template_save'],
    ['POST', '/admin/templates/activate','admin_template_activate'],
    ['POST', '/admin/templates/delete', 'admin_template_delete'],
    ['GET',  '/admin/templates/export/{id}', 'admin_template_export'],
    ['POST', '/admin/templates/import', 'admin_template_import'],

    ['GET',  '/admin/plugins',          'admin_plugins'],
    ['GET',  '/admin/plugins/export/{id}', 'admin_plugin_export'],
    ['POST', '/admin/plugins/toggle',   'admin_plugin_toggle'],
    ['POST', '/admin/plugins/delete',   'admin_plugin_delete'],
    ['POST', '/admin/plugins/upload',   'admin_plugin_upload'],

    ['GET',  '/admin/redirects',        'admin_redirects'],
    ['POST', '/admin/redirects/save',   'admin_redirect_save'],
    ['POST', '/admin/redirects/delete', 'admin_redirect_delete'],

    ['GET',  '/admin/users',            'admin_users'],
    ['POST', '/admin/users/save',       'admin_user_save'],
    ['POST', '/admin/users/delete',     'admin_user_delete'],

    ['GET',  '/admin/settings',         'admin_settings'],
    ['POST', '/admin/settings/save',    'admin_settings_save'],
];

// Bővítmények saját útvonalakat vehetnek fel a 'routes' filterrel
$routes = apply_filters('routes', $routes);

foreach ($routes as [$m, $pattern, $handler]) {
    if ($m !== $method) continue;
    $regex = '#^' . preg_replace('/\{[a-z]+\}/', '([^/]+)', $pattern) . '$#u';
    if (preg_match($regex, $uri, $matches)) {
        array_shift($matches);
        $handler(...$matches);
        exit;
    }
}

// Fallback: /{slug} → static page
if ($method === 'GET' && preg_match('#^/([a-z0-9\-]+)$#u', $uri, $m)) {
    front_page($m[1]);
    exit;
}

http_response_code(404);
echo view('front/404');
