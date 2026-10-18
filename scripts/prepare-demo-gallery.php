<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}
require_once __DIR__ . '/../app/bootstrap.php';
if (config('app_env') === 'production') {
    throw new RuntimeException('Test galerileri yalnızca yerel önizlemede hazırlanabilir.');
}

// Keep each current cover and existing gallery; top up demo projects to ten images.
$manifest = json_decode(file_get_contents(__DIR__ . '/../uploads/demo/gallery-generation.json'), true, 512, JSON_THROW_ON_ERROR);
$candidates = [
    'uploads/demo/ahsap-kalip-test-hero.webp',
    'uploads/demo/ahsap-kalip-test-detay.webp',
];
foreach ($manifest['assets'] as $asset) {
    $candidates[] = 'uploads/demo/' . $asset['file'];
}
foreach ($candidates as $path) {
    if (!is_file(__DIR__ . '/../' . $path) || !getimagesize(__DIR__ . '/../' . $path)) {
        throw new RuntimeException('Galeri test görseli bulunamadı: ' . $path);
    }
}

$pdo = db();
$pdo->beginTransaction();
try {
    $projects = $pdo->query('SELECT id, slug, image FROM projects WHERE is_demo = 1 ORDER BY id FOR UPDATE')->fetchAll();
    $repository = new ProjectRepository();
    $snapshot = [];
    foreach ($projects as $project) {
        $snapshot[] = $project + ['gallery' => $repository->gallery((int) $project['id'])];
    }
    $backupDirectory = __DIR__ . '/../.private/backups';
    if (!is_dir($backupDirectory) && !mkdir($backupDirectory, 0755, true)) {
        throw new RuntimeException('Galeri yedek dizini oluşturulamadı.');
    }
    $backupPath = $backupDirectory . '/demo-gallery-' . gmdate('Y-m-d-His') . '-' . bin2hex(random_bytes(3)) . '.json';
    if (file_put_contents($backupPath, json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)) === false) {
        throw new RuntimeException('Galeri yedeği kaydedilemedi.');
    }
    $insert = $pdo->prepare('INSERT INTO project_gallery (project_id, image, sort_order) VALUES (?, ?, ?)');
    $result = [];
    foreach ($snapshot as $project) {
        if (empty($project['image'])) {
            throw new RuntimeException('Önce vitrin görselini seçin: ' . $project['slug']);
        }
        $seen = [project_image($project['image']) => true];
        $sort = 0;
        foreach ($project['gallery'] as $image) {
            if (!empty($image['image'])) {
                $seen[project_image($image['image'])] = true;
            }
            $sort = max($sort, (int) $image['sort_order']);
        }
        $added = 0;
        foreach ($candidates as $path) {
            if (count($seen) >= 10) {
                break;
            }
            $imageUrl = project_image($path);
            if (!isset($seen[$imageUrl])) {
                $insert->execute([(int) $project['id'], $path, ++$sort]);
                $seen[$imageUrl] = true;
                $added++;
            }
        }
        if (count($seen) < 10) {
            throw new RuntimeException('Yeterli farklı test görseli yok: ' . $project['slug']);
        }
        $result[] = $project['slug'] . ': ' . count($seen) . ' görsel (vitrin dahil), ' . $added . ' yeni galeri kaydı';
    }
    $pdo->commit();
    echo implode(PHP_EOL, $result), PHP_EOL;
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $exception;
}
