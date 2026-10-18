<?php
$isBrick = $calculator === 'tugla-fiyat-hesaplama';
if (!$isBrick) {
    require __DIR__ . '/calculator-labor.php';
    return;
}
$verifiedRate = site_setting('calc_rates_verified') === '1' && site_setting('calc_rate_date') !== '' && site_setting('calc_rate_source') !== '';
$costRate = $verifiedRate ? site_setting('calc_cost_standard') : '';
$brickPrice = $verifiedRate ? site_setting('calc_brick_price') : '';
$brickLabor = $verifiedRate ? site_setting('calc_brick_labor') : '';
?>
<section class="page-hero calculator-hero"><div class="container">
    <div class="eyebrow light">Projeniz için ön hesaplama</div>
    <h1><?= $isBrick ? 'Tuğla adedi ve<br><em>maliyet hesabı.</em>' : 'Kalıp işçiliği<br><em>maliyet hesabı.</em>' ?></h1>
    <p><?= $isBrick ? 'Duvar ölçülerini girin, kapı ve pencere boşluklarını düşün. Tuğla türü ve örüm şekline göre gereken adedi, dilerseniz maliyeti hesaplayın.' : 'Kalıp yapılacak yüzeylerin alanını ve m² işçilik bedelini girin.' ?></p>
</div></section>
<section class="calculator-section"><div class="container calculator-layout">
    <div class="calculator-card" data-calculator="<?= $isBrick ? 'brick' : 'cost' ?>">
        <h2><?= $isBrick ? 'Duvarınızı hesaplayın' : 'Alan ve birim fiyat bilgileri' ?></h2>
        <?php if ($isBrick): ?>
            <fieldset class="calc-group">
                <legend><span>01</span> Duvar ölçüleri</legend>
                <div class="calc-grid">
                    <label class="calc-full">Alanı nasıl gireceksiniz?
                        <select data-brick-mode>
                            <option value="dimensions">Duvar eni ve yüksekliği ile</option>
                            <option value="area">Hazır net duvar alanı ile</option>
                        </select>
                    </label>
                    <label data-brick-dimensions>Duvar eni (m)<input type="number" min="0.01" max="10000" step="any" value="5" required data-brick-width></label>
                    <label data-brick-dimensions>Duvar yüksekliği (m)<input type="number" min="0.01" max="1000" step="any" value="3" required data-brick-height></label>
                    <label class="calc-full" data-brick-net-area hidden>Net duvar alanı (m²)<input type="number" min="0.01" max="1000000" step="any" value="100" required data-brick-area><small>Kapı ve pencere boşlukları çıkarılmış alanı girin.</small></label>
                </div>
                <div data-brick-opening-controls>
                    <label class="calc-toggle"><input type="checkbox" data-brick-has-openings aria-controls="brick-openings"> Kapı veya pencere boşluğu düş</label>
                    <div id="brick-openings" class="calc-openings" hidden>
                        <p class="calc-note">Farklı ölçüdeki her boşluğu ayrı ekleyin. Aynı ölçüdeki boşluklar için adedi değiştirin.</p>
                        <div data-brick-openings></div>
                        <button type="button" class="calc-add" data-brick-add-opening>+ Kapı / pencere ekle</button>
                    </div>
                </div>
            </fieldset>
            <template data-brick-opening-template>
                <div class="calc-opening">
                    <div class="calc-opening-heading"><strong data-opening-heading></strong><button type="button" class="calc-remove" data-brick-remove-opening>Kaldır</button></div>
                    <div class="calc-grid calc-opening-grid">
                        <label>En (m)<input type="number" min="0.01" max="10000" step="any" value="1" required data-opening-width></label>
                        <label>Yükseklik (m)<input type="number" min="0.01" max="1000" step="any" value="2.1" required data-opening-height></label>
                        <label>Adet<input type="number" min="1" max="10000" step="1" value="1" required data-opening-count></label>
                    </div>
                </div>
            </template>
            <fieldset class="calc-group">
                <legend><span>02</span> Tuğla ve örüm şekli</legend>
                <div class="calc-grid">
                    <label>Tuğla türü
                        <select data-brick-type>
                            <option value="8.5" data-rate="25" data-thickness="8,5" data-alternate-rate="62" data-alternate-thickness="19">8,5'luk — 19 × 19 × 8,5 cm</option>
                            <option value="10" data-rate="25" data-thickness="10" data-alternate-rate="40" data-alternate-thickness="19">10'luk kısa — 19 × 10 × 19 cm</option>
                            <option value="10-long" data-rate="20" data-thickness="10" data-alternate-rate="40" data-alternate-thickness="20">10'luk uzun — 25 × 10 × 19 cm</option>
                            <option value="13.5" data-rate="25" data-thickness="13,5" data-alternate-rate="35" data-alternate-thickness="19" selected>13,5'luk — 19 × 19 × 13,5 cm</option>
                            <option value="custom">Diğer ürün / kendi tüketim değerim</option>
                        </select>
                    </label>
                    <label data-brick-bond-label>Örüm şekli
                        <select data-brick-bond>
                            <option value="standard">Dik örüm — 13,5 cm duvar</option>
                            <option value="alternate">Yatık örüm — 19 cm duvar</option>
                        </select>
                    </label>
                    <label>Tüketim (adet/m²)<input type="number" min="0.01" max="1000" step="any" value="25" readonly required data-brick-size></label>
                    <label>Fire payı (%)<input type="number" min="0" max="50" step="any" value="5" required data-brick-waste></label>
                </div>
                <p class="calc-note" data-brick-consumption-note>Seçilen ürün ve örüm şekline göre 25 adet/m² kullanılır. Farklı üretici değeri için “Diğer ürün” seçin.</p>
            </fieldset>
            <fieldset class="calc-group">
                <legend><span>03</span> Maliyet <small>İsteğe bağlı</small></legend>
                <div class="calc-grid">
                    <label>Tuğla birim fiyatı (₺/adet)<input type="number" min="0" max="1000000" step="any" value="<?= e($brickPrice) ?>" placeholder="Tedarikçi fiyatınız" data-brick-price></label>
                    <label>İşçilik (₺/m²)<input type="number" min="0" max="1000000" step="any" value="<?= e($brickLabor) ?>" placeholder="Uygulamacı fiyatınız" data-brick-labor></label>
                </div>
                <p class="calc-note">Tuğla adedini hesaplamak için fiyat girmeniz gerekmez.</p>
            </fieldset>
            <p class="calc-error" role="alert" data-brick-error hidden></p>
        <?php else: ?>
        <div class="calc-grid">
            <label>Kalıp yüzey alanı (m²)<input type="number" min="0.1" max="1000000" step="0.1" value="500" data-cost-area></label>
            <label>Kalıp işçilik fiyatı (₺/m²)<input type="number" min="0.01" max="1000000" step="0.01" value="<?= e($costRate) ?>" placeholder="İşçilik birim fiyatınız" data-cost-level></label>
        </div>
        <?php endif; ?>
        <button type="button" class="button button-dark" data-calculate>Hesapla</button>
        <div class="calc-result" role="status" aria-live="polite">
            <small><?= $isBrick ? 'Fire dahil alınacak tuğla' : 'Girilen değerlere göre yaklaşık toplam' ?></small>
            <strong data-calc-total>—</strong>
            <span data-calc-detail>Hesaplamak için alanları doldurun.</span>
            <?php if ($isBrick): ?>
                <dl class="calc-breakdown">
                    <div><dt>Brüt duvar alanı</dt><dd data-brick-gross>—</dd></div>
                    <div><dt>Kapı / pencere boşlukları</dt><dd data-brick-opening-area>—</dd></div>
                    <div><dt>Net örülecek alan</dt><dd data-brick-net>—</dd></div>
                    <div><dt>Fire hariç tuğla</dt><dd data-brick-base>—</dd></div>
                    <div><dt>Fire için ek tuğla</dt><dd data-brick-extra>—</dd></div>
                    <div><dt>Tuğla bedeli</dt><dd data-brick-material-cost>—</dd></div>
                    <div><dt>İşçilik bedeli</dt><dd data-brick-labor-cost>—</dd></div>
                    <div class="calc-breakdown-total"><dt>Tuğla + işçilik toplamı</dt><dd data-brick-cost>—</dd></div>
                </dl>
            <?php endif; ?>
        </div>
        <a class="button button-dark" data-quote-link href="<?= e(url('iletisim?from=hesaplamalar/' . $calculator)) ?>">Bu proje için teklif isteyin <span>↗</span></a>
        <noscript><p>Hesaplama için JavaScript'i açın veya alan ve birim fiyat bilgilerinizi iletişim formundan gönderin.</p></noscript>
    </div>
    <aside class="calculator-note">
        <h2>Hesabın kapsamı</h2>
        <?php if ($verifiedRate): ?><p>Varsayılan fiyatların tarihi: <?= e(site_setting('calc_rate_date')) ?>. Kaynak: <?= e(site_setting('calc_rate_source')) ?>.</p><?php else: ?><p>Hazır piyasa fiyatı kullanılmaz. Tedarikçinizden veya uygulamacınızdan aldığınız birim fiyatı girin.</p><?php endif; ?>
        <p><?= $isBrick ? 'Net alan = duvar eni × yüksekliği − her kapı/pencerenin eni × yüksekliği × adedi. Gerekli tuğla = net alan × tüketim × (1 + fire / 100). Son adet yukarı yuvarlanır.' : 'İşçilik bedeli = kalıp yüzey alanı × m² işçilik fiyatı.' ?></p>
        <?php if ($isBrick): ?>
            <p>Tuğla bedeli fire dahil adet üzerinden, işçilik net örülecek alan üzerinden hesaplanır. İki fiyat da girildiğinde toplam gösterilir.</p>
            <p>Örneğin 5 × 3 m duvar ve 1 × 2,1 m kapı için net alan 12,9 m² olur. 25 adet/m² tüketim ve %5 fire ile 339 tuğla gerekir.</p>
            <p>Hazır tüketimler belirtilen ürün ölçüsü ve duvar kalınlığı içindir. Kaynak: <a href="https://www.kudret.com/urunlerimiz/yatay-delikli-tuglalar/" target="_blank" rel="noopener noreferrer">Kudret Tuğla ürün teknik bilgileri</a>. Başka bir ürün için üreticinin adet/m² değerini girin.</p>
        <?php endif; ?>
        <p><?= e(site_setting('calc_rate_scope') ?: ($isBrick ? 'Harç, nakliye ve diğer ek bedeller ayrıca hesaplanmaz. Birim fiyatınızın kapsamını kontrol edin.' : 'Bu hesap yalnızca girilen kalıp işçilik bedelini gösterir. Malzeme, demir, tuğla ve çatı işleri ayrıca değerlendirilir.')) ?></p>
        <p><?= $isBrick ? 'Fire payı kesim ve kırılma için eklenir. Varsayılan %5 değerini ihtiyacınıza göre değiştirebilirsiniz. Örüm şekli duvar kalınlığını ve m² başına tuğla adedini değiştirir.' : 'Kalıp yüzey alanını projeden belirleyin; arsa veya kat alanını kalıp metrajı yerine kullanmayın.' ?></p>
        <p>Bu sonuç bağlayıcı teklif değildir. İş kapsamı, metraj ve iş programı proje üzerinden netleştirilir.</p>
    </aside>
</div></section>
<section class="calculator-faq container"><h2>Sık sorulanlar</h2>
    <?php if ($isBrick): ?>
        <details><summary>1 m² duvar için kaç tuğla gerekir?</summary><p>Adet, tuğlanın boyutuna ve örüm yönüne bağlıdır. Bu hesapta 19 × 19 cm yüzüyle örülen 8,5'luk ve 13,5'luk ürünler için 25 adet/m² kullanılır. 13,5'luk tuğlanın yatık örümünde 19 cm duvar için tüketim 35 adet/m² olur. Kullandığınız ürünün teknik bilgisi esas alınmalıdır.</p></details>
        <details><summary>Kapı ve pencere boşlukları nasıl düşülür?</summary><p>Her boşluğun eni ve yüksekliği çarpılır; aynı ölçüden birden fazla varsa adetle çarpılır. Bu alanlar toplanarak brüt duvar alanından çıkarılır. Farklı boşlukların enlerini ve yüksekliklerini ayrı ayrı toplayıp çarpmayın.</p></details>
        <details><summary>Fiyat girmeden tuğla adedi hesaplanır mı?</summary><p>Evet. Fiyat alanlarını boş bırakabilirsiniz. Tuğla bedeli için adet fiyatını, işçilik bedeli için m² fiyatını girin. Toplam maliyet iki fiyat da girildiğinde gösterilir.</p></details>
    <?php else: ?>
        <details><summary>Kalıp işçilik fiyatı nasıl belirlenir?</summary><p>Kalıp metrajı, proje detayları ve yapılacak iş incelenerek teklif verilir. Hesaplayıcıda kendi teklifinizdeki m² işçilik fiyatını kullanın.</p></details>
    <?php endif; ?>
    <details><summary>Teklif için ne paylaşmalıyım?</summary><p>Projenin konumunu, çizimlerini, yapılacak işi ve hedef başlangıç tarihini paylaşın.</p></details>
    <a class="text-link" href="<?= e(url('istanbul-kaba-insaat')) ?>">İstanbul kaba inşaat</a>
</section>
<?php require __DIR__ . '/../partials/related-links.php'; ?>
