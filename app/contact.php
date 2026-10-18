<?php

declare(strict_types=1);

function contact_page(): array
{
    ensure_session();
    $errors = [];
    $text = static fn (string $key, array $input): string => is_string($input[$key] ?? null) ? trim($input[$key]) : '';
    $from = $text('from', $_GET);
    if ($from === '' && isset($_SERVER['HTTP_REFERER']) && parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST) === parse_url(config('site_url'), PHP_URL_HOST)) {
        $path = (string) parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH);
        $base = rtrim((string) parse_url(config('site_url'), PHP_URL_PATH), '/');
        if (str_starts_with($path, $base . '/')) {
            $from = substr($path, strlen($base) + 1);
        }
    }
    $from = preg_match('~^[a-z0-9/-]{0,190}$~D', $from) ? $from : '';
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        verify_csrf();
        $token = $text('request_token', $_POST);
        $form = $_SESSION['contact_forms'][$token] ?? null;
        $data = [];
        foreach (['name', 'email', 'phone', 'message', 'district', 'building_type', 'stage'] as $field) {
            $data[$field] = $text($field, $_POST);
        }
        $area = $text('area', $_POST);
        $data['area'] = $area === '' ? null : (is_numeric($area) && (float) $area > 0 && (float) $area <= 1000000 ? (float) $area : false);
        $limits = ['name' => 100, 'email' => 150, 'phone' => 30, 'message' => 10000, 'district' => 100, 'building_type' => 100, 'stage' => 100];
        foreach ($limits as $field => $limit) {
            if (mb_strlen($data[$field]) > $limit) {
                $errors[] = 'Alan uzunluklarını kontrol edin.';
                break;
            }
        }
        $phone = preg_replace('/\D/', '', $data['phone']);
        if ($data['name'] === '' || $data['message'] === '' || ($data['email'] === '' && $data['phone'] === '')) {
            $errors[] = 'Adınızı, mesajınızı ve size ulaşabileceğimiz telefon veya e-posta bilgisini girin.';
        }
        if (($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) || ($data['phone'] !== '' && !preg_match('/^\d{10,15}$/D', $phone)) || $data['area'] === false) {
            $errors[] = 'Telefon, e-posta veya alan bilgisini kontrol edin.';
        }
        if (!$form || time() - $form['created'] > 7200 || $text('website', $_POST) !== '') {
            $errors[] = 'Formun süresi doldu. Sayfayı yenileyip tekrar deneyin.';
        }
        $recent = array_filter($_SESSION['contact_sent'] ?? [], static fn ($time) => $time > time() - 600);
        if (count($recent) >= 3 && empty($form['sent'])) {
            $errors[] = 'Kısa sürede birden fazla talep alındı. Biraz sonra tekrar deneyin.';
        }
        if (!$errors) {
            $data['source'] = $form['source'];
            $data['request_token'] = $token;
            $created = (new MessageRepository())->create($data);
            $_SESSION['contact_forms'][$token]['sent'] = true;
            if ($created) {
                $recent[] = time();
            }
            $_SESSION['contact_sent'] = $recent;
            $_SESSION['lead_success'] = $created;
            flash('success', 'Talebiniz ulaştı. İlettiğiniz bilgiler üzerinden sizinle iletişim kuracağız.');
            redirect('iletisim');
        }
    }
    $_SESSION['contact_forms'] = array_filter($_SESSION['contact_forms'] ?? [], static fn ($form) => time() - $form['created'] <= 7200);
    $_SESSION['contact_forms'] = array_slice($_SESSION['contact_forms'], -9, null, true);
    $requestToken = bin2hex(random_bytes(32));
    $_SESSION['contact_forms'][$requestToken] = ['created' => time(), 'source' => $from];
    $leadSuccess = !empty($_SESSION['lead_success']);
    unset($_SESSION['lead_success']);
    $areaDefault = $text('area', $_GET);
    if (!is_numeric($areaDefault) || (float) $areaDefault <= 0 || (float) $areaDefault > 1000000) {
        $areaDefault = '';
    }
    return compact('errors', 'requestToken', 'leadSuccess', 'areaDefault') + ['flash' => take_flash()];
}
