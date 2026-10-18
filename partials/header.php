<?php

require __DIR__ . '/seo-data.php';
$navigationServices = service_catalog();
unset($navigationServices['istanbul-kaba-insaat']);

?>
<!doctype html>
<html lang="tr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= e($metaTitle) ?></title>
        <meta name="description" content="<?= e($metaDescription) ?>">
        <meta name="robots" content="<?= e($robots) ?>">
        <?php if ($verification = site_setting('search_console_verification')): ?><meta name="google-site-verification" content="<?= e($verification) ?>"><?php endif; ?>
        <meta property="og:title" content="<?= e($metaTitle) ?>">
        <meta property="og:description" content="<?= e($metaDescription) ?>">
        <meta property="og:type" content="<?= $isArticle ? 'article' : 'website' ?>">
        <meta property="og:url" content="<?= e($canonicalUrl) ?>">
        <meta property="og:site_name" content="<?= e($siteName) ?>">
        <meta property="og:locale" content="tr_TR">
        <?php if ($shareImage): ?>
            <meta property="og:image" content="<?= e($shareImage) ?>">
            <meta name="twitter:image" content="<?= e($shareImage) ?>">
        <?php endif; ?>
        <meta name="twitter:card" content="<?= $shareImage ? 'summary_large_image' : 'summary' ?>">
        <meta name="twitter:title" content="<?= e($metaTitle) ?>">
        <meta name="twitter:description" content="<?= e($metaDescription) ?>">
        <script type="application/ld+json"><?= $schemaJson ?></script>
        <?= site_setting('custom_head') ?>
        <meta name="theme-color" content="#17211d">
        <link rel="canonical" href="<?= e($canonicalUrl) ?>">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link
            href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@500;700&display=swap"
            rel="stylesheet"
        >
        <link rel="stylesheet" href="<?= e(asset_url('assets/css/style.css')) ?>">
        <link rel="stylesheet" href="<?= e(asset_url('assets/css/seo.css')) ?>">
        <link rel="stylesheet" href="<?= e(asset_url('assets/css/contact.css')) ?>">
        <?php if (($calculator ?? '') === 'kaba-insaat-maliyeti'): ?>
            <link rel="stylesheet" href="<?= e(asset_url('assets/css/labor-calculator.css')) ?>">
        <?php endif; ?>
        <?php if ($currentRoute === '' && ($heroImage = absolute_asset(site_setting('hero_image')))): ?>
            <link
                rel="preload"
                as="image"
                fetchpriority="high"
                href="<?= e($heroImage) ?>"
                <?= str_replace(['srcset=', 'sizes='], ['imagesrcset=', 'imagesizes='], responsive_image(site_setting('hero_image'), '100vw')) ?>
            >
        <?php endif; ?>
        <?php if ($hasGallery): ?>
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0.36/dist/fancybox/fancybox.css">
            <link rel="stylesheet" href="<?= e(asset_url('assets/css/gallery.css')) ?>">
        <?php endif; ?>
    </head>
    <body<?= whatsapp_url() !== '' ? ' class="has-whatsapp"' : '' ?>>
        <div class="topbar">
            <div class="container topbar-inner">
                <span><?= e(site_setting('service_areas', 'İstanbul')) ?></span>
                <?php if ($sitePhone !== ''): ?><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $sitePhone)) ?>"><?= e($sitePhone) ?></a><?php endif; ?>
            </div>
        </div>
        <header class="site-header">
            <div class="container header-inner">
                <a class="brand" href="<?= e(url()) ?>">
                    <?php if ($logo): ?>
                        <img class="brand-logo" src="<?= e($logo) ?>" alt="<?= e($siteName) ?> logosu" width="38" height="38">
                    <?php endif; ?>
                    <span>
                        <strong><?= e($siteName) ?></strong>
                        <small>Kalıp · Demir · Beton</small>
                    </span>
                </a>
                <button class="menu-toggle" type="button" data-menu aria-label="Menüyü aç" aria-controls="site-navigation" aria-expanded="false">Menü <span>☰</span></button>
                <nav id="site-navigation" class="site-nav" data-nav aria-label="Ana menü">
                    <a class="<?= $currentRoute === '' ? 'active' : '' ?>" <?= $currentRoute === '' ? 'aria-current="page"' : '' ?> href="<?= e(url()) ?>">Anasayfa</a>
                    <a class="<?= $currentRoute === 'istanbul-kaba-insaat' ? 'active' : '' ?>" <?= $currentRoute === 'istanbul-kaba-insaat' ? 'aria-current="page"' : '' ?> href="<?= e(url('istanbul-kaba-insaat')) ?>">Kaba İnşaat</a>
                    <a
                        class="<?= str_starts_with($currentRoute, 'projeler') || str_starts_with($currentRoute, 'proje/') ? 'active' : '' ?>"
                        <?= $currentRoute === 'projeler' ? 'aria-current="page"' : '' ?>
                        href="<?= e(url('projeler')) ?>"
                    >
                        Projeler
                    </a>
                    <details class="nav-dropdown" data-nav-dropdown>
                        <summary class="<?= isset($navigationServices[$currentRoute]) ? 'active' : '' ?>">Hizmetler</summary>
                        <div class="nav-submenu">
                            <?php foreach ($navigationServices as $path => $navigationService): ?>
                                <a class="<?= $currentRoute === $path ? 'active' : '' ?>" <?= $currentRoute === $path ? 'aria-current="page"' : '' ?> href="<?= e(url($path)) ?>"><?= e($navigationService['title']) ?></a>
                            <?php endforeach; ?>
                        </div>
                    </details>
                    <details class="nav-dropdown" data-nav-dropdown>
                        <summary class="<?= str_starts_with($currentRoute, 'hesaplamalar/') ? 'active' : '' ?>">Hesaplamalar</summary>
                        <div class="nav-submenu">
                            <?php foreach (['hesaplamalar/kaba-insaat-maliyeti' => 'Kalıp ve demir işçiliği', 'hesaplamalar/tugla-fiyat-hesaplama' => 'Tuğla adedi ve maliyeti'] as $path => $label): ?>
                                <a class="<?= $currentRoute === $path ? 'active' : '' ?>" <?= $currentRoute === $path ? 'aria-current="page"' : '' ?> href="<?= e(url($path)) ?>"><?= e($label) ?></a>
                            <?php endforeach; ?>
                        </div>
                    </details>
                    <a class="<?= $currentRoute === 'hakkimizda' ? 'active' : '' ?>" <?= $currentRoute === 'hakkimizda' ? 'aria-current="page"' : '' ?> href="<?= e(url('hakkimizda')) ?>">Hakkımızda</a>
                    <a class="<?= str_starts_with($currentRoute, 'blog') ? 'active' : '' ?>" <?= $currentRoute === 'blog' ? 'aria-current="page"' : '' ?> href="<?= e(url('blog')) ?>">Blog</a>
                    <a class="nav-cta" <?= $currentRoute === 'iletisim' ? 'aria-current="page"' : '' ?> href="<?= e(url('iletisim')) ?>">Projenizi konuşalım <span>↗</span></a>
                </nav>
            </div>
        </header>
        <main>
