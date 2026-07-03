<?php
/**
 * R2 Uploader — Front Controller
 *
 * All HTTP requests are routed through this file.
 * Point your web server document root to this /public/ directory.
 */

require dirname(__DIR__) . '/vendor/autoload.php';

// Load environment variables
if (file_exists(dirname(__DIR__) . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
    $dotenv->safeLoad();
}

// Set Content-Security-Policy header
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src https://fonts.gstatic.com; img-src 'self' data:; connect-src 'self'");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("Referrer-Policy: strict-origin-when-cross-origin");

// Bootstrap and run
$app = new R2Uploader\App();
$app->run();
