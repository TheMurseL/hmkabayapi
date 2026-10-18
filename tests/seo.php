<?php

declare(strict_types=1);

ini_set('session.save_path', sys_get_temp_dir());
require __DIR__ . '/../app/bootstrap.php';

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$pdo = db();
$pdo->beginTransaction();
try {
    (new SettingsRepository())->setMany([
        'site_url' => 'https://seo-test.example',
        'business_name' => 'SEO Test Firma',
        'business_address' => 'Test adresi',
        'business_city' => 'İstanbul',
        'business_logo' => 'uploads/logo.webp',
        'share_image' => 'uploads/share.webp',
        'contact_phone' => '+90 212 000 00 00',
        'contact_email' => 'test@example.com',
        'business_latitude' => '41',
        'business_longitude' => '29',
        'business_opens' => '08:30',
        'business_closes' => '18:00',
    ]);
    // An explicit environment URL continues to take precedence over panel settings.
    $expectedUrl = rtrim(getenv('SITE_URL') ?: 'https://seo-test.example', '/');
    check(config('site_url') === $expectedUrl, 'Panel domain / environment precedence failed.');
    check(project_image('uploads/photo.webp') === $expectedUrl . '/uploads/photo.webp', 'Uploaded project image URL failed.');
    check(blog_image('uploads/photo.webp') === $expectedUrl . '/uploads/photo.webp', 'Uploaded blog image URL failed.');
    check(str_contains(content_html("## Başlık\n\nMetin <script>"), '<h2>Başlık</h2>'), 'Article headings failed.');
    check(!str_contains(content_html('<script>'), '<script>'), 'Article escaping failed.');
    $_GET['route'] = 'blog/test-yazisi';
    $post = [
        'title' => 'Test </script><script>alert(1)</script>',
        'slug' => 'test-yazisi',
        'image' => 'uploads/photo.webp',
        'excerpt' => 'Test açıklaması',
        'content' => '## Test başlığı',
        'created_at' => '2026-01-01 12:00:00',
        'updated_at' => '2026-02-01 12:00:00',
    ];
    http_response_code(200);
    ob_start();
    render('post', [
        'post' => $post,
        'metaTitle' => $post['title'],
        'metaDescription' => $post['excerpt'],
    ]);
    $html = ob_get_clean();
    check((bool) preg_match('~<script type="application/ld\+json">(.*?)</script>~s', $html, $match), 'JSON-LD missing.');
    $schema = json_decode($match[1], true, 512, JSON_THROW_ON_ERROR);
    $types = array_column($schema['@graph'], '@type');
    check(in_array('BlogPosting', $types, true) && in_array('BreadcrumbList', $types, true), 'Article/breadcrumb schema missing.');
    $organization = $schema['@graph'][0];
    check($organization['address']['addressLocality'] === 'İstanbul', 'Panel address not in schema.');
    check($organization['logo'] === $expectedUrl . '/uploads/logo.webp', 'Panel logo not in schema.');
    check($organization['geo']['latitude'] === 41, 'Coordinates missing.');
    $article = $schema['@graph'][array_search('BlogPosting', $types, true)];
    check($article['author']['url'] === $expectedUrl, 'Organization author must link to the homepage.');
    check(str_contains($html, url('hakkimizda')), 'About page navigation link is missing.');
    check(!str_contains($html, '</script><script>alert(1)'), 'JSON-LD script escaping failed.');
    check(!str_contains($html, 'fancybox.umd.js'), 'Gallery JS loaded on a blog page.');
    check(substr_count($html, '<h1>') === 1, 'Expected one main heading.');
    echo "SEO checks passed; temporary settings rolled back.\n";
} finally {
    $pdo->rollBack();
}
