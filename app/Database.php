<?php

declare(strict_types=1);

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', config('db_host'), config('db_name'));
        self::$connection = new PDO($dsn, (string) config('db_user'), (string) config('db_pass'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        self::$connection->exec("SET time_zone = '+00:00'");

        return self::$connection;
    }

    public static function migrate(): void
    {
        if (PHP_SAPI !== 'cli') {
            throw new RuntimeException('Şema güncellemeleri yalnızca komut satırından çalıştırılır.');
        }
        $pdo = self::connection();
        $pdo->exec('CREATE TABLE IF NOT EXISTS site_settings (setting_key VARCHAR(100) PRIMARY KEY, setting_value TEXT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        $seedAbout = $pdo->prepare('INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES (?, ?)');
        foreach (about_defaults() as $key => $value) {
            $seedAbout->execute([$key, $value]);
        }
        $pdo->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS services (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                slug VARCHAR(190) NOT NULL UNIQUE,
                title VARCHAR(180) NOT NULL,
                description TEXT NOT NULL,
                intro TEXT NOT NULL,
                sections LONGTEXT NOT NULL,
                process LONGTEXT NOT NULL,
                questions LONGTEXT NOT NULL,
                seo_title VARCHAR(180) DEFAULT NULL,
                seo_description VARCHAR(320) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            SQL);
        $pdo->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS project_services (
                project_id INT UNSIGNED NOT NULL,
                service_id INT UNSIGNED NOT NULL,
                PRIMARY KEY (project_id, service_id),
                FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
                FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            SQL);
        ServiceRepository::seedDefaults($pdo);
        $pdo->exec('CREATE TABLE IF NOT EXISTS project_gallery (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, project_id INT UNSIGNED NOT NULL, image VARCHAR(500) NOT NULL, caption VARCHAR(255) DEFAULT NULL, sort_order INT NOT NULL DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX idx_project_gallery_project (project_id), CONSTRAINT fk_project_gallery_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        $columns = [
            'projects' => [
                'is_demo' => 'TINYINT(1) NOT NULL DEFAULT 0',
                'building_type' => 'VARCHAR(180) NULL',
                'work_scope' => 'TEXT NULL',
                'quantities' => 'TEXT NULL',
                'work_process' => 'TEXT NULL',
                'challenges' => 'TEXT NULL',
                'delivery_result' => 'TEXT NULL',
                'updated_at' => 'TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP',
                'seo_title' => 'VARCHAR(180) NULL',
                'seo_description' => 'VARCHAR(320) NULL',
            ],
            'posts' => [
                'author_name' => 'VARCHAR(180) NULL',
                'reviewer_name' => 'VARCHAR(180) NULL',
                'reviewed_at' => 'DATE NULL',
                'sources' => 'TEXT NULL',
                'search_intent' => 'VARCHAR(180) NULL',
                'updated_at' => 'TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP',
                'seo_title' => 'VARCHAR(180) NULL',
                'seo_description' => 'VARCHAR(320) NULL',
            ],
            'messages' => [
                'request_token' => 'CHAR(64) NULL UNIQUE',
                'district' => 'VARCHAR(100) NULL',
                'building_type' => 'VARCHAR(100) NULL',
                'area' => 'DECIMAL(12,2) NULL',
                'stage' => 'VARCHAR(100) NULL',
                'source' => 'VARCHAR(500) NULL',
                'lead_status' => "VARCHAR(30) NOT NULL DEFAULT 'new'",
            ],
        ];

        foreach ($columns as $table => $tableColumns) {
            foreach ($tableColumns as $column => $definition) {
                $check = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name AND COLUMN_NAME = :column_name');
                $check->execute([
                    'table_name' => $table,
                    'column_name' => $column,
                ]);
                if (!(int) $check->fetchColumn()) {
                    $pdo->exec(sprintf('ALTER TABLE `%s` ADD COLUMN `%s` %s', $table, $column, $definition));
                }
            }
        }
        foreach (['services' => 'service_id', 'projects' => 'project_id'] as $table => $foreignColumn) {
            $pdo->exec("CREATE TABLE IF NOT EXISTS post_{$table} (post_id INT UNSIGNED NOT NULL, {$foreignColumn} INT UNSIGNED NOT NULL, PRIMARY KEY (post_id, {$foreignColumn}), FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE, FOREIGN KEY ({$foreignColumn}) REFERENCES {$table}(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }
}

function db(): PDO
{
    return Database::connection();
}
