<!doctype html>
<html lang="tr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">
        <title>Giriş | <?= e(config('site_name')) ?></title>
        <link rel="stylesheet" href="<?= e(admin_asset('assets/css/admin.css')) ?>">
        <script src="<?= e(admin_asset('assets/js/admin.js')) ?>" defer></script>
    </head>
    <body class="login-page">
        <section class="login-story">
            <a class="brand" href="<?= e(url()) ?>">
                <span><?= e(config('site_name')) ?><small>WEB SİTESİ YÖNETİMİ</small></span>
            </a>
            <div class="story-content">
                <span class="eyebrow">SAHADAN WEB SİTENİZE</span>
                <h1>İşinizin her adımı.<br>Tek bir merkezde.</h1>
                <p>Projelerinizi, hizmetlerinizi ve iletişim taleplerinizi<br>aynı çalışma alanından yönetin.</p>
                <div class="construction-visual" aria-hidden="true">
                    <div class="visual-ring"></div>
                    <div class="visual-ring outer"></div>
                    <div class="structure"></div>
                </div>
            </div>
            <span class="story-footer">Daha düzenli içerik. Daha güçlü bir web sitesi.</span>
        </section>
        <main class="login-form-side">
            <div class="login-form-wrap">
                <span class="login-kicker">YÖNETİM PANELİNE HOŞ GELDİNİZ</span>
                <h2>Çalışma alanınıza<br>giriş yapın</h2>
                <p>Devam etmek için hesap bilgilerinizi girin.</p>
                <?php if ($error): ?>
                    <div class="form-message error" role="alert"><?= e($error) ?></div>
                <?php endif; ?>
                <form method="post">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <label for="email">
                        E-posta adresi
                        <input id="email" required type="email" name="email" autocomplete="username" placeholder="ad@firma.com" value="<?= old('email') ?>">
                    </label>
                    <label for="password">
                        Şifre
                        <span class="password-field">
                            <input id="password" required type="password" name="password" autocomplete="current-password" placeholder="Şifrenizi girin">
                            <button type="button" class="password-toggle" data-password-toggle="password" aria-label="Şifreyi göster" aria-pressed="false">Göster</button>
                        </span>
                    </label>
                    <button class="button button-dark login-submit" type="submit">Giriş yap <?= admin_icon('arrow') ?></button>
                </form>
                <div class="login-help">Hesabınıza erişemiyor musunuz?<br>Sistem yöneticinizle iletişime geçin.</div>
                <div class="login-security"><?= admin_icon('check') ?> Yalnızca yetkili kullanıcı erişimi</div>
            </div>
            <div class="login-copyright">© <?= date('Y') ?> <?= e(config('site_name')) ?>. Tüm hakları saklıdır.</div>
        </main>
    </body>
</html>
