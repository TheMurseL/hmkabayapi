<section class="page-hero">
    <div class="container">
        <div class="eyebrow light">
            <span></span>
            Projeler
        </div>
        <h1>Kaba inşaat<br><em>projeleri.</em></h1>
    </div>
</section>
<section class="listing">
    <div class="container">
        <div class="filter-row">
            <p><?= count($projects) ?> proje listeleniyor</p>
            <div class="filters">
                <a class="<?= $filter === 'all' ? 'selected' : '' ?>" href="<?= e(url('projeler')) ?>">Tümü</a>
                <a class="<?= $filter === 'ongoing' ? 'selected' : '' ?>" href="<?= e(url('projeler?durum=ongoing')) ?>">Devam edenler</a>
                <a class="<?= $filter === 'completed' ? 'selected' : '' ?>" href="<?= e(url('projeler?durum=completed')) ?>">Bitenler</a>
            </div>
        </div>
        <div class="project-list">
            <?php if (!$projects): ?>
                <div class="project-empty">
                    <h2>Projenizi birlikte planlayalım.</h2>
                    <p>Bu kategoride henüz yayınlanmış proje bulunmuyor. Kalıp, demir ve beton uygulaması için proje ve konum bilgilerinizi paylaşabilirsiniz.</p>
                    <a class="button button-dark" href="<?= e(url('iletisim?from=projeler')) ?>">Projenizi anlatın <span>↗</span></a>
                </div>
            <?php endif; ?>
            <?php foreach ($projects as $project): ?>
                <a class="project-card project-card-large" href="<?= e(url('proje/' . $project['slug'])) ?>">
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
                            <p><?= e($project['location']) ?> · <?= e($project['year']) ?></p>
                        </div>
                        <span class="arrow">↗</span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
