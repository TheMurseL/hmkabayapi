<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

// Legacy compatibility for existing admin integrations.
defined('SITE_URL') || define('SITE_URL', (string) config('site_url'));
defined('SITE_NAME') || define('SITE_NAME', (string) config('site_name'));
