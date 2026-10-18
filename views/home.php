<section class="hero">
    <div class="hero-image" aria-hidden="true">
        <?php if ($heroImage = absolute_asset(site_setting('hero_image'))): ?><img class="hero-photo" src="<?= e($heroImage) ?>" <?= image_dimensions(site_setting('hero_image')) ?> <?= responsive_image(site_setting('hero_image'), '100vw') ?> alt="" fetchpriority="high" decoding="async"><?php endif; ?>
    </div>
    <div class="container hero-content">
        <div class="eyebrow light">
            <span></span>
            Kalıp, demir ve beton uygulamaları
        </div>
        <h1>İstanbul’da<br><em>kaba inşaat.</em></h1>
        <p>Temelden katlara kadar kalıp, demir ve beton uygulamaları yapıyoruz. Projenize göre radye temel, subasman, perde ve istinat duvarlarını birlikte planlıyoruz. Tuğla duvar ve çatı taleplerinizi de değerlendiriyoruz.</p>
        <a class="button button-light" href="<?= e(url('iletisim?from=anasayfa')) ?>">Keşif ve teklif isteyin <span>↗</span></a>
        <a class="text-link hero-service-link" href="<?= e(url('istanbul-kaba-insaat')) ?>">Kaba inşaat uygulamalarımızı inceleyin ↗</a>
    </div>
    <div class="hero-note">
        01
        <span>—</span>
        04
        <br>
        <small>
            Yapının ilk
            <br>
            güvenli adımı
        </small>
    </div>
</section>
<section class="intro-band">
    <div class="container intro-grid">
        <div class="section-number">
            01
            <span>/</span>
            04
        </div>
        <div>
            <div class="eyebrow"><?= e(config('site_name')) ?> hakkında</div>
            <h2>İşi sadece yapmak değil,<br><em>hakkını vermek.</em></h2>
        </div>
        <div class="intro-copy">
            <p>Güncel projenizi ve saha koşullarını inceliyor; kalıp, demir ve beton işlerinin kapsamını, malzeme sorumluluklarını ve başlangıç tarihini birlikte belirliyoruz.</p>
        </div>
    </div>
</section>
<?php if ($featured): ?>
<section class="projects-section">
    <div class="container">
        <div class="section-head">
            <div>
                <div class="eyebrow">Seçilmiş işler</div>
                <h2>Son projeler</h2>
            </div>
            <a class="text-link" href="<?= e(url('projeler')) ?>">Tüm projeler <span>↗</span></a>
        </div>
        <div class="project-grid">
            <?php foreach ($featured as $project): ?>
                <a class="project-card" href="<?= e(url('proje/' . $project['slug'])) ?>">
                    <div class="project-image">
                        <img
                            loading="lazy"
                            decoding="async"
                            <?= image_dimensions($project['image']) ?> <?= responsive_image($project['image']) ?>
                            src="<?= e(project_image($project['image'])) ?>"
                            alt="<?= e($project['title']) ?>"
                        >
                        <span class="project-status <?= $project['category'] === 'ongoing' ? 'ongoing' : '' ?>"><?= $project['category'] === 'ongoing' ? 'Devam ediyor' : 'Tamamlandı' ?></span>
                    </div>
                    <div class="project-meta">
                        <div>
                            <h3><?= e($project['title']) ?></h3>
                            <?php if (!empty($project['is_demo'])): ?><small>Örnek proje; gerçek referans değildir.</small><?php endif; ?>
                            <p><?= e($project['location']) ?></p>
                        </div>
                        <span class="arrow">↗</span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
<section class="statement">
    <div class="container statement-inner">
        <span class="quote-mark">“</span>
        <h2>Kalıp, demir ve beton.<br><em>Projeden uygulamaya.</em></h2>
        <span class="statement-sign">— <?= e(config('site_name')) ?></span>
    </div>
</section>
<section class="blog-preview">
    <div class="container">
        <div class="section-head">
            <div>
                <div class="eyebrow">Sahadan notlar</div>
                <h2>Blogdan</h2>
            </div>
            <a class="text-link" href="<?= e(url('blog')) ?>">Tüm yazılar <span>↗</span></a>
        </div>
        <div class="post-grid">
            <?php foreach ($latestPosts as $post): ?>
                <a class="post-card" href="<?= e(url('blog/' . $post['slug'])) ?>">
                    <img
                        loading="lazy"
                        decoding="async"
                        <?= image_dimensions($post['image']) ?> <?= responsive_image($post['image']) ?>
                        src="<?= e(blog_image($post['image'])) ?>"
                        alt="<?= e($post['title']) ?>"
                    >
                    <div class="post-body">
                        <small><?= display_date($post['created_at'], 'd.m.Y') ?></small>
                        <h3><?= e($post['title']) ?></h3>
                        <p><?= e($post['excerpt']) ?></p>
                        <span class="text-link">
                            Yazıyı oku
                            <span>↗</span>
                        </span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php require __DIR__ . '/../partials/service-areas.php'; ?>
<section class="contact-strip">
    <div class="container contact-strip-inner">
        <div>
            <div class="eyebrow light">Birlikte başlayalım</div>
            <h2>Projenizi konuşalım.</h2>
        </div>
        <a class="button button-outline" href="<?= e(url('iletisim')) ?>">İletişime geç <span>↗</span></a>
    </div>
</section>
<?php require __DIR__ . '/../partials/related-links.php'; ?>
