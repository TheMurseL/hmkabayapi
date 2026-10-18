<?php
$measurementId = site_setting('ga4_measurement_id');
if (site_is_indexable() && preg_match('/^G-[A-Z0-9]{4,20}$/D', $measurementId)):
    ?>
<div class="measurement-choice" data-measurement-choice hidden role="region" aria-label="Ziyaret ölçümü tercihi">
    <p>Site kullanımını ve teklif taleplerinin sayısını Google Analytics ile ölçmemize izin verir misiniz? Formdaki iletişim bilgileri ölçüme gönderilmez.</p>
    <button type="button" data-measurement-accept>İzin ver</button><button type="button" data-measurement-reject>Reddet</button>
</div>
<button type="button" class="measurement-preference" data-measurement-preference>Ölçüm tercihi</button>
<script>window.siteMeasurement = <?= seo_json(['id' => $measurementId, 'path' => parse_url(url(trim((string) ($_GET['route'] ?? ''), '/')), PHP_URL_PATH)]) ?>;</script>
<script defer src="<?= e(asset_url('assets/js/measurement.js')) ?>"></script>
<?php endif; ?>
