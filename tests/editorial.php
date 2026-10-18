<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';
db()->beginTransaction();
try {
    if (display_date('2026-10-10 22:30:00', 'Y-m-d H:i') !== '2026-10-11 01:30') {
        throw new RuntimeException('UTC to Istanbul date conversion failed.');
    }
    $repository = new PostRepository();
    $serviceId = (int) db()->query('SELECT id FROM services LIMIT 1')->fetchColumn();
    $projectId = (int) db()->query('SELECT id FROM projects LIMIT 1')->fetchColumn();
    $input = ['title' => 'Editör kontrolü', 'slug' => 'editor-test-' . bin2hex(random_bytes(5)), 'excerpt' => 'Kontrol özeti', 'content' => '## Kontrol', 'author_name' => 'Test yazarı', 'reviewer_name' => 'Test inceleyeni', 'reviewed_at' => date('Y-m-d'), 'sources' => 'https://example.com/source', 'search_intent' => 'test sorgusu', 'published' => 1];
    $id = $repository->save(0, $input, [$serviceId], [$projectId]);
    $input['title'] = 'Yeni başlık';
    $input['slug'] = 'degismemeli';
    $repository->save($id, $input, [$serviceId], [$projectId]);
    $post = $repository->findBySlug(db()->query('SELECT slug FROM posts WHERE id=' . $id)->fetchColumn());
    if (!$post || $post['slug'] === 'degismemeli' || count($repository->related($id, 'services')) !== 1 || count($repository->related($id, 'projects')) !== 1) {
        throw new RuntimeException('Editorial save / stable slug / relation failed.');
    }
    if (!in_array($id, array_column($repository->forContent($serviceId, 'services'), 'id'))) {
        throw new RuntimeException('Reverse service relation failed.');
    }
    $input['published'] = 0;
    $repository->save($id, $input);
    if ($repository->findBySlug($post['slug']) !== null) {
        throw new RuntimeException('Draft is publicly accessible.');
    }
    $input['sources'] = 'javascript:alert(1)';
    try {
        $repository->save($id, $input);
        throw new LogicException('Unsafe source accepted.');
    } catch (RuntimeException $exception) {
        if ($exception instanceof LogicException) {
            throw $exception;
        }
    }
    $input['sources'] = '';
    $input['reviewed_at'] = '2099-01-01';
    try {
        $repository->save($id, $input);
        throw new LogicException('Future review date accepted.');
    } catch (RuntimeException $exception) {
        if ($exception instanceof LogicException) {
            throw $exception;
        }
    }
    echo "Editorial fields, stable URLs, related content and draft visibility passed; transaction rolled back.\n";
} finally {
    db()->rollBack();
}
