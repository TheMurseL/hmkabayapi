<section class="admin-section" id="project-form">
    <div class="section-heading">
        <div>
            <div class="eyebrow"><?= $editingProject ? 'Proje düzenle' : 'Yeni proje' ?></div>
            <h2><?= $editingProject ? 'Projeyi güncelle' : 'Proje ekle' ?></h2>
        </div>
        <a class="text-link" href="<?= e(url('admin?section=projects')) ?>">Proje listesine dön</a>
    </div>
    <form class="admin-panel" method="post" enctype="multipart/form-data">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="type" value="project">
        <input type="hidden" name="id" value="<?= (int) ($projectForm['id'] ?? 0) ?>">
        <div class="form-row">
            <label>
                Proje adı
                <input required name="title" value="<?= e($projectForm['title'] ?? '') ?>">
            </label>
            <label>
                Durum
                <select name="category">
                    <option value="ongoing" <?= ($projectForm['category'] ?? '') === 'ongoing' ? 'selected' : '' ?>>
                        Devam ediyor
                    </option>
                    <option value="completed" <?= ($projectForm['category'] ?? '') === 'completed' ? 'selected' : '' ?>>
                        Tamamlandı
                    </option>
                </select>
            </label>
        </div>
        <div class="form-row">
            <label>
                Lokasyon
                <input required name="location" value="<?= e($projectForm['location'] ?? '') ?>">
            </label>
            <label>
                Yıl
                <input name="year" value="<?= e($projectForm['year'] ?? '') ?>">
            </label>
            <label>
                Alan
                <input name="area" value="<?= e($projectForm['area'] ?? '') ?>">
            </label>
        </div>
        <label>
            Kısa açıklama
            <textarea required name="summary" rows="2"><?= e($projectForm['summary'] ?? '') ?></textarea>
        </label>
        <label>
            Detaylı açıklama
            <textarea required name="description" rows="5"><?= e($projectForm['description'] ?? '') ?></textarea>
        </label>
        <?php require __DIR__ . '/project-case.php'; ?>
        <div class="form-row">
            <label>
                Vitrin görseli
                <input type="file" name="image" accept="image/jpeg,image/png,image/webp">
            </label>
            <label>
                veya vitrin görseli URL'si
                <input name="image_url" value="<?= e($projectForm['image'] ?? '') ?>">
            </label>
        </div>
        <p class="project-image-help">Vitrin görseli proje listesinde ve detay sayfasının en başında gösterilir.</p>
        <?php if (!empty($projectForm['image'])): ?>
            <img
                class="admin-preview"
                src="<?= e(str_starts_with($projectForm['image'], 'uploads/') ? url($projectForm['image']) : $projectForm['image']) ?>"
                alt="Mevcut proje görseli"
            >
        <?php endif; ?>
        <label>
            Diğer proje görselleri
            <input type="file" name="gallery_images[]" accept="image/jpeg,image/png,image/webp" multiple>
            <small>Birden fazla fotoğraf seçebilirsin. Bu görseller vitrinin altında küçük galeri olarak gösterilir. Mevcut galeriye eklenir.</small>
        </label>
        <div class="form-row">
            <label>
                SEO başlığı
                <input name="seo_title" value="<?= e($projectForm['seo_title'] ?? '') ?>">
            </label>
            <label>
                SEO description
                <input name="seo_description" value="<?= e($projectForm['seo_description'] ?? '') ?>">
            </label>
        </div>
        <label>
            <input type="checkbox" name="featured" value="1" <?= !empty($projectForm['featured']) ? 'checked' : '' ?>>
            Anasayfada göster
        </label>
        <button class="button button-dark"><?= $editingProject ? 'Projeyi güncelle' : 'Projeyi kaydet' ?></button>
        <label><input type="checkbox" name="is_demo" value="1" <?= !empty($projectForm['is_demo']) ? 'checked' : '' ?>> Örnek proje; gerçek referans olarak kullanılmaz ve indekslenmez</label>
    </form>
</section>
<?php if ($editingProject): ?>
    <section class="admin-section">
        <div class="section-heading">
            <div>
                <div class="eyebrow">Proje galerisi</div>
                <h2>Galeri görselleri</h2>
            </div>
            <span>Birden fazla görsel seçebilirsin.</span>
        </div>
        <p class="project-image-help">Yeni fotoğrafları yukarıdaki “Diğer proje görselleri” alanından ekleyip projeyi güncelle. İstersen galerideki bir fotoğrafı vitrin yapabilirsin.</p>
        <?php if ($projectGallery): ?>
            <div class="gallery-admin-grid">
                <?php foreach ($projectGallery as $image): ?>
                    <div class="gallery-admin-item">
                        <img src="<?= e(str_starts_with($image['image'], 'uploads/') ? url($image['image']) : $image['image']) ?>" alt="Proje galerisi">
                        <form method="post">
                            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="type" value="project_cover">
                            <input type="hidden" name="id" value="<?= (int) $image['id'] ?>">
                            <input type="hidden" name="project_id" value="<?= (int) $editingProject ?>">
                            <button class="button button-dark">Vitrin yap</button>
                        </form>
                        <form method="post">
                            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="type" value="delete_gallery">
                            <input type="hidden" name="id" value="<?= (int) $image['id'] ?>">
                            <input type="hidden" name="project_id" value="<?= (int) $editingProject ?>">
                            <button class="delete" onclick="return confirm('Bu görsel silinsin mi?')">Görseli sil</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>
