<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}
require_once __DIR__ . '/../app/bootstrap.php';
if (config('app_env') === 'production') {
    throw new RuntimeException('Önizleme içerikleri canlı ortamda hazırlanmaz.');
}

// Optional local preview data. Existing settings and uploaded project photos are preserved.
$districts = [
    'Adalar', 'Arnavutköy', 'Ataşehir', 'Avcılar', 'Bağcılar', 'Bahçelievler',
    'Bakırköy', 'Başakşehir', 'Bayrampaşa', 'Beşiktaş', 'Beykoz', 'Beylikdüzü',
    'Beyoğlu', 'Büyükçekmece', 'Çatalca', 'Çekmeköy', 'Esenler', 'Esenyurt',
    'Eyüpsultan', 'Fatih', 'Gaziosmanpaşa', 'Güngören', 'Kadıköy', 'Kağıthane',
    'Kartal', 'Küçükçekmece', 'Maltepe', 'Pendik', 'Sancaktepe', 'Sarıyer',
    'Silivri', 'Sultanbeyli', 'Sultangazi', 'Şile', 'Şişli', 'Tuzla',
    'Ümraniye', 'Üsküdar', 'Zeytinburnu',
];
$heroImage = 'uploads/demo/ahsap-kalip-test-hero.webp';
$detailImage = 'uploads/demo/ahsap-kalip-test-detay.webp';
$defaults = [
    'service_areas' => 'İstanbul’un tüm ilçeleri',
    'service_districts' => implode("\n", $districts),
    'hero_image' => $heroImage,
];
foreach ([$heroImage, $detailImage] as $image) {
    if (!is_file(__DIR__ . '/../' . $image)) {
        throw new RuntimeException('Test görseli bulunamadı: ' . $image);
    }
}
$pdo = db();
$pdo->beginTransaction();
try {
    $insert = $pdo->prepare('INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES (?, ?)');
    foreach ($defaults as $key => $value) {
        $insert->execute([$key, $value]);
    }

    // Replace only known stock covers of the three installation examples.
    $exampleCovers = [
        'cinar-evleri' => ['photo-1600585154340-be6161a56a0c', $heroImage],
        'marmara-konutlari' => ['photo-1600607687939-ce8a6c25118c', $detailImage],
        'koycegiz-villalari' => ['photo-1600566753086-00f18fb6b3ea', $heroImage],
    ];
    $update = $pdo->prepare('UPDATE projects SET image=? WHERE slug=? AND is_demo=1 AND image=?');
    foreach ($exampleCovers as $slug => [$stockPhoto, $replacement]) {
        $original = 'https://images.unsplash.com/' . $stockPhoto . '?auto=format&fit=crop&w=1200&q=85';
        $update->execute([$replacement, $slug, $original]);
    }
    // Fill only empty galleries of known examples that still use the test covers.
    $gallery = $pdo->prepare(<<<'SQL'
        INSERT INTO project_gallery (project_id, image, sort_order)
        SELECT p.id, ?, 1 FROM projects p
        WHERE p.slug = ? AND p.is_demo = 1 AND p.image = ?
            AND NOT EXISTS (SELECT 1 FROM project_gallery g WHERE g.project_id = p.id)
        SQL);
    foreach ($exampleCovers as $slug => [$stockPhoto, $cover]) {
        $otherImage = $cover === $heroImage ? $detailImage : $heroImage;
        $gallery->execute([$otherImage, $slug, $cover]);
    }
    $pdo->commit();
} catch (Throwable $exception) {
    $pdo->rollBack();
    throw $exception;
}
echo "Yerel önizleme için test görselleri ve çalışma bölgeleri hazır. Mevcut düzenlemeler ve iletişim bilgileri korundu.\n";
