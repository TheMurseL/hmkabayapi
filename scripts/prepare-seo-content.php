<?php

declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}
require __DIR__ . '/../app/bootstrap.php';

// Identify only byte-for-byte matching installation examples. Never replace edited records.
$seedSql = file_get_contents(__DIR__ . '/../database.sql');
if (!preg_match('/INSERT INTO projects \((.*?)\) VALUES\s*(.*?);/s', $seedSql, $match)) {
    throw new RuntimeException('Başlangıç projeleri okunamadı.');
}
$fields = array_map('trim', explode(',', $match[1]));
preg_match_all("/\(((?:'[^']*'|[^)])*)\)/s", $match[2], $tuples);
$count = 0;
foreach ($tuples[1] as $tuple) {
    $expected = db()->query('SELECT ' . $tuple)->fetch(PDO::FETCH_NUM);
    if (count($expected) !== count($fields)) {
        throw new RuntimeException('Örnek içerik alanları eşleşmedi.');
    }
    $seed = array_combine($fields, $expected);
    $query = db()->prepare('SELECT * FROM projects WHERE slug=?');
    $query->execute([$seed['slug']]);
    $actual = $query->fetch();
    if (!$actual || !empty($actual['is_demo'])) {
        continue;
    }
    $matches = true;
    foreach ($seed as $field => $value) {
        if ($field === 'is_demo') {
            continue;
        }
        if ((string) $actual[$field] !== (string) $value) {
            $matches = false;
        }
    }
    foreach (ProjectRepository::CASE_FIELDS as $field => $label) {
        if (!empty($actual[$field])) {
            $matches = false;
        }
    }
    if ($matches) {
        // Flagging an example is not a substantive project update; preserve lastmod.
        db()->prepare('UPDATE projects SET is_demo=1, updated_at=updated_at WHERE id=?')->execute([$actual['id']]);
        $count++;
    }
}

$repository = new ServiceRepository();
foreach (service_defaults() as $slug => $original) {
    $statement = db()->prepare('SELECT id FROM services WHERE slug=?');
    $statement->execute([$slug]);
    $id = (int) $statement->fetchColumn();
    $service = $repository->find($id);
    if (!$service) {
        continue;
    }
    $unchanged = true;
    foreach (['title', 'description', 'intro', 'sections', 'questions'] as $field) {
        if ($service[$field] !== $original[$field]) {
            $unchanged = false;
        }
    }
    if (($service['process'] ?? []) !== ($original['process'] ?? [])) {
        $unchanged = false;
    }
    if (!$unchanged || !empty($service['seo_title']) || !empty($service['seo_description'])) {
        continue;
    }
    $service['seo_title'] = $original['title'];
    $service['seo_description'] = $original['description'];
    $repository->save($id, $service);
}

$drafts = post_defaults();
$created = 0;
foreach ($drafts as $slug => $draft) {
    $query = db()->prepare('SELECT id FROM posts WHERE slug=?');
    $query->execute([$slug]);
    if ($query->fetchColumn()) {
        continue;
    }
    $query = db()->prepare('SELECT id FROM services WHERE slug=?');
    $query->execute([$draft['service']]);
    $serviceId = (int) $query->fetchColumn();
    (new PostRepository())->save(0, [
        'title' => $draft['title'],
        'slug' => $slug,
        'excerpt' => $draft['excerpt'],
        'content' => $draft['content'],
        'search_intent' => $draft['intent'],
        'seo_title' => $draft['title'],
        'seo_description' => $draft['excerpt'],
        'published' => $draft['published'],
    ], $serviceId ? [$serviceId] : []);
    $created++;
}
(new SettingsRepository())->setMany(['seo_initial_posts_reviewed' => '1']);

echo $count . ' örnek proje işaretlendi; ' . $created . " rehber eklendi. Düzenlenmiş içerikler korundu.\n";
