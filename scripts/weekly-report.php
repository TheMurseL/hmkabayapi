<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}
require_once __DIR__ . '/../app/bootstrap.php';
$directory = __DIR__ . '/../.private/reports';
if (!is_dir($directory)) {
    mkdir($directory, 0755, true);
}
$start = (new DateTimeImmutable('-7 days', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
$statement = db()->prepare('SELECT COALESCE(NULLIF(source, \'\'), \'Doğrudan / bilinmiyor\') AS source_page, lead_status, COUNT(*) AS lead_count FROM messages WHERE created_at >= ? GROUP BY source_page, lead_status ORDER BY lead_count DESC');
$statement->execute([$start]);
$report = ['generated_at' => date(DATE_ATOM), 'from_utc' => $start, 'scope' => 'Site form records only; source_page is the preceding page, not verified organic attribution; no live Google data connected.', 'leads' => $statement->fetchAll()];
$path = $directory . '/weekly-' . date('Y-m-d') . '.json';
file_put_contents($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), LOCK_EX);
echo "Haftalık talep raporu kaydedildi. Kişisel iletişim alanları dışa aktarılmadı.\n";
