<?php if (!empty($breadcrumbs) && http_response_code() !== 404): ?>
    <nav class="breadcrumbs container" aria-label="Sayfa yolu">
        <ol>
            <?php foreach ($breadcrumbs as $index => $crumb): ?>
                <li>
                    <?php if ($index < count($breadcrumbs) - 1): ?>
                        <a href="<?= e(url($crumb['path'])) ?>"><?= e($crumb['name']) ?></a>
                    <?php else: ?>
                        <span aria-current="page"><?= e($crumb['name']) ?></span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>
    </nav>
<?php endif; ?>
