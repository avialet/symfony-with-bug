<?php

// BUG: Debug mode enabled in production
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// BUG: No HTTPS enforcement
// BUG: Missing security headers (CSP, X-Frame-Options, etc.)

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    // BUG: Leaking environment info
    if (isset($_GET['debug'])) {
        phpinfo();
        exit;
    }

    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
