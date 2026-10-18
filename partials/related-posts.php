<?php
$relatedType = isset($service) ? 'services' : 'projects';
$relatedId = (int) ($service['id'] ?? $project['id'] ?? 0);
$relatedPosts = $relatedId ? (new PostRepository())->forContent($relatedId, $relatedType) : [];
if ($relatedPosts): ?>
<section class="container related-guides"><h2>Bu konuda rehberler</h2><ul><?php foreach ($relatedPosts as $relatedPost): ?><li><a href="<?= e(url('blog/' . $relatedPost['slug'])) ?>"><?= e($relatedPost['title']) ?></a><p><?= e($relatedPost['excerpt']) ?></p></li><?php endforeach; ?></ul></section>
<?php endif; ?>
