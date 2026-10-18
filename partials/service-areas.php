<?php
$serviceAreaSummary = trim(site_setting('service_areas'));
$serviceDistricts = array_values(array_unique(array_filter(array_map(
    'trim',
    preg_split('/[\r\n,]+/u', site_setting('service_districts')) ?: []
))));
?>
<?php if ($serviceAreaSummary !== '' || $serviceDistricts): ?>
    <section class="service-areas" aria-labelledby="service-areas-heading">
        <div class="container">
            <div class="service-areas-intro">
                <div>
                    <div class="eyebrow">Çalışma bölgelerimiz</div>
                    <h2 id="service-areas-heading"><?= e($serviceAreaSummary ?: 'Hizmet verdiğimiz bölgeler') ?></h2>
                </div>
                <p>Projenizin konumunu ve çizimlerini paylaşın; yapılacak işi birlikte netleştirelim.</p>
            </div>
            <?php if ($serviceDistricts): ?>
                <details class="service-districts">
                    <summary>Hizmet verdiğimiz ilçeler <span><?= count($serviceDistricts) ?></span></summary>
                    <ul>
                        <?php foreach ($serviceDistricts as $district): ?>
                            <li><?= e($district) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </details>
            <?php endif; ?>
        </div>
    </section>
<?php endif; ?>
