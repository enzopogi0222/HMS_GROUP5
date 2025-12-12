<?php

/**
 * This file is part of CodeIgniter 4 framework.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

/**
 * This file is used by PHP's built-in development server to simulate
 * Apache's mod_rewrite functionality. This allows the development server
 * to properly route requests to the front controller.
 *
 * Usage:
 * php -S localhost:8080 -t public/ system/rewrite.php
 */

// If the request is for a file that exists, serve it directly
if (is_file($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . $_SERVER['SCRIPT_NAME'])) {
    return false;
}

// Set the environment if not already set
if (!isset($_SERVER['CI_ENVIRONMENT'])) {
    $_SERVER['CI_ENVIRONMENT'] = 'development';
}

// Route everything else to index.php
$_SERVER['SCRIPT_NAME'] = '/index.php';

require $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'index.php';
