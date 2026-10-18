<?php

declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}
require __DIR__ . '/../app/bootstrap.php';
Database::migrate();
echo "Veritabanı şeması güncellendi. Mevcut içerikler korundu.\n";
