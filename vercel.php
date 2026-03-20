<?php

declare(strict_types=1);

$publicPath = __DIR__.'/public';

$_SERVER['DOCUMENT_ROOT'] = $publicPath;
$_SERVER['SCRIPT_FILENAME'] = $publicPath.'/index.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';

require $publicPath.'/index.php';
