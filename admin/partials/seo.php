<?php
require_once __DIR__ . '/../../app/SeoReadiness.php';
$seoChecks = SeoReadiness::checks();
$seoInventory = SeoReadiness::inventory();
$seoReport = SeoReadiness::lastReport();
$funnel = db()->query('SELECT lead_status, COUNT(*) AS total FROM messages GROUP BY lead_status')->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<section class="admin-section">
    <div class="section-heading"><h2>Yayın hazırlığı ve takip</h2><span>Kontroller gerçek yapılandırma ve kayıtlardan okunur.</span></div>
    <div class="admin-table-wrap"><table class="admin-table"><tr><th>Kontrol</th><th>Durum</th><th>Açıklama</th></tr>
    <?php foreach ($seoChecks as $check): ?><tr><td><a href="<?= e(url('admin?section=' . $check['section'])) ?>"><?= e($check['name']) ?></a></td><td><?= $check['ok'] ? 'Hazır' : 'Bekleyen / kontrol edilmeli' ?></td><td><?= e($check['detail']) ?></td></tr><?php endforeach; ?>
    </table></div>
</section>
<section class="admin-section"><h2>Teklif süreci</h2><div class="stats-grid">
<?php foreach (['new' => 'Yeni', 'qualified' => 'Nitelikli', 'visit' => 'Keşif', 'quoted' => 'Teklif', 'won' => 'Kazanıldı', 'lost' => 'Sonuçsuz'] as $key => $label): ?><div class="admin-panel"><span><?= e($label) ?></span><h3><?= (int) ($funnel[$key] ?? 0) ?></h3></div><?php endforeach; ?>
</div><a class="text-link" href="<?= e(url('admin?section=messages')) ?>">Talepleri yönet</a></section>
<section class="admin-section"><h2>Sayfa envanteri</h2><div class="admin-table-wrap"><table class="admin-table"><tr><th>Sayfa</th><th>İnceleme notları</th><th>Düzenle</th></tr>
<?php foreach ($seoInventory as $item): ?><tr><td><a href="<?= e($item['url']) ?>" target="_blank" rel="noopener"><?= e($item['title']) ?></a></td><td><?= e(implode(' · ', $item['issues']) ?: 'Otomatik kontrolde eksik alan bulunmadı; içerik doğruluğu ayrıca incelenir.') ?></td><td><a href="<?= e(url($item['edit'])) ?>">Düzenle</a></td></tr><?php endforeach; ?>
</table></div></section>
<section class="admin-section"><h2>Son teknik denetim</h2>
<?php if ($seoReport): ?><p><?= e($seoReport['finished_at']) ?> · <?= (int) $seoReport['page_count'] ?> sayfa · <?= count($seoReport['failures']) ?> hata.</p>
<?php if ($seoReport['failures']): ?><ul><?php foreach ($seoReport['failures'] as $failure): ?><li><?= e($failure) ?></li><?php endforeach; ?></ul><?php else: ?><p>HTTP, canonical, sitemap, indeksleme ve sayfa yapısı kontrolleri başarılı.</p><?php endif; ?>
<?php else: ?><p>Komut satırından scripts/seo-audit.php çalıştırıldığında rapor burada görünür.</p><?php endif; ?>
</section>
