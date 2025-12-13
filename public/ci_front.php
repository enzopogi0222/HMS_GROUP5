<?php

declare(strict_types=1);

define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);

$pathsPath = realpath(FCPATH . '../app/Config/Paths.php');

if ($pathsPath === false) {
    header('HTTP/1.1 503 Service Unavailable.', true, 503);
    echo 'Your app/Config/Paths.php file does not appear to be correctly set.';
    exit(1);
}

require $pathsPath;

$paths = new Config\Paths();

$bootPath = rtrim($paths->systemDirectory, '\\/ ') . DIRECTORY_SEPARATOR . 'Boot.php';

if (! is_file($bootPath)) {
    header('HTTP/1.1 503 Service Unavailable.', true, 503);
    echo 'Your system/Boot.php file does not appear to be correctly set.';
    exit(1);
}

require $bootPath;

$exitCode = \CodeIgniter\Boot::bootWeb($paths);
exit($exitCode);
