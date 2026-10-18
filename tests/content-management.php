<?php

declare(strict_types=1);

ini_set('session.save_path', sys_get_temp_dir());
require __DIR__ . '/../app/bootstrap.php';
ensure_session();

function assertContent(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function expectInvalid(callable $operation): void
{
    try {
        $operation();
    } catch (RuntimeException) {
        return;
    }
    throw new RuntimeException('Invalid content was accepted.');
}

function renderContent(string $view, array $data): string
{
    ob_start();
    try {
        render($view, $data);

        return ob_get_contents();
    } finally {
        ob_end_clean();
    }
}

$pdo = db();
$serviceRepository = new ServiceRepository();
$projectRepository = new ProjectRepository();
$initialProjectCount = (int) $pdo->query('SELECT COUNT(*) FROM projects')->fetchColumn();
$initialServices = $serviceRepository->all();
assertContent(count($initialServices) === count(service_defaults()), 'Default services missing.');
$pdo->beginTransaction();

try {
    $service = $initialServices[0];
    $data = ServiceRepository::formData($service);
    $data['title'] = 'Test hizmeti <script>alert(1)</script>';
    $data['seo_title'] = 'Özgün hizmet SEO başlığı';
    $data['seo_description'] = 'Özgün hizmet SEO açıklaması';
    $data['intro'] = "Birinci paragraf.\n\n## Hizmetin kapsamı\n\nİkinci paragraf.";
    $data['sections'] = [['Kapsam', '<script>kapsam</script>']];
    $data['process'] = [['Planlama', 'Saha programı hazırlanır.']];
    $data['questions'] = [['Nasıl başlarız?', 'Proje bilgilerini paylaşın.']];
    $serviceRepository->save((int) $service['id'], $data);
    ServiceRepository::seedDefaults($pdo);
    $savedService = $serviceRepository->find((int) $service['id']);
    assertContent($savedService['title'] === $data['title'], 'Seeding overwrote edited content.');
    assertContent($savedService['slug'] === $service['slug'], 'Service URL changed.');
    assertContent($savedService['questions'] === $data['questions'], 'FAQ round-trip failed.');
    assertContent(!empty($savedService['updated_at']), 'Service modification date missing.');
    expectInvalid(fn () => $serviceRepository->save((int) $service['id'], array_replace($data, ['title' => ''])));
    expectInvalid(fn () => $serviceRepository->save((int) $service['id'], array_replace($data, ['sections' => [['Başlık', '']]])));
    expectInvalid(fn () => $serviceRepository->save((int) $service['id'], array_replace($data, ['questions' => array_fill(0, 31, ['Soru', 'Cevap'])])));
    expectInvalid(fn () => $serviceRepository->save(0, $data));

    $projectData = [
        'title' => 'Geçici proje',
        'slug' => 'content-test-' . bin2hex(random_bytes(6)),
        'category' => 'completed',
        'location' => 'İstanbul',
        'year' => '2026',
        'area' => '100 m²',
        'summary' => 'Test özeti',
        'description' => 'Test açıklaması',
        'image' => '',
        'featured' => 0,
        'building_type' => 'Villa',
        'work_scope' => 'Temel ve betonarme <script>alert(2)</script>',
        'quantities' => '100 m² uygulama alanı',
        'work_process' => "Planlama\n\nUygulama",
        'challenges' => 'Saha koordinasyonu',
        'delivery_result' => 'Kontrol ve teslim',
    ];
    $projectId = $projectRepository->save(0, $projectData, [(int) $service['id'], (int) $service['id']]);
    $project = $projectRepository->findBySlug($projectData['slug']);
    assertContent($project !== null && $project['work_scope'] === $projectData['work_scope'], 'Case-study content not saved.');
    assertContent(count($projectRepository->services($projectId)) === 1, 'Duplicate association saved.');
    assertContent(in_array($projectId, array_column($projectRepository->forService((int) $service['id']), 'id')), 'Service project lookup failed.');
    $changed = array_replace($projectData, ['title' => 'Yeni başlık', 'slug' => 'must-not-change']);
    $projectRepository->save($projectId, $changed, [(int) $service['id']]);
    assertContent($projectRepository->findBySlug($projectData['slug'])['title'] === 'Yeni başlık', 'Project URL was not preserved.');
    expectInvalid(fn () => $projectRepository->save($projectId, $projectData, [0]));

    // The cover stays first; selecting a gallery photo preserves the previous cover.
    $cover = 'uploads/demo/ahsap-kalip-test-hero.webp';
    $detail = 'uploads/demo/ahsap-kalip-test-detay.webp';
    $projectRepository->save($projectId, array_replace($projectData, ['image' => $cover]), [(int) $service['id']]);
    $pdo->prepare('INSERT INTO project_gallery (project_id, image, caption, sort_order) VALUES (?, ?, ?, ?)')
        ->execute([$projectId, $detail, 'Detay fotoğrafı', 1]);
    $galleryId = (int) $pdo->lastInsertId();
    expectInvalid(fn () => $projectRepository->useGalleryImageAsCover(0, $galleryId));
    $projectRepository->useGalleryImageAsCover($projectId, $galleryId);
    $project = $projectRepository->findBySlug($projectData['slug']);
    assertContent($project['image'] === $detail, 'Selected gallery image did not become the cover.');
    $project['gallery'] = $projectRepository->gallery($projectId);
    assertContent($project['gallery'][0]['image'] === $cover, 'Previous cover was lost from the gallery.');
    assertContent($project['gallery'][0]['caption'] === null, 'Previous cover inherited an unrelated caption.');
    // Duplicating the cover in stored gallery data must not repeat it as a thumbnail.
    $project['gallery'][] = ['image' => $detail, 'caption' => ''];
    $project['gallery'][] = ['image' => $cover, 'caption' => ''];
    ob_start();
    view('project', ['project' => $project]);
    $galleryHtml = ob_get_clean();
    $galleryDocument = new DOMDocument();
    @$galleryDocument->loadHTML('<?xml encoding="UTF-8">' . $galleryHtml);
    $galleryXpath = new DOMXPath($galleryDocument);
    assertContent($galleryXpath->query('//a[@class="project-cover"]/img')->item(0)->getAttribute('src') === project_image($detail), 'Detail cover does not match list cover.');
    assertContent($galleryXpath->query('//div[@class="gallery-grid"]/a')->length === 1, 'Gallery repeats the cover or duplicate images.');
    assertContent($galleryXpath->query('//div[@class="gallery-grid"]/a/img')->item(0)->getAttribute('src') === project_image($cover), 'Gallery thumbnail is missing.');
    $pdo->prepare('UPDATE projects SET image = NULL WHERE id = ?')->execute([$projectId]);
    $projectRepository->useGalleryImageAsCover($projectId, $galleryId);
    assertContent($projectRepository->findBySlug($projectData['slug'])['image'] === $cover, 'Gallery cover selection failed for a project with no cover.');
    assertContent($projectRepository->gallery($projectId) === [], 'Empty previous cover was added to the gallery.');

    $_GET['route'] = $savedService['slug'];
    http_response_code(200);
    $html = renderContent('service', [
        'service' => $savedService,
        'metaTitle' => $savedService['seo_title'],
        'metaDescription' => $savedService['seo_description'],
        'schemaType' => 'service',
        'relatedProjects' => $projectRepository->forService((int) $service['id']),
    ]);
    assertContent(str_contains($html, '<h2>Hizmetin kapsamı</h2>'), 'Service paragraph/heading rendering failed.');
    assertContent(str_contains($html, 'id="service-guide"'), 'Detailed service guide missing.');
    assertContent(substr_count($html, 'Birinci paragraf.') === 1, 'Service overview was duplicated or lost when separating the guide.');
    assertContent(str_contains($html, $projectData['slug']), 'Related project link missing.');
    assertContent(str_contains($html, 'Özgün hizmet SEO açıklaması'), 'Service SEO description missing.');
    assertContent(!str_contains($html, '<script>alert(1)</script>'), 'Service HTML escaped incorrectly.');
    preg_match('~<script type="application/ld\+json">(.*?)</script>~s', $html, $match);
    $graph = json_decode($match[1], true, 512, JSON_THROW_ON_ERROR)['@graph'];
    $serviceSchema = array_values(array_filter($graph, fn (array $item): bool => $item['@type'] === 'Service'))[0];
    assertContent($serviceSchema['name'] === $savedService['title'], 'Service schema title is stale.');
    $breadcrumb = array_values(array_filter($graph, fn (array $item): bool => $item['@type'] === 'BreadcrumbList'))[0];
    assertContent(end($breadcrumb['itemListElement'])['name'] === $savedService['title'], 'Service breadcrumb title is stale.');

    $_GET['route'] = 'proje/' . $projectData['slug'];
    $html = renderContent('project', [
        'project' => $project,
        'relatedServices' => $projectRepository->services($projectId),
    ]);
    assertContent(str_contains($html, '<h2>İş kapsamı</h2>'), 'Case-study section missing.');
    assertContent(!str_contains($html, '<script>alert(2)</script>'), 'Project content escaped incorrectly.');
    assertContent(str_contains($html, 'Projede uygulanan hizmetler'), 'Project service links missing.');
    foreach (ProjectRepository::CASE_FIELDS as $field => $label) {
        $project[$field] = '';
    }
    $html = renderContent('project', ['project' => $project]);
    assertContent(!str_contains($html, 'project-case-section'), 'Blank case-study fields were displayed.');
    $projectRepository->save($projectId, $projectData, []);
    assertContent($projectRepository->services($projectId) === [], 'Service deselection failed.');

    // Render the authenticated panel without changing credentials or creating an HTTP session.
    $_SESSION['admin_id'] = 1;
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_GET = ['section' => 'services', 'edit_service' => $service['id']];
    $_POST = array_replace($data, [
        '_csrf' => csrf_token(),
        'type' => 'service',
        'id' => $service['id'],
        'title' => '',
        'intro' => 'Kaybolmaması gereken form metni',
    ]);
    ob_start();
    require __DIR__ . '/../admin/index.php';
    $panel = ob_get_clean();
    assertContent(str_contains($panel, 'data-repeater="questions"'), 'Service panel repeaters missing.');
    assertContent(str_contains($panel, 'name="seo_title"'), 'Service SEO form missing.');
    assertContent(str_contains($panel, 'Kaybolmaması gereken form metni'), 'Invalid submission lost form values.');
    assertContent($serviceRepository->find((int) $service['id'])['title'] === $savedService['title'], 'Invalid submission changed stored service.');
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_POST = [];
    $section = 'projects';
    $editingProject = $projectId;
    $projectForm = $projectRepository->findBySlug($projectData['slug']);
    $selectedServiceIds = [];
    $projectGallery = [];
    ob_start();
    require __DIR__ . '/../admin/dashboard.php';
    $panel = ob_get_clean();
    assertContent(str_contains($panel, 'name="work_scope"'), 'Project case-study form missing.');
    assertContent(str_contains($panel, 'name="service_ids[]"'), 'Project service selection missing.');
    $editingProject = null;
    $projectForm = [];
    ob_start();
    require __DIR__ . '/../admin/partials/project-form.php';
    $newProjectForm = ob_get_clean();
    assertContent(str_contains($newProjectForm, 'name="gallery_images[]"') && str_contains($newProjectForm, ' multiple'), 'New projects cannot upload multiple gallery images.');
} finally {
    $pdo->rollBack();
}

assertContent((int) $pdo->query('SELECT COUNT(*) FROM projects')->fetchColumn() === $initialProjectCount, 'Temporary project persisted.');
assertContent($serviceRepository->all() === $initialServices, 'Temporary service edits persisted.');
echo "Content management checks passed; all test content rolled back.\n";
