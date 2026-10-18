<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/ui.php';
ensure_session();
header('X-Robots-Tag: noindex, nofollow');

function admin_slug(string $table, string $title, ?int $ignoreId = null): string
{
    if ($ignoreId) {
        $existing = db()->prepare("SELECT slug FROM {$table} WHERE id = ?");
        $existing->execute([$ignoreId]);
        if ($slug = $existing->fetchColumn()) {
            return (string) $slug;
        }
    }
    $base = slugify($title) ?: 'kayit';
    $candidate = $base;
    $number = 2;
    do {
        $sql = "SELECT id FROM {$table} WHERE slug = :slug" . ($ignoreId ? ' AND id != :id' : '') . ' LIMIT 1';
        $stmt = db()->prepare($sql);
        $params = [
            'slug' => $candidate,
        ];
        if ($ignoreId) {
            $params['id'] = $ignoreId;
        }
        $stmt->execute($params);
        if (!$stmt->fetch()) {
            return $candidate;
        }
        $candidate = $base . '-' . $number++;
    } while (true);
}

function admin_image(array $file, string $fallback = ''): string
{
    return save_upload($file) ?: $fallback;
}

function save_gallery_images(int $projectId, array $files): void
{
    if (empty($files['name']) || !is_array($files['name'])) {
        return;
    }
    $sort = (int) (db()->query('SELECT COALESCE(MAX(sort_order), 0) FROM project_gallery WHERE project_id = ' . $projectId)->fetchColumn() ?: 0);
    foreach ($files['name'] as $index => $name) {
        if (($files['error'][$index] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $file = [
            'name' => $name,
            'type' => $files['type'][$index] ?? '',
            'tmp_name' => $files['tmp_name'][$index] ?? '',
            'error' => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
            'size' => $files['size'][$index] ?? 0,
        ];
        $path = save_upload($file);
        db()->prepare('INSERT INTO project_gallery (project_id, image, sort_order) VALUES (?, ?, ?)')->execute([$projectId, $path, ++$sort]);
    }
}

if (isset($_GET['logout'])) {
    unset($_SESSION['admin_id']);
    redirect('admin');
}

if (!is_admin()) {
    $error = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();
        $email = $_POST['email'] ?? null;
        $password = $_POST['password'] ?? null;
        $admin = false;
        if (is_string($email) && is_string($password)) {
            $stmt = db()->prepare('SELECT * FROM admins WHERE email = :email LIMIT 1');
            $stmt->execute(['email' => trim($email)]);
            $admin = $stmt->fetch();
        }
        if ($admin && password_verify($password, $admin['password'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = $admin['id'];
            redirect('admin');
        }
        $error = 'E-posta veya şifre hatalı.';
    }
    require __DIR__ . '/login.php';
    exit;
}

$settings = new SettingsRepository();
$serviceRepository = new ServiceRepository();
$projectRepository = new ProjectRepository();
$section = $_GET['section'] ?? 'dashboard';
$sectionTitles = [
    'dashboard' => 'Genel bakış',
    'projects' => 'Projeler',
    'services' => 'Hizmetler',
    'blog' => 'Blog',
    'settings' => 'SEO & site ayarları',
    'messages' => 'Mesajlar',
    'seo' => 'SEO kontrol merkezi',
];
$allowedSections = array_keys($sectionTitles);
if (!in_array($section, $allowedSections, true)) {
    $section = 'dashboard';
}
$editingProject = isset($_GET['edit_project']) ? (int) $_GET['edit_project'] : null;
$editingPost = isset($_GET['edit_post']) ? (int) $_GET['edit_post'] : null;
$editingService = isset($_GET['edit_service']) ? (int) $_GET['edit_service'] : null;
$creatingProject = isset($_GET['new_project']);
$creatingPost = isset($_GET['new_post']);
if ($creatingProject) {
    $section = 'projects';
}
if ($creatingPost) {
    $section = 'blog';
}
if ($editingService) {
    $section = 'services';
}
if ($editingProject) {
    $section = 'projects';
}
if ($editingPost) {
    $section = 'blog';
}
$error = null;
$submittedService = null;
$submittedProject = null;
$submittedPost = null;
$submittedSettings = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $type = $_POST['type'] ?? '';
        if ($type === 'admin_password') {
            $section = 'settings';
            $currentPassword = $_POST['current_password'] ?? null;
            $newPassword = $_POST['new_password'] ?? null;
            $passwordConfirmation = $_POST['password_confirmation'] ?? null;
            if (!is_string($currentPassword) || !is_string($newPassword) || !is_string($passwordConfirmation)
                || $currentPassword === '' || strlen($currentPassword) > 4096 || str_contains($newPassword, "\0")) {
                throw new RuntimeException('Parola alanlarını kontrol edin.');
            }
            if (mb_strlen($newPassword) < 12) {
                throw new RuntimeException('Yeni parola en az 12 karakter olmalıdır.');
            }
            if (strlen($newPassword) > 72) {
                throw new RuntimeException('Yeni parola çok uzun. Daha kısa bir parola seçin.');
            }
            if (!hash_equals($newPassword, $passwordConfirmation)) {
                throw new RuntimeException('Yeni parola ve tekrarı eşleşmiyor.');
            }
            $adminId = (int) $_SESSION['admin_id'];
            $statement = db()->prepare('SELECT password FROM admins WHERE id = ?');
            $statement->execute([$adminId]);
            $currentHash = $statement->fetchColumn();
            if (!is_string($currentHash) || !password_verify($currentPassword, $currentHash)) {
                throw new RuntimeException('Mevcut parola hatalı.');
            }
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            db()->prepare('UPDATE admins SET password = ? WHERE id = ?')->execute([$newHash, $adminId]);
            session_regenerate_id(true);
            flash('success', 'Yönetici parolanız güncellendi.');
            redirect('admin?section=settings');
        }
        if ($type === 'service') {
            $section = 'services';
            $editingService = (int) ($_POST['id'] ?? 0);
            $submittedService = $_POST;
            $serviceRepository->save($editingService, $_POST);
            flash('success', 'Hizmet içeriği ve SEO alanları güncellendi.');
            redirect('admin?section=services&edit_service=' . $editingService);
        }
        if ($type === 'gallery_upload') {
            save_gallery_images((int) $_POST['project_id'], $_FILES['gallery_images'] ?? []);
            flash('success', 'Galeri görselleri yüklendi.');
            redirect('admin?section=projects&edit_project=' . (int) $_POST['project_id']);
        }
        if ($type === 'project_cover') {
            $editingProject = (int) ($_POST['project_id'] ?? 0);
            $section = 'projects';
            $projectRepository->useGalleryImageAsCover($editingProject, (int) ($_POST['id'] ?? 0));
            flash('success', 'Vitrin görseli güncellendi. Önceki vitrin görseli galeriye taşındı.');
            redirect('admin?section=projects&edit_project=' . $editingProject);
        }
        if ($type === 'settings') {
            $submittedSettings = true;
            $siteUrl = trim($_POST['site_url'] ?? '');
            if ($siteUrl !== '' && (!filter_var($siteUrl, FILTER_VALIDATE_URL) || !in_array(parse_url($siteUrl, PHP_URL_SCHEME), ['http', 'https'], true) || parse_url($siteUrl, PHP_URL_QUERY) || parse_url($siteUrl, PHP_URL_FRAGMENT) || parse_url($siteUrl, PHP_URL_USER))) {
                throw new RuntimeException('Site adresi http:// veya https:// ile başlayan, parametre içermeyen bir adres olmalıdır.');
            }
            $businessSettings = [];
            foreach ([
                'site_url',
                'business_name',
                'business_logo',
                'share_image',
                'business_address',
                'business_city',
                'business_postcode',
                'business_social_urls',
                'business_latitude',
                'business_longitude',
                'business_opens',
                'business_closes',
            ] as $key) {
                $businessSettings[$key] = $_POST[$key] ?? '';
            }
            foreach ([
                'business_latitude' => 90,
                'business_longitude' => 180,
            ] as $key => $limit) {
                $coordinate = trim($businessSettings[$key]);
                if ($coordinate !== '' && (!is_numeric($coordinate) || abs((float) $coordinate) > $limit)) {
                    throw new RuntimeException('Koordinat değerlerini kontrol edin.');
                }
            }
            foreach (['business_opens', 'business_closes'] as $key) {
                if ($businessSettings[$key] !== '' && !preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/', $businessSettings[$key])) {
                    throw new RuntimeException('Çalışma saatlerini SS:DD biçiminde girin.');
                }
            }
            foreach (['business_logo', 'share_image'] as $key) {
                $businessSettings[$key] = admin_image($_FILES[$key . '_upload'] ?? [], $businessSettings[$key]);
            }
            $extra = [];
            foreach (['site_title', 'site_description', 'site_keywords', 'contact_phone', 'contact_email', 'contact_whatsapp', 'custom_head', 'service_areas', 'service_districts', 'hero_image', 'business_about', 'about_image', 'ga4_measurement_id', 'search_console_verification', 'business_profile_url', 'calc_rate_date', 'calc_rate_source', 'calc_rate_scope', 'calc_cost_standard', 'calc_brick_price', 'calc_brick_labor'] as $key) {
                if (isset($_POST[$key]) && !is_string($_POST[$key])) {
                    throw new RuntimeException('Ayar biçimi geçersiz.');
                }
                $extra[$key] = trim($_POST[$key] ?? '');
            }
            $extra['indexing_enabled'] = !empty($_POST['indexing_enabled']) ? '1' : '0';
            $extra['calc_rates_verified'] = !empty($_POST['calc_rates_verified']) ? '1' : '0';
            if (mb_strlen($extra['service_areas']) > 200 || mb_strlen($extra['service_districts']) > 5000) {
                throw new RuntimeException('Çalışma bölgesi ve ilçe listesinin uzunluğunu kontrol edin.');
            }
            if (mb_strlen($extra['business_about']) > 10000) {
                throw new RuntimeException('Hakkımızda metni en fazla 10.000 karakter olabilir.');
            }
            if ($extra['contact_email'] !== '' && !filter_var($extra['contact_email'], FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('E-posta adresini kontrol edin.');
            }
            foreach (['contact_phone', 'contact_whatsapp'] as $key) {
                if ($extra[$key] !== '' && !preg_match('/^\d{10,15}$/D', preg_replace('/\D/', '', $extra[$key]))) {
                    throw new RuntimeException('Telefonu ülke koduyla birlikte girin.');
                }
            }
            if ($extra['ga4_measurement_id'] !== '' && !preg_match('/^G-[A-Z0-9]{4,20}$/D', $extra['ga4_measurement_id'])) {
                throw new RuntimeException('GA4 kimliği G- ile başlamalıdır.');
            }
            if ($extra['search_console_verification'] !== '' && !preg_match('/^[a-zA-Z0-9_-]{10,256}$/D', $extra['search_console_verification'])) {
                throw new RuntimeException('Search Console doğrulama kodunu kontrol edin.');
            }
            if ($extra['business_profile_url'] !== '' && (!filter_var($extra['business_profile_url'], FILTER_VALIDATE_URL) || parse_url($extra['business_profile_url'], PHP_URL_SCHEME) !== 'https')) {
                throw new RuntimeException('İşletme profili için geçerli HTTPS adresi girin.');
            }
            foreach (['calc_cost_standard', 'calc_brick_price', 'calc_brick_labor'] as $key) {
                if ($extra[$key] !== '' && (!is_numeric($extra[$key]) || (float) $extra[$key] < 0 || (float) $extra[$key] > 1000000)) {
                    throw new RuntimeException('Birim fiyat 0–1.000.000 aralığında olmalıdır.');
                }
            }
            $rateDate = $extra['calc_rate_date'];
            $parsedDate = $rateDate !== '' ? DateTimeImmutable::createFromFormat('!Y-m-d', $rateDate) : false;
            if ($rateDate !== '' && (!$parsedDate || $parsedDate->format('Y-m-d') !== $rateDate || $rateDate > display_date('now', 'Y-m-d'))) {
                throw new RuntimeException('Fiyat tarihini kontrol edin.');
            }
            if ($extra['calc_rates_verified'] === '1' && ($rateDate === '' || $extra['calc_rate_source'] === '' || $extra['calc_rate_scope'] === '')) {
                throw new RuntimeException('Onaylı fiyatlar için tarih, kaynak ve kapsam gereklidir.');
            }
            $extra['hero_image'] = admin_image($_FILES['hero_image_upload'] ?? [], $extra['hero_image']);
            $extra['about_image'] = admin_image($_FILES['about_image_upload'] ?? [], $extra['about_image']);
            db()->beginTransaction();
            try {
                $settings->setMany($businessSettings + $extra);
                db()->commit();
            } catch (Throwable $exception) {
                if (db()->inTransaction()) {
                    db()->rollBack();
                } throw $exception;
            }
            flash('success', 'Site ayarları güncellendi.');
            redirect('admin?section=settings');
        }
        if ($type === 'project') {
            $id = (int) ($_POST['id'] ?? 0);
            $isNew = $id === 0;
            $creatingProject = $isNew;
            $section = 'projects';
            $editingProject = $id ?: null;
            $data = [
                'title' => trim($_POST['title'] ?? ''),
                'slug' => admin_slug('projects', $_POST['title'] ?? '', $id ?: null),
                'category' => $_POST['category'] ?? 'ongoing',
                'location' => trim($_POST['location'] ?? ''),
                'year' => trim($_POST['year'] ?? ''),
                'area' => trim($_POST['area'] ?? ''),
                'summary' => trim($_POST['summary'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'image' => admin_image($_FILES['image'] ?? [], trim($_POST['image_url'] ?? '')),
                'seo_title' => trim($_POST['seo_title'] ?? ''),
                'seo_description' => trim($_POST['seo_description'] ?? ''),
                'featured' => !empty($_POST['featured']) ? 1 : 0,
                'is_demo' => !empty($_POST['is_demo']) ? 1 : 0,
            ];
            foreach (ProjectRepository::CASE_FIELDS as $field => $label) {
                $data[$field] = trim($_POST[$field] ?? '');
            }
            $submittedProject = $data;
            db()->beginTransaction();
            try {
                $id = $projectRepository->save($id, $data, $_POST['service_ids'] ?? []);
                save_gallery_images($id, $_FILES['gallery_images'] ?? []);
                db()->commit();
            } catch (Throwable $exception) {
                db()->rollBack();
                throw $exception;
            }
            flash('success', $isNew ? 'Proje eklendi.' : 'Proje güncellendi.');
            redirect('admin?section=projects');
        }
        if ($type === 'post') {
            $id = (int) ($_POST['id'] ?? 0);
            $section = 'blog';
            $editingPost = $id ?: null;
            $creatingPost = $id === 0;
            $data = [
                'title' => trim($_POST['title'] ?? ''),
                'slug' => admin_slug('posts', $_POST['title'] ?? '', $id ?: null),
                'excerpt' => trim($_POST['excerpt'] ?? ''),
                'content' => trim($_POST['content'] ?? ''),
                'image' => admin_image($_FILES['image'] ?? [], trim($_POST['image_url'] ?? '')),
                'seo_title' => trim($_POST['seo_title'] ?? ''),
                'seo_description' => trim($_POST['seo_description'] ?? ''),
                'published' => !empty($_POST['published']) ? 1 : 0,
            ];
            foreach (['author_name', 'reviewer_name', 'reviewed_at', 'sources', 'search_intent'] as $field) {
                $data[$field] = trim($_POST[$field] ?? '');
            }
            $submittedPost = $data;
            (new PostRepository())->save($id, $data, (array) ($_POST['post_service_ids'] ?? []), (array) ($_POST['post_project_ids'] ?? []));
            flash('success', $id ? 'Blog yazısı güncellendi.' : 'Blog yazısı eklendi.');
            redirect('admin?section=blog');
        }
        if ($type === 'delete_project' || $type === 'delete_post') {
            $table = $type === 'delete_project' ? 'projects' : 'posts';
            db()->prepare("DELETE FROM {$table} WHERE id = ?")->execute([(int) $_POST['id']]);
            flash('success', 'Kayıt silindi.');
            redirect('admin?section=' . ($type === 'delete_project' ? 'projects' : 'blog'));
        }
        if ($type === 'delete_gallery') {
            db()->prepare('DELETE FROM project_gallery WHERE id = ?')->execute([(int) $_POST['id']]);
            flash('success', 'Galeri görseli silindi.');
            redirect('admin?section=projects&edit_project=' . (int) $_POST['project_id']);
        }
        if ($type === 'message_action') {
            $id = (int) $_POST['id'];
            if (($_POST['action'] ?? '') === 'delete') {
                db()->prepare('DELETE FROM messages WHERE id = ?')->execute([$id]);
            } else {
                $status = $_POST['lead_status'] ?? 'new';
                if (!in_array($status, ['new', 'qualified', 'visit', 'quoted', 'won', 'lost'], true)) {
                    throw new RuntimeException('Talep aşaması geçersiz.');
                }
                db()->prepare('UPDATE messages SET is_read = 1, lead_status = ? WHERE id = ?')->execute([$status, $id]);
            }
            flash('success', 'Mesaj güncellendi.');
            redirect('admin?section=messages');
        }
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

$projects = db()->query('SELECT * FROM projects ORDER BY created_at DESC')->fetchAll();
$posts = db()->query('SELECT * FROM posts ORDER BY created_at DESC')->fetchAll();
$messages = db()->query('SELECT * FROM messages ORDER BY created_at DESC LIMIT 50')->fetchAll();
$site = $settings->all();
if ($submittedSettings) {
    $site = array_replace($site, array_filter($_POST, 'is_string'));
    $site['indexing_enabled'] = !empty($_POST['indexing_enabled']) ? '1' : '0';
    $site['calc_rates_verified'] = !empty($_POST['calc_rates_verified']) ? '1' : '0';
}
$services = $serviceRepository->all();
$serviceForm = $editingService ? $serviceRepository->find($editingService) : null;
if ($submittedService && $serviceForm) {
    foreach (['title', 'description', 'intro', 'seo_title', 'seo_description'] as $field) {
        $serviceForm[$field] = is_string($submittedService[$field] ?? null) ? $submittedService[$field] : '';
    }
    foreach (['sections', 'process', 'questions'] as $field) {
        $serviceForm[$field] = [];
        foreach (array_slice((array) ($submittedService[$field] ?? []), 0, 30) as $row) {
            if (is_array($row) && is_string($row[0] ?? null) && is_string($row[1] ?? null)) {
                $serviceForm[$field][] = [$row[0], $row[1]];
            }
        }
    }
}
if ($editingService && !$serviceForm) {
    $error ??= 'Düzenlenecek hizmet bulunamadı.';
}
$selectedServiceIds = $editingProject ? array_column($projectRepository->services($editingProject), 'id') : [];
if ($submittedProject) {
    $selectedServiceIds = array_map('intval', (array) ($_POST['service_ids'] ?? []));
}
$projectForm = null;
$projectGallery = [];

if ($editingProject) {
    $stmt = db()->prepare('SELECT * FROM projects WHERE id = ?');
    $stmt->execute([$editingProject]);
    $projectForm = $stmt->fetch() ?: null;

    $stmt = db()->prepare('SELECT * FROM project_gallery WHERE project_id = ? ORDER BY sort_order ASC, id ASC');
    $stmt->execute([$editingProject]);
    $projectGallery = $stmt->fetchAll();
}

$postForm = null;

if ($editingPost) {
    $stmt = db()->prepare('SELECT * FROM posts WHERE id = ?');
    $stmt->execute([$editingPost]);
    $postForm = $stmt->fetch() ?: null;
}
$projectForm ??= [
    'category' => 'ongoing',
    'featured' => 0,
];
if ($submittedProject) {
    $projectForm = $submittedProject + ['id' => $editingProject];
}
$postForm ??= [
    'published' => 0,
];
if ($submittedPost) {
    $postForm = $submittedPost + ['id' => $editingPost];
}
$postRepository = new PostRepository();
$postServiceIds = $submittedPost ? (array) ($_POST['post_service_ids'] ?? []) : ($editingPost ? array_column($postRepository->related($editingPost, 'services'), 'id') : []);
$postProjectIds = $submittedPost ? (array) ($_POST['post_project_ids'] ?? []) : ($editingPost ? array_column($postRepository->related($editingPost, 'projects'), 'id') : []);
$flash = take_flash();
$adminStatement = db()->prepare('SELECT name, email FROM admins WHERE id = ?');
$adminStatement->execute([(int) $_SESSION['admin_id']]);
$currentAdmin = $adminStatement->fetch() ?: ['name' => 'Yönetici', 'email' => ''];
$messageCounts = db()->query('SELECT COUNT(*) AS total, COALESCE(SUM(is_read = 0), 0) AS unread FROM messages')->fetch();
require __DIR__ . '/dashboard.php';
