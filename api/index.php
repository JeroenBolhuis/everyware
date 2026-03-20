<?php

declare(strict_types=1);

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$publicPath = realpath(__DIR__.'/../public');

if ($publicPath === false) {
    http_response_code(500);

    exit('Public path not found.');
}

$requestedFile = realpath($publicPath.($requestPath === false ? '/' : $requestPath));

if (
    $requestedFile !== false
    && str_starts_with($requestedFile, $publicPath)
    && is_file($requestedFile)
) {
    $extension = strtolower(pathinfo($requestedFile, PATHINFO_EXTENSION));
    $mimeTypes = [
        'avif' => 'image/avif',
        'css' => 'text/css; charset=UTF-8',
        'gif' => 'image/gif',
        'ico' => 'image/x-icon',
        'jpg' => 'image/jpeg',
        'js' => 'application/javascript; charset=UTF-8',
        'json' => 'application/json; charset=UTF-8',
        'png' => 'image/png',
        'svg' => 'image/svg+xml',
        'txt' => 'text/plain; charset=UTF-8',
        'webp' => 'image/webp',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
    ];

    header('Content-Type: '.($mimeTypes[$extension] ?? 'application/octet-stream'));
    header('Cache-Control: public, max-age=31536000, immutable');

    readfile($requestedFile);

    exit;
}

$_SERVER['DOCUMENT_ROOT'] = $publicPath;
$_SERVER['SCRIPT_FILENAME'] = $publicPath.'/index.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';

require $publicPath.'/index.php';
