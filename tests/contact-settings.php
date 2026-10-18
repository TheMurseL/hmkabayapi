<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/../app/bootstrap.php';

function checkContact(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function contactRequest(string $base, string $path, string $jar, ?array $data = null, bool $multipart = false): array
{
    $handle = curl_init($base . '/' . ltrim($path, '/'));
    curl_setopt_array($handle, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_TIMEOUT => 15, CURLOPT_PROXY => '']);
    if ($data !== null) {
        curl_setopt($handle, CURLOPT_POSTFIELDS, $multipart ? $data : http_build_query($data));
    }
    $body = curl_exec($handle);
    $status = curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
    $error = curl_error($handle);
    curl_close($handle);
    checkContact($body !== false, 'Local HTTP request failed: ' . $error);
    return ['status' => $status, 'body' => $body];
}

function contactDocument(string $html): DOMXPath
{
    $document = new DOMDocument('1.0', 'UTF-8');
    $document->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
    return new DOMXPath($document);
}

function contactSettingsForm(string $html): array
{
    $xpath = contactDocument($html);
    $form = $xpath->query('//form[.//input[@name="type" and @value="settings"]]')->item(0);
    checkContact($form !== null, 'Authenticated settings form is missing.');
    $data = [];
    foreach ($xpath->query('.//input[@name] | .//textarea[@name] | .//select[@name]', $form) as $control) {
        $type = strtolower($control->getAttribute('type'));
        if ($control->hasAttribute('disabled') || in_array($type, ['file', 'submit', 'button', 'reset', 'image'], true)
            || (in_array($type, ['checkbox', 'radio'], true) && !$control->hasAttribute('checked'))) {
            continue;
        }
        if ($control->tagName === 'textarea') {
            $value = $control->textContent;
        } elseif ($control->tagName === 'select') {
            $option = $xpath->query('.//option[@selected]', $control)->item(0) ?? $xpath->query('.//option', $control)->item(0);
            $value = $option ? ($option->hasAttribute('value') ? $option->getAttribute('value') : $option->textContent) : '';
        } else {
            $value = $control->hasAttribute('value') ? $control->getAttribute('value') : 'on';
        }
        $data[$control->getAttribute('name')] = $value;
    }
    foreach (['_csrf', 'contact_whatsapp', 'service_areas', 'service_districts', 'hero_image', 'business_about', 'about_image'] as $key) {
        checkContact(array_key_exists($key, $data), 'Settings control missing: ' . $key);
    }
    checkContact($xpath->query('.//input[@type="file" and @name="about_image_upload"]', $form)->length === 1, 'About page image upload control is missing.');
    return $data;
}

$base = rtrim((string) config('site_url'), '/');
$origin = parse_url($base);
checkContact(in_array($origin['scheme'] ?? '', ['http', 'https'], true)
    && in_array(strtolower($origin['host'] ?? ''), ['localhost', '127.0.0.1', '[::1]', '::1'], true)
    && !isset($origin['user']) && !isset($origin['pass']) && !isset($origin['query']) && !isset($origin['fragment']),
    'This integration test may only run against a local site.');

$pdo = db();
$original = (new SettingsRepository())->all();
$jar = tempnam(sys_get_temp_dir(), 'insaat-contact-');
$fixture = tempnam(sys_get_temp_dir(), 'insaat-contact-image-');
$uploads = realpath(__DIR__ . '/../uploads');
checkContact($jar !== false && $fixture !== false && $uploads !== false, 'Temporary files or upload directory unavailable.');
$initialFiles = array_fill_keys(glob($uploads . '/*') ?: [], true);
$adminId = null;
$managedKeys = [];
$ownedImages = [];
$settingsTouched = false;
$fixtureHash = '';

try {
    $email = 'contact-settings-' . bin2hex(random_bytes(8)) . '@example.invalid';
    $password = bin2hex(random_bytes(24));
    $pdo->prepare('INSERT INTO admins (name, email, password) VALUES (?, ?, ?)')
        ->execute(['Geçici iletişim ayarı testi', $email, password_hash($password, PASSWORD_DEFAULT)]);
    $adminId = (int) $pdo->lastInsertId();
    $login = contactRequest($base, 'admin/', $jar);
    $token = contactDocument($login['body'])->query('//input[@name="_csrf"]')->item(0);
    checkContact($login['status'] === 200 && $token !== null, 'Login CSRF token is missing.');
    $response = contactRequest($base, 'admin/', $jar, ['_csrf' => $token->getAttribute('value'), 'email' => $email, 'password' => $password]);
    unset($password);
    checkContact($response['status'] === 303, 'Temporary admin login failed.');

    $panel = contactRequest($base, 'admin/?section=settings', $jar);
    checkContact($panel['status'] === 200, 'Settings page unavailable.');
    $payload = contactSettingsForm($panel['body']);
    $managedKeys = array_values(array_diff(array_unique(array_merge(array_keys($payload), ['indexing_enabled', 'calc_rates_verified'])), ['_csrf', 'type']));
    $payload['contact_whatsapp'] = '+90 (000) 000 00 01';
    $payload['service_areas'] = 'İstanbul — geçici ayar kontrolü';
    $payload['service_districts'] = "Kadıköy\nÜsküdar";
    $payload['business_about'] = "Hakkımızda geçici tanıtım testi: kalıp, demir ve beton uygulamaları.\n\nProje kapsamına göre sahadaki uygulamaları planlıyoruz.";

    $image = imagecreatetruecolor(1200, 800);
    imagefill($image, 0, 0, imagecolorallocate($image, 114, 91, 66));
    imagepng($image, $fixture);
    imagedestroy($image);
    $fixtureHash = hash_file('sha256', $fixture);
    $payload['hero_image_upload'] = new CURLFile($fixture, 'image/png', 'contact-settings-test.png');
    $payload['about_image_upload'] = new CURLFile($fixture, 'image/png', 'about-settings-test.png');
    $settingsTouched = true;
    $response = contactRequest($base, 'admin/?section=settings', $jar, $payload, true);
    checkContact($response['status'] === 303, 'Settings and image upload did not save.');
    $saved = (new SettingsRepository())->all();
    checkContact($saved['contact_whatsapp'] === $payload['contact_whatsapp']
        && $saved['service_areas'] === $payload['service_areas'] && $saved['service_districts'] === $payload['service_districts']
        && $saved['business_about'] === $payload['business_about'], 'Contact, region or about settings did not persist.');
    foreach ($original as $key => $value) {
        if (!in_array($key, ['contact_whatsapp', 'service_areas', 'service_districts', 'hero_image', 'business_about', 'about_image'], true)) {
            checkContact(array_key_exists($key, $saved) && $saved[$key] === $value, 'Unrelated setting changed: ' . $key);
        }
    }
    foreach (['hero_image', 'about_image'] as $key) {
        checkContact(preg_match('/^uploads\/[a-f0-9]{32}\.png$/D', $saved[$key]) === 1
            && is_file(__DIR__ . '/../' . $saved[$key]), 'Uploaded image was not stored: ' . $key);
        $path = __DIR__ . '/../' . $saved[$key];
        checkContact(hash_file('sha256', $path) === $fixtureHash, 'Stored image content changed.');
        checkContact(count(glob(substr($path, 0, -4) . '-w*.webp') ?: []) >= 2, 'Responsive upload variants missing.');
    }

    foreach (['', 'iletisim', 'istanbul-kaba-insaat'] as $path) {
        $page = contactRequest($base, $path, $jar);
        checkContact($page['status'] === 200, 'Public contact page unavailable: ' . $path);
        $xpath = contactDocument($page['body']);
        $links = $xpath->query('//a[starts-with(@href, "https://wa.me/")]');
        checkContact($links->length >= 2, 'Shared WhatsApp links missing: ' . $path);
        foreach ($links as $link) {
            $target = parse_url($link->getAttribute('href'));
            parse_str($target['query'] ?? '', $query);
            checkContact(($target['path'] ?? '') === '/900000000001' && str_contains($query['text'] ?? '', 'Proje ve fotoğraflarımı'), 'WhatsApp link did not use the saved number and message.');
            checkContact($link->getAttribute('target') === '_blank' && str_contains($link->getAttribute('rel'), 'noopener') && str_contains($link->getAttribute('rel'), 'noreferrer'), 'External link protections missing.');
        }
        checkContact(str_contains($page['body'], $payload['service_areas']), 'Saved service area is missing from page text.');
        $districts = [];
        foreach ($xpath->query('//details[contains(@class, "service-districts")]//li') as $district) {
            $districts[] = trim($district->textContent);
        }
        if ($path !== 'istanbul-kaba-insaat') {
            checkContact($districts === ['Kadıköy', 'Üsküdar'], 'District list did not use the saved entries: ' . $path);
        }
        $schema = $xpath->query('//script[@type="application/ld+json"]')->item(0);
        $graph = json_decode($schema?->textContent ?? '', true, 512, JSON_THROW_ON_ERROR)['@graph'] ?? [];
        $areaNodes = array_values(array_filter($graph, static fn (array $node): bool => isset($node['areaServed'])));
        checkContact(count($areaNodes) >= ($path === 'istanbul-kaba-insaat' ? 2 : 1), 'Organization/service area schema is missing.');
        foreach ($areaNodes as $node) {
            checkContact(in_array($payload['service_areas'], $node['areaServed'], true), 'Service area schema did not use the saved setting.');
        }
        if ($path === '') {
            checkContact($xpath->query('//img[contains(@class, "hero-photo") and @src="' . url($saved['hero_image']) . '"]')->length === 1, 'Uploaded hero image did not render.');
        }
    }
    $about = contactRequest($base, 'hakkimizda', $jar);
    checkContact($about['status'] === 200, 'About page is unavailable.');
    $aboutDocument = contactDocument($about['body']);
    $aboutSection = $aboutDocument->query('//section[contains(concat(" ", normalize-space(@class), " "), " about-page ")]')->item(0);
    checkContact($aboutSection !== null, 'About page content section is missing.');
    foreach (explode("\n\n", $payload['business_about']) as $paragraph) {
        checkContact(str_contains($aboutSection->textContent, $paragraph), 'Saved about paragraph did not render.');
    }
    checkContact($aboutDocument->query('//*[contains(concat(" ", normalize-space(@class), " "), " about-page-image ")]//img[@src="' . url($saved['about_image']) . '"]')->length === 1, 'Uploaded about image did not render.');

    $panel = contactRequest($base, 'admin/?section=settings', $jar);
    $payload = contactSettingsForm($panel['body']);
    $payload['contact_whatsapp'] = '';
    checkContact(contactRequest($base, 'admin/?section=settings', $jar, $payload)['status'] === 303, 'Clearing WhatsApp did not save.');
    foreach (['', 'iletisim', 'istanbul-kaba-insaat'] as $path) {
        $page = contactRequest($base, $path, $jar);
        $xpath = contactDocument($page['body']);
        checkContact($page['status'] === 200 && $xpath->query('//a[starts-with(@href, "https://wa.me/")]')->length === 0
            && $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " whatsapp-floating ")]')->length === 0,
            'Empty WhatsApp setting did not hide all links: ' . $path);
    }
} finally {
    $cleanupErrors = [];
    try {
        if ($settingsTouched) {
            $pdo->beginTransaction();
            $restore = $pdo->prepare('INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
            $remove = $pdo->prepare('DELETE FROM site_settings WHERE setting_key = ?');
            foreach ($managedKeys as $key) {
                if (array_key_exists($key, $original)) {
                    $restore->execute([$key, $original[$key]]);
                } else {
                    $remove->execute([$key]);
                }
            }
            $pdo->commit();
            $restored = (new SettingsRepository())->all();
            ksort($original);
            ksort($restored);
            checkContact($restored === $original, 'Settings were not restored exactly.');
        }
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $cleanupErrors[] = $error->getMessage();
    }
    try {
        if ($adminId !== null) {
            contactRequest($base, 'admin/?logout=1', $jar);
        }
    } catch (Throwable $error) {
        $cleanupErrors[] = $error->getMessage();
    }
    try {
        if ($adminId !== null) {
            $pdo->prepare('DELETE FROM admins WHERE id = ? AND email = ?')->execute([$adminId, $email]);
        }
    } catch (Throwable $error) {
        $cleanupErrors[] = $error->getMessage();
    }
    // Identify only new copies of our exact image, including uploads saved before a failed POST.
    foreach (glob($uploads . '/*.png') ?: [] as $path) {
        if (!isset($initialFiles[$path]) && preg_match('/^[a-f0-9]{32}\.png$/D', basename($path))
            && $fixtureHash !== '' && hash_file('sha256', $path) === $fixtureHash) {
            $ownedImages[] = $path;
        }
    }
    foreach ($ownedImages as $path) {
        foreach (array_merge([$path], glob(substr($path, 0, -4) . '-w*.webp') ?: []) as $ownedFile) {
            if (is_file($ownedFile) && dirname((string) realpath($ownedFile)) === $uploads && !unlink($ownedFile)) {
                $cleanupErrors[] = 'Temporary uploaded image could not be removed.';
            }
        }
    }
    foreach ([$fixture, $jar] as $path) {
        if (is_file($path) && !unlink($path)) {
            $cleanupErrors[] = 'Temporary test file could not be removed.';
        }
    }
    checkContact(!$cleanupErrors, 'Test cleanup failed: ' . implode('; ', $cleanupErrors));
}

echo "Dynamic WhatsApp, districts, schema, about page and admin image upload checks passed; original settings restored and fixtures removed.\n";
