<?php

declare(strict_types=1);

final class SettingsRepository
{
    public function all(): array
    {
        $rows = db()->query('SELECT setting_key, setting_value FROM site_settings')->fetchAll();
        return array_column($rows, 'setting_value', 'setting_key');
    }

    public function get(string $key, string $default = ''): string
    {
        $stmt = db()->prepare('SELECT setting_value FROM site_settings WHERE setting_key = :key LIMIT 1');
        $stmt->execute([
            'key' => $key,
        ]);
        return (string) ($stmt->fetchColumn() ?: $default);
    }

    public function setMany(array $settings): void
    {
        $stmt = db()->prepare('INSERT INTO site_settings (setting_key, setting_value) VALUES (:key, :value) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
        foreach ($settings as $key => $value) {
            $stmt->execute([
                'key' => $key,
                'value' => trim((string) $value),
            ]);
        }
    }
}
