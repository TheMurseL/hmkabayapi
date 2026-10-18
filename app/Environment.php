<?php

declare(strict_types=1);

final class AppEnvironment
{
    private ?array $settings = null;

    public function __construct(private string $applicationRoot)
    {
    }

    public function get(string $name, string $fallback = ''): string
    {
        // Read and validate the file even when a server variable overrides it.
        $settings = $this->settings ??= $this->load();
        $value = getenv($name);
        return $value !== false ? $value : ($settings[$name] ?? $fallback);
    }

    private function load(): array
    {
        $explicitPath = getenv('APP_CONFIG_FILE');
        $externalPath = $explicitPath !== false && $explicitPath !== ''
            ? $explicitPath
            : dirname($this->applicationRoot) . '/hmkabayapi-config.php';
        $localPath = $this->applicationRoot . '/config/local.php';
        $external = is_file($externalPath);
        if ($explicitPath !== false && $explicitPath !== '' && !$external) {
            throw new RuntimeException('Özel yapılandırma dosyası bulunamadı.');
        }
        $path = $external ? $externalPath : $localPath;
        if (!is_file($path)) {
            return [];
        }
        if (!is_readable($path)) {
            throw new RuntimeException('Özel yapılandırma dosyası okunamıyor.');
        }
        if ($external) {
            $resolvedPath = realpath($path);
            $publicRoots = [$this->applicationRoot, (string) ($_SERVER['DOCUMENT_ROOT'] ?? '')];
            foreach ($publicRoots as $publicRoot) {
                $resolvedRoot = $publicRoot !== '' ? realpath($publicRoot) : false;
                if ($resolvedPath !== false && $resolvedRoot !== false) {
                    $file = str_replace('\\', '/', $resolvedPath);
                    $root = rtrim(str_replace('\\', '/', $resolvedRoot), '/') . '/';
                    if (PHP_OS_FAMILY === 'Windows') {
                        $file = strtolower($file);
                        $root = strtolower($root);
                    }
                    if (str_starts_with($file, $root)) {
                        throw new RuntimeException('Canlı yapılandırma dosyası web kökünün dışında olmalı.');
                    }
                }
            }
        }
        try {
            $settings = (static fn(string $file): mixed => require $file)($path);
        } catch (Throwable) {
            throw new RuntimeException('Özel yapılandırma dosyası geçerli PHP içermiyor.');
        }
        $allowed = ['APP_ENV', 'SITE_URL', 'DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'TRUSTED_PROXIES'];
        if (!is_array($settings)) {
            throw new RuntimeException('Özel yapılandırma dosyası bir ayar dizisi döndürmeli.');
        }
        foreach ($settings as $name => $value) {
            if (!in_array($name, $allowed, true) || !is_string($value) || str_contains($value, "\0")) {
                throw new RuntimeException('Özel yapılandırmada geçersiz ayar var.');
            }
        }
        return $settings;
    }
}
