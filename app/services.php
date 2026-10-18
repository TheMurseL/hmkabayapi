<?php

declare(strict_types=1);

function service_catalog(): array
{
    return array_column((new ServiceRepository())->all(), null, 'slug');
}

function service_defaults(): array
{
    static $defaults;
    if ($defaults !== null) {
        return $defaults;
    }
    $core = require __DIR__ . '/content/core-services.php';
    $specialist = require __DIR__ . '/content/specialist-services.php';

    return $defaults = array_merge(
        array_slice($core, 0, 1, true),
        $specialist,
        array_slice($core, 1, null, true)
    );
}
