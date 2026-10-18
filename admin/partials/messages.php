<section class="admin-section">
    <div class="section-heading">
        <div>
            <div class="eyebrow">Gelen kutusu</div>
            <h2>İletişim mesajları</h2>
        </div>
    </div>
    <?php $tableSearchLabel = 'Gönderen veya mesaj içinde ara'; ?>
    <?php require __DIR__ . '/table-search.php'; ?>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <tr>
                <th>Gönderen</th>
                <th>Mesaj</th>
                <th>Tarih</th>
                <th>İşlemler</th>
            </tr>
            <?php foreach ($messages as $message): ?>
                <tr class="<?= empty($message['is_read']) ? 'unread' : '' ?>">
                    <td><strong><?= e($message['name']) ?></strong><br><?= e($message['email']) ?><br><?= e($message['phone']) ?></td>
                    <td><?= e($message['message']) ?><br><small><?= e(implode(' · ', array_filter([$message['district'], $message['building_type'], $message['area'] ? $message['area'] . ' m²' : '', $message['stage']]))) ?></small><br><small>Kaynak sayfa: <?= e($message['source'] ?: 'Doğrudan / bilinmiyor') ?></small></td>
                    <td><?= display_date($message['created_at'], 'd.m.Y H:i') ?></td>
                    <td>
                        <form method="post">
                            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="type" value="message_action">
                            <input type="hidden" name="id" value="<?= (int) $message['id'] ?>">
                            <label>Talep aşaması<select name="lead_status"><?php foreach (['new' => 'Yeni', 'qualified' => 'Nitelikli', 'visit' => 'Keşif', 'quoted' => 'Teklif verildi', 'won' => 'Kazanıldı', 'lost' => 'Sonuçsuz'] as $value => $label): ?><option value="<?= e($value) ?>" <?= ($message['lead_status'] ?? 'new') === $value ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
                            <button name="action" value="read" class="delete">Kaydet / okundu</button>
                            <button name="action" value="delete" class="delete" onclick="return confirm('Bu mesaj silinsin mi?')">Sil</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</section>
