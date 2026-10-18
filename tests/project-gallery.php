<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/../app/bootstrap.php';

function checkGallery(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function galleryDocument(string $html): DOMXPath
{
    $document = new DOMDocument();
    @$document->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NONET);
    return new DOMXPath($document);
}

$base = config('site_url');
checkGallery(in_array(parse_url($base, PHP_URL_HOST), ['localhost', '127.0.0.1'], true), 'Only local integration testing is allowed.');
$jar = tempnam(sys_get_temp_dir(), 'project-gallery-');
$fixture = tempnam(sys_get_temp_dir(), 'project-gallery-image-');
$request = static function (string $path, ?array $data = null) use ($base, $jar): array {
    $handle = curl_init($base . '/' . $path);
    curl_setopt_array($handle, [CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar, CURLOPT_TIMEOUT => 15, CURLOPT_PROXY => '']);
    if ($data !== null) {
        curl_setopt($handle, CURLOPT_POSTFIELDS, $data);
    }
    $body = curl_exec($handle);
    $status = curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
    $error = curl_error($handle);
    curl_close($handle);
    checkGallery(is_string($body), 'HTTP request failed: ' . $error);
    return [$status, $body];
};
$tokenFrom = static function (string $html): string {
    $token = galleryDocument($html)->query('//input[@name="_csrf"]')->item(0);
    checkGallery($token !== null, 'CSRF token is missing.');
    return $token->getAttribute('value');
};
$pdo = db();
$adminId = null;
$projectIds = [];
$uploads = realpath(__DIR__ . '/../uploads');
$initialFiles = array_fill_keys(glob($uploads . '/*') ?: [], true);
$fixtureHash = '';
$email = 'gallery-' . bin2hex(random_bytes(8)) . '@example.invalid';
$slug = 'gallery-test-' . bin2hex(random_bytes(8));

try {
    $image = imagecreatetruecolor(320, 200);
    imagefill($image, 0, 0, imagecolorallocate($image, 51, 106, 84));
    imagepng($image, $fixture);
    imagedestroy($image);
    $fixtureHash = hash_file('sha256', $fixture);
    $password = bin2hex(random_bytes(24));
    $pdo->prepare('INSERT INTO admins (name, email, password) VALUES (?, ?, ?)')
        ->execute(['Geçici galeri testi', $email, password_hash($password, PASSWORD_DEFAULT)]);
    $adminId = (int) $pdo->lastInsertId();
    [, $login] = $request('admin/');
    [$status] = $request('admin/', ['_csrf' => $tokenFrom($login), 'email' => $email, 'password' => $password]);
    checkGallery($status === 303, 'Temporary admin login failed.');
    unset($password);
    [, $panel] = $request('admin/?section=projects&new_project=1');
    $token = $tokenFrom($panel);
    checkGallery(galleryDocument($panel)->query('//input[@name="gallery_images[]" and @multiple]')->length === 1, 'New-project multi-upload control missing.');
    $payload = ['_csrf' => $token, 'type' => 'project', 'id' => '0', 'title' => $slug,
        'category' => 'completed', 'location' => 'İstanbul', 'year' => '2026', 'area' => '100 m²',
        'summary' => 'Geçici galeri testi', 'description' => 'Geçici galeri testi', 'is_demo' => '1',
        'image' => new CURLFile($fixture, 'image/png', 'cover.png'),
        'gallery_images[0]' => new CURLFile($fixture, 'image/png', 'detail-one.png'),
        'gallery_images[1]' => new CURLFile($fixture, 'image/png', 'detail-two.png')];
    [$status] = $request('admin/?section=projects&new_project=1', $payload);
    $repository = new ProjectRepository();
    $project = $repository->findBySlug($slug);
    if ($project) {
        $projectIds[] = (int) $project['id'];
    }
    checkGallery($status === 303 && $project !== null, 'Project with multiple images was not created.');
    $projectId = (int) $project['id'];
    $gallery = $repository->gallery($projectId);
    checkGallery(count($gallery) === 2, 'Multi-file upload did not save both gallery images.');
    $originalCover = $project['image'];
    $selectedCover = $gallery[1]['image'];
    [$status] = $request('admin/?section=projects&edit_project=' . $projectId, [
        '_csrf' => $token, 'type' => 'project_cover', 'project_id' => $projectId, 'id' => $gallery[1]['id']]);
    checkGallery($status === 303, 'Cover selection failed.');
    $project = $repository->findBySlug($slug);
    $gallery = $repository->gallery($projectId);
    checkGallery($project['image'] === $selectedCover && in_array($originalCover, array_column($gallery, 'image'), true), 'Cover selection lost an image.');
    $update = $payload;
    unset($update['image'], $update['gallery_images[1]']);
    $update['id'] = (string) $projectId;
    $update['image_url'] = $selectedCover;
    [$status] = $request('admin/?section=projects&edit_project=' . $projectId, $update);
    checkGallery($status === 303 && count($repository->gallery($projectId)) === 3, 'Adding photos replaced existing gallery images.');
    checkGallery($repository->findBySlug($slug)['image'] === $selectedCover, 'Adding gallery photos changed the selected cover.');
    [, $listing] = $request('projeler');
    $listImage = galleryDocument($listing)->query('//a[contains(@href,"/proje/' . $slug . '")]//img')->item(0);
    checkGallery($listImage !== null && $listImage->getAttribute('src') === project_image($selectedCover), 'Listing cover is stale.');
    [, $detail] = $request('proje/' . $slug);
    $xpath = galleryDocument($detail);
    checkGallery($xpath->query('//a[@class="project-cover"]/img')->item(0)->getAttribute('src') === project_image($selectedCover), 'Detail cover is stale.');
    checkGallery($xpath->query('//div[@class="gallery-grid"]/a')->length === 3, 'Detail thumbnails are missing.');
    checkGallery($xpath->query('//a[@data-fancybox="project-gallery"]')->length === 4, 'Cover and thumbnails do not share a lightbox.');
    [, $panel] = $request('admin/?section=projects&edit_project=' . $projectId);
    checkGallery(galleryDocument($panel)->query('//input[@name="type" and @value="project_cover"]')->length === 3, 'Gallery cover buttons are missing.');

    // Failed gallery uploads must not leave a partially created project.
    unset($payload['image'], $payload['gallery_images[0]'], $payload['gallery_images[1]']);
    $payload['title'] = $slug . '-invalid';
    $payload['image_url'] = $originalCover;
    $payload['gallery_images[0]'] = new CURLFile(__FILE__, 'text/plain', 'invalid.txt');
    [$status, $invalidPanel] = $request('admin/?section=projects&new_project=1', $payload);
    $invalid = $repository->findBySlug($payload['title']);
    if ($invalid) {
        $projectIds[] = (int) $invalid['id'];
    }
    checkGallery($status === 200 && $invalid === null && str_contains($invalidPanel, 'Görsel'), 'Invalid upload left a partially saved project.');
} finally {
    foreach ($projectIds as $id) {
        $pdo->prepare('DELETE FROM projects WHERE id = ?')->execute([$id]);
    }
    if ($adminId !== null) {
        $request('admin/?logout=1');
        $pdo->prepare('DELETE FROM admins WHERE id = ? AND email = ?')->execute([$adminId, $email]);
    }
    foreach (glob($uploads . '/*.png') ?: [] as $path) {
        if (!isset($initialFiles[$path]) && preg_match('/^[a-f0-9]{32}\.png$/D', basename($path))
            && $fixtureHash !== '' && hash_file('sha256', $path) === $fixtureHash) {
            foreach (array_merge([$path], glob(substr($path, 0, -4) . '-w*.webp') ?: []) as $ownedFile) {
                checkGallery(dirname((string) realpath($ownedFile)) === $uploads && unlink($ownedFile), 'Temporary image cleanup failed.');
            }
        }
    }
    unlink($fixture);
    unlink($jar);
}
echo "Project multi-upload, cover selection, listing/detail thumbnails and failed-upload rollback passed; temporary data removed.\n";
