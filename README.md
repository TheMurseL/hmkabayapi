# H&M Kaba Yapı

Kaba inşaat ve uygulama sitesi. PHP 8+, MySQL ve Apache üzerinde çalışır. Kalıp, demir ve beton uygulamaları yapılır; tuğla duvar ve çatı işleri talebe göre değerlendirilir. Malzeme, ekipman ve saha hazırlığı sorumlulukları proje teklifinde belirlenir.

Git deposu kaynak kodu, testleri, bakım scriptlerini ve `uploads/demo/` altındaki örnek görselleri içerir. `README.md` dışındaki Markdown belgeleri, SQL dosyaları, gerçek veritabanı, sunucuya yüklenen görseller, erişim bilgileri, `.private/` yedekleri ve yerel çalışma raporları repoya alınmaz. Yerelde saklanan `database.sql` güncel veritabanının yedeği değil, yeni kurulum için örnek şema ve başlangıç verisidir; kurulum için ayrıca temin edilmelidir. Ortam değişkenlerini sunucu yapılandırmasında tanımlayın; uygulama `.env` dosyasını otomatik okumaz. GitHub'a kod göndermek tek başına canlı siteyi güncellemez; bu depoda otomatik dağıtım henüz yapılandırılmamıştır.

Yerel test için `C:/xampp/php/php.exe scripts/prepare-preview-content.php` komutu, eksik ayarlara İstanbul'un tüm ilçelerini ve ahşap kalıp test görsellerini ekler; production ortamında çalışmaz. Mevcut ayarlar ve yüklenmiş proje fotoğrafları korunur. Geçici iletişim numarası eklenmez. **SEO & site ayarları** bölümünden gerçek numara, e-posta, çalışma bölgesi, ilçe listesi ve anasayfa görseli değiştirilebilir. Numara boş veya bilinen geçici değerse WhatsApp bağlantıları gizlenir. Proje fotoğrafları **Projeler → Düzenle** ekranından yönetilir.

Test görselleri yerleşik `image_gen` ile üretildi: `uploads/demo/ahsap-kalip-test-hero.webp` ve `uploads/demo/ahsap-kalip-test-detay.webp`. İstemler: doğal ışıkta villa şantiyesinin ahşap kolon ve kiriş kalıpları; tahta kalıp köşesi, ahşap kuşak ve desteklerin yakın görünümü. İnsan, yazı, logo, makine ve ıslak beton istenmedi. Görseller gerçek iş referansı değildir; kendi fotoğraflarınızla değiştirilecektir. WebP boyutları 1536 × 1024 px; 640/960/1280/1536 px duyarlı türevleri vardır. İlçe adları [resmî ilçe listesiyle](https://istanbul.diyanet.gov.tr/adalar/Sayfalar/subsites.aspx?menucategory=Kurumsal2) karşılaştırıldı.

## Kurulum

1. XAMPP üzerinde Apache ve MySQL'i başlatın.
2. Git deposuna dahil edilmeyen `database.sql` kurulum dosyasını güvenli bir kanaldan temin edip yeni veritabanına phpMyAdmin üzerinden içe aktarın.
3. Gerekirse `SITE_URL`, `DB_HOST`, `DB_NAME`, `DB_USER` ve `DB_PASS` ortam değişkenlerini tanımlayın. Varsayılan değerler yerel XAMPP kurulumu içindir. `APP_ENV` varsayılanı `local` olur.
4. `C:/xampp/php/php.exe scripts/migrate.php` ile şemayı güncelleyin. Mevcut kurulumlarda SQL dosyasını yeniden içe aktarmayın.
5. `C:/xampp/php/php.exe scripts/prepare-seo-content.php` ile örnekleri işaretleyin ve üç karar rehberini ekleyin. Düzenlenmiş kayıtlar korunur; işlem tekrar çalıştırılabilir.
6. `http://localhost/insaat/` adresini açın.

## Yapı

- `app/`: bootstrap, yardımcılar, veritabanı ve repository katmanı
- `views/`: yalnızca sunum şablonları
- `partials/`: ortak layout parçaları
- `assets/`: CSS ve JavaScript
- `admin/`: CSRF korumalı içerik yönetimi

Yönetim paneli: `http://localhost/insaat/admin`

- Yeni kurulum e-postası: `admin@example.com`
- Şifre: `password`

Marka adı H&M Kaba Yapı olarak güncellenmiştir. Mevcut kurulumlarda yönetici giriş e-postası ve şifresi değişmez. Gerçek iletişim e-postasını SEO & Ayarlar bölümünden girin.

## İçerik ve SEO yönetimi

Panel, `productManagement` projesindeki görsel düzen esas alınarak yenilendi: beyaz sabit sol menü, yeşil vurgu renkleri, özet kartları, üst bilgi çubuğu ve mobil açılır menü. Panel stilleri ziyaretçi sitesinden bağımsızdır; mevcut giriş bilgileri ve içerikler korunur.

Projeler ve blog ekranları listeyle açılır. **Proje ekle / Yazı oluştur** düğmeleriyle yeni kayıt formuna, **Düzenle** bağlantısıyla mevcut kayıt formuna geçilir. Liste araması mevcut ekrandaki kayıtları filtreler. Mobil menü Esc tuşuyla veya kapatma düğmesiyle kapanır.

- **Hizmetler:** altı hizmet sayfasının içeriklerini, kapsam kartlarını, çalışma adımlarını, soru-cevaplarını ve SEO alanlarını düzenleyin. İstanbul kaba inşaat, radye temel, subasman, perde/istinat duvarı ve villa sayfaları kalıp, demir ve beton uygulamalarını anlatır. Tuğla duvar ve çatı işleri ayrı hizmet sayfasındadır. Hizmet açıklamasındaki ilk `##` öncesi metin girişte, sonraki başlıklı bölümler metraj/teklif rehberinde görünür. Mevcut sayfa adresleri korunur.
- **Hakkımızda:** `/hakkimizda` sayfası mevcut kullanıcı tercihiyle yalnızca tanıtım metni ve iletişim bağlantısını gösterir. **SEO & site ayarları → Hakkımızda** alanından metni değiştirin; görsel alanı boş kalmalıdır. Varsayılan içerik `app/about.php` içindedir; CLI migration eksik ayarları ekler, mevcut düzenlemeleri değiştirmez.
- **Projeler:** yapı türü, iş kapsamı, metraj, çalışma süreci, saha koşulları ve teslim sonucu alanlarını doldurun. İlgili hizmetleri seçerek karşılıklı bağlantı oluşturun.
- **Proje fotoğrafları:** Vitrin görseli hem proje listesinde hem detayın başında görünür. Yeni proje oluştururken veya düzenlerken “Diğer proje görselleri” alanından çoklu yükleme yapılabilir; mevcut fotoğraflara eklenir ve vitrinin altında küçük galeri olarak gösterilir. Galerideki “Vitrin yap” düğmesi seçilen fotoğrafla vitrini değiştirir; önceki vitrin fotoğrafını galeride korur.
- Şema güncellemesi yalnızca `scripts/migrate.php` komutuyla yapılır; normal ziyaretlerde DDL veya seed çalışmaz. Güncellemeden önce veritabanını yedekleyin.
- Ek kullanım ayrıntıları yerelde saklanan `SEO.md` belgesindedir; bu belge Git deposuna dahil edilmez.

İçerik kaynakları `app/content/core-services.php` ve `app/content/specialist-services.php` içindedir. **18.10.2026 güncel kapsamı kalıp, demir ve beton uygulamalarıdır.** `C:/xampp/php/php.exe scripts/update-service-content.php` bu kapsamın hizmet, tanıtıcı blog ve site açıklaması güncellemesini yerel kurulumda bir kez uygular; aynı sürüm tekrar çalıştırıldığında panel düzenlemelerini ezmez. Önceki içerikler ve ilişkiler `.private/backups/` altında yedeklenir.

Testler: `C:/xampp/php/php.exe tests/seo.php` ve `C:/xampp/php/php.exe tests/content-management.php`.

Galeri HTTP testi: `C:/xampp/php/php.exe tests/project-gallery.php`. Apache/MySQL açık olmalıdır. Geçici yönetici ve projeyle çoklu yükleme, vitrin seçimi, mevcut galeriye ekleme, liste/detay çıktısı ve hatalı yüklemede geri alma kontrol edilir. Geçici kayıtlar ve görseller temizlenir; Windows'ta Apache'nin yüklediği dosyaları temizlemek için yükseltilmiş terminal gerekebilir.

Kalıp ve demir işçilik hesabı: `/hesaplamalar/kaba-insaat-maliyeti`. Daire adedi × net daire alanı + toplam ortak alan + seçilmiş bodrum/otopark alanı veya hazır toplam alan girilebilir. Kat sayısı yalnızca bilgi amaçlıdır; alan yeniden kat sayısıyla çarpılmaz. Kalıp için bina alanı veya kullanıcıdan alınan kalıp yüzey metrajı; demir için bina alanı veya projedeki tonaj kullanılır. Birim fiyatlar kullanıcı tarafından girilir; metraj katsayısı veya hazır piyasa fiyatı yoktur. Malzemeler hesaba katılmaz. KDV isteğe bağlıdır ve oranı kullanıcı girer. Birim değişince ilgili fiyat temizlenir; geçersiz girişte eski sonuç ve teklif alanı kaldırılır. Matematik kontrolleri: `node tests/labor-calculator.js`; tarayıcı senaryoları `tests/public-browser.ps1 -CalculatorsOnly` içinde.

Ek doğrulamalar: `tests/seo-flow.php` (gerçek HTTP form akışı), `tests/editorial.php` (yazar/kaynak/ilişkiler), `tests/seo-environment.php` (ortam ve proxy), `tests/public-browser.ps1` (mobil/masaüstü, hesaplayıcı, izin akışı ve yerel laboratuvar). PHP testleri PHP CLI ile çalıştırılır. HTTP testinde Apache/MySQL açık olmalıdır. Form testi geçici mesajlarını sonunda siler.

SEO kontrol merkezi: `/admin?section=seo`. Tam tarama: `C:/xampp/php/php.exe scripts/seo-audit.php`; hızlı tarama için `--critical`. Raporlar HTTP erişimine kapalı `.private/reports/` altındadır. Günlük/haftalık Windows görevleri `scripts/install-seo-tasks.ps1` ile kurulur; XAMPP servisleri açık olmalıdır. Haftalık tam tarama kişisel alanlar içermeyen talep raporunu da üretir.

Yerelde indeksleme ve GA4 yüklemesi kapalıdır. Tarihli denetim ve yayın hazırlık raporları yerel çalışma dosyalarıdır; Git deposuna dahil edilmez.

Yönetici parolası **SEO & site ayarları → Yönetici parolası** formundan mevcut parola doğrulanarak değiştirilebilir; kurulum parolasıyla yayına çıkılmamalıdır. Yerel kullanıcı hesabı bu çalışma sırasında değiştirilmedi.

Yayın dosyası oluşturma: `powershell -NoProfile -ExecutionPolicy Bypass -File scripts/build-release.ps1`. Bu komut yalnızca ilgili PowerShell süreci için yürütme seçeneğini belirler; sistem politikasını değiştirmez. Doğrudan `.private/releases/` altında SHA256 manifestli kod/medya ZIP'i üretir. Paket; SQL, yedek, test, bakım betiği ve geliştirme raporlarını içermez. Veritabanı ayrı ve korumalı biçimde taşınır; hostingte DB ortam değişkenleri, gerçek HTTPS `SITE_URL` ve yayın koşulları ayrıca ayarlanmalıdır. Paket oluşturmak canlı yayın yapmaz.

Yerel Chrome arayüz testi: `tests/admin-browser.ps1`. Test hesabının şifresini `INSAAT_TEST_PASSWORD` ortam değişkeniyle verin; varsayılan test e-postası mevcut yerel yönetici hesabıdır, `-Email` ile değiştirilebilir. Araç giriş, altı bölümün masaüstü/mobil görünümü, liste araması, yeni kayıt formları, şifre gösterme ve hizmet satırı düğmelerini kontrol eder; içerik kaydetmez. Ekran görüntüleri geçici dizinde tutulur; izole tarayıcı profili test sonunda silinir.
