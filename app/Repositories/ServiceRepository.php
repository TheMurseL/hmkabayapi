<?php

declare(strict_types=1);

final class ServiceRepository
{
    public static function seedDefaults(PDO $pdo): void
    {
        $sql = <<<'SQL'
            INSERT IGNORE INTO services (
                slug, title, description, intro, sections, process, questions, seo_title, seo_description
            ) VALUES (
                :slug, :title, :description, :intro, :sections, :process, :questions, :seo_title, :seo_description
            )
            SQL;
        $statement = $pdo->prepare($sql);
        $existingSlugs = $pdo->query('SELECT slug FROM services')->fetchAll(PDO::FETCH_COLUMN);

        foreach (service_defaults() as $slug => $service) {
            if (in_array($slug, $existingSlugs, true)) {
                continue;
            }
            $statement->execute([
                'slug' => $slug,
                'title' => $service['title'],
                'description' => $service['description'],
                'intro' => $service['intro'],
                'sections' => seo_json($service['sections']),
                'process' => seo_json($service['process'] ?? []),
                'questions' => seo_json($service['questions']),
                'seo_title' => $service['seo_title'] ?? '',
                'seo_description' => $service['seo_description'] ?? '',
            ]);
        }
    }

    public function all(): array
    {
        $rows = db()->query('SELECT * FROM services ORDER BY id ASC')->fetchAll();

        $order = array_flip(array_keys(service_defaults()));
        usort($rows, static function (array $left, array $right) use ($order): int {
            return (($order[$left['slug']] ?? PHP_INT_MAX) <=> ($order[$right['slug']] ?? PHP_INT_MAX))
                ?: ((int) $left['id'] <=> (int) $right['id']);
        });

        return array_map([$this, 'decode'], $rows);
    }

    public function find(int $id): ?array
    {
        $statement = db()->prepare('SELECT * FROM services WHERE id = ?');
        $statement->execute([$id]);
        $row = $statement->fetch();

        return $row ? $this->decode($row) : null;
    }

    public function save(int $id, array $data): void
    {
        if (!$this->find($id)) {
            throw new RuntimeException('Düzenlenecek hizmet bulunamadı.');
        }

        $data = self::formData($data);

        $sql = <<<'SQL'
            UPDATE services
            SET title = :title,
                description = :description,
                intro = :intro,
                sections = :sections,
                process = :process,
                questions = :questions,
                seo_title = :seo_title,
                seo_description = :seo_description
            WHERE id = :id
            SQL;

        foreach (['sections', 'process', 'questions'] as $field) {
            $data[$field] = seo_json($data[$field]);
        }

        db()->prepare($sql)->execute($data + ['id' => $id]);
    }

    public static function formData(array $input): array
    {
        $data = [];
        $limits = [
            'title' => 180,
            'description' => 2000,
            'intro' => 20000,
            'seo_title' => 180,
            'seo_description' => 320,
        ];
        foreach ($limits as $field => $limit) {
            $value = $input[$field] ?? '';
            if (!is_string($value) || mb_strlen($value) > $limit) {
                throw new RuntimeException('Hizmet metinlerini ve alan uzunluklarını kontrol edin.');
            }
            $data[$field] = trim($value);
        }
        if ($data['title'] === '' || $data['description'] === '' || $data['intro'] === '') {
            throw new RuntimeException('Hizmet başlığı, özeti ve açıklaması zorunludur.');
        }
        foreach (['sections', 'process', 'questions'] as $field) {
            $rows = $input[$field] ?? [];
            if (!is_array($rows) || count($rows) > 30) {
                throw new RuntimeException('Her içerik grubunda en fazla 30 satır kullanılabilir.');
            }
            $data[$field] = [];
            foreach ($rows as $row) {
                if (!is_array($row) || count($row) !== 2 || !is_string($row[0] ?? null) || !is_string($row[1] ?? null)) {
                    throw new RuntimeException('İçerik satırlarının biçimi geçersiz.');
                }
                [$heading, $text] = array_map('trim', $row);
                if ($heading === '' && $text === '') {
                    continue;
                }
                if ($heading === '' || $text === '' || mb_strlen($heading) > 300 || mb_strlen($text) > 10000) {
                    throw new RuntimeException('Her satırın başlığını ve açıklamasını birlikte doldurun.');
                }
                $data[$field][] = [$heading, $text];
            }
        }

        return $data;
    }

    private function decode(array $row): array
    {
        foreach (['sections', 'process', 'questions'] as $field) {
            $row[$field] = json_decode($row[$field], true, 512, JSON_THROW_ON_ERROR);
        }

        return $row;
    }
}
