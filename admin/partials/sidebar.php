<?php
$navigation = [
    'dashboard' => ['Genel bakış', 'grid'],
    'projects' => ['Projeler', 'building'],
    'services' => ['Hizmetler', 'layers'],
    'blog' => ['Blog yazıları', 'file'],
    'messages' => ['İletişim mesajları', 'mail'],
    'settings' => ['SEO & firma ayarları', 'settings'],
    'seo' => ['SEO kontrol merkezi', 'check'],
];
?>
<aside class="sidebar" id="admin-sidebar" aria-label="Yönetim menüsü">
    <div class="sidebar-brand-row">
        <a class="brand" href="<?= e(url('admin')) ?>">
            <span><?= e(config('site_name')) ?><small>WEB SİTESİ YÖNETİMİ</small></span>
        </a>
        <button class="icon-button mobile-menu" type="button" data-menu-close aria-label="Menüyü kapat"><?= admin_icon('close') ?></button>
    </div>
    <div class="workspace-label"><span class="status-dot"></span> Yönetim çalışma alanı</div>
    <div class="nav-caption">ÇALIŞMA ALANI</div>
    <nav aria-label="Ana menü">
        <?php foreach ($navigation as $key => [$label, $icon]): ?>
            <a
                class="nav-item <?= $section === $key ? 'active' : '' ?>"
                href="<?= e(url('admin?section=' . $key)) ?>"
                <?= $section === $key ? 'aria-current="page"' : '' ?>
            >
                <?= admin_icon($icon) ?>
                <span><?= e($label) ?></span>
                <?php if ($key === 'messages' && (int) $messageCounts['unread'] > 0): ?>
                    <span class="nav-count"><?= (int) $messageCounts['unread'] ?></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <div class="sidebar-bottom">
        <div class="system-note">
            <?= admin_icon('check') ?>
            <div>İçerikleriniz tek merkezde<small>Projeler, hizmetler ve site ayarları.</small></div>
        </div>
        <div class="account">
            <span class="avatar"><?= e(mb_strtoupper(mb_substr($currentAdmin['name'], 0, 1))) ?></span>
            <span><?= e($currentAdmin['name']) ?><small>Sistem yöneticisi</small></span>
            <a class="icon-button" href="<?= e(url('admin?section=settings')) ?>" aria-label="Firma ayarlarını aç"><?= admin_icon('settings') ?></a>
        </div>
    </div>
</aside>
