<?php

$metaTitle = $metaTitle ?? site_setting('site_title', config('site_name'));
$metaDescription = $metaDescription ?? site_setting('site_description', 'İstanbul’da kalıp, demir ve beton uygulamaları. Radye temel, subasman, perde ve istinat duvarları ile talebe göre tuğla duvar ve çatı işleri.');
$currentRoute = trim((string) ($_GET['route'] ?? ''), '/');
$schemaType = $schemaType ?? 'website';
$siteUrl = config('site_url');
$siteName = site_setting('business_name') ?: config('site_name');
$sitePhone = site_setting('contact_phone');
$siteEmail = site_setting('contact_email');
$isArticle = isset($post) && isset($post['slug']);
$hasGallery = isset($project) && isset($project['slug']);
$canonicalUrl = url($canonicalRoute ?? $currentRoute);
$shareImage = absolute_asset(site_setting('share_image'));
if ($isArticle) {
    $articleImage = absolute_asset($post['image']);
    $shareImage = $articleImage ?: $shareImage;
} elseif ($hasGallery) {
    $shareImage = project_image($project['image']);
}
$labels = [
    'hakkimizda' => 'Hakkımızda',
    'projeler' => 'Projeler',
    'blog' => 'Kaba inşaat rehberi',
    'iletisim' => 'İletişim',
    'istanbul-kaba-insaat' => 'İstanbul kaba inşaat',
    'villa-kaba-insaat' => 'Villa kaba inşaat',
    'duvar-ve-cati-uygulamalari' => 'Tuğla ve çatı işleri',
    'hesaplamalar/kaba-insaat-maliyeti' => 'Kalıp ve demir işçiliği maliyet hesabı',
    'hesaplamalar/tugla-fiyat-hesaplama' => 'Tuğla fiyatı hesaplama',
];
$pageLabel = $service['title'] ?? ($labels[$currentRoute] ?? $metaTitle);
$breadcrumbs = [];
if (http_response_code() !== 404 && $currentRoute !== '') {
    $breadcrumbs[] = [
        'name' => 'Anasayfa',
        'path' => '',
    ];
    if ($isArticle) {
        $breadcrumbs[] = [
            'name' => 'Blog',
            'path' => 'blog',
        ];
    } elseif ($hasGallery) {
        $breadcrumbs[] = [
            'name' => 'Projeler',
            'path' => 'projeler',
        ];
    }
    $breadcrumbs[] = [
        'name' => $isArticle ? $post['title'] : ($hasGallery ? $project['title'] : $pageLabel),
        'path' => $canonicalRoute ?? $currentRoute,
    ];
}
$organization = [
    '@type' => 'GeneralContractor',
    '@id' => $siteUrl . '#organization',
    'name' => $siteName,
    'url' => $siteUrl,
    'areaServed' => array_values(array_filter(array_map('trim', explode(',', site_setting('service_areas', 'İstanbul'))))),
];
if ($sitePhone !== '') {
    $organization['telephone'] = $sitePhone;
}
if ($siteEmail !== '') {
    $organization['email'] = $siteEmail;
}
if ($logo = absolute_asset(site_setting('business_logo'))) {
    $organization['logo'] = $logo;
}
if ($address = site_setting('business_address')) {
    $organization['address'] = [
        '@type' => 'PostalAddress',
        'streetAddress' => $address,
        'addressCountry' => 'TR',
    ];
}
if (isset($organization['address'])) {
    if ($city = site_setting('business_city')) {
        $organization['address']['addressLocality'] = $city;
    }
    if ($postcode = site_setting('business_postcode')) {
        $organization['address']['postalCode'] = $postcode;
    }
}
$latitude = site_setting('business_latitude');
$longitude = site_setting('business_longitude');
if (is_numeric($latitude) && is_numeric($longitude) && abs((float) $latitude) <= 90 && abs((float) $longitude) <= 180) {
    $organization['geo'] = [
        '@type' => 'GeoCoordinates',
        'latitude' => (float) $latitude,
        'longitude' => (float) $longitude,
    ];
}
$opens = site_setting('business_opens');
$closes = site_setting('business_closes');
if ($opens !== '' && $closes !== '') {
    $organization['openingHoursSpecification'] = [
        '@type' => 'OpeningHoursSpecification',
        'dayOfWeek' => [
            'Monday',
            'Tuesday',
            'Wednesday',
            'Thursday',
            'Friday',
        ],
        'opens' => $opens,
        'closes' => $closes,
    ];
}
$socialUrls = array_values(
    array_filter(
        preg_split('/\\R/u', site_setting('business_social_urls')) ?: [],
        static fn ($value) => filter_var(trim($value), FILTER_VALIDATE_URL)
            && in_array(parse_url(trim($value), PHP_URL_SCHEME), ['http', 'https'], true)
    )
);
if ($socialUrls) {
    $organization['sameAs'] = array_map('trim', $socialUrls);
}
if ($profile = site_setting('business_profile_url')) {
    $organization['sameAs'] = array_values(array_unique(array_merge($organization['sameAs'] ?? [], [$profile])));
}
$graph = [
    $organization,
    [
        '@type' => 'WebSite',
        '@id' => $siteUrl . '#website',
        'url' => $siteUrl,
        'name' => $siteName,
        'inLanguage' => 'tr-TR',
    ],
];
if ($breadcrumbs) {
    $graph[] = [
        '@type' => 'BreadcrumbList',
        'itemListElement' => array_map(static fn ($crumb, $index) => [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'name' => $crumb['name'],
            'item' => url($crumb['path']),
        ], $breadcrumbs, array_keys($breadcrumbs)),
    ];
}
if ($schemaType === 'service') {
    $graph[] = [
        '@type' => 'Service',
        'name' => $pageLabel,
        'description' => $metaDescription,
        'provider' => [
            '@id' => $siteUrl . '#organization',
        ],
        'areaServed' => $organization['areaServed'],
        'url' => $canonicalUrl,
    ];
}
if ($schemaType === 'about') {
    $graph[] = [
        '@type' => 'AboutPage',
        '@id' => $canonicalUrl . '#webpage',
        'url' => $canonicalUrl,
        'name' => $metaTitle,
        'description' => $metaDescription,
        'inLanguage' => 'tr-TR',
        'isPartOf' => ['@id' => $siteUrl . '#website'],
        'about' => ['@id' => $siteUrl . '#organization'],
    ];
}
if ($isArticle) {
    $graph[] = [
        '@type' => 'BlogPosting',
        'headline' => $post['title'],
        'description' => $metaDescription,
        'datePublished' => date(DATE_ATOM, strtotime($post['created_at'])),
        'author' => [
            '@type' => 'Organization',
            'name' => $siteName,
            'url' => $siteUrl,
        ],
        'publisher' => [
            '@id' => $siteUrl . '#organization',
        ],
        'mainEntityOfPage' => $canonicalUrl,
        'inLanguage' => 'tr-TR',
    ];
    if ($articleImage) {
        $graph[array_key_last($graph)]['image'] = $articleImage;
    }
}
if ($isArticle && !empty($post['updated_at'])) {
    $graph[array_key_last($graph)]['dateModified'] = date(DATE_ATOM, strtotime($post['updated_at']));
}
if ($isArticle && !empty($post['author_name'])) {
    $graph[array_key_last($graph)]['author'] = ['@type' => 'Person', 'name' => $post['author_name']];
}
$siteHost = parse_url($siteUrl, PHP_URL_HOST);
$isLocalSite = $siteHost === 'localhost' || filter_var($siteHost, FILTER_VALIDATE_IP);
$isErrorPage = http_response_code() === 404;

$robots = $isErrorPage || !site_is_indexable() || !empty($project['is_demo'])
    ? 'noindex, follow'
    : 'index, follow, max-image-preview:large';

$schemaJson = seo_json([
    '@context' => 'https://schema.org',
    '@graph' => $graph,
]);
