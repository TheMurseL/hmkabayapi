<?php

declare(strict_types=1);

final class SeoReadiness
{
    public static function checks(): array
    {
        $checks = [];
        $add = static function (string $name, bool $ok, string $detail, string $section = 'settings') use (&$checks): void {
            $checks[] = compact('name', 'ok', 'detail', 'section');
        };
        $add('Yayın / indeksleme', site_is_indexable(), site_is_indexable() ? 'HTTPS site adresi, canlı ortam ve panelde indeksleme onayı açık. Sertifika ve yönlendirmeler canlı sunucuda ayrıca kontrol edilmeli.' : 'Yerel geliştirmede noindex beklenen durumdur. Canlıda gerçek HTTPS site adresi, APP_ENV=production ve panel onayı gerekir.');
        $adminHashes = db()->query('SELECT password FROM admins')->fetchAll(PDO::FETCH_COLUMN);
        $hasDefaultAdminPassword = false;
        foreach ($adminHashes as $hash) {
            if (password_verify('password', (string) $hash)) {
                $hasDefaultAdminPassword = true;
                break;
            }
        }
        $add('Yönetici parolası', $adminHashes !== [] && !$hasDefaultAdminPassword, $hasDefaultAdminPassword ? 'Kurulum parolasını kullanan bir yönetici hesabı var. Ayarlar bölümündeki parola değiştirme formundan yeni parola belirlenmeli.' : 'Yönetici hesaplarında kurulum parolası eşleşmesi kontrol edilir; bu kontrol parola gücünü değerlendirmez.');
        $email = site_setting('contact_email');
        $emailDomain = strtolower((string) substr(strrchr($email, '@') ?: '', 1));
        $emailReady = filter_var($email, FILTER_VALIDATE_EMAIL)
            && !in_array($emailDomain, ['example.com', 'example.net', 'example.org', 'localhost'], true)
            && !preg_match('/\.(?:example|invalid|test)$/D', $emailDomain);
        $add('İletişim alanları', self::contactNumberIsPlausible(site_setting('contact_phone')) && $emailReady, 'Firma telefonu ve e-postası girilmeli; boş, geçici veya örnek değerler hazır sayılmaz. Biçim kontrolü bu bilgilerin firmaya ait olduğunu doğrulamaz.');
        $add('WhatsApp bağlantısı (isteğe bağlı)', self::contactNumberIsPlausible(site_setting('contact_whatsapp')), 'WhatsApp kullanılacaksa gerçek numara girilmeli. Geçici önizleme numarası hazır sayılmaz; numaranın WhatsApp hesabı olduğu ayrıca kontrol edilmeli.');
        $add('İşletme kimliği', site_setting('business_address') !== '' && site_setting('business_city') !== '', 'Adres / il alanları gerçek işletme bilgileriyle doldurulmalı. Alanların dolu olması adres doğrulaması değildir.');
        $add('Logo', site_setting('business_logo') !== '', 'Logo daha sonra eklenebilir; bu alan boşken marka adı metin olarak gösterilir. Dosya yolu bulunması görselin doğruluğunu veya kullanım iznini doğrulamaz.');
        $add('Sosyal paylaşım görseli', site_setting('share_image') !== '', 'Sosyal paylaşım için firmaya ait görsel eklenmeli. Dosya yolu bulunması görselin doğruluğunu veya kullanım iznini doğrulamaz.');
        $heroPath = str_replace('\\', '/', (string) parse_url(site_setting('hero_image'), PHP_URL_PATH));
        $heroReady = $heroPath !== '' && !preg_match('~(?:^|/)demo(?:/|$)~i', $heroPath);
        $add('Gerçek saha fotoğrafı', $heroReady, 'Anasayfadaki demo görseller gerçek saha fotoğrafı olarak hazır sayılmaz. Demo dışındaki görselin gerçekliği ve kullanım izni ayrıca kontrol edilmeli.');
        $add('GA4 yapılandırması', (bool) preg_match('/^G-[A-Z0-9]{4,20}$/D', site_setting('ga4_measurement_id')), 'Ölçüm kimliği girildikten sonra canlıda DebugView ile doğrulanmalı.');
        $add('Search Console hazırlığı', site_setting('search_console_verification') !== '', 'Kod bulunması Google hesap doğrulamasının tamamlandığı anlamına gelmez. DNS yöntemi de kullanılabilir.');
        $add('Google işletme profili', site_setting('business_profile_url') !== '', 'Gerçek profil bağlantısı ve işletme doğrulaması gerekir.');
        $add('Hazır tuğla fiyatları (isteğe bağlı)', site_setting('calc_rates_verified') === '1', 'Tuğla hesabındaki hazır fiyatlar için onaylı tarih, kaynak ve kapsam gerekir. Onay yoksa ziyaretçi kendi fiyatını girer. Kalıp ve demir hesabı her zaman ziyaretçinin birim fiyatlarıyla çalışır.');
        $realProjects = (int) db()->query("SELECT COUNT(*) FROM projects WHERE is_demo=0 AND COALESCE(work_scope,'')<>'' AND COALESCE(delivery_result,'')<>''")->fetchColumn();
        $add('Proje kanıtları', $realProjects > 0, $realProjects . ' gerçek proje kaydında kapsam ve teslim sonucu mevcut. Fotoğraf / bilgi doğruluğu ayrıca kontrol edilmeli.', 'projects');
        $report = self::lastReport();
        $fresh = $report && ($report['site_url'] ?? '') === config('site_url') && isset($report['finished_at']) && strtotime($report['finished_at']) >= time() - 26 * 3600;
        $add('HTTP denetimi', $fresh && empty($report['failures']), $report ? 'Son tarama: ' . $report['finished_at'] . ' · Hata: ' . count($report['failures']) : 'Henüz HTTP denetim raporu yok.', 'seo');
        return $checks;
    }

    private static function contactNumberIsPlausible(string $number): bool
    {
        $digits = preg_replace('/\D/', '', $number);
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }
        if (strlen($digits) === 12 && str_starts_with($digits, '90')) {
            $digits = substr($digits, 2);
        } elseif (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        return (bool) preg_match('/^[1-9][0-9]{9,14}$/D', $digits)
            && !preg_match('/^([0-9])\1+$/D', $digits);
    }

    public static function inventory(): array
    {
        $items = [];
        foreach (['services' => '', 'projects' => 'proje/', 'posts' => 'blog/'] as $table => $prefix) {
            foreach (db()->query("SELECT * FROM {$table} ORDER BY id")->fetchAll() as $row) {
                $issues = [];
                if (empty($row['seo_title'])) {
                    $issues[] = 'Özel SEO başlığı yok; sayfa başlığı kullanılıyor';
                }
                if (empty($row['seo_description'])) {
                    $issues[] = 'Özel meta açıklama yok; sayfa özeti kullanılıyor';
                }
                if ($table === 'projects' && !empty($row['is_demo'])) {
                    $issues[] = 'Örnek proje; sitemap dışında ve noindex';
                }
                if ($table === 'projects' && (empty($row['work_scope']) || empty($row['delivery_result']))) {
                    $issues[] = 'Gerçek kapsam / sonuç bilgisi bekleniyor';
                }
                if ($table === 'posts' && empty($row['published'])) {
                    $issues[] = 'Taslak; public URL 404';
                }
                if ($table === 'posts' && empty($row['reviewed_at'])) {
                    $issues[] = 'Editoryal inceleme kaydı yok';
                }
                if ($table === 'posts' && empty($row['search_intent'])) {
                    $issues[] = 'Hedef arama niyeti tanımlanmamış';
                }
                $items[] = ['title' => $row['title'], 'url' => url($prefix . $row['slug']), 'issues' => $issues, 'edit' => 'admin?section=' . ($table === 'posts' ? 'blog&edit_post=' : ($table === 'services' ? 'services&edit_service=' : 'projects&edit_project=')) . $row['id']];
            }
        }
        return $items;
    }

    public static function lastReport(): ?array
    {
        $path = __DIR__ . '/../.private/reports/seo-latest.json';
        return is_file($path) ? (json_decode((string) file_get_contents($path), true) ?: null) : null;
    }
}
