<section class="detail-hero">
    <div class="container">
        <a class="back-link" href="<?= e(url('projeler')) ?>">← Tüm projeler</a>
        <div class="eyebrow light">
            <span></span>
            <?= $project['category'] === 'ongoing' ? 'Devam eden proje' : 'Tamamlanan proje' ?>
        </div>
        <h1><?= e($project['title']) ?></h1>
        <p><?= e($project['summary']) ?></p>
    </div>
</section>
<section class="project-detail">
    <div class="container">
        <?php
        $coverUrl = project_image($project['image']);
        $galleryImages = [];
        $seenImages = [$coverUrl => true];
        foreach ($project['gallery'] ?? [] as $image) {
            if (empty($image['image'])) {
                continue;
            }
            $imageUrl = project_image($image['image']);
            if (!isset($seenImages[$imageUrl])) {
                $galleryImages[] = $image;
                $seenImages[$imageUrl] = true;
            }
        }
        ?>
        <div class="project-media">
            <a class="project-cover" href="<?= e($coverUrl) ?>" data-fancybox="project-gallery" data-caption="<?= e($project['title']) ?>" aria-label="<?= e($project['title']) ?> — vitrin görselini büyüt">
                <img decoding="async" class="detail-image" <?= image_dimensions($project['image']) ?> <?= responsive_image($project['image'], '100vw') ?> src="<?= e($coverUrl) ?>" alt="<?= e($project['title']) ?>">
            </a>
        <?php if ($galleryImages): ?>
            <section class="project-gallery" aria-label="Diğer proje görselleri">
                <div class="section-heading">
                    <h2>Proje galerisi</h2>
                    <span><?= count($galleryImages) ?> görsel · Büyütmek için tıklayın</span>
                </div>
                <div class="gallery-grid">
                    <?php foreach ($galleryImages as $image): ?>
                        <a
                            href="<?= e(project_image($image['image'])) ?>"
                            data-fancybox="project-gallery"
                            data-caption="<?= e($image['caption'] ?: $project['title']) ?>"
                            aria-label="<?= e($image['caption'] ?: $project['title']) ?> — galeri görselini büyüt"
                        >
                            <img loading="lazy" decoding="async" <?= image_dimensions($image['image']) ?> <?= responsive_image($image['image'], '150px') ?> src="<?= e(project_image($image['image'])) ?>" alt="<?= e($image['caption'] ?: $project['title']) ?>">
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
        </div>
        <div class="detail-grid">
            <div>
                <div class="eyebrow">Proje özeti</div>
                <h2>İşin her adımında<br><em>aynı özen.</em></h2>
            </div>
            <div>
                <div class="article-content"><?= content_html($project['description']) ?></div>
            </div>
        </div>
        <div class="detail-facts">
            <div>
                <small>Lokasyon</small>
                <strong><?= e($project['location']) ?></strong>
            </div>
            <div>
                <small>Proje yılı</small>
                <strong><?= e($project['year']) ?></strong>
            </div>
            <div>
                <small>İnşaat alanı</small>
                <strong><?= e($project['area'] ?: '—') ?></strong>
            </div>
            <div>
                <small>Durum</small>
                <strong><?= $project['category'] === 'ongoing' ? 'Devam ediyor' : 'Tamamlandı' ?></strong>
            </div>
        </div>
        <?php if (!empty($project['building_type'])): ?>
            <p class="project-building-type"><strong>Yapı türü:</strong> <?= e($project['building_type']) ?></p>
        <?php endif; ?>
        <?php foreach (ProjectRepository::CASE_FIELDS as $field => $label): ?>
            <?php if ($field !== 'building_type' && trim((string) ($project[$field] ?? '')) !== ''): ?>
                <section class="project-case-section">
                    <h2><?= e($label) ?></h2>
                    <div class="article-content"><?= content_html($project[$field]) ?></div>
                </section>
            <?php endif; ?>
        <?php endforeach; ?>
        <?php if (!empty($relatedServices)): ?>
            <section class="project-case-section project-service-links">
                <h2>Projede uygulanan hizmetler</h2>
                <ul>
                    <?php foreach ($relatedServices as $relatedService): ?>
                        <li><a href="<?= e(url($relatedService['slug'])) ?>"><?= e($relatedService['title']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/../partials/related-posts.php'; ?>
<?php require __DIR__ . '/../partials/related-links.php'; ?>
