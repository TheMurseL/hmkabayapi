<section class="article-hero">
    <div class="container">
        <a class="back-link" href="<?= e(url('blog')) ?>">← Bloga dön</a>
        <div class="eyebrow">Saha notu · <?= display_date($post['created_at'], 'd.m.Y') ?></div>
        <h1><?= e($post['title']) ?></h1>
        <p><?= e($post['excerpt']) ?></p>
    </div>
</section>
<article class="article">
    <img
        loading="lazy"
        decoding="async"
        class="article-image"
        <?= image_dimensions($post['image']) ?> <?= responsive_image($post['image']) ?>
        src="<?= e(blog_image($post['image'])) ?>"
        alt="<?= e($post['title']) ?>"
    >
    <p class="article-author">Yayınlayan: <a href="<?= e(url()) ?>"><?= e(site_setting('business_name') ?: config('site_name')) ?></a> · <time datetime="<?= e(date(DATE_ATOM, strtotime($post['created_at']))) ?>"><?= e(display_date($post['created_at'], 'd.m.Y')) ?></time></p>
    <div class="article-content"><?= content_html($post['content']) ?></div>
    <?php if (!empty($post['author_name'])): ?><p>Yazar: <?= e($post['author_name']) ?></p><?php endif; ?>
    <?php if (!empty($post['reviewer_name']) && !empty($post['reviewed_at'])): ?><p>İnceleyen: <?= e($post['reviewer_name']) ?> · <time datetime="<?= e($post['reviewed_at']) ?>"><?= e($post['reviewed_at']) ?></time></p><?php endif; ?>
    <?php if (!empty($post['sources'])): ?><section class="article-content"><h2>Kaynaklar</h2><ul><?php foreach (preg_split('/\R/u', $post['sources']) ?: [] as $source): ?><?php if (filter_var($source, FILTER_VALIDATE_URL) && in_array(parse_url($source, PHP_URL_SCHEME), ['http', 'https'], true)): ?><li><a href="<?= e($source) ?>" rel="noopener"><?= e(parse_url($source, PHP_URL_HOST)) ?></a></li><?php endif; ?><?php endforeach; ?></ul></section><?php endif; ?>
    <?php if (!empty($post['id'])): ?>
    <?php foreach (['services' => 'İlgili hizmetler', 'projects' => 'İlgili projeler'] as $type => $label): ?>
    <?php $relatedItems = (new PostRepository())->related((int) $post['id'], $type);
        if ($relatedItems): ?><section class="article-content"><h2><?= e($label) ?></h2><ul><?php foreach ($relatedItems as $related): ?><li><a href="<?= e(url(($type === 'projects' ? 'proje/' : '') . $related['slug'])) ?>"><?= e($related['title']) ?></a></li><?php endforeach; ?></ul></section><?php endif; ?>
    <?php endforeach; endif; ?>
</article>
<?php require __DIR__ . '/../partials/related-links.php'; ?>
