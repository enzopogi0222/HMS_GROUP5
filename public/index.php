<?php

/**
 * This file is part of the CodeIgniter 4 framework.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

/*
 |--------------------------------------------------------------------------
 | CHECK PHP VERSION
 |--------------------------------------------------------------------------
 | CodeIgniter 4 requires PHP 7.4 or higher.
 */
if (version_compare(PHP_VERSION, '7.4', '<')) {
    $message = 'Your PHP version is ' . PHP_VERSION . '. CodeIgniter 4 requires PHP 7.4 or higher.';
    exit($message);
}

/*
 |--------------------------------------------------------------------------
 | DEFINE FCPATH
 |--------------------------------------------------------------------------
 | Define the path to the front controller (this file).
 */
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);

/*
 |--------------------------------------------------------------------------
 | LOAD PATHS CONFIG
 |--------------------------------------------------------------------------
 | This file sets up the framework paths.
 */
require dirname(FCPATH) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Paths.php';

$paths = new Config\Paths();

/*
 |--------------------------------------------------------------------------
 | BOOTSTRAP THE APPLICATION
 |--------------------------------------------------------------------------
 | Load the Boot class and run the application.
 */
require rtrim($paths->systemDirectory, '\\/ ') . DIRECTORY_SEPARATOR . 'Boot.php';

// Run the application
exit(CodeIgniter\Boot::bootWeb($paths));
