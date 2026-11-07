<?php

/**
 * Development router for `php artisan serve`.
 *
 * Windows junctions used by `php artisan storage:link` are visible to PHP
 * only through the application layer, not through the CLI server's static
 * file handler. Normalise the script name so Laravel resolves the request
 * path correctly and the native `storage.public` route can serve the file.
 */

$publicPath = getcwd();

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

if ($uri !== '/' && is_file($publicPath.$uri)) {
    return false;
}

$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = $publicPath.'/index.php';

require_once $publicPath.'/index.php';
