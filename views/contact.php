<section class="page-hero">
    <div class="container">
        <div class="eyebrow light">
            <span></span>
            İletişim
        </div>
        <h1>Projeniz için<br><em>buradayız.</em></h1>
    </div>
</section>
<section class="contact-section">
    <div class="container contact-grid">
        <div>
            <div class="eyebrow">Bir kahve içelim</div>
            <h2>İşinizi anlatın,<br><em>birlikte planlayalım.</em></h2>
            <p>Projenizin konumunu, çizimlerini ve başlangıç tarihini paylaşın. Kalıp, demir ve beton uygulamalarından hangilerini istediğinizi, varsa tuğla duvar ve çatı taleplerinizi belirtin.</p>
            <div class="contact-details">
                <?php if ($phone = site_setting('contact_phone')): ?>
                <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $phone)) ?>"><small>Telefon</small><?= e($phone) ?></a>
                <?php endif; ?>
                <?php if ($whatsapp = whatsapp_url()): ?>
                <a href="<?= e($whatsapp) ?>" target="_blank" rel="noopener noreferrer"><small>WhatsApp</small>Proje ve fotoğraf gönderin ↗</a>
                <?php endif; ?>
                <?php if ($contactEmail = site_setting('contact_email')): ?>
                    <a href="mailto:<?= e($contactEmail) ?>">
                        <small>E-posta</small>
                        <?= e($contactEmail) ?>
                    </a>
                <?php endif; ?>
            </div>
            <?php if ($profile = site_setting('business_profile_url')): ?><p><a class="text-link" href="<?= e($profile) ?>" target="_blank" rel="noopener">Google işletme profilimiz ↗</a></p><?php endif; ?>
            <?php if ($address = site_setting('business_address')): ?>
                <address class="business-address">
                    <?= e($address) ?>
                </address>
            <?php endif; ?>
        </div>
        <form class="contact-form" method="post">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="request_token" value="<?= e($requestToken) ?>">
            <label class="form-trap" aria-hidden="true">Web sitesi<input name="website" tabindex="-1" autocomplete="off"></label>
            <label>
                Ad soyad
                <input required name="name" type="text" maxlength="100" autocomplete="name" value="<?= old('name') ?>" placeholder="Adınız soyadınız">
            </label>
            <label>
                E-posta
                <input name="email" type="email" maxlength="150" autocomplete="email" value="<?= old('email') ?>" placeholder="ornek@mail.com">
            </label>
            <label>
                Telefon
                <input name="phone" type="tel" maxlength="30" autocomplete="tel" value="<?= old('phone') ?>" placeholder="05__ ___ __ __">
            </label>
            <small>Telefon veya e-posta alanlarından en az birini doldurun.</small>
            <label>İlçe / proje konumu (isteğe bağlı)<input name="district" maxlength="100" value="<?= old('district') ?>"></label>
            <label>Yapı türü (isteğe bağlı)<select name="building_type"><option value="">Seçin</option><?php foreach (['Konut', 'Villa', 'Ticari yapı', 'Diğer'] as $type): ?><option <?= ($_POST['building_type'] ?? '') === $type ? 'selected' : '' ?>><?= e($type) ?></option><?php endforeach; ?></select></label>
            <label>İşin alanı / metrajı, m² (isteğe bağlı)<input type="number" min="0.1" max="1000000" step="0.1" name="area" value="<?= old('area', $areaDefault) ?>"></label>
            <label>Projenin aşaması (isteğe bağlı)<select name="stage"><option value="">Seçin</option><?php foreach (['Proje hazırlığı', 'Ruhsat süreci', 'Başlamaya hazır', 'Devam eden şantiye'] as $stage): ?><option <?= ($_POST['stage'] ?? '') === $stage ? 'selected' : '' ?>><?= e($stage) ?></option><?php endforeach; ?></select></label>
            <label>
                Mesajınız
                <textarea required name="message" maxlength="10000" rows="5" placeholder="İş kapsamı ve hedef başlangıç tarihinizden bahsedin..."><?= old('message') ?></textarea>
            </label>
            <button class="button button-dark" type="submit">Mesajı gönder <span>↗</span></button>
            <?php foreach ($errors as $error): ?>
                <div class="form-message error"><?= e($error) ?></div>
            <?php endforeach; ?>
            <?php if ($flash): ?>
                <div class="form-message" role="status" <?= $leadSuccess ? 'data-lead-success' : '' ?>><?= e($flash['message']) ?></div>
            <?php endif; ?>
        </form>
    </div>
</section>
<?php require __DIR__ . '/../partials/service-areas.php'; ?>
