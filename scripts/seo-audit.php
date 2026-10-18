<?php

declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}
require __DIR__ . '/../app/bootstrap.php';

$criticalOnly = in_array('--critical', $argv, true);
$report = ['started_at' => date(DATE_ATOM), 'environment' => config('app_env'), 'indexable' => null, 'mode' => $criticalOnly ? 'critical' : 'full', 'pages' => [], 'failures' => []];
$assert = static function (bool $condition, string $message) use (&$report): void {
    if (!$condition) {
        $report['failures'][] = $message;
    }
};
$fetch = static function (string $target): array {
    $headers = [];
    $curl = curl_init($target);
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 20, CURLOPT_USERAGENT => 'HM-Site-Audit/1.0', CURLOPT_HEADERFUNCTION => static function ($handle, string $line) use (&$headers): int {
        if (str_contains($line, ':')) {
            [$key, $value] = explode(':', $line, 2);
            $headers[strtolower(trim($key))] = trim($value);
        }
        return strlen($line);
    }]);
    $body = curl_exec($curl);
    if ($body === false) {
        $error = curl_error($curl);
        curl_close($curl);
        throw new RuntimeException($target . ': ' . $error);
    }
    $result = ['status' => curl_getinfo($curl, CURLINFO_RESPONSE_CODE), 'seconds' => curl_getinfo($curl, CURLINFO_TOTAL_TIME), 'headers' => $headers, 'body' => $body];
    curl_close($curl);
    return $result;
};
try {
    $report['indexable'] = site_is_indexable();
    $report['site_url'] = config('site_url');
    $sitemap = $fetch(url('sitemap.xml'));
    $assert($sitemap['status'] === 200, 'Sitemap HTTP 200 değil.');
    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($sitemap['body']);
    if (!$xml) {
        throw new RuntimeException('Sitemap geçerli XML değil.');
    }
    $allUrls = [];
    foreach ($xml->url as $entry) {
        $target = (string) $entry->loc;
        $assert(str_starts_with($target, url()), 'Sitemap farklı kök içeriyor: ' . $target);
        if (str_starts_with($target, url())) {
            $allUrls[] = $target;
        }
        if (isset($entry->lastmod)) {
            $lastmod = strtotime((string) $entry->lastmod);
            $assert($lastmod !== false && $lastmod <= time() + 60, 'Sitemap tarihi geçersiz veya gelecekte: ' . $target);
        }
    }
    $assert(count($allUrls) === count(array_unique($allUrls)), 'Sitemap yinelenen URL içeriyor.');
    $targets = $criticalOnly ? array_map('url', ['', 'istanbul-kaba-insaat', 'villa-kaba-insaat', 'iletisim', 'hesaplamalar/kaba-insaat-maliyeti']) : array_slice(array_unique($allUrls), 0, 500);
    $assert(count($allUrls) <= 500, 'Tarama sınırı aşıldı; 500 URL üzeri için kapasiteyi artırın.');
    $links = [];
    $titles = [];
    $descriptions = [];
    foreach ($targets as $target) {
        $response = $fetch($target);
        $assert($response['status'] === 200, 'Sayfa HTTP ' . $response['status'] . ': ' . $target);
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $response['body']);
        $xpath = new DOMXPath($dom);
        $value = static fn (string $query): string => trim((string) $xpath->evaluate('string(' . $query . ')'));
        $title = $value('//title');
        $description = $value('//meta[@name="description"]/@content');
        $canonical = $value('//link[@rel="canonical"]/@href');
        $robots = $value('//meta[@name="robots"]/@content');
        $assert($title !== '' && $description !== '', 'Başlık / açıklama eksik: ' . $target);
        $assert($xpath->query('//h1')->length === 1, 'Tek H1 bekleniyor: ' . $target);
        $assert($xpath->query('//link[@rel="canonical"]')->length === 1 && $canonical === $target, 'Canonical uyuşmuyor: ' . $target);
        $assert(str_contains($robots, 'noindex') !== site_is_indexable(), 'İndeksleme durumu ortamla uyuşmuyor: ' . $target);
        $assert(!preg_match('/(?:Fatal error|Warning|Notice):.*(?:on line|<b>)/s', $response['body']), 'PHP hata çıktısı: ' . $target);
        $assert(($response['headers']['x-content-type-options'] ?? '') === 'nosniff', 'İçerik türü güvenlik başlığı eksik: ' . $target);
        $nodes = $xpath->query('//script[@type="application/ld+json"]');
        $assert($nodes->length > 0, 'JSON-LD eksik: ' . $target);
        foreach ($nodes as $node) {
            try {
                $schema = json_decode($node->textContent, true, 512, JSON_THROW_ON_ERROR);
                $assert(isset($schema['@context'], $schema['@graph']), 'JSON-LD temel alanları eksik: ' . $target);
            } catch (JsonException) {
                $assert(false, 'JSON-LD bozuk: ' . $target);
            }
        }
        if ($target !== url('iletisim')) {
            $assert(!isset($response['headers']['set-cookie']), 'İçerik sayfası gereksiz çerez oluşturuyor: ' . $target);
            $assert(!str_contains($response['headers']['cache-control'] ?? '', 'no-store'), 'İçerik sayfasında gereksiz no-store: ' . $target);
        }
        $titles[$title][] = $target;
        $descriptions[$description][] = $target;
        foreach ($xpath->query('//a[@href]') as $anchor) {
            $link = preg_replace('/#.*$/', '', $anchor->getAttribute('href'));
            if (str_starts_with($link, url()) && !str_starts_with($link, url('admin')) && !str_contains($link, '?logout')) {
                $links[$link] = true;
            }
        }
        $report['pages'][] = ['url' => $target, 'status' => $response['status'], 'title' => $title, 'canonical' => $canonical, 'robots' => $robots, 'response_ms' => (int) round($response['seconds'] * 1000)];
    }
    foreach (['Başlık' => $titles, 'Açıklama' => $descriptions] as $label => $values) {
        foreach ($values as $paths) {
            $assert(count($paths) === 1, $label . ' tekrarı: ' . implode(', ', $paths));
        }
    }
    if (!$criticalOnly) {
        foreach (array_slice(array_keys($links), 0, 500) as $target) {
            if (in_array($target, $targets, true)) {
                continue;
            }
            $response = $fetch($target);
            $assert($response['status'] >= 200 && $response['status'] < 400, 'Bozuk iç bağlantı HTTP ' . $response['status'] . ': ' . $target);
        }
    }
    foreach (['istanbul-kaba-insaat/' => 'istanbul-kaba-insaat', 'index.php' => '', 'hesaplamalar' => 'hesaplamalar/kaba-insaat-maliyeti'] as $path => $destination) {
        $response = $fetch(url($path));
        $assert($response['status'] === 301 && ($response['headers']['location'] ?? '') === url($destination), '301 yönlendirme hatası: ' . $path);
    }
    foreach (['olmayan-seo-kontrol-sayfasi', 'blog/olmayan/alt-sayfa'] as $path) {
        $response = $fetch(url($path));
        $assert($response['status'] === 404 && str_contains($response['body'], 'noindex'), 'Gerçek 404 / noindex eksik: ' . $path);
    }
    foreach (['database.sql', 'project_memory.md', 'tests/seo.php', '.private/reports/seo-latest.json', 'partials/header.php', 'views/home.php', 'admin/login.php', 'admin/partials/settings.php'] as $path) {
        $assert(in_array($fetch(url($path))['status'], [403, 404], true), 'Özel dosya erişimi açık: ' . $path);
    }
    $invalidRoute = $fetch(url('index.php?route%5B%5D=x'));
    $assert($invalidRoute['status'] === 400 && !str_contains($invalidRoute['body'], 'Array to string conversion'), 'Geçersiz route girdisi güvenli biçimde reddedilmiyor.');
    $robots = $fetch(url('robots.txt'));
    $assert($robots['status'] === 200 && str_contains($robots['body'], 'Sitemap: ' . url('sitemap.xml')), 'Robots sitemap bağlantısı eksik.');
    $admin = $fetch(url('admin/'));
    $assert(str_contains($admin['headers']['x-robots-tag'] ?? '', 'noindex'), 'Panel noindex başlığı eksik.');
    $css = $fetch(asset_url('assets/css/seo.css'));
    $assert($css['status'] === 200 && str_contains($css['headers']['cache-control'] ?? '', 'max-age'), 'CSS önbellek başlığı eksik.');
    if (!$criticalOnly) {
        (static function (): void {
            require __DIR__ . '/weekly-report.php';
        })();
    }
} catch (Throwable $exception) {
    $report['failures'][] = $exception->getMessage();
}
$report['page_count'] = count($report['pages']);
$report['finished_at'] = date(DATE_ATOM);
$directory = __DIR__ . '/../.private/reports';
if (!is_dir($directory)) {
    mkdir($directory, 0755, true);
}
$json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
file_put_contents($directory . '/seo-latest.json', $json, LOCK_EX);
file_put_contents($directory . '/seo-' . date('Y-m-d-His') . '.json', $json, LOCK_EX);
echo $report['page_count'] . ' sayfa; ' . count($report['failures']) . " hata. Rapor: .private/reports/seo-latest.json\n";
foreach ($report['failures'] as $failure) {
    echo '- ' . $failure . "\n";
}
exit($report['failures'] ? 1 : 0);
