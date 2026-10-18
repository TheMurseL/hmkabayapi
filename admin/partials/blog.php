<?php if ($editingPost || $creatingPost): ?>
    <?php require __DIR__ . '/post-form.php'; ?>
<?php endif; ?>
<section class="admin-section">
    <div class="section-heading">
        <div>
            <div class="eyebrow">İçerik listesi</div>
            <h2>Blog yazıları</h2>
        </div>
        <a class="button button-dark" href="<?= e(url('admin?section=blog&new_post=1')) ?>"><?= admin_icon('plus') ?> Yazı oluştur</a>
    </div>
    <?php $tableSearchLabel = 'Blog başlığı veya durum ara'; ?>
    <?php require __DIR__ . '/table-search.php'; ?>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <tr>
                <th>Başlık</th>
                <th>Durum</th>
                <th>Görsel</th>
                <th>İşlemler</th>
            </tr>
            <?php foreach ($posts as $item): ?>
                <tr>
                    <td><a class="table-title" href="<?= e(url('admin?section=blog&edit_post=' . $item['id'])) ?>"><?= e($item['title']) ?></a></td>
                    <td><span class="badge-soft <?= !empty($item['published']) ? 'success' : 'warning' ?>"><?= !empty($item['published']) ? 'Yayında' : 'Taslak' ?></span></td>
                    <td>
                        <?php if ($item['image']): ?>
                            <img class="table-thumb" loading="lazy" src="<?= e(blog_image($item['image'])) ?>" alt="<?= e($item['title']) ?>">
                        <?php else: ?>
                            <span class="table-subtitle">Yok</span>
                        <?php endif; ?>
                    </td>
                    <td class="actions">
                        <a href="<?= e(url('admin?section=blog&edit_post=' . $item['id'])) ?>">Düzenle</a>
                        <form method="post">
                            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="type" value="delete_post">
                            <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                            <button class="delete" onclick="return confirm('Bu yazı silinsin mi?')">Sil</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</section>
