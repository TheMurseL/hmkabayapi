<?php
$introParts = preg_split('/(?=^##\s)/mu', $service['intro'], 2);
$serviceOverview = trim($introParts[0] ?? '');
$serviceGuide = trim($introParts[1] ?? '');
?>
<section class="page-hero service-hero">
    <div class="container">
        <div class="eyebrow light"><?= $service['slug'] === 'duvar-ve-cati-uygulamalari' ? 'İstanbul’da duvar ve çatı uygulamaları' : 'İstanbul’da kalıp, demir ve beton' ?></div>
        <h1><?= e($service['title']) ?></h1>
        <p><?= e($service['description']) ?></p>
        <a class="button button-light" href="<?= e(url('iletisim?from=' . $service['slug'])) ?>">Projem için teklif iste <span>↗</span></a>
    </div>
</section>
<nav class="service-page-nav" aria-label="Hizmet sayfasındaki bölümler">
    <div class="container">
        <a href="#service-scope">İşin kapsamı</a>
        <?php if ($serviceGuide !== ''): ?><a href="#service-guide">Metraj ve teklif bilgileri</a><?php endif; ?>
        <?php if ($service['process']): ?><a href="#service-process">Çalışma adımları</a><?php endif; ?>
        <?php if ($service['questions']): ?><a href="#service-faq">Sık sorulanlar</a><?php endif; ?>
    </div>
</nav>
<section class="service-content" id="service-scope">
    <div class="container">
        <div class="service-intro">
            <h2>Projenizde<br><em>üstlendiğimiz işler.</em></h2>
            <div class="article-content"><?= content_html($serviceOverview) ?></div>
        </div>
        <?php if ($service['sections']): ?>
            <div class="service-cards<?= count($service['sections']) === 4 ? ' service-cards-two' : '' ?>">
                <?php foreach ($service['sections'] as $index => [$heading, $text]): ?>
                    <article>
                        <span><?= sprintf('%02d', $index + 1) ?></span>
                        <h3><?= e($heading) ?></h3>
                        <p><?= e($text) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php if ($serviceGuide !== ''): ?>
    <section class="service-guide" id="service-guide">
        <div class="container service-guide-layout">
            <div class="service-guide-heading">
                <div class="eyebrow">İşe başlamadan önce</div>
                <h2>İşin kapsamını ve<br>teklifi <em>netleştirelim.</em></h2>
                <p>Proje, metraj ve saha bilgileri aynı iş için karşılaştırılabilir teklif hazırlamamızı sağlar.</p>
                <a class="text-link" href="<?= e(url('hesaplamalar/kaba-insaat-maliyeti')) ?>">Kalıp ve demir işçilik hesabı <span>↗</span></a>
                <?php if ($service['slug'] === 'duvar-ve-cati-uygulamalari'): ?>
                    <a class="text-link" href="<?= e(url('hesaplamalar/tugla-fiyat-hesaplama')) ?>">Tuğla adedi ve maliyet hesabı <span>↗</span></a>
                <?php endif; ?>
            </div>
            <div class="article-content service-guide-copy"><?= content_html($serviceGuide) ?></div>
        </div>
    </section>
<?php endif; ?>
<?php if ($service['process']): ?>
    <section class="service-content service-process" id="service-process">
        <div class="container">
            <h2>Çalışma adımları</h2>
            <div class="process-grid">
                <?php foreach ($service['process'] as $index => [$heading, $text]): ?>
                    <article>
                        <strong><?= sprintf('%02d', $index + 1) ?></strong>
                        <h3><?= e($heading) ?></h3>
                        <div class="article-content"><?= content_html($text) ?></div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>
<?php if ($service['questions']): ?>
    <section class="service-faq" id="service-faq">
        <div class="container">
            <h2>Sık sorulan sorular</h2>
            <?php foreach ($service['questions'] as [$question, $answer]): ?>
                <details>
                    <summary><?= e($question) ?></summary>
                    <p><?= e($answer) ?></p>
                </details>
            <?php endforeach; ?>
            <a class="button button-dark" href="<?= e(url('iletisim?from=' . $service['slug'])) ?>">Projenizi anlatın <span>↗</span></a>
        </div>
    </section>
<?php endif; ?>
<?php if (!empty($relatedProjects)): ?>
    <section class="listing service-projects">
        <div class="container">
            <div class="section-heading">
                <h2>Bu hizmetle ilgili projeler</h2>
            </div>
            <div class="project-list">
                <?php foreach ($relatedProjects as $relatedProject): ?>
                    <a class="project-card" href="<?= e(url('proje/' . $relatedProject['slug'])) ?>">
                        <div class="project-image">
                            <img
                                loading="lazy"
                                decoding="async"
                                <?= image_dimensions($relatedProject['image']) ?>
                                src="<?= e(project_image($relatedProject['image'])) ?>"
                                alt="<?= e($relatedProject['title']) ?>"
                            >
                        </div>
                        <div class="project-meta">
                            <div>
                                <h3><?= e($relatedProject['title']) ?></h3>
                                <p><?= e($relatedProject['location']) ?></p>
                            </div>
                            <span class="arrow">↗</span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>
<?php require __DIR__ . '/../partials/related-posts.php'; ?>
<?php require __DIR__ . '/../partials/related-links.php'; ?>
