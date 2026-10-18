<section class="page-hero calculator-hero">
    <div class="container">
        <div class="eyebrow light">Projeniz için ön hesaplama</div>
        <h1>Kalıp ve demir<br><em>işçilik hesabı.</em></h1>
        <p>Bina bilgilerinizi veya hazır metrajınızı girin. Kendi birim fiyatlarınızla kalıp ve demir işçiliğini ayrı ayrı hesaplayın.</p>
    </div>
</section>
<section class="calculator-section labor-section">
    <div class="container labor-calculator" data-labor-calculator>
        <form class="calculator-card labor-form" data-labor-form novalidate>
            <div class="labor-form-heading">
                <div class="eyebrow">Projenizi tanımlayın</div>
                <h2>Bilgiler sizden,<br>hesabı bizden.</h2>
                <p class="calc-note">Fiyatları aldığınız işçilik teklifine göre girin. Hazır piyasa fiyatı veya gizli metraj katsayısı kullanılmaz.</p>
            </div>
            <fieldset class="calc-group">
                <legend><span>01</span> Bina ve alan bilgileri</legend>
                <div class="calc-grid">
                    <label class="calc-full">Alanı nasıl gireceksiniz?
                        <select name="mode" aria-controls="labor-building labor-manual">
                            <option value="building">Daire ve bina bilgileriyle</option>
                            <option value="manual">Hazır toplam alan ile</option>
                        </select>
                    </label>
                </div>
                <div id="labor-building" data-labor-group="building">
                    <div class="calc-grid">
                        <label>Toplam daire sayısı<input name="apartments" type="number" min="1" max="10000" step="1" value="8" required></label>
                        <label>Bir kattaki daire sayısı<input name="perFloor" type="number" min="1" max="1000" step="1" value="2" required></label>
                        <label>Bir dairenin net alanı (m²)<input name="apartmentArea" type="number" min="0.01" max="1000000" step="any" value="100" required></label>
                        <label>Toplam ortak alan (m²)<input name="commonArea" type="number" min="0" max="1000000" step="any" value="0" required><small>Tüm konut katlarındaki merdiven, sahanlık ve diğer ortak alanların toplamı.</small></label>
                    </div>
                    <label class="calc-toggle"><input name="basementEnabled" type="checkbox" aria-controls="labor-basement"> Bodrum / otopark alanı ekle</label>
                    <div id="labor-basement" class="calc-grid labor-extra" data-labor-group="basement" hidden>
                        <label class="calc-full">Toplam bodrum / otopark alanı (m²)<input name="basementArea" type="number" min="0.01" max="1000000" step="any" placeholder="Örneğin 200" required disabled><small>Ortak alan toplamına dahil ettiğiniz bir alanı tekrar eklemeyin.</small></label>
                    </div>
                </div>
                <div id="labor-manual" class="calc-grid" data-labor-group="manual" hidden>
                    <label class="calc-full">Hesaba esas toplam bina alanı (m²)<input name="area" type="number" min="0.01" max="1000000" step="any" value="500" required disabled><small>İşçilik teklifinizin esas aldığı, ortak alan ve bodrum dahil toplam alanı girin.</small></label>
                </div>
            </fieldset>
            <fieldset class="calc-group">
                <legend><span>02</span> Kalıp işçiliği</legend>
                <div class="calc-grid">
                    <label class="calc-full">Teklifiniz hangi metraja göre?
                        <select name="formworkBasis" aria-controls="labor-surface">
                            <option value="building">Bina alanı üzerinden (m²)</option>
                            <option value="surface">Kalıp yüzey alanı üzerinden (m²)</option>
                        </select>
                    </label>
                    <div id="labor-surface" class="calc-full" data-labor-group="surface" hidden>
                        <label>Kalıp yüzey alanı (m²)<input name="formworkArea" type="number" min="0.01" max="1000000" step="any" placeholder="Projedeki kalıp metrajı" required disabled></label>
                    </div>
                    <label class="calc-full"><span data-labor-formwork-label>Kalıp işçilik fiyatı (₺/m² bina alanı)</span><input name="formworkRate" type="number" min="0.01" max="1000000" step="any" placeholder="Teklifinizdeki birim fiyat" required></label>
                </div>
                <p class="calc-note">Bina alanı ile kalıp yüzey alanı farklıdır. Teklifinizdeki metraj türünü seçin; yüzey metrajı otomatik türetilmez.</p>
            </fieldset>
            <fieldset class="calc-group">
                <legend><span>03</span> Demir işçiliği</legend>
                <label class="calc-toggle labor-toggle-first"><input name="ironEnabled" type="checkbox" checked aria-controls="labor-iron"> Demir işçiliğini hesaba dahil et</label>
                <div id="labor-iron" class="calc-grid labor-extra" data-labor-group="iron">
                    <label class="calc-full">Demir işçiliği fiyatlandırması
                        <select name="ironBasis" aria-controls="labor-tonnes">
                            <option value="area">Bina alanı üzerinden (₺/m²)</option>
                            <option value="tonne">Demir miktarı üzerinden (₺/ton)</option>
                        </select>
                    </label>
                    <div id="labor-tonnes" class="calc-full" data-labor-group="tonnes" hidden>
                        <label>Projede belirtilen demir miktarı (ton)<input name="ironTonnes" type="number" min="0.001" max="100000" step="any" placeholder="Statik projedeki tonaj" required disabled></label>
                    </div>
                    <label class="calc-full"><span data-labor-iron-label>Demir işçilik fiyatı (₺/m² bina alanı)</span><input name="ironRate" type="number" min="0.01" max="1000000" step="any" placeholder="Teklifinizdeki birim fiyat" required></label>
                </div>
            </fieldset>
            <fieldset class="calc-group">
                <legend><span>04</span> Vergi tercihi</legend>
                <label class="calc-toggle labor-toggle-first"><input name="vatEnabled" type="checkbox" aria-controls="labor-vat"> Birim fiyatlar KDV hariç, KDV ekle</label>
                <div id="labor-vat" class="calc-grid labor-extra" data-labor-group="vat" hidden>
                    <label>KDV oranı (%)<input name="vatRate" type="number" min="0" max="100" step="any" placeholder="Teklifinizdeki oran" required disabled></label>
                </div>
                <p class="calc-note">Fiyatlarınız KDV dahilse bu seçeneği kapalı bırakın.</p>
            </fieldset>
            <p class="calc-error" data-labor-error role="alert" hidden></p>
            <div class="labor-actions">
                <button class="button button-dark" type="submit">İşçilik bedelini hesapla <span>↗</span></button>
                <button class="text-link labor-reset" type="reset">Sıfırla</button>
            </div>
            <noscript><p>Hesaplamak için JavaScript'i açın veya metraj ve fiyat bilgilerinizi iletişim formundan paylaşın.</p></noscript>
        </form>
        <aside class="labor-summary" aria-label="Hesaplama özeti">
            <div class="labor-summary-card">
                <div class="eyebrow light">Hesaplama özeti</div>
                <h2>İşçiliğinizi<br>kalem kalem görün.</h2>
                <div class="labor-project-facts">
                    <div><span>Hesaba esas toplam alan</span><strong data-labor-out="area">—</strong></div>
                    <div data-labor-floor-fact><span>Konut katı / daire</span><strong data-labor-out="building">—</strong></div>
                </div>
                <div class="labor-line">
                    <div><h3>Kalıp işçiliği</h3><span data-labor-out="formworkDetail">Metraj ve birim fiyat bekleniyor.</span></div>
                    <strong data-labor-out="formworkCost">—</strong>
                </div>
                <div class="labor-line" data-labor-iron-line>
                    <div><h3>Demir işçiliği</h3><span data-labor-out="ironDetail">Metraj ve birim fiyat bekleniyor.</span></div>
                    <strong data-labor-out="ironCost">—</strong>
                </div>
                <dl class="labor-subtotals">
                    <div><dt>İşçilik ara toplamı</dt><dd data-labor-out="subtotal">—</dd></div>
                    <div><dt data-labor-vat-label>KDV</dt><dd data-labor-out="vat">Eklenmedi</dd></div>
                </dl>
                <div class="labor-total" role="status" aria-live="polite" aria-atomic="true">
                    <span>Tahmini işçilik toplamı</span>
                    <strong data-labor-out="total">—</strong>
                    <p data-labor-out="status">Toplamı görmek için birim fiyatları girin.</p>
                </div>
                <dl class="labor-unit-costs">
                    <div><dt>Bina m² başına</dt><dd data-labor-out="perSquareMetre">—</dd></div>
                    <div data-labor-apartment-fact><dt>Daire başına ortalama</dt><dd data-labor-out="perApartment">—</dd></div>
                </dl>
                <a class="button button-light" data-labor-quote href="<?= e(url('iletisim?from=hesaplamalar/kaba-insaat-maliyeti')) ?>">Projem için teklif iste <span>↗</span></a>
            </div>
            <div class="labor-scope">
                <h3>Hesaba neler dahil?</h3>
                <p>Yalnızca seçtiğiniz kalıp ve demir işçilikleri. Beton, demir ve kalıp malzemesi, nakliye, tuğla ve diğer işler bu toplama dahil değildir.</p>
                <p>Daire alanları, toplam ortak alan ve varsa bodrum / otopark alanı toplanır. Kat sayısı alanı tekrar çarpmaz. Hesaba esas alan, resmi brüt alan veya statik metraj yerine geçmez.</p>
                <p>Sonuç bağlayıcı teklif değildir. İş kapsamı ve metraj, proje incelenerek netleştirilir.</p>
            </div>
        </aside>
    </div>
</section>
<section class="calculator-faq container">
    <h2>Hesaplamayla ilgili sorular</h2>
    <details><summary>Kalıp yüzey alanı ile bina alanı aynı mı?</summary><p>Hayır. Kalıp yüzey alanı, kalıp yapılacak yüzeylerin projeden ölçülen toplamıdır. İşçilik anlaşmanız bina alanına veya kalıp yüzeyine göre olabilir; fiyatın birimi ile seçilen metraj aynı olmalıdır.</p></details>
    <details><summary>Demir işçiliği nasıl hesaplanır?</summary><p>Teklifinizin birimine göre bina alanı × m² işçilik fiyatı veya projedeki demir tonajı × ton başına işçilik fiyatı kullanılır. Tonaj bina alanından tahmin edilmez; statik projeden alınmalıdır.</p></details>
    <details><summary>Otopark ve ortak alanları nasıl eklemeliyim?</summary><p>Tüm konut katlarındaki ortak alanların toplamını ilgili alana yazın. Bodrum ve otopark alanını ayrıca ekleyebilirsiniz; aynı alanı iki kez dahil etmeyin. Hazır alan yönteminde bu bölümler zaten toplamda olmalıdır.</p></details>
    <details><summary>Teklif için hangi bilgiler gerekir?</summary><p>Projenin konumunu, mimari ve statik çizimlerini, metrajlarını, yapılacak işin kapsamını ve hedef başlangıç tarihini paylaşın.</p></details>
    <a class="text-link" href="<?= e(url('istanbul-kaba-insaat')) ?>">İstanbul kaba inşaat <span>↗</span></a>
</section>
<?php require __DIR__ . '/../partials/related-links.php'; ?>
