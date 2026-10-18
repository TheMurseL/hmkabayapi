<?php
$sectionDescriptions = [
    'dashboard' => 'Hoş geldiniz. Web sitenizin güncel durumuna ve son içeriklerine göz atın.',
    'projects' => 'Projelerinizi, saha fotoğraflarını ve uygulama ayrıntılarını yönetin.',
    'services' => 'Hizmet sayfalarınızı, çalışma adımlarını ve SEO içeriklerini düzenleyin.',
    'blog' => 'Saha notlarınızı ve rehber içeriklerinizi yayınlayın.',
    'settings' => 'Firma bilgilerinizi, arama motoru ayarlarını ve hesaplama değerlerini düzenleyin.',
    'messages' => 'Web sitenizden gelen keşif ve teklif taleplerini takip edin.',
    'seo' => 'Yayın hazırlığını, içerik eksiklerini, teknik taramayı ve teklif sonuçlarını takip edin.',
];
?>
<!doctype html>
<html lang="tr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">
        <title><?= e($sectionTitles[$section]) ?> | <?= e(config('site_name')) ?></title>
        <link rel="stylesheet" href="<?= e(admin_asset('assets/css/admin.css')) ?>">
        <script src="<?= e(admin_asset('assets/js/admin.js')) ?>" defer></script>
    </head>
    <body class="admin-workspace">
        <a class="skip-link" href="#admin-content">İçeriğe geç</a>
        <button class="sidebar-backdrop" type="button" data-menu-close aria-label="Menüyü kapat" tabindex="-1"></button>
        <?php require __DIR__ . '/partials/sidebar.php'; ?>
        <div class="main-shell">
            <header class="topbar">
                <div class="topbar-start">
                    <button
                        class="icon-button mobile-menu"
                        type="button"
                        data-menu-toggle
                        aria-label="Menüyü aç"
                        aria-expanded="false"
                        aria-controls="admin-sidebar"
                    ><?= admin_icon('menu') ?></button>
                    <div class="breadcrumb-label">
                        Çalışma alanı <span>/</span> <strong><?= e($sectionTitles[$section]) ?></strong>
                    </div>
                </div>
                <div class="topbar-actions">
                    <span class="today"><?= display_date('now') ?></span>
                    <span class="role-badge">Admin</span>
                    <a class="icon-button" href="<?= e(url()) ?>" target="_blank" rel="noopener" aria-label="Web sitesini aç" title="Web sitesini aç"><?= admin_icon('external') ?></a>
                    <a class="icon-button" href="<?= e(url('admin?logout=1')) ?>" aria-label="Çıkış yap" title="Çıkış yap"><?= admin_icon('logout') ?></a>
                </div>
            </header>
            <main class="admin-content" id="admin-content">
                <div class="page-heading">
                    <div>
                        <div class="eyebrow">WEB SİTENİZİN KONTROL MERKEZİ</div>
                        <h1><?= e($sectionTitles[$section]) ?></h1>
                        <p><?= e($sectionDescriptions[$section]) ?></p>
                    </div>
                    <a class="button button-white" href="<?= e(url()) ?>" target="_blank" rel="noopener">
                        <?= admin_icon('external') ?> Siteyi görüntüle
                    </a>
                </div>
                <?php if ($flash): ?>
                    <div class="admin-message" role="status"><?= e($flash['message']) ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="admin-message error" role="alert"><?= e($error) ?></div>
                <?php endif; ?>
                <?php if ($section === 'dashboard'): ?>
                    <?php require __DIR__ . '/partials/overview.php'; ?>
                <?php elseif ($section === 'services'): ?>
                    <?php require __DIR__ . '/partials/services.php'; ?>
                <?php elseif ($section === 'projects'): ?>
                    <?php require __DIR__ . '/partials/projects.php'; ?>
                <?php elseif ($section === 'blog'): ?>
                    <?php require __DIR__ . '/partials/blog.php'; ?>
                <?php elseif ($section === 'settings'): ?>
                    <?php require __DIR__ . '/partials/settings.php'; ?>
                <?php elseif ($section === 'messages'): ?>
                    <?php require __DIR__ . '/partials/messages.php'; ?>
                <?php elseif ($section === 'seo'): ?>
                    <?php require __DIR__ . '/partials/seo.php'; ?>
                <?php endif; ?>
            </main>
            <footer class="page-footer">
                <span>© <?= date('Y') ?> <?= e(config('site_name')) ?></span>
                <span>Web sitesi ve içerik yönetimi</span>
            </footer>
        </div>
    </body>
</html>
