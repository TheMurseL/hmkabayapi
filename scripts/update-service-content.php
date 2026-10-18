<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}
require __DIR__ . '/../app/bootstrap.php';
if (config('app_env') === 'production') {
    throw new RuntimeException('Bu içerik güncellemesi yerel geliştirme içindir.');
}

$version = '2026-10-18-application-scope-v2';
$markerKey = 'service_content_version';
$pdo = db();
$repository = new ServiceRepository();
$pdo->beginTransaction();
try {
    $marker = $pdo->prepare('SELECT setting_value FROM site_settings WHERE setting_key = ? FOR UPDATE');
    $marker->execute([$markerKey]);
    if ($marker->fetchColumn() === $version) {
        $pdo->rollBack();
        echo "Hizmet içerikleri daha önce güncellendi; paneldeki düzenlemeler korunuyor.\n";
        exit;
    }
    $existing = $repository->all();
    $backup = [
        'version' => $version,
        'services' => $existing,
        'posts' => $pdo->query('SELECT * FROM posts ORDER BY id')->fetchAll(),
        'site_settings' => $pdo->query("SELECT * FROM site_settings WHERE setting_key IN ('site_description', 'service_content_version') ORDER BY setting_key")->fetchAll(),
        'project_services' => $pdo->query('SELECT * FROM project_services ORDER BY project_id, service_id')->fetchAll(),
        'post_services' => $pdo->query('SELECT * FROM post_services ORDER BY post_id, service_id')->fetchAll(),
        'post_projects' => $pdo->query('SELECT * FROM post_projects ORDER BY post_id, project_id')->fetchAll(),
    ];
    $directory = __DIR__ . '/../.private/backups';
    if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
        throw new RuntimeException('Hizmet yedek dizini oluşturulamadı.');
    }
    $path = $directory . '/service-content-' . gmdate('Y-m-d-His') . '-' . bin2hex(random_bytes(3)) . '.json';
    if (file_put_contents($path, json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)) === false) {
        throw new RuntimeException('Hizmet içerikleri yedeklenemedi.');
    }
    ServiceRepository::seedDefaults($pdo);
    $current = array_column($repository->all(), null, 'slug');
    $messages = [];
    foreach (service_defaults() as $slug => $content) {
        $repository->save((int) $current[$slug]['id'], $content);
        $messages[] = $slug;
    }
    foreach ($existing as $previous) {
        $saved = $repository->find((int) $previous['id']);
        if (!$saved || $saved['slug'] !== $previous['slug']) {
            throw new RuntimeException('Mevcut hizmet adresi değişti; işlem geri alındı.');
        }
    }

    // Refresh the site's introductory articles without changing publication, media or authorship.
    $updatePost = $pdo->prepare(<<<'SQL'
        UPDATE posts SET title = :title, excerpt = :excerpt, content = :content,
            seo_title = :seo_title, seo_description = :seo_description, search_intent = :search_intent
        WHERE id = :id
        SQL);
    $postDefaults = post_defaults();
    $updatedPosts = 0;
    foreach ($backup['posts'] as $previous) {
        if (!isset($postDefaults[$previous['slug']])) {
            continue;
        }
        $draft = $postDefaults[$previous['slug']];
        $data = [
            'title' => $draft['title'],
            'excerpt' => $draft['excerpt'],
            'content' => $draft['content'],
            'seo_title' => $draft['title'],
            'seo_description' => $draft['excerpt'],
            'search_intent' => $draft['intent'],
        ];
        $changed = array_filter($data, static fn ($value, $key) => $value !== $previous[$key], ARRAY_FILTER_USE_BOTH);
        if ($changed) {
            $updatePost->execute($data + ['id' => $previous['id']]);
            $updatedPosts++;
        }
        $checkPost = $pdo->prepare('SELECT * FROM posts WHERE id = ?');
        $checkPost->execute([$previous['id']]);
        $saved = $checkPost->fetch();
        foreach (array_diff(array_keys($previous), array_keys($data), ['updated_at']) as $field) {
            if ($saved[$field] !== $previous[$field]) {
                throw new RuntimeException('Yazının adresi veya korunacak alanları değişti; işlem geri alındı.');
            }
        }
    }
    foreach (['project_services' => 'project_id, service_id', 'post_services' => 'post_id, service_id', 'post_projects' => 'post_id, project_id'] as $table => $order) {
        if ($pdo->query('SELECT * FROM ' . $table . ' ORDER BY ' . $order)->fetchAll() !== $backup[$table]) {
            throw new RuntimeException('Hizmet ilişkileri değişti; işlem geri alındı.');
        }
    }
    (new SettingsRepository())->setMany([
        'site_description' => 'İstanbul’da kalıp, demir ve beton uygulamaları. Radye temel, subasman, perde ve istinat duvarları ile talebe göre tuğla duvar ve çatı işleri.',
        $markerKey => $version,
    ]);
    $pdo->commit();
    echo "Hizmet içerikleri hazır (" . count($messages) . "):\n" . implode("\n", $messages) . "\n";
    echo $updatedPosts . " yazı ve site açıklaması güncellendi. Önceki içerikler yedeklendi; adresler ve ilişkiler korundu.\n";
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $exception;
}
