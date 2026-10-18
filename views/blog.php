<section class="page-hero">
    <div class="container">
        <div class="eyebrow light">
            <span></span>
            Blog
        </div>
        <h1>Kaba inşaat<br><em>üzerine notlar.</em></h1>
    </div>
</section>
<section class="listing">
    <div class="container">
        <div class="post-grid post-grid-wide">
            <?php foreach ($posts as $post): ?>
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
