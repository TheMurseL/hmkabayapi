<section class="admin-section">
    <div class="section-heading">
        <div>
            <div class="eyebrow">SEO &amp; genel ayarlar</div>
            <h2>Site ayarları</h2>
        </div>
        <span>Meta, iletişim ve özel head kodları</span>
    </div>
    <form class="admin-panel" method="post" enctype="multipart/form-data">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="type" value="settings">
        <label>
            Site başlığı
            <input name="site_title" value="<?= e($site['site_title'] ?? config('site_name')) ?>">
        </label>
        <label>
            Canlı site adresi
            <input type="url" name="site_url" placeholder="https://www.firmaniz.com" value="<?= e($site['site_url'] ?? '') ?>">
        </label>
        <p>Boş bırakıldığında yerel adres kullanılır. SITE_URL ortam değişkeni tanımlıysa önceliklidir. Canlı yayın sırasında doğru HTTPS adresini girin.</p>
        <label>
            Firma adı
            <input name="business_name" value="<?= e($site['business_name'] ?? config('site_name')) ?>">
        </label>
        <div class="form-row">
            <label>
                Logo URL veya site içi dosya yolu
                <input name="business_logo" placeholder="uploads/logo.webp" value="<?= e($site['business_logo'] ?? '') ?>">
            </label>
            <label>
                Sosyal paylaşım görseli URL veya dosya yolu
                <input name="share_image" placeholder="uploads/paylasim.webp" value="<?= e($site['share_image'] ?? '') ?>">
            </label>
        </div>
        <div class="form-row">
            <label>
                Logo yükle
                <input type="file" name="business_logo_upload" accept="image/jpeg,image/png,image/webp">
            </label>
            <label>
                Paylaşım görseli yükle (öneri: 1200 × 630 px)
                <input type="file" name="share_image_upload" accept="image/jpeg,image/png,image/webp">
            </label>
        </div>
        <label>
            Gerçek işletme adresi
            <textarea name="business_address" rows="3"><?= e($site['business_address'] ?? '') ?></textarea>
        </label>
        <div class="form-row">
            <label>
                İl
                <input name="business_city" value="<?= e($site['business_city'] ?? '') ?>">
            </label>
            <label>
                Posta kodu
                <input name="business_postcode" value="<?= e($site['business_postcode'] ?? '') ?>">
            </label>
        </div>
        <div class="form-row">
            <label>
                Enlem (isteğe bağlı)
                <input type="number" step="any" min="-90" max="90" name="business_latitude" value="<?= e($site['business_latitude'] ?? '') ?>">
            </label>
            <label>
                Boylam (isteğe bağlı)
                <input type="number" step="any" min="-180" max="180" name="business_longitude" value="<?= e($site['business_longitude'] ?? '') ?>">
            </label>
        </div>
        <div class="form-row">
            <label>
                Hafta içi açılış
                <input type="time" name="business_opens" value="<?= e($site['business_opens'] ?? '') ?>">
            </label>
            <label>
                Hafta içi kapanış
                <input type="time" name="business_closes" value="<?= e($site['business_closes'] ?? '') ?>">
            </label>
        </div>
        <label>
            Sosyal medya / Google işletme profili adresleri (her satıra bir URL)
            <textarea name="business_social_urls" rows="4"><?= e($site['business_social_urls'] ?? '') ?></textarea>
        </label>
        <label>
            Site açıklaması / meta description
            <textarea name="site_description" rows="3"><?= e($site['site_description'] ?? '') ?></textarea>
        </label>
        <input type="hidden" name="site_keywords" value="<?= e($site['site_keywords'] ?? '') ?>">
        <div class="form-row">
            <label>
                Telefon
                <input name="contact_phone" value="<?= e($site['contact_phone'] ?? '') ?>">
            </label>
            <label>
                E-posta
                <input name="contact_email" value="<?= e($site['contact_email'] ?? '') ?>">
            </label>
        </div>
        <?php require __DIR__ . '/seo-settings.php'; ?>
        <label>
            Özel head kodları
            <textarea name="custom_head" rows="6" placeholder="Özel meta veya doğrulama kodlarınızı buraya ekleyin"><?= e($site['custom_head'] ?? '') ?></textarea>
        </label>
        <button class="button button-dark">Ayarları kaydet</button>
    </form>
</section>
<section class="admin-section">
    <div class="section-heading">
        <div>
            <div class="eyebrow">Hesap güvenliği</div>
            <h2>Yönetici parolası</h2>
        </div>
    </div>
    <form class="admin-panel" method="post">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="type" value="admin_password">
        <p>Yalnızca giriş yaptığınız yönetici hesabının parolası değişir. En az 12 karakter kullanın.</p>
        <label>
            Mevcut parola
            <input type="password" name="current_password" autocomplete="current-password" required>
        </label>
        <div class="form-row">
            <label>
                Yeni parola
                <input type="password" name="new_password" autocomplete="new-password" minlength="12" maxlength="72" required>
            </label>
            <label>
                Yeni parola tekrarı
                <input type="password" name="password_confirmation" autocomplete="new-password" minlength="12" maxlength="72" required>
            </label>
        </div>
        <button class="button button-dark" type="submit">Parolayı güncelle</button>
    </form>
</section>
