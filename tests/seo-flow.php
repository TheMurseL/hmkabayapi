<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

function expect(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function request_page(string $path, string $cookieJar, ?array $data = null): array
{
    $curl = curl_init(url($path));
    $headers = [];
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_COOKIEJAR => $cookieJar, CURLOPT_COOKIEFILE => $cookieJar, CURLOPT_TIMEOUT => 15, CURLOPT_HEADERFUNCTION => static function ($handle, string $line) use (&$headers): int {
        if (str_contains($line, ':')) {
            [$key, $value] = explode(':', $line, 2);
            $headers[strtolower(trim($key))] = trim($value);
        }
        return strlen($line);
    }]);
    if ($data !== null) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
    }
    $body = curl_exec($curl);
    if ($body === false) {
        throw new RuntimeException(curl_error($curl));
    }
    $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    curl_close($curl);
    return compact('status', 'headers', 'body');
}

$jar = tempnam(sys_get_temp_dir(), 'insaat-seo-flow-');
$tokens = [];
$fixtureStem = 'seo-fixture-' . bin2hex(random_bytes(8));
$fixture = __DIR__ . '/../uploads/' . $fixtureStem . '.png';
try {
    $home = request_page('', $jar);
    expect($home['status'] === 200 && !isset($home['headers']['set-cookie']), 'Public GET must not create a session.');
    $page = request_page('iletisim?from=hesaplamalar/kaba-insaat-maliyeti&area=750', $jar);
    expect($page['status'] === 200 && isset($page['headers']['set-cookie']), 'Contact must create its CSRF session.');
    preg_match('/name="_csrf" value="([^"]+)"/', $page['body'], $csrf);
    preg_match('/name="request_token" value="([^"]+)"/', $page['body'], $token);
    expect(isset($csrf[1], $token[1]), 'Contact tokens missing.');
    $tokens[] = $token[1];
    $payload = ['_csrf' => $csrf[1], 'request_token' => $token[1], 'name' => 'Otomatik kontrol', 'email' => '', 'phone' => '05000000000', 'message' => 'Geçici form bütünlük kontrolü', 'district' => 'Test', 'building_type' => 'Villa', 'area' => '750', 'stage' => 'Proje hazırlığı', 'website' => ''];
    $response = request_page('iletisim', $jar, $payload);
    expect($response['status'] === 303, 'Valid phone-only submission must use PRG 303.');
    $query = db()->prepare('SELECT * FROM messages WHERE request_token=?');
    $query->execute([$token[1]]);
    $message = $query->fetch();
    expect($message && $message['source'] === 'hesaplamalar/kaba-insaat-maliyeti' && (float) $message['area'] === 750.0, 'Source/area lost in lead save.');
    $success = request_page('iletisim', $jar);
    expect(str_contains($success['body'], 'data-lead-success'), 'Successful save marker missing.');
    expect(!str_contains(request_page('iletisim', $jar)['body'], 'data-lead-success'), 'Lead success must not repeat on refresh.');
    expect(request_page('iletisim', $jar, $payload)['status'] === 303, 'Repeated submission must be safely acknowledged.');
    $query = db()->prepare('SELECT COUNT(*) FROM messages WHERE request_token=?');
    $query->execute([$token[1]]);
    expect((int) $query->fetchColumn() === 1, 'Duplicate lead inserted.');
    expect(!str_contains(request_page('iletisim', $jar)['body'], 'data-lead-success'), 'Duplicate must not generate another lead event.');
    $payload['_csrf'] = 'invalid';
    $invalidCsrf = request_page('iletisim', $jar, $payload);
    expect($invalidCsrf['status'] === 403, 'Invalid CSRF must fail; received ' . $invalidCsrf['status']);
    $page = request_page('iletisim', $jar);
    preg_match('/name="request_token" value="([^"]+)"/', $page['body'], $spamToken);
    $tokens[] = $spamToken[1];
    $payload['_csrf'] = $csrf[1];
    $payload['request_token'] = $spamToken[1];
    $payload['website'] = 'spam';
    expect(request_page('iletisim', $jar, $payload)['status'] === 200, 'Honeypot must show form validation.');
    $query->execute([$spamToken[1]]);
    expect((int) $query->fetchColumn() === 0, 'Honeypot inserted a lead.');

    $html = content_html("## Başlık\n\n- [Hizmet](/istanbul-kaba-insaat)\n- [Risk](javascript:alert)\n\nKalem | Açıklama\n--- | ---\n<script> | [Dış](https://example.com)");
    expect(str_contains($html, '<table>') && str_contains($html, '<ul>') && str_contains($html, url('istanbul-kaba-insaat')), 'Markdown links/lists/table failed.');
    expect(!str_contains($html, '<script>') && !str_contains($html, 'href="javascript:'), 'Markdown XSS protection failed.');

    if (!is_dir(dirname($fixture))) {
        mkdir(dirname($fixture), 0755, true);
    }
    $image = imagecreatetruecolor(1400, 700);
    imagepng($image, $fixture);
    imagedestroy($image);
    responsive_variants($fixture);
    $variants = responsive_image('uploads/' . $fixtureStem . '.png');
    expect(str_contains($variants, '640w') && str_contains($variants, '960w') && str_contains($variants, '1280w') && !str_contains($variants, '1920w'), 'Responsive variants must not upscale.');
    expect(str_contains(image_dimensions('uploads/' . $fixtureStem . '.png'), 'width="1400"'), 'Intrinsic dimensions missing.');
    expect(responsive_image('../app/helpers.php') === '', 'Responsive image traversal must be rejected.');
    echo "HTTP lead, CSRF, duplicate, attribution, Markdown and image checks passed; fixtures removed.\n";
} finally {
    foreach ($tokens as $token) {
        db()->prepare('DELETE FROM messages WHERE request_token=?')->execute([$token]);
    }
    if (is_file($jar)) {
        unlink($jar);
    }
    foreach (glob(__DIR__ . '/../uploads/' . $fixtureStem . '*') ?: [] as $path) {
        if (is_file($path)) {
            unlink($path);
        }
    }
}
