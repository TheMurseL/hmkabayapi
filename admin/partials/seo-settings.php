<h2>Yayın ve ölçüm</h2>
<p>Ortam: <strong><?= e(config('app_env')) ?></strong>. İndeksleme için sunucuda APP_ENV=production ve aşağıdaki onay birlikte gerekir. Yerel/staging ortamında kapalı kalır.</p>
<label><input type="checkbox" name="indexing_enabled" value="1" <?= ($site['indexing_enabled'] ?? '') === '1' ? 'checked' : '' ?>> Canlı sitede indekslemeye izin ver</label>
<label>GA4 ölçüm kimliği<input name="ga4_measurement_id" placeholder="G-XXXXXXXXXX" value="<?= e($site['ga4_measurement_id'] ?? '') ?>"></label>
<p>Ölçüm yalnızca indekslemeye açık canlı sitede ve ziyaretçi izin verdikten sonra çalışır. Başarılı form, telefon/WhatsApp tıklaması ve hesaplayıcı kullanımı ayrı olaylardır.</p>
<label>Search Console HTML doğrulama kodu<input name="search_console_verification" value="<?= e($site['search_console_verification'] ?? '') ?>"></label>
<p>Yalnızca meta etiketindeki content değerini girin. Alan adı mülkü için Google'ın verdiği DNS kaydı hosting panelinden eklenir.</p>
<h2>Gerçek firma bilgileri ve görseller</h2>
<label>Çalışma bölgesi kısa açıklaması<input name="service_areas" maxlength="200" placeholder="İstanbul’un tüm ilçeleri" value="<?= e($site['service_areas'] ?? '') ?>"></label>
<label>Hizmet verilen ilçeler (her satıra bir ilçe)<textarea name="service_districts" rows="6" maxlength="5000"><?= e($site['service_districts'] ?? '') ?></textarea></label>
<p>Bölge açıklaması üst çubukta ve sayfalarda, ilçe listesi anasayfa ile iletişim sayfasında gösterilir.</p>
<label>Google işletme profili bağlantısı<input type="url" name="business_profile_url" value="<?= e($site['business_profile_url'] ?? '') ?>"></label>
<label>WhatsApp numarası (ülke koduyla)<input type="tel" name="contact_whatsapp" placeholder="90…" value="<?= e($site['contact_whatsapp'] ?? '') ?>"></label>
<p>Geçici numarayı kendi WhatsApp numaranızla değiştirin. Boş bırakırsanız WhatsApp bağlantıları gizlenir.</p>
<div class="form-row">
    <label>Anasayfa kapak görseli — dosya yolu / URL<input name="hero_image" value="<?= e($site['hero_image'] ?? '') ?>"></label>
    <label>Görsel yükle<input type="file" name="hero_image_upload" accept="image/jpeg,image/png,image/webp"></label>
</div>
<p>Anasayfa görselini buradan yükleyebilirsiniz. Proje görselleri Projeler bölümünden yönetilir.</p>
<h2>Hakkımızda sayfası</h2>
<label>Firma tanıtımı<textarea name="business_about" rows="8" maxlength="10000"><?= e($site['business_about'] ?? about_defaults()['business_about']) ?></textarea></label>
<p>Paragrafları boş satırla ayırabilirsiniz.</p>
<div class="form-row">
    <label>Hakkımızda görseli — dosya yolu / URL<input name="about_image" value="<?= e($site['about_image'] ?? about_defaults()['about_image']) ?>"></label>
    <label>Görsel yükle<input type="file" name="about_image_upload" accept="image/jpeg,image/png,image/webp"></label>
</div>
<h2>Hesaplayıcı fiyat kaynağı</h2>
<p>Doğrulanmış kaynak girilmedikçe ziyaretçi kendi birim fiyatını kullanır. Eski hazır fiyatlar otomatik yayınlanmaz.</p>
<div class="form-row"><label>Kalıp işçilik fiyatı (₺/m² kalıp yüzeyi)<input type="number" min="0" max="1000000" step="0.01" name="calc_cost_standard" value="<?= e($site['calc_cost_standard'] ?? '') ?>"></label><label>Tuğla fiyatı (₺/adet)<input type="number" min="0" max="1000000" step="0.01" name="calc_brick_price" value="<?= e($site['calc_brick_price'] ?? '') ?>"></label></div>
<label>Tuğla işçilik (₺/m²)<input type="number" min="0" max="1000000" step="0.01" name="calc_brick_labor" value="<?= e($site['calc_brick_labor'] ?? '') ?>"></label>
<label>Fiyat tarihi<input type="date" name="calc_rate_date" max="<?= display_date('now', 'Y-m-d') ?>" value="<?= e($site['calc_rate_date'] ?? '') ?>"></label>
<label>Fiyatın kaynağı / teklif referansı<input name="calc_rate_source" value="<?= e($site['calc_rate_source'] ?? '') ?>"></label>
<label>Dahil ve hariç kalemler; KDV durumu<textarea name="calc_rate_scope" rows="4"><?= e($site['calc_rate_scope'] ?? '') ?></textarea></label>
<label><input type="checkbox" name="calc_rates_verified" value="1" <?= ($site['calc_rates_verified'] ?? '') === '1' ? 'checked' : '' ?>> Tarih, fiyat ve kapsamı kontrol ettim; varsayılan fiyatları göster</label>
