<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/../app/bootstrap.php';

function authCheck(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function authRequest(string $jar, string $path = 'admin/', ?array $payload = null): array
{
    $headers = [];
    $handle = curl_init(url($path));
    curl_setopt_array($handle, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_PROXY => '',
        CURLOPT_HEADERFUNCTION => static function ($handle, string $line) use (&$headers): int {
            if (str_contains($line, ':')) {
                [$name, $value] = explode(':', $line, 2);
                $headers[strtolower(trim($name))][] = trim($value);
            }
            return strlen($line);
        },
    ]);
    if ($payload !== null) {
        curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query($payload));
    }
    $body = curl_exec($handle);
    $status = curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
    $error = curl_error($handle);
    curl_close($handle);
    authCheck($body !== false, 'Local admin HTTP request failed: ' . $error);
    authCheck(!preg_match('/Fatal error|Stack trace|Uncaught TypeError/', $body), 'Admin response exposed a server error.');
    return compact('status', 'headers', 'body');
}

function authToken(array $response): string
{
    preg_match('/name="_csrf" value="([a-f0-9]+)"/', $response['body'], $match);
    authCheck(isset($match[1]), 'Admin CSRF token is missing.');
    return $match[1];
}

$origin = parse_url((string) config('site_url'));
authCheck(config('app_env') === 'local'
    && in_array($origin['scheme'] ?? '', ['http', 'https'], true)
    && in_array(strtolower($origin['host'] ?? ''), ['localhost', '127.0.0.1', '[::1]', '::1'], true)
    && !isset($origin['user']) && !isset($origin['pass']) && !isset($origin['query']) && !isset($origin['fragment']),
    'Admin integration tests may only run against the local site.');

$jar = tempnam(sys_get_temp_dir(), 'insaat-auth-');
$freshJar = tempnam(sys_get_temp_dir(), 'insaat-auth-fresh-');
authCheck($jar !== false && $freshJar !== false, 'Temporary cookie storage is unavailable.');
$email = 'auth-fixture-' . bin2hex(random_bytes(10)) . '@example.invalid';
$oldPassword = bin2hex(random_bytes(20));
$newPassword = bin2hex(random_bytes(20));
$adminId = null;
$existingAdmins = db()->query('SELECT id, password FROM admins ORDER BY id')->fetchAll();
try {
    db()->prepare('INSERT INTO admins (name, email, password) VALUES (?, ?, ?)')
        ->execute(['Geçici kimlik doğrulama kontrolü', $email, password_hash($oldPassword, PASSWORD_DEFAULT)]);
    $adminId = (int) db()->lastInsertId();
    $storedHash = static function () use ($adminId): string {
        $statement = db()->prepare('SELECT password FROM admins WHERE id = ?');
        $statement->execute([$adminId]);
        return (string) $statement->fetchColumn();
    };
    $originalHash = $storedHash();
    $csrf = authToken(authRequest($jar));
    foreach ([['email' => [$email], 'password' => $oldPassword], ['email' => $email, 'password' => [$oldPassword]]] as $malformed) {
        $response = authRequest($jar, 'admin/', ['_csrf' => $csrf] + $malformed);
        authCheck($response['status'] === 200 && str_contains($response['body'], 'E-posta veya şifre hatalı.'), 'Malformed login input must show the generic login error.');
    }
    $response = authRequest($jar, 'admin/', ['_csrf' => $csrf, 'email' => $email, 'password' => $oldPassword]);
    authCheck($response['status'] === 303, 'Temporary admin login failed.');
    $settings = authRequest($jar, 'admin/?section=settings');
    $csrf = authToken($settings);
    $document = new DOMDocument('1.0', 'UTF-8');
    $document->loadHTML('<?xml encoding="UTF-8">' . $settings['body'], LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($document);
    authCheck($xpath->query('//form[.//input[@name="type" and @value="admin_password"]]')->length === 1, 'Separate password form is missing.');
    authCheck($xpath->query('//form[.//input[@name="type" and @value="settings"]]//input[@type="password"]')->length === 0, 'Password controls must stay outside the site settings form.');
    authCheck($xpath->query('//form[.//input[@name="type" and @value="admin_password"]]//input[@type="password" and @value]')->length === 0, 'Password controls must never be repopulated.');
    $payload = ['_csrf' => $csrf, 'type' => 'admin_password', 'current_password' => $oldPassword, 'new_password' => $newPassword, 'password_confirmation' => $newPassword];

    $response = authRequest($jar, 'admin/?section=settings', array_replace($payload, ['_csrf' => 'invalid']));
    authCheck($response['status'] === 403 && $storedHash() === $originalHash, 'Invalid CSRF must leave the password unchanged.');
    $invalidInputs = [
        ['current_password' => 'incorrect-current-password'],
        ['new_password' => 'short', 'password_confirmation' => 'short'],
        ['new_password' => str_repeat('a', 73), 'password_confirmation' => str_repeat('a', 73)],
        ['new_password' => str_repeat('ü', 40), 'password_confirmation' => str_repeat('ü', 40)],
        ['password_confirmation' => 'different-confirmation'],
        ['current_password' => [$oldPassword]],
        ['new_password' => [$newPassword]],
        ['password_confirmation' => [$newPassword]],
    ];
    foreach ($invalidInputs as $invalid) {
        $response = authRequest($jar, 'admin/?section=settings', array_replace($payload, $invalid));
        authCheck($response['status'] === 200 && $storedHash() === $originalHash, 'Invalid password change must leave the password unchanged.');
        authCheck(!str_contains($response['body'], $oldPassword) && !str_contains($response['body'], $newPassword), 'Submitted passwords must never appear in the response.');
    }
    $response = authRequest($jar, 'admin/?section=settings', $payload);
    $changedHash = $storedHash();
    authCheck($response['status'] === 303 && password_verify($newPassword, $changedHash) && !password_verify($oldPassword, $changedHash), 'Valid password change was not saved securely.');
    authCheck(isset($response['headers']['set-cookie']), 'Password change must regenerate the session.');
    $settings = authRequest($jar, 'admin/?section=settings');
    authCheck(str_contains($settings['body'], 'Yönetici parolanız güncellendi.') && str_contains($settings['body'], 'name="type" value="admin_password"'), 'Authenticated session or success flash was lost after password change.');

    $freshCsrf = authToken(authRequest($freshJar));
    $response = authRequest($freshJar, 'admin/', ['_csrf' => $freshCsrf, 'email' => $email, 'password' => $oldPassword]);
    authCheck($response['status'] === 200 && str_contains($response['body'], 'E-posta veya şifre hatalı.'), 'Old password must fail in a fresh session.');
    $response = authRequest($freshJar, 'admin/', ['_csrf' => $freshCsrf, 'email' => $email, 'password' => $newPassword]);
    authCheck($response['status'] === 303, 'New password must work in a fresh session.');
    $statement = db()->prepare('SELECT id, password FROM admins WHERE id <> ? ORDER BY id');
    $statement->execute([$adminId]);
    authCheck($statement->fetchAll() === $existingAdmins, 'A pre-existing administrator account was modified.');
    echo "Admin malformed input, password validation, CSRF, session renewal and fresh login checks passed; temporary admin removed.\n";
} finally {
    if ($adminId !== null) {
        db()->prepare('DELETE FROM admins WHERE id = ? AND email = ?')->execute([$adminId, $email]);
    }
    foreach ([$jar, $freshJar] as $path) {
        if (is_file($path)) {
            unlink($path);
        }
    }
}
