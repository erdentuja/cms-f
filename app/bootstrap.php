<?php
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

mb_internal_encoding('UTF-8');
date_default_timezone_set('Europe/Budapest');

session_name('aurora_session');
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
]);

require APP_ROOT . '/app/helpers.php';
require APP_ROOT . '/app/db.php';
require APP_ROOT . '/app/auth.php';
require APP_ROOT . '/app/plugins.php';
require APP_ROOT . '/app/modules.php';

db(); // init + install on first run
plugins_boot(); // bekapcsolt bővítmények betöltése
