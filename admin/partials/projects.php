<?php if ($editingProject || $creatingProject || $submittedProject): ?>
    <?php require __DIR__ . '/project-form.php'; ?>
<?php endif; ?>
<section class="admin-section">
    <div class="section-heading">
        <div>
            <div class="eyebrow">İçerik listesi</div>
            <h2>Projeler</h2>
        </div>
        <a class="button button-dark" href="<?= e(url('admin?section=projects&new_project=1')) ?>"><?= admin_icon('plus') ?> Proje ekle</a>
    </div>
    <?php $tableSearchLabel = 'Proje adı veya lokasyon ara'; ?>
    <?php require __DIR__ . '/table-search.php'; ?>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <tr>
                <th>Başlık</th>
                <th>Durum</th>
                <th>Görsel</th>
                <th>İşlemler</th>
            </tr>
            <?php foreach ($projects as $item): ?>
                <tr>
                    <td>
                        <a class="table-title" href="<?= e(url('admin?section=projects&edit_project=' . $item['id'])) ?>"><?= e($item['title']) ?></a>
                        <small class="table-subtitle"><?= e($item['location']) ?></small>
                    </td>
                    <td><span class="badge-soft <?= $item['category'] === 'ongoing' ? 'warning' : 'success' ?>"><?= $item['category'] === 'ongoing' ? 'Devam ediyor' : 'Tamamlandı' ?></span></td>
                    <td>
                        <?php if ($item['image']): ?>
                            <img class="table-thumb" loading="lazy" src="<?= e(project_image($item['image'])) ?>" alt="<?= e($item['title']) ?>">
                        <?php else: ?>
                            <span class="table-subtitle">Yok</span>
                        <?php endif; ?>
                    </td>
                    <td class="actions">
                        <a href="<?= e(url('admin?section=projects&edit_project=' . $item['id'])) ?>">Düzenle</a>
                        <form method="post">
                            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="type" value="delete_project">
                            <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                            <button class="delete" onclick="return confirm('Bu proje silinsin mi?')">Sil</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</section>
