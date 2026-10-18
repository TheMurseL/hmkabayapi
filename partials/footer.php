</main>
<footer class="site-footer">
    <div class="container footer-grid">
        <div>
            <a class="brand footer-brand" href="<?= e(url()) ?>">
                <?php if ($footerLogo = absolute_asset(site_setting('business_logo'))): ?>
                    <img class="brand-logo" src="<?= e($footerLogo) ?>" alt="<?= e(config('site_name')) ?> logosu" width="38" height="38" loading="lazy">
                <?php endif; ?>
                <span>
                    <strong><?= e(config('site_name')) ?></strong>
                    <small>Kalıp · Demir · Beton</small>
                </span>
            </a>
            <p>İstanbul’da kalıp, demir ve beton uygulamaları. Projenize göre temel, taşıyıcı sistem, tuğla duvar ve çatı işleri.</p>
        </div>
        <div>
            <h3>Menü</h3>
            <a href="<?= e(url()) ?>">Anasayfa</a>
            <a href="<?= e(url('istanbul-kaba-insaat')) ?>">Kaba İnşaat</a>
            <a href="<?= e(url('projeler')) ?>">Projeler</a>
            <a href="<?= e(url('hakkimizda')) ?>">Hakkımızda</a>
            <a href="<?= e(url('blog')) ?>">Blog</a>
            <?php foreach ($navigationServices as $path => $navigationService): ?>
                <a href="<?= e(url($path)) ?>"><?= e($navigationService['title']) ?></a>
            <?php endforeach; ?>
            <a href="<?= e(url('hesaplamalar/kaba-insaat-maliyeti')) ?>">Kalıp ve demir işçiliği</a>
            <a href="<?= e(url('hesaplamalar/tugla-fiyat-hesaplama')) ?>">Tuğla adedi ve maliyeti</a>
        </div>
        <div>
            <h3>İletişim</h3>
            <?php if ($phone = site_setting('contact_phone')): ?><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $phone)) ?>"><?= e($phone) ?></a><?php endif; ?>
            <?php if ($whatsapp = whatsapp_url()): ?>
                <a href="<?= e($whatsapp) ?>" target="_blank" rel="noopener noreferrer">WhatsApp’tan proje gönderin ↗</a>
            <?php endif; ?>
            <a href="<?= e(url('iletisim')) ?>">Keşif ve teklif talebi</a>
            <?php if ($contactEmail = site_setting('contact_email')): ?>
                <a href="mailto:<?= e($contactEmail) ?>"><?= e($contactEmail) ?></a>
            <?php endif; ?>
            <?php if (site_setting('business_opens') && site_setting('business_closes')): ?>
                <span>Hafta içi <?= e(site_setting('business_opens')) ?> — <?= e(site_setting('business_closes')) ?></span>
            <?php endif; ?>
            <?php if ($address = site_setting('business_address')): ?>
                <address class="business-address">
                    <?= e($address) ?>
                </address>
            <?php endif; ?>
            <p><?= e(site_setting('service_areas', 'İstanbul’un tüm ilçeleri')) ?></p>
        </div>
    </div>
    <div class="container footer-bottom">
        <span>© <?= date('Y') ?> <?= e(config('site_name')) ?></span>
        <span>İşçilikte güven, yapıda iz.</span>
    </div>
</footer>
<?php if ($whatsapp = whatsapp_url()): ?>
    <a class="whatsapp-floating" href="<?= e($whatsapp) ?>" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp’tan proje ve fotoğraf gönderin (yeni sekmede açılır)">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false">
            <path d="M20.8 11.6a8.8 8.8 0 0 1-13 7.7L3 20.8l1.5-4.6A8.8 8.8 0 1 1 20.8 11.6Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="m8.5 7.6 1.3 2.1-.9 1.1c.7 1.5 1.9 2.7 3.4 3.4l1.1-.9 2.1 1.3c.2.1.3.4.2.6-.3 1-1.1 1.6-2.1 1.5-3.6-.4-6.7-3.5-7.1-7.1-.1-1 .5-1.8 1.5-2.1.2-.1.5 0 .6.2Z" fill="currentColor"/>
        </svg>
        <span>WhatsApp</span>
    </a>
<?php endif; ?>
<?php if ($hasGallery ?? false): ?>
    <script defer src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0.36/dist/fancybox/fancybox.umd.js"></script>
<?php endif; ?>
<?php require __DIR__ . '/measurement.php'; ?>
<?php if (($calculator ?? '') === 'kaba-insaat-maliyeti'): ?>
    <script defer src="<?= e(asset_url('assets/js/labor-calculator.js')) ?>"></script>
<?php endif; ?>
<script defer src="<?= e(asset_url('assets/js/app.js')) ?>"></script>
</body>
</html>
