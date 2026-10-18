<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

if (isset($_GET['route']) && !is_string($_GET['route'])) {
    http_response_code(400);
    header('X-Robots-Tag: noindex, nofollow');
    exit('Geçersiz sayfa adresi.');
}

$route = trim((string) ($_GET['route'] ?? ''), '/');
$parts = $route === '' ? [] : explode('/', $route);
if (!site_is_indexable()) {
    header('X-Robots-Tag: noindex, follow');
}
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $canonicalPath = parse_url(url($route), PHP_URL_PATH);
    $host = parse_url(config('site_url'), PHP_URL_HOST);
    $port = parse_url(config('site_url'), PHP_URL_PORT);
    $expectedHost = $host . ($port ? ':' . $port : '');
    $isLive = config('app_env') === 'production' && $host !== 'localhost' && !filter_var($host, FILTER_VALIDATE_IP);
    $isHttps = request_is_https();
    $wrongOrigin = $isLive && (strcasecmp($_SERVER['HTTP_HOST'] ?? '', $expectedHost) !== 0 || (parse_url(config('site_url'), PHP_URL_SCHEME) === 'https' && !$isHttps));
    if ($requestPath !== $canonicalPath || $wrongOrigin) {
        $query = $_GET;
        unset($query['route']);
        header('Location: ' . url($route) . ($query ? '?' . http_build_query($query) : ''), true, 301);
        exit;
    }
}
if ($route === 'index.php' || $route === 'hesaplamalar') {
    header('Location: ' . url($route === 'hesaplamalar' ? 'hesaplamalar/kaba-insaat-maliyeti' : ''), true, 301);
    exit;
}
$projects = new ProjectRepository();
$posts = new PostRepository();

$services = service_catalog();

if ($route === 'sitemap.xml') {
    header('Content-Type: application/xml; charset=UTF-8');
    $urls = array_merge([
        '',
        'hakkimizda',
        'projeler',
        'blog',
        'iletisim',
        'hesaplamalar/kaba-insaat-maliyeti',
        'hesaplamalar/tugla-fiyat-hesaplama',
    ], array_keys($services));
    $lastModified = [];
    foreach ($services as $slug => $service) {
        $lastModified[$slug] = $service['updated_at'] ?: $service['created_at'];
    }
    foreach ($projects->all() as $project) {
        if (!empty($project['is_demo'])) {
            continue;
        }
        $path = 'proje/' . $project['slug'];
        $urls[] = $path;
        $lastModified[$path] = $project['updated_at'] ?: $project['created_at'];
    }
    foreach ($posts->all() as $post) {
        $path = 'blog/' . $post['slug'];
        $urls[] = $path;
        $lastModified[$path] = $post['updated_at'] ?: $post['created_at'];
    }
    echo '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    foreach (array_unique($urls) as $path) {
        echo '<url><loc>' . e(url($path)) . '</loc>' . (isset($lastModified[$path]) ? '<lastmod>' . date(DATE_ATOM, strtotime($lastModified[$path])) . '</lastmod>' : '') . '</url>';
    }
    echo '</urlset>';
    exit;
}

if ($route === 'robots.txt') {
    header('Content-Type: text/plain; charset=UTF-8');
    $basePath = rtrim((string) parse_url(config('site_url'), PHP_URL_PATH), '/');
    // Allow crawling so Google can read noindex on accidentally exposed previews.
    echo "User-agent: *\nAllow: /\nDisallow: " . $basePath . "/admin/\nDisallow: " . $basePath . "/.private/\nSitemap: " . url('sitemap.xml') . "\n";
    exit;
}

$notFound = static function (): never {
    http_response_code(404);
    render('404', [
        'metaTitle' => 'Sayfa bulunamadı | ' . config('site_name'),
    ]);
    exit;
};

$expectedSegments = in_array($parts[0] ?? '', ['proje', 'blog', 'hesaplamalar'], true) && isset($parts[1]) ? 2 : 1;
if (count($parts) > $expectedSegments || (($parts[0] ?? '') === 'proje' && empty($parts[1]))) {
    $notFound();
}

if (isset($services[$route])) {
    $service = $services[$route];
    render('service', [
        'service' => $service,
        'metaTitle' => ($service['seo_title'] ?: $service['title']) . ' | ' . config('site_name'),
        'metaDescription' => $service['seo_description'] ?: $service['description'],
        'relatedProjects' => $projects->forService((int) $service['id']),
        'schemaType' => 'service',
    ]);
    exit;
}

if (($parts[0] ?? '') === 'proje' && !empty($parts[1])) {
    $project = $projects->findBySlug($parts[1]);
    if ($project) {
        $project['gallery'] = $projects->gallery((int) $project['id']);
    }
    if (!$project || (!empty($project['is_demo']) && config('app_env') === 'production')) {
        $notFound();
    }
    if (!empty($project['is_demo'])) {
        header('X-Robots-Tag: noindex, follow');
    }

    render('project', [
        'project' => $project,
        'relatedServices' => $projects->services((int) $project['id']),
        'metaTitle' => ($project['seo_title'] ?: $project['title']) . ' | ' . config('site_name'),
        'metaDescription' => $project['seo_description'] ?: $project['summary'],
    ]);
    exit;
}

if (($parts[0] ?? '') === 'blog' && !empty($parts[1])) {
    $post = $posts->findBySlug($parts[1]);
    if (!$post) {
        $notFound();
    }

    render('post', [
        'post' => $post,
        'metaTitle' => ($post['seo_title'] ?: $post['title']) . ' | ' . config('site_name'),
        'metaDescription' => $post['seo_description'] ?: $post['excerpt'],
    ]);
    exit;
}

if (($parts[0] ?? '') === 'hesaplamalar') {
    $calculator = $parts[1] ?? 'kaba-insaat-maliyeti';
    if (!in_array($calculator, ['kaba-insaat-maliyeti', 'tugla-fiyat-hesaplama'], true)) {
        $notFound();
    }
    $isBrick = $calculator === 'tugla-fiyat-hesaplama';
    render('calculator', [
        'calculator' => $calculator,
        'metaTitle' => ($isBrick ? 'Tuğla Fiyatı Hesaplama' : 'Kalıp ve Demir İşçiliği Maliyet Hesabı') . ' | ' . config('site_name'),
        'metaDescription' => $isBrick ? 'Duvar ölçüsüne göre yaklaşık tuğla adedi, malzeme ve işçilik maliyetini hesaplayın.' : 'Daire bilgileri veya hazır metraj ile kalıp ve demir işçilik bedelini hesaplayın. Kendi m² ve ton birim fiyatlarınızla ayrı maliyet dökümünü görün.',
    ]);
    exit;
}

switch ($parts[0] ?? '') {
    case '':
        render('home', [
            'featured' => $projects->featured(),
            'latestPosts' => $posts->latest(),
            'metaTitle' => 'İstanbul Kaba İnşaat · Kalıp, Demir ve Beton | ' . config('site_name'),
            'metaDescription' => 'İstanbul’da kalıp, demir ve beton uygulamaları. Radye temel, subasman, perde ve istinat duvarları için proje ve saha bilgilerinizi paylaşın, teklif alın.',
        ]);
        break;
    case 'hakkimizda':
        render('about', [
            'metaTitle' => 'Hakkımızda | ' . config('site_name'),
            'metaDescription' => 'H&M Kaba Yapı olarak İstanbul’da kalıp, demir ve beton uygulamaları yapıyoruz. Çalışma alanlarımızı ve projeye göre teklif yaklaşımımızı tanıyın.',
            'schemaType' => 'about',
        ]);
        break;
    case 'projeler':
        $filter = in_array($_GET['durum'] ?? '', ['ongoing', 'completed'], true) ? $_GET['durum'] : 'all';
        render('projects', [
            'projects' => $projects->all($filter),
            'filter' => $filter,
            'metaTitle' => 'Kaba İnşaat Projeleri | ' . config('site_name'),
            'metaDescription' => 'Kaba inşaat proje sayfalarını, konum ve uygulama kapsamı bilgileriyle inceleyin.',
        ]);
        break;
    case 'blog':
        render('blog', [
            'posts' => $posts->all(),
            'metaTitle' => 'Kaba İnşaat Rehberi | ' . config('site_name'),
            'metaDescription' => 'Kalıp, demir ve beton uygulamalarında teklif, metraj ve şantiye hazırlığı hakkında notlar.',
        ]);
        break;
    case 'iletisim':
        require_once __DIR__ . '/app/contact.php';
        render('contact', contact_page() + [
            'metaTitle' => 'Kaba İnşaat İçin İletişim ve Teklif | ' . config('site_name'),
            'metaDescription' => 'Kalıp, demir ve beton uygulamaları için konum, proje ve metraj bilgilerinizi paylaşın. Tuğla duvar ve çatı taleplerinizi de belirtin.',
        ]);
        break;
    default:
        $notFound();
}
