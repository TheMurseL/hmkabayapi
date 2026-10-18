<?php
$aboutDefaults = about_defaults();
$aboutCopy = site_setting('business_about', $aboutDefaults['business_about']);
$aboutImagePath = site_setting('about_image', $aboutDefaults['about_image']);
$aboutImage = absolute_asset($aboutImagePath);
?>
<section class="page-hero about-hero">
    <div class="container">
        <h1>Hakkımızda</h1>
    </div>
</section>
<section class="about-page">
    <div class="container about-page-layout<?= $aboutImage ? '' : ' about-page-text-only' ?>">
        <div class="about-page-copy">
            <h2><?= e(config('site_name')) ?></h2>
            <div class="article-content"><?= content_html($aboutCopy) ?></div>
            <a class="button button-dark" href="<?= e(url('iletisim?from=hakkimizda')) ?>">İletişime geçin <span>↗</span></a>
        </div>
        <?php if ($aboutImage): ?>
            <figure class="about-page-image">
                <img
                    src="<?= e($aboutImage) ?>"
                    <?= image_dimensions($aboutImagePath) ?>
                    <?= responsive_image($aboutImagePath, '(max-width: 900px) calc(100vw - 36px), 480px') ?>
                    alt="Kaba inşaat uygulama detayı"
                    loading="lazy"
                    decoding="async"
                >
            </figure>
        <?php endif; ?>
    </div>
</section>
