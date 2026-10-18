<?php

declare(strict_types=1);

final class ProjectRepository
{
    private static function publicFilter(): string
    {
        return config('app_env') === 'production' ? ' AND p.is_demo = 0' : '';
    }
    public const CASE_FIELDS = [
        'building_type' => 'Yapı türü',
        'work_scope' => 'İş kapsamı',
        'quantities' => 'Metraj ve uygulama miktarları',
        'work_process' => 'Çalışma süreci',
        'challenges' => 'Saha koşulları ve çözümler',
        'delivery_result' => 'Teslim sonucu',
    ];

    public function services(int $projectId): array
    {
        $statement = db()->prepare(<<<'SQL'
            SELECT s.*
            FROM services s
            INNER JOIN project_services ps ON ps.service_id = s.id
            WHERE ps.project_id = ?
            ORDER BY s.id
            SQL);
        $statement->execute([$projectId]);

        return $statement->fetchAll();
    }

    public function forService(int $serviceId): array
    {
        $statement = db()->prepare(<<<'SQL'
            SELECT p.*
            FROM projects p
            INNER JOIN project_services ps ON ps.project_id = p.id
            WHERE ps.service_id = ?
            SQL . self::publicFilter() . ' ORDER BY p.featured DESC, p.created_at DESC');
        $statement->execute([$serviceId]);

        return $statement->fetchAll();
    }

    public function save(int $id, array $input, array $serviceIds): int
    {
        $fields = [
            'title',
            'slug',
            'category',
            'location',
            'year',
            'area',
            'summary',
            'description',
            'image',
            'seo_title',
            'seo_description',
            'featured',
            'is_demo',
        ];
        $fields = array_merge($fields, array_keys(self::CASE_FIELDS));
        $data = [];
        foreach ($fields as $field) {
            $value = $input[$field] ?? '';
            if (!is_scalar($value) && $value !== null) {
                throw new RuntimeException('Proje alanlarının biçimini kontrol edin.');
            }
            $data[$field] = trim((string) $value);
        }
        $data['featured'] = !empty($input['featured']) ? 1 : 0;
        $data['is_demo'] = !empty($input['is_demo']) ? 1 : 0;
        if (trim($data['title']) === '' || !in_array($data['category'], ['ongoing', 'completed'], true)) {
            throw new RuntimeException('Proje başlığını ve durumunu kontrol edin.');
        }
        $limits = [
            'title' => 180,
            'slug' => 190,
            'location' => 160,
            'year' => 20,
            'area' => 80,
            'image' => 500,
            'seo_title' => 180,
            'seo_description' => 320,
            'building_type' => 180,
        ];
        foreach ($limits as $field => $limit) {
            if (mb_strlen($data[$field]) > $limit) {
                throw new RuntimeException('Proje alanlarından biri izin verilen uzunluğu aşıyor.');
            }
        }
        foreach (array_merge(['summary', 'description'], array_keys(self::CASE_FIELDS)) as $field) {
            if (strlen($data[$field]) > 65535) {
                throw new RuntimeException('Proje metni çok uzun.');
            }
        }
        foreach ($serviceIds as $serviceId) {
            if (filter_var($serviceId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
                throw new RuntimeException('Seçilen hizmet kimliği geçersiz.');
            }
        }
        $serviceIds = array_values(array_unique(array_map('intval', $serviceIds)));
        $validIds = array_column((new ServiceRepository())->all(), 'id');
        if (array_diff($serviceIds, $validIds)) {
            throw new RuntimeException('Seçilen hizmet bulunamadı.');
        }
        $pdo = db();
        $ownsTransaction = !$pdo->inTransaction();
        if ($ownsTransaction) {
            $pdo->beginTransaction();
        }
        try {
            if ($id) {
                $existing = $pdo->prepare('SELECT slug FROM projects WHERE id = ?');
                $existing->execute([$id]);
                $slug = $existing->fetchColumn();
                if ($slug === false) {
                    throw new RuntimeException('Düzenlenecek proje bulunamadı.');
                }
                $data['slug'] = $slug;
                $assignments = array_map(static fn (string $field): string => "$field = :$field", $fields);
                $sql = 'UPDATE projects SET ' . implode(', ', $assignments) . ' WHERE id = :id';
                $pdo->prepare($sql)->execute($data + ['id' => $id]);
            } else {
                $sql = 'INSERT INTO projects (' . implode(', ', $fields) . ') VALUES (:' . implode(', :', $fields) . ')';
                $pdo->prepare($sql)->execute($data);
                $id = (int) $pdo->lastInsertId();
            }
            $pdo->prepare('DELETE FROM project_services WHERE project_id = ?')->execute([$id]);
            $statement = $pdo->prepare('INSERT INTO project_services (project_id, service_id) VALUES (?, ?)');
            foreach ($serviceIds as $serviceId) {
                $statement->execute([$id, $serviceId]);
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

    public function featured(int $limit = 3): array
    {
        $stmt = db()->prepare('SELECT p.* FROM projects p WHERE p.featured = 1' . self::publicFilter() . ' ORDER BY p.created_at DESC LIMIT :limit');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function all(?string $category = null): array
    {
        if (in_array($category, ['ongoing', 'completed'], true)) {
            $stmt = db()->prepare('SELECT p.* FROM projects p WHERE p.category = :category' . self::publicFilter() . ' ORDER BY p.featured DESC, p.created_at DESC');
            $stmt->execute([
                'category' => $category,
            ]);
            return $stmt->fetchAll();
        }
        return db()->query('SELECT p.* FROM projects p WHERE 1=1' . self::publicFilter() . ' ORDER BY p.featured DESC, p.created_at DESC')->fetchAll();
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = db()->prepare('SELECT * FROM projects WHERE slug = :slug LIMIT 1');
        $stmt->execute([
            'slug' => $slug,
        ]);
        return $stmt->fetch() ?: null;
    }

    public function useGalleryImageAsCover(int $projectId, int $imageId): void
    {
        $pdo = db();
        $ownsTransaction = !$pdo->inTransaction();
        if ($ownsTransaction) {
            $pdo->beginTransaction();
        }
        try {
            $statement = $pdo->prepare('SELECT image FROM projects WHERE id = ? FOR UPDATE');
            $statement->execute([$projectId]);
            $previousCover = $statement->fetchColumn();
            $statement = $pdo->prepare('SELECT image FROM project_gallery WHERE id = ? AND project_id = ? FOR UPDATE');
            $statement->execute([$imageId, $projectId]);
            $nextCover = $statement->fetchColumn();
            if ($previousCover === false || !$nextCover) {
                throw new RuntimeException('Vitrin olarak seçilen proje görseli bulunamadı.');
            }
            $pdo->prepare('UPDATE projects SET image = ? WHERE id = ?')->execute([$nextCover, $projectId]);
            if ($previousCover !== null && $previousCover !== '' && $previousCover !== $nextCover) {
                $pdo->prepare('UPDATE project_gallery SET image = ?, caption = NULL WHERE id = ? AND project_id = ?')
                    ->execute([$previousCover, $imageId, $projectId]);
            } else {
                $pdo->prepare('DELETE FROM project_gallery WHERE id = ? AND project_id = ?')->execute([$imageId, $projectId]);
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
    }

    public function gallery(int $projectId): array
    {
        $stmt = db()->prepare('SELECT * FROM project_gallery WHERE project_id = :project_id ORDER BY sort_order ASC, id ASC');
        $stmt->execute([
            'project_id' => $projectId,
        ]);
        return $stmt->fetchAll();
    }
}
