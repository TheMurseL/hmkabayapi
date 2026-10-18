<?php

declare(strict_types=1);
date_default_timezone_set('UTC');

if (getenv('APP_ENV') === 'production') {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
}

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/services.php';
require_once __DIR__ . '/posts.php';
require_once __DIR__ . '/about.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Repositories/ProjectRepository.php';
require_once __DIR__ . '/Repositories/PostRepository.php';
require_once __DIR__ . '/Repositories/MessageRepository.php';
require_once __DIR__ . '/Repositories/SettingsRepository.php';
require_once __DIR__ . '/Repositories/ServiceRepository.php';

function config(string $key, mixed $default = null): mixed
{
    static $values;
    $values ??= [
        'site_url' => rtrim(getenv('SITE_URL') ?: 'http://localhost/insaat', '/'),
        'site_name' => 'H&M Kaba Yapı',
        'db_host' => getenv('DB_HOST') ?: '127.0.0.1',
        'db_name' => getenv('DB_NAME') ?: 'insaat_portfolio',
        'db_user' => getenv('DB_USER') ?: 'root',
        'db_pass' => getenv('DB_PASS') ?: '',
        'app_env' => getenv('APP_ENV') ?: 'local',
        'trusted_proxies' => getenv('TRUSTED_PROXIES') ?: '',
    ];
    if ($key === 'site_name') {
        return site_setting('business_name') ?: $values[$key];
    }
    if ($key === 'site_url') {
        $configured = getenv('SITE_URL') ?: site_setting('site_url');
        if ($configured && filter_var($configured, FILTER_VALIDATE_URL) && in_array(parse_url($configured, PHP_URL_SCHEME), ['http', 'https'], true)) {
            return rtrim($configured, '/');
        }
    }
    return $values[$key] ?? $default;
}

function view(string $name, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require __DIR__ . '/../views/' . $name . '.php';
}

function render(string $view, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require __DIR__ . '/../partials/header.php';
    require __DIR__ . '/../partials/breadcrumbs.php';
    view($view, $data);
    require __DIR__ . '/../partials/footer.php';
}
