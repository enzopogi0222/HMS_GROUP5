<?php

declare(strict_types=1);

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '');

$publicPath = rtrim(str_replace('\\', '/', __DIR__ . '/public'), '/');
$path       = $publicPath . $uri;

// If the requested resource exists in /public then serve it directly.
if ($uri !== '/' && is_file($path)) {
    return false;
}

$frontController = $publicPath . '/index.php';
if (! is_file($frontController)) {
    $frontController = $publicPath . '/ci_front.php';
}

require $frontController;
