<fieldset class="content-fieldset">
    <legend>Proje vaka çalışması</legend>
    <p>Yalnızca gerçek proje bilgilerini girin. Boş alanlar sitede gösterilmez. Paragrafları boş satırla ayırabilirsiniz.</p>
    <?php foreach (ProjectRepository::CASE_FIELDS as $field => $label): ?>
        <label>
            <?= e($label) ?>
            <?php if ($field === 'building_type'): ?>
                <input name="<?= e($field) ?>" maxlength="180" value="<?= e($projectForm[$field] ?? '') ?>" placeholder="Örnek: Villa, konut, ticari yapı">
            <?php else: ?>
                <textarea name="<?= e($field) ?>" rows="4"><?= e($projectForm[$field] ?? '') ?></textarea>
            <?php endif; ?>
        </label>
    <?php endforeach; ?>
</fieldset>
<fieldset class="content-fieldset">
    <legend>İlgili hizmetler</legend>
    <p>Bu projede uygulanan hizmetleri seçin. Hizmet ve proje sayfaları karşılıklı bağlantı verecektir.</p>
    <div class="service-checkboxes">
        <?php foreach ($services as $serviceOption): ?>
            <label>
                <input type="checkbox" name="service_ids[]" value="<?= (int) $serviceOption['id'] ?>" <?= in_array($serviceOption['id'], $selectedServiceIds) ? 'checked' : '' ?>>
                <?= e($serviceOption['title']) ?>
            </label>
        <?php endforeach; ?>
    </div>
</fieldset>
