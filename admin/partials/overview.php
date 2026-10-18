<?php
$stats = [
    ['Toplam proje', count($projects), 'building', 'Saha ve referans portföyünüz', 'blue', 'projects'],
    ['Hizmet sayfası', count($services), 'layers', 'Web sitenizin hizmet içerikleri', 'green', 'services'],
    ['Blog yazısı', count($posts), 'file', 'Rehberler ve saha notları', 'purple', 'blog'],
    ['Okunmamış mesaj', (int) $messageCounts['unread'], 'mail', 'Yanıt bekleyen iletişim talepleri', 'orange', 'messages'],
];
$siteHost = parse_url(config('site_url'), PHP_URL_HOST);
$setupChecks = [
    'Canlı site adresi' => $siteHost && $siteHost !== 'localhost' && !filter_var($siteHost, FILTER_VALIDATE_IP),
    'Firma telefonu' => trim($site['contact_phone'] ?? '') !== '',
    'İletişim e-postası' => trim($site['contact_email'] ?? '') !== '',
    'Firma adresi' => trim($site['business_address'] ?? '') !== '',
];
?>
<section class="stats-grid" aria-label="İçerik özeti">
    <?php foreach ($stats as [$label, $value, $icon, $detail, $color, $destination]): ?>
        <a class="stat-card" href="<?= e(url('admin?section=' . $destination)) ?>">
            <div class="stat-top">
                <span><?= e($label) ?></span>
                <span class="stat-icon <?= e($color) ?>"><?= admin_icon($icon) ?></span>
            </div>
            <div class="stat-number"><?= (int) $value ?></div>
            <small><?= e($detail) ?></small>
        </a>
    <?php endforeach; ?>
</section>
<div class="dashboard-grid">
    <div class="dashboard-column">
        <section class="panel">
            <div class="panel-header">
                <div><h2>Son projeler</h2><p>Proje portföyünüzün güncel özeti</p></div>
                <a class="text-link" href="<?= e(url('admin?section=projects')) ?>">Tümünü gör <?= admin_icon('arrow') ?></a>
            </div>
            <?php if ($projects): ?>
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead><tr><th>Proje</th><th>Lokasyon</th><th>Durum</th></tr></thead>
                        <tbody>
                            <?php foreach (array_slice($projects, 0, 5) as $recentProject): ?>
                                <tr>
                                    <td><a class="table-title" href="<?= e(url('admin?section=projects&edit_project=' . $recentProject['id'])) ?>"><?= e($recentProject['title']) ?></a><small class="table-subtitle"><?= e($recentProject['year']) ?></small></td>
                                    <td><?= e($recentProject['location']) ?></td>
                                    <td><span class="badge-soft <?= $recentProject['category'] === 'ongoing' ? 'warning' : 'success' ?>"><?= $recentProject['category'] === 'ongoing' ? 'Devam ediyor' : 'Tamamlandı' ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state"><h3>İlk projenizi ekleyin</h3><p>Proje fotoğraflarınız ve uygulama bilgileriniz burada listelenir.</p></div>
            <?php endif; ?>
        </section>
        <section class="panel">
            <div class="panel-header">
                <div><h2>Son iletişim talepleri</h2><p>Web sitenizden gelen mesajlar</p></div>
                <a class="text-link" href="<?= e(url('admin?section=messages')) ?>">Gelen kutusu <?= admin_icon('arrow') ?></a>
            </div>
            <?php if ($messages): ?>
                <div class="recent-messages">
                    <?php foreach (array_slice($messages, 0, 3) as $recentMessage): ?>
                        <a href="<?= e(url('admin?section=messages')) ?>">
                            <span class="message-icon"><?= admin_icon('mail') ?></span>
                            <span><strong><?= e($recentMessage['name']) ?></strong><small><?= e(mb_strimwidth($recentMessage['message'], 0, 100, '…')) ?></small></span>
                            <span class="badge-soft <?= empty($recentMessage['is_read']) ? 'warning' : 'success' ?>"><?= empty($recentMessage['is_read']) ? 'Yeni' : 'Okundu' ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <span class="empty-icon"><?= admin_icon('mail') ?></span>
                    <h3>Henüz iletişim talebi yok</h3>
                    <p>Ziyaretçilerinizin gönderdiği keşif ve teklif talepleri burada görünecek.</p>
                </div>
            <?php endif; ?>
        </section>
    </div>
    <div class="dashboard-column">
        <section class="quick-panel">
            <div class="eyebrow">BİR SONRAKİ ADIM</div>
            <h2>İçeriklerinizi<br>güncel tutun.</h2>
            <p>Yeni projeleriniz, saha fotoğraflarınız ve rehber içeriklerinizle ziyaretçilerinize çalışmalarınızı anlatın.</p>
            <div class="quick-links">
                <a class="button button-quick" href="<?= e(url('admin?section=projects&new_project=1')) ?>"><?= admin_icon('plus') ?> Proje ekle</a>
                <a class="button button-quick" href="<?= e(url('admin?section=blog&new_post=1')) ?>"><?= admin_icon('file') ?> Blog yazısı oluştur</a>
                <a class="button button-quick" href="<?= e(url('admin?section=services')) ?>"><?= admin_icon('layers') ?> Hizmetleri düzenle</a>
            </div>
        </section>
        <section class="panel">
            <div class="panel-header"><div><h2>Yayın hazırlığı</h2><p>Firma bilgilerinizin durumu</p></div></div>
            <ul class="setup-checklist">
                <?php foreach ($setupChecks as $label => $complete): ?>
                    <li><span><?= e($label) ?></span><span class="badge-soft <?= $complete ? 'success' : 'warning' ?>"><?= $complete ? 'Tanımlı' : 'Eksik' ?></span></li>
                <?php endforeach; ?>
            </ul>
            <a class="setup-link text-link" href="<?= e(url('admin?section=settings')) ?>">Firma bilgilerini düzenle <?= admin_icon('arrow') ?></a>
        </section>
    </div>
</div>
