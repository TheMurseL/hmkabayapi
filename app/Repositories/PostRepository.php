<?php

declare(strict_types=1);

final class PostRepository
{
    public function save(int $id, array $input, array $serviceIds = [], array $projectIds = []): int
    {
        $fields = ['title', 'slug', 'excerpt', 'content', 'image', 'seo_title', 'seo_description', 'published', 'author_name', 'reviewer_name', 'reviewed_at', 'sources', 'search_intent'];
        $data = [];
        foreach ($fields as $field) {
            if (isset($input[$field]) && !is_scalar($input[$field])) {
                throw new RuntimeException('Yazı alanlarının biçimini kontrol edin.');
            }
            $data[$field] = trim((string) ($input[$field] ?? ''));
        }
        $data['published'] = !empty($input['published']) ? 1 : 0;
        if ($data['title'] === '' || $data['excerpt'] === '' || $data['content'] === '') {
            throw new RuntimeException('Başlık, açıklama ve içerik zorunludur.');
        }
        foreach (['title' => 180, 'slug' => 190, 'image' => 500, 'seo_title' => 180, 'seo_description' => 320, 'author_name' => 180, 'reviewer_name' => 180, 'search_intent' => 180] as $field => $limit) {
            if (mb_strlen($data[$field]) > $limit) {
                throw new RuntimeException('Yazı alanlarından biri çok uzun.');
            }
        }
        if (strlen($data['excerpt']) > 65535 || strlen($data['sources']) > 65535 || strlen($data['content']) > 1000000) {
            throw new RuntimeException('Yazı metni çok uzun.');
        }
        $date = $data['reviewed_at'];
        if ($date !== '') {
            $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
            if (!$parsed || $parsed->format('Y-m-d') !== $date || $date > display_date('now', 'Y-m-d') || $data['reviewer_name'] === '') {
                throw new RuntimeException('İnceleme için gerçek sorumlu adı ve geçmiş/geçerli bir tarih girin.');
            }
        }
        $data['reviewed_at'] = $date ?: null;
        foreach (preg_split('/\R/u', $data['sources']) ?: [] as $source) {
            if ($source !== '' && (!filter_var($source, FILTER_VALIDATE_URL) || !in_array(parse_url($source, PHP_URL_SCHEME), ['http', 'https'], true))) {
                throw new RuntimeException('Her kaynak satırına geçerli bir http/https adresi girin.');
            }
        }
        $relations = ['services' => $serviceIds, 'projects' => $projectIds];
        foreach ($relations as $table => &$ids) {
            foreach ($ids as $value) {
                if (filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
                    throw new RuntimeException('İlgili içerik kimliği geçersiz.');
                }
            }
            $ids = array_values(array_unique(array_map('intval', $ids)));
            if (array_diff($ids, db()->query("SELECT id FROM {$table}")->fetchAll(PDO::FETCH_COLUMN))) {
                throw new RuntimeException('İlgili içerik bulunamadı.');
            }
        }
        unset($ids);
        $pdo = db();
        $ownsTransaction = !$pdo->inTransaction();
        if ($ownsTransaction) {
            $pdo->beginTransaction();
        }
        try {
            if ($id) {
                $existing = $pdo->prepare('SELECT slug FROM posts WHERE id = ?');
                $existing->execute([$id]);
                $slug = $existing->fetchColumn();
                if ($slug === false) {
                    throw new RuntimeException('Yazı bulunamadı.');
                }
                $data['slug'] = $slug;
                $pdo->prepare('UPDATE posts SET ' . implode(',', array_map(static fn ($field) => "$field=:$field", $fields)) . ' WHERE id=:id')->execute($data + ['id' => $id]);
            } else {
                $pdo->prepare('INSERT INTO posts (' . implode(',', $fields) . ') VALUES (:' . implode(',:', $fields) . ')')->execute($data);
                $id = (int) $pdo->lastInsertId();
            }
            foreach ($relations as $table => $ids) {
                $column = $table === 'services' ? 'service_id' : 'project_id';
                $pdo->prepare("DELETE FROM post_{$table} WHERE post_id = ?")->execute([$id]);
                $insert = $pdo->prepare("INSERT INTO post_{$table} (post_id, {$column}) VALUES (?, ?)");
                foreach ($ids as $relatedId) {
                    $insert->execute([$id, $relatedId]);
                }
            }
            if ($ownsTransaction) {
                $pdo->commit();
            }
        } catch (Throwable $exception) {
            if ($ownsTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
        return $id;
    }

    public function related(int $postId, string $type): array
    {
        if (!in_array($type, ['services', 'projects'], true)) {
            throw new InvalidArgumentException('İlişki türü geçersiz.');
        }
        $column = $type === 'services' ? 'service_id' : 'project_id';
        $statement = db()->prepare("SELECT t.* FROM {$type} t JOIN post_{$type} r ON r.{$column}=t.id WHERE r.post_id=? ORDER BY t.id");
        $statement->execute([$postId]);
        return $statement->fetchAll();
    }

    public function forContent(int $id, string $type): array
    {
        if (!in_array($type, ['services', 'projects'], true)) {
            throw new InvalidArgumentException('İlişki türü geçersiz.');
        }
        $column = $type === 'services' ? 'service_id' : 'project_id';
        $statement = db()->prepare("SELECT p.* FROM posts p JOIN post_{$type} r ON r.post_id=p.id WHERE r.{$column}=? AND p.published=1 ORDER BY p.created_at DESC LIMIT 6");
        $statement->execute([$id]);
        return $statement->fetchAll();
    }

    public function latest(int $limit = 2): array
    {
        $stmt = db()->prepare('SELECT * FROM posts WHERE published = 1 ORDER BY created_at DESC LIMIT :limit');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function all(): array
    {
        return db()->query('SELECT * FROM posts WHERE published = 1 ORDER BY created_at DESC')->fetchAll();
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = db()->prepare('SELECT * FROM posts WHERE slug = :slug AND published = 1 LIMIT 1');
        $stmt->execute([
            'slug' => $slug,
        ]);
        return $stmt->fetch() ?: null;
    }
}
