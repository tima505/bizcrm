<?php

$publicPath = __DIR__.'/public';

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

// Serve real FILES as-is (built-in server emulates Apache mod_rewrite
// here). The default Laravel router uses file_exists(), which is also
// true for directories — that breaks a bare /admin request, because
// public/admin/ exists as the Vite build output directory. PHP's
// built-in server then can't list it and returns its own 404 before
// Laravel is reached. is_file() narrows the check to actual files.
if ($uri !== '/' && is_file($publicPath.$uri)) {
    return false;
}

$formattedDateTime = date('D M j H:i:s Y');
$requestMethod = $_SERVER['REQUEST_METHOD'];
$remoteAddress = $_SERVER['REMOTE_ADDR'].':'.$_SERVER['REMOTE_PORT'];

file_put_contents('php://stdout', "[$formattedDateTime] $remoteAddress [$requestMethod] URI: $uri\n");

require_once $publicPath.'/index.php';
