<section class="admin-section">
    <div class="section-heading">
        <div>
            <div class="eyebrow">Hizmet içerikleri</div>
            <h2>Hizmet sayfaları</h2>
        </div>
    </div>
    <?php $tableSearchLabel = 'Hizmet adı veya sayfa adresi ara'; ?>
    <?php require __DIR__ . '/table-search.php'; ?>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Başlık</th>
                    <th>Sayfa adresi</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($services as $item): ?>
                    <tr>
                        <td><?= e($item['title']) ?></td>
                        <td>/<?= e($item['slug']) ?></td>
                        <td class="actions">
                            <a href="<?= e(url('admin?section=services&edit_service=' . $item['id'])) ?>">Düzenle</a>
                            <a href="<?= e(url($item['slug'])) ?>" target="_blank" rel="noopener">Sayfayı görüntüle</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php if ($serviceForm): ?>
    <section class="admin-section">
        <div class="section-heading">
            <h2><?= e($serviceForm['title']) ?> içeriğini düzenle</h2>
        </div>
        <form class="admin-panel" method="post">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="type" value="service">
            <input type="hidden" name="id" value="<?= (int) $serviceForm['id'] ?>">
            <label>
                Sayfa adresi (mevcut SEO bağlantılarını korumak için sabit)
                <input readonly value="<?= e(url($serviceForm['slug'])) ?>">
            </label>
            <label>
                Hizmet başlığı
                <input required name="title" maxlength="180" value="<?= e($serviceForm['title']) ?>">
            </label>
            <label>
                Üst alan özeti
                <textarea required name="description" maxlength="2000" rows="3"><?= e($serviceForm['description']) ?></textarea>
            </label>
            <label>
                Hizmet açıklaması
                <textarea required name="intro" maxlength="20000" rows="7"><?= e($serviceForm['intro']) ?></textarea>
            </label>
            <p>Paragrafları boş satırla ayırın. Alt başlık için ayrı bir satırda ## veya ### kullanabilirsiniz.</p>
            <?php foreach (['sections' => 'Uygulama kapsamı', 'process' => 'Çalışma adımları', 'questions' => 'Sık sorulan sorular'] as $field => $groupTitle): ?>
                <fieldset class="content-fieldset" data-repeater="<?= e($field) ?>">
                    <legend><?= e($groupTitle) ?></legend>
                    <div data-repeater-rows>
                        <?php foreach ($serviceForm[$field] as $rowIndex => [$heading, $text]): ?>
                            <div class="content-row" data-repeater-row>
                                <label>
                                    <?= $field === 'questions' ? 'Soru' : 'Başlık' ?>
                                    <input name="<?= e($field) ?>[<?= (int) $rowIndex ?>][0]" maxlength="300" value="<?= e($heading) ?>" data-pair="0">
                                </label>
                                <label>
                                    <?= $field === 'questions' ? 'Cevap' : 'Açıklama' ?>
                                    <textarea name="<?= e($field) ?>[<?= (int) $rowIndex ?>][1]" maxlength="10000" rows="3" data-pair="1"><?= e($text) ?></textarea>
                                </label>
                                <button type="button" class="content-action" data-remove-row>Satırı kaldır</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <template>
                        <div class="content-row" data-repeater-row>
                            <label>
                                <?= $field === 'questions' ? 'Soru' : 'Başlık' ?>
                                <input maxlength="300" data-pair="0">
                            </label>
                            <label>
                                <?= $field === 'questions' ? 'Cevap' : 'Açıklama' ?>
                                <textarea maxlength="10000" rows="3" data-pair="1"></textarea>
                            </label>
                            <button type="button" class="content-action" data-remove-row>Satırı kaldır</button>
                        </div>
                    </template>
                    <button type="button" class="content-action" data-add-row>Yeni satır ekle</button>
                </fieldset>
            <?php endforeach; ?>
            <fieldset class="content-fieldset">
                <legend>SEO alanları</legend>
                <p>Boş bırakılırsa hizmet başlığı ve üst alan özeti kullanılır. Firma adı başlığa otomatik eklenir.</p>
                <label>
                    SEO başlığı
                    <input name="seo_title" maxlength="180" value="<?= e($serviceForm['seo_title'] ?? '') ?>">
                </label>
                <label>
                    SEO açıklaması
                    <textarea name="seo_description" maxlength="320" rows="3"><?= e($serviceForm['seo_description'] ?? '') ?></textarea>
                </label>
            </fieldset>
            <button class="button button-dark">Hizmeti güncelle</button>
        </form>
    </section>
<?php endif; ?>
