<?php
/**
 * Application bootstrap: autoloader, config, session. Included by index.php.
 *
 * Returns true if the app is installed (config present & valid), false
 * otherwise so the front controller can redirect to install.php.
 */

use App\Core\Config;

define('APP_ROOT', dirname(__DIR__));

require APP_ROOT . '/app/helpers.php';

$installed = Config::load(APP_ROOT . '/config/config.php');

if ($installed) {
    date_default_timezone_set(Config::get('app.timezone', 'Asia/Bangkok'));
    $debug = (bool) Config::get('app.debug', false);
    error_reporting($debug ? E_ALL : (E_ALL & ~E_DEPRECATED & ~E_NOTICE));
    ini_set('display_errors', $debug ? '1' : '0');
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('AIFIN_SESS');
    session_start();
}

return $installed;
