<?php
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

mb_internal_encoding('UTF-8');
date_default_timezone_set('Europe/Budapest');

if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
}

ini_set('session.use_strict_mode', '1');
session_name('aurora_session');
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'cookie_samesite' => 'Lax',
]);

require APP_ROOT . '/app/helpers.php';
require APP_ROOT . '/app/db.php';
require APP_ROOT . '/app/auth.php';
require APP_ROOT . '/app/plugins.php';
require APP_ROOT . '/app/modules.php';

db(); // init + install on first run
plugins_boot(); // bekapcsolt bővítmények betöltése
