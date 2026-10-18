<section class="admin-section" id="post-form">
    <div class="section-heading">
        <div>
            <div class="eyebrow"><?= $editingPost ? 'Blog yazısı düzenle' : 'Yeni blog yazısı' ?></div>
            <h2><?= $editingPost ? 'Yazıyı güncelle' : 'Blog yazısı ekle' ?></h2>
        </div>
        <a class="text-link" href="<?= e(url('admin?section=blog')) ?>">Blog listesine dön</a>
    </div>
    <form class="admin-panel" method="post" enctype="multipart/form-data">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="type" value="post">
        <input type="hidden" name="id" value="<?= (int) ($postForm['id'] ?? 0) ?>">
        <label>
            Başlık
            <input required name="title" value="<?= e($postForm['title'] ?? '') ?>">
        </label>
        <label>
            Kısa açıklama
            <textarea required name="excerpt" rows="3"><?= e($postForm['excerpt'] ?? '') ?></textarea>
        </label>
        <label>
            İçerik
            <textarea required name="content" rows="9"><?= e($postForm['content'] ?? '') ?></textarea>
        </label>
        <p>Paragrafları boş satırla ayırın. Başlık: ## Başlık, alt başlık: ### Başlık. Liste: her satırda - Madde. Bağlantı: [İstanbul kalıp işçiliği](/istanbul-kaba-insaat). HTML çalıştırılmaz.</p>
        <label>Hedef arama niyeti / ana sorgu<input name="search_intent" maxlength="180" value="<?= e($postForm['search_intent'] ?? '') ?>"></label>
        <div class="form-row"><label>Yazar adı (gerçek kişi, isteğe bağlı)<input name="author_name" maxlength="180" value="<?= e($postForm['author_name'] ?? '') ?>"></label><label>İnceleyen kişi (isteğe bağlı)<input name="reviewer_name" maxlength="180" value="<?= e($postForm['reviewer_name'] ?? '') ?>"></label></div>
        <label>Gerçek inceleme tarihi<input type="date" name="reviewed_at" max="<?= display_date('now', 'Y-m-d') ?>" value="<?= e($postForm['reviewed_at'] ?? '') ?>"></label>
        <label>Kaynaklar (her satırda bir http/https URL)<textarea name="sources" rows="4"><?= e($postForm['sources'] ?? '') ?></textarea></label>
        <fieldset><legend>İlgili hizmetler</legend><?php foreach ($services as $related): ?><label><input type="checkbox" name="post_service_ids[]" value="<?= (int) $related['id'] ?>" <?= in_array($related['id'], $postServiceIds) ? 'checked' : '' ?>> <?= e($related['title']) ?></label><?php endforeach; ?></fieldset>
        <fieldset><legend>İlgili gerçek projeler</legend><?php foreach ($projects as $related): ?><label><input type="checkbox" name="post_project_ids[]" value="<?= (int) $related['id'] ?>" <?= in_array($related['id'], $postProjectIds) ? 'checked' : '' ?>> <?= e($related['title']) ?></label><?php endforeach; ?></fieldset>
        <div class="form-row">
            <label>
                Fotoğraf yükle
                <input type="file" name="image" accept="image/jpeg,image/png,image/webp">
            </label>
            <label>
                veya görsel URL
                <input name="image_url" value="<?= e($postForm['image'] ?? '') ?>">
            </label>
        </div>
        <?php if (!empty($postForm['image'])): ?>
            <img
                class="admin-preview"
                src="<?= e(str_starts_with($postForm['image'], 'uploads/') ? url($postForm['image']) : $postForm['image']) ?>"
                alt="Mevcut blog görseli"
            >
        <?php endif; ?>
        <div class="form-row">
            <label>
                SEO başlığı
                <input name="seo_title" value="<?= e($postForm['seo_title'] ?? '') ?>">
            </label>
            <label>
                SEO description
                <input name="seo_description" value="<?= e($postForm['seo_description'] ?? '') ?>">
            </label>
        </div>
        <label>
            <input type="checkbox" name="published" value="1" <?= !empty($postForm['published']) ? 'checked' : '' ?>>
            Yayında
        </label>
        <button class="button button-dark"><?= $editingPost ? 'Yazıyı güncelle' : 'Yazıyı kaydet' ?></button>
    </form>
</section>
