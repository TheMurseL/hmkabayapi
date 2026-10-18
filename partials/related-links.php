<section class="seo-links">
    <div class="container">
        <h2>Projenizin sonraki adımı</h2>
        <ul>
            <?php foreach ([
                'hesaplamalar/kaba-insaat-maliyeti' => 'Kalıp ve demir işçiliği hesabı',
                'hesaplamalar/tugla-fiyat-hesaplama' => 'Tuğla maliyeti hesaplama',
                'projeler' => 'Projeleri inceleyin',
                'iletisim' => 'Keşif ve teklif için iletişim',
            ] + array_column(service_catalog(), 'title', 'slug') as $path => $label): ?>
                <?php if ($path !== trim((string) ($_GET['route'] ?? ''), '/')): ?>
                    <li>
                        <a href="<?= e(url($path)) ?>"><?= e($label) ?></a>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
