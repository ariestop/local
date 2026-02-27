<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token()) ?>">
    <meta name="app-base" content="<?= htmlspecialchars(rtrim(parse_url($config['app']['url'] ?? '', PHP_URL_PATH) ?: '', '/')) ?>">
    <meta name="app-history-limit" content="<?= (int)($config['app']['history_limit'] ?? 10) ?>">
    <?php if (!empty($GLOBALS['_debugbar_renderer'])): echo $GLOBALS['_debugbar_renderer']->renderHead(); endif; ?>
    <title><?= htmlspecialchars($config['app']['name'] ?? 'Доска объявлений') ?> - продажа недвижимости</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root { --accent: #2c5f2d; --accent-light: #97d077; }
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: #f8f9fa; }
        .navbar { background: #fff !important; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
        .navbar-brand { font-weight: 600; color: var(--accent) !important; }
        .btn-primary { background: var(--accent); border-color: var(--accent); }
        .btn-primary:hover { background: #234a24; border-color: #234a24; }
        .table-hover tbody tr:hover { background: rgba(44, 95, 45, .06); }
        .main-content { min-height: 60vh; }
        .modal-header { border-bottom: 1px solid #eee; }
        .table thead th { font-weight: 600; color: #333; background: #fff; }
        .cost { font-weight: 600; white-space: nowrap; }
        a.text-dark:hover { color: var(--accent) !important; }
        .btn-loading { pointer-events: none; opacity: 0.8; }
        .btn-loading .btn-spinner { display: inline-block; width: 1em; height: 1em; border: 2px solid currentColor; border-right-color: transparent; border-radius: 50%; animation: spin 0.6s linear infinite; vertical-align: -0.15em; margin-right: 0.35em; }
        @keyframes spin { to { transform: rotate(360deg); } }
        #toastContainer { position: fixed; bottom: 1rem; right: 1rem; z-index: 1100; }

        /* Skeleton / lazy */
        .skeleton-row { opacity: 0; animation: skeletonFadeIn 0.35s ease forwards; }
        .skeleton-row:nth-child(1) { animation-delay: 0.02s; }
        .skeleton-row:nth-child(2) { animation-delay: 0.04s; }
        .skeleton-row:nth-child(3) { animation-delay: 0.06s; }
        .skeleton-row:nth-child(4) { animation-delay: 0.08s; }
        .skeleton-row:nth-child(5) { animation-delay: 0.1s; }
        .skeleton-row:nth-child(n+6) { animation-delay: 0.12s; }
        @keyframes skeletonFadeIn { to { opacity: 1; } }
        .img-loading { background: linear-gradient(90deg, #eee 25%, #f5f5f5 50%, #eee 75%); background-size: 200% 100%; animation: skeletonShimmer 1s infinite; }
        @keyframes skeletonShimmer { to { background-position: 200% 0; } }

        /* Photo preview */
        .photo-preview-area, .photo-preview-area-edit { margin-bottom: 0.75rem; }
        .photo-preview-list { display: flex; flex-wrap: wrap; gap: 0.5rem; min-height: 80px; padding: 0.5rem; background: #f8f9fa; border-radius: 8px; }
        .photo-preview-item { position: relative; width: 80px; height: 60px; flex-shrink: 0; border-radius: 6px; overflow: hidden; cursor: grab; border: 2px solid transparent; transition: border-color 0.2s; }
        .photo-preview-item:hover { border-color: var(--accent); }
        .photo-preview-item.dragging { opacity: 0.5; cursor: grabbing; }
        .photo-preview-item img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .photo-preview-remove { position: absolute; top: 2px; right: 2px; width: 22px; height: 22px; padding: 0; border: none; border-radius: 50%; background: rgba(0,0,0,0.6); color: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 12px; }
        .photo-preview-remove:hover { background: #dc3545; }
        .photo-preview-delete { position: absolute; bottom: 0; left: 0; right: 0; margin: 0; padding: 2px 4px; background: rgba(0,0,0,0.6); font-size: 11px; color: #fff; }
        .photo-preview-delete input { margin-right: 4px; }

        /* Copy phone */
        .copy-phone-btn { padding: 0.2rem 0.5rem; font-size: 0.8rem; }

        /* Mobile */
        @media (max-width: 768px) {
            .navbar .container { padding-left: 0.75rem; padding-right: 0.75rem; }
            .main-content .container { padding-left: 0.75rem; padding-right: 0.75rem; }
            .table-responsive { margin: 0 -0.75rem; overflow-x: auto; -webkit-overflow-scrolling: touch; }
            .table { font-size: 0.9rem; }
            .table th, .table td { padding: 0.5rem 0.4rem; }
            .posts-mobile .table thead { display: none; }
            .posts-mobile .table tbody tr { display: block; margin-bottom: 0.75rem; border: 1px solid #dee2e6; border-radius: 8px; padding: 0.75rem; background: #fff; }
            .posts-mobile .table tbody td { display: block; border: none; padding: 0.25rem 0; }
            .posts-mobile .table tbody td::before { content: attr(data-label); font-weight: 600; display: inline-block; min-width: 90px; }
            .posts-mobile .table tbody td.cost { font-size: 1.1rem; }
            .detail-photo-wrap { border-radius: 8px; overflow: hidden; }
            .detail-photo-wrap img { max-height: 280px; }
            .photo-preview-item { width: 70px; height: 52px; }
        }
        @media (max-width: 576px) {
            .btn-group-mobile { flex-direction: column; width: 100%; }
            .btn-group-mobile .btn { width: 100%; }
            .pagination { flex-wrap: wrap; justify-content: center; }
        }
    </style>
</head>
<body data-page="<?= htmlspecialchars(str_replace('main/', '', $view ?? '')) ?>">
    <?php
    ensure_session();
    $isAdmin = !empty($user) && (int) ($user['is_admin'] ?? 0) === 1;
    $assetUrl = static function (string $path): string {
        $fullPath = dirname(__DIR__, 2) . '/public' . $path;
        $version = file_exists($fullPath) ? (string) filemtime($fullPath) : (string) time();
        return $path . '?v=' . rawurlencode($version);
    };
    ?>
    <nav class="navbar navbar-expand-lg navbar-light bg-white py-3">
        <div class="container">
            <a class="navbar-brand" href="/"><?= htmlspecialchars($config['app']['name']) ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-controls="nav" aria-expanded="false">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="nav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="/">Объявления</a></li>
                    <?php if ($user): ?>
                    <li class="nav-item"><a class="nav-link" href="/add">Добавить объявление</a></li>
                    <?php endif; ?>
                    <?php if ($isAdmin): ?>
                    <li class="nav-item"><a class="nav-link text-danger" href="/admin-report">Админ-отчёт</a></li>
                    <?php endif; ?>
                </ul>
                <div class="d-flex gap-2">
                    <?php if ($user): ?>
                    <a class="navbar-text me-2 text-decoration-none text-dark" href="/edit-advert"><?= htmlspecialchars($user['name']) ?></a>
                    <a class="btn btn-outline-secondary btn-sm me-1" href="/favorites" title="Избранное"><i class="bi bi-heart"></i></a>
                    <a class="btn btn-outline-secondary btn-sm" href="/logout">Выход</a>
                    <?php else: ?>
                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#loginModal">Вход</button>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#registerModal">Регистрация</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <main class="main-content py-4">
        <div class="container">
            <?php
            $view = $view ?? 'main/index';
            $viewFile = dirname(__DIR__) . '/views/' . str_replace('.', '/', $view) . '.php';
            if (file_exists($viewFile)) {
                include $viewFile;
            }
            ?>
        </div>
    </main>

    <div id="toastContainer" class="toast-container" aria-live="polite" aria-atomic="true"></div>
    <footer class="py-4 mt-auto text-muted small border-top">
        <div class="container text-center">Доска объявлений о продаже недвижимости. Саратов и Энгельс.</div>
    </footer>

    <!-- Login Modal -->
    <div class="modal fade" id="loginModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Вход</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="loginError" class="alert alert-danger d-none"></div>
                    <form id="loginForm"><?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label">E-mail *</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Пароль *</label>
                            <input type="password" name="password" class="form-control" required>
                            <a href="/forgot-password" class="small text-muted">Забыли пароль?</a>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Войти</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Register Modal -->
    <div class="modal fade" id="registerModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Регистрация</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="registerError" class="alert alert-danger d-none"></div>
                    <form id="registerForm"><?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label">E-mail *</label>
                            <input type="email" name="email" id="regEmail" class="form-control" required>
                            <div id="emailStatus" class="form-text"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Пароль *</label>
                            <input type="password" name="password" id="regPassword" class="form-control" required minlength="5">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Повторите пароль *</label>
                            <input type="password" name="password2" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Ваше имя *</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Капча *</label>
                            <div class="d-flex align-items-center gap-2">
                                <img src="/api/captcha" id="captchaImg" alt="Капча" class="border rounded" style="height:40px">
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="document.getElementById('captchaImg').src='/api/captcha?'+Date.now()" title="Обновить"><i class="bi bi-arrow-clockwise"></i></button>
                                <input type="text" name="captcha" class="form-control" placeholder="Введите код" style="max-width:120px" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100" id="regSubmit">Регистрация</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/vue@3.5.25/dist/vue.global.prod.js"></script>
    <script src="<?= htmlspecialchars($assetUrl('/assets/api.js')) ?>"></script>
    <script src="<?= htmlspecialchars($assetUrl('/assets/ux.js')) ?>"></script>
    <script src="<?= htmlspecialchars($assetUrl('/assets/vue/shared.js')) ?>"></script>
    <script src="<?= htmlspecialchars($assetUrl('/assets/vue/forms.js')) ?>"></script>
    <script src="<?= htmlspecialchars($assetUrl('/assets/vue/favorites.js')) ?>"></script>
    <script src="<?= htmlspecialchars($assetUrl('/assets/vue/gallery.js')) ?>"></script>
    <script src="<?= htmlspecialchars($assetUrl('/assets/vue-app.js')) ?>"></script>
    <?php if (!empty($GLOBALS['_debugbar_renderer'])): echo $GLOBALS['_debugbar_renderer']->render(); endif; ?>
</body>
</html>
