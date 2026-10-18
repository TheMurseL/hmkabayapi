<?php

declare(strict_types=1);

$options = getopt('', ['env:', 'enabled:', 'host:', 'scheme:']);
putenv('APP_ENV=' . ($options['env'] ?? 'local'));
putenv('SITE_URL=' . ($options['scheme'] ?? 'https') . '://' . ($options['host'] ?? 'seo-test.example'));
putenv('TRUSTED_PROXIES=10.0.0.10');
require __DIR__ . '/../app/bootstrap.php';
db()->beginTransaction();
try {
    (new SettingsRepository())->setMany(['indexing_enabled' => $options['enabled'] ?? '0']);
    $host = strtolower($options['host'] ?? 'seo-test.example');
    $expected = ($options['env'] ?? '') === 'production' && ($options['enabled'] ?? '') === '1'
        && ($options['scheme'] ?? 'https') === 'https' && $host !== 'localhost'
        && !str_ends_with($host, '.localhost') && !str_ends_with($host, '.test') && !filter_var($host, FILTER_VALIDATE_IP);
    if (site_is_indexable() !== $expected) {
        throw new RuntimeException('Environment/indexing switch failed.');
    }
    if (config('app_env') === 'production') {
        foreach ((new ProjectRepository())->all() as $project) {
            if (!empty($project['is_demo'])) {
                throw new RuntimeException('Example project exposed in production listing.');
            }
        }
    }
    $_SERVER['HTTPS'] = 'off';
    $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
    $_SERVER['REMOTE_ADDR'] = '198.51.100.9';
    if (request_is_https()) {
        throw new RuntimeException('Untrusted forwarded header accepted.');
    }
    $_SERVER['REMOTE_ADDR'] = '10.0.0.10';
    if (!request_is_https()) {
        throw new RuntimeException('Trusted proxy HTTPS not detected.');
    }
    $_SERVER['HTTPS'] = 'on';
    $_SERVER['REMOTE_ADDR'] = '198.51.100.9';
    if (!request_is_https()) {
        throw new RuntimeException('Direct HTTPS not detected.');
    }
    echo 'Environment ' . config('app_env') . ': indexing=' . (site_is_indexable() ? 'on' : 'off') . "; proxy checks passed.\n";
} finally {
    db()->rollBack();
}
