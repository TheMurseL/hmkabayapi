<?php

declare(strict_types=1);

function e(mixed $value): string
{
    if (!is_scalar($value) && $value !== null) {
        return '';
    }
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    return config('site_url') . '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . url($path), true, ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' ? 303 : 302);
    exit;
}

function old(string $key, string $default = ''): string
{
    return e($_POST[$key] ?? $default);
}

function csrf_token(): string
{
    ensure_session();
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function verify_csrf(): void
{
    ensure_session();
    if (empty($_SESSION['_csrf']) || !is_string($_POST['_csrf'] ?? null) || !hash_equals($_SESSION['_csrf'], $_POST['_csrf'])) {
        http_response_code(403);
        exit('Geçersiz güvenlik anahtarı.');
    }
}

function flash(string $type, string $message): void
{
    ensure_session();
    $_SESSION['_flash'] = compact('type', 'message');
}

function take_flash(): ?array
{
    ensure_session();
    $flash = $_SESSION['_flash'] ?? null;
    unset($_SESSION['_flash']);
    return $flash;
}

function slugify(string $text): string
{
    $text = mb_strtolower(trim($text), 'UTF-8');
    $text = strtr($text, [
        'ş' => 's',
        'ğ' => 'g',
        'ı' => 'i',
        'İ' => 'i',
        'ö' => 'o',
        'ü' => 'u',
        'ç' => 'c',
    ]);
    return trim((string) preg_replace('/[^a-z0-9]+/', '-', $text), '-');
}

function project_image(?string $image): string
{
    return absolute_asset($image) ?: 'https://images.unsplash.com/photo-1541888946425-d81bb19240f5?auto=format&fit=crop&w=1200&q=85';
}

function blog_image(?string $image): string
{
    return absolute_asset($image) ?: url('assets/images/rehber.svg');
}

function seo_json(array $data): string
{
    return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
}

function image_dimensions(?string $asset): string
{
    if (!$asset || str_contains($asset, '://')) {
        return '';
    }
    $uploads = realpath(__DIR__ . '/../uploads');
    $path = realpath(__DIR__ . '/../' . ltrim($asset, '/'));
    if (!$uploads || !$path || !str_starts_with($path, $uploads . DIRECTORY_SEPARATOR)) {
        return '';
    }
    $variants = glob(dirname($path) . '/' . pathinfo($path, PATHINFO_FILENAME) . '-w*.webp') ?: [];
    if ($variants) {
        natsort($variants);
        $path = end($variants);
    }
    $size = @getimagesize($path);
    return $size ? 'width="' . (int) $size[0] . '" height="' . (int) $size[1] . '"' : '';
}

function content_html(string $content): string
{
    $blocks = preg_split('/\R\s*\R/u', trim($content)) ?: [];
    return implode("\n", array_map(static function (string $block): string {
        if (preg_match('/^##\s+([^\r\n]+)$/u', trim($block), $match)) {
            return '<h2>' . inline_content($match[1]) . '</h2>';
        }
        if (preg_match('/^###\s+([^\r\n]+)$/u', trim($block), $match)) {
            return '<h3>' . inline_content($match[1]) . '</h3>';
        }
        $lines = preg_split('/\R/u', trim($block)) ?: [];
        if (count($lines) >= 2 && preg_match('/^\|?\s*:?-{3,}:?\s*\|[| :\-]+$/', $lines[1])) {
            $cells = static fn ($line) => array_map('trim', explode('|', trim(trim($line), '|')));
            $head = $cells($lines[0]);
            $rows = array_map($cells, array_slice($lines, 2));
            if (count($head) > 1 && !array_filter($rows, static fn ($row) => count($row) !== count($head))) {
                return '<div class="content-table"><table><thead><tr>' . implode('', array_map(static fn ($cell) => '<th scope="col">' . inline_content($cell) . '</th>', $head)) . '</tr></thead><tbody>'
                    . implode('', array_map(static fn ($row) => '<tr>' . implode('', array_map(static fn ($cell) => '<td>' . inline_content($cell) . '</td>', $row)) . '</tr>', $rows)) . '</tbody></table></div>';
            }
        }
        if ($lines && count(array_filter($lines, static fn ($line) => (bool) preg_match('/^-\s+\S/u', $line))) === count($lines)) {
            return '<ul>' . implode('', array_map(static fn ($line) => '<li>' . inline_content(preg_replace('/^-\s+/u', '', $line)) . '</li>', $lines)) . '</ul>';
        }
        return '<p>' . nl2br(inline_content($block)) . '</p>';
    }, $blocks));
}

function inline_content(string $text): string
{
    // Escape all user HTML; permit only explicitly validated Markdown links.
    $parts = preg_split('/(\[[^\]\r\n]+\]\([^\s)]+\))/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];
    return implode('', array_map(static function (string $part): string {
        if (preg_match('/^\[([^\]]+)\]\(([^\s)]+)\)$/u', $part, $match)) {
            $target = $match[2];
            $internal = str_starts_with($target, '/') && !str_starts_with($target, '//') && !str_contains($target, '\\');
            $external = filter_var($target, FILTER_VALIDATE_URL) && in_array(parse_url($target, PHP_URL_SCHEME), ['http', 'https'], true) && !parse_url($target, PHP_URL_USER);
            if ($internal || $external) {
                return '<a href="' . e($internal ? url(ltrim($target, '/')) : $target) . '">' . e($match[1]) . '</a>';
            }
        }
        return e($part);
    }, $parts));
}

function ensure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Lax', 'cookie_secure' => request_is_https(), 'use_strict_mode' => true]);
}

function request_is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
        return true;
    }
    $trusted = array_filter(array_map('trim', explode(',', (string) config('trusted_proxies'))));
    return in_array($_SERVER['REMOTE_ADDR'] ?? '', $trusted, true) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
}

function site_is_indexable(): bool
{
    $siteUrl = config('site_url');
    $host = strtolower((string) parse_url($siteUrl, PHP_URL_HOST));
    return config('app_env') === 'production' && parse_url($siteUrl, PHP_URL_SCHEME) === 'https'
        && site_setting('indexing_enabled') === '1'
        && $host !== 'localhost' && !str_ends_with($host, '.localhost') && !str_ends_with($host, '.test')
        && !filter_var($host, FILTER_VALIDATE_IP);
}

function asset_url(string $path): string
{
    $file = __DIR__ . '/../' . $path;
    return url($path) . (is_file($file) ? '?v=' . filemtime($file) : '');
}

function display_date(string $utcValue, string $format = 'd.m.Y'): string
{
    return (new DateTimeImmutable($utcValue, new DateTimeZone('UTC')))
        ->setTimezone(new DateTimeZone('Europe/Istanbul'))->format($format);
}

function absolute_asset(?string $asset): ?string
{
    if (!$asset) {
        return null;
    }
    return str_starts_with($asset, 'http://') || str_starts_with($asset, 'https://') ? $asset : url($asset);
}

function is_admin(): bool
{
    return !empty($_SESSION['admin_id']);
}

function site_setting(string $key, string $default = ''): string
{
    static $settings;
    $settings ??= (new SettingsRepository())->all();
    return (string) ($settings[$key] ?? $default);
}

function whatsapp_url(?string $number = null, string $message = 'Merhaba, kalıp, demir ve beton uygulamaları için teklif almak istiyorum. Proje ve fotoğraflarımı paylaşabilir miyim?'): string
{
    $digits = preg_replace('/\D/', '', $number ?? site_setting('contact_whatsapp'));
    if (str_starts_with($digits, '00')) {
        $digits = substr($digits, 2);
    } elseif (strlen($digits) === 11 && str_starts_with($digits, '0')) {
        $digits = '90' . substr($digits, 1);
    } elseif (strlen($digits) === 10 && str_starts_with($digits, '5')) {
        $digits = '90' . $digits;
    }
    if (!preg_match('/^[1-9][0-9]{9,14}$/D', $digits) || preg_match('/^(?:90)?0+$/D', $digits)) {
        return '';
    }
    return 'https://wa.me/' . $digits . ($message !== '' ? '?text=' . rawurlencode($message) : '');
}

function save_upload(array $file, string $directory = 'uploads'): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || ($file['size'] ?? 0) > 8 * 1024 * 1024) {
        throw new RuntimeException('Görsel yüklenemedi veya dosya boyutu 8 MB sınırını aşıyor.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $size = @getimagesize($file['tmp_name']);
    if (!$size || $size[0] * $size[1] > 12000000 || max($size[0], $size[1]) > 8000) {
        throw new RuntimeException('Görsel en fazla 12 megapiksel ve kenar uzunluğu 8000 piksel olabilir.');
    }
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    if (!isset($extensions[$mime])) {
        throw new RuntimeException('Yalnızca JPG, PNG veya WEBP görseller yüklenebilir.');
    }
    $absolute = __DIR__ . '/../' . trim($directory, '/');
    if (!is_dir($absolute)) {
        mkdir($absolute, 0755, true);
    }
    $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($file['tmp_name'], $absolute . '/' . $filename)) {
        throw new RuntimeException('Görsel sunucuya kaydedilemedi.');
    }
    responsive_variants($absolute . '/' . $filename);
    return trim($directory, '/') . '/' . $filename;
}

function responsive_variants(string $path): void
{
    if (!function_exists('imagewebp')) {
        return;
    }
    $size = @getimagesize($path);
    if (!$size || $size[0] * $size[1] > 12000000) {
        return;
    }
    $source = match ($size['mime']) {
        'image/jpeg' => @imagecreatefromjpeg($path),
        'image/png' => @imagecreatefrompng($path),
        'image/webp' => @imagecreatefromwebp($path),
        default => false,
    };
    if (!$source) {
        return;
    }
    if ($size['mime'] === 'image/jpeg' && function_exists('exif_read_data')) {
        $exif = @exif_read_data($path);
        $orientation = (int) ($exif['Orientation'] ?? 1);
        if (in_array($orientation, [2, 4, 5, 7], true)) {
            imageflip($source, IMG_FLIP_HORIZONTAL);
        }
        $angle = match ($orientation) {
            3, 4 => 180, 6, 7 => -90, 5, 8 => 90, default => 0
        };
        if ($angle) {
            $rotated = imagerotate($source, $angle, 0);
            if ($rotated) {
                imagedestroy($source);
                $source = $rotated;
            }
        }
    }
    $width = imagesx($source);
    $height = imagesy($source);
    $stem = pathinfo($path, PATHINFO_DIRNAME) . '/' . pathinfo($path, PATHINFO_FILENAME);
    foreach (array_unique(array_map(static fn ($w) => min($w, $width), [640, 960, 1280, 1920])) as $targetWidth) {
        $targetHeight = max(1, (int) round($height * $targetWidth / $width));
        $target = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($target, false);
        imagesavealpha($target, true);
        imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
        imagewebp($target, $stem . '-w' . $targetWidth . '.webp', 80);
        imagedestroy($target);
    }
    imagedestroy($source);
}

function responsive_image(?string $asset, string $sizes = '(max-width: 720px) 100vw, 50vw'): string
{
    if (!$asset || !str_starts_with($asset, 'uploads/') || str_contains($asset, '..')) {
        return '';
    }
    $stem = pathinfo($asset, PATHINFO_DIRNAME) . '/' . pathinfo($asset, PATHINFO_FILENAME);
    $variants = [];
    foreach (glob(__DIR__ . '/../' . $stem . '-w*.webp') ?: [] as $path) {
        if (preg_match('/-w(\d+)\.webp$/', $path, $match)) {
            $variants[(int) $match[1]] = url($stem . '-w' . $match[1] . '.webp') . ' ' . $match[1] . 'w';
        }
    }
    ksort($variants);
    return $variants ? 'srcset="' . e(implode(', ', $variants)) . '" sizes="' . e($sizes) . '"' : '';
}
