<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
    </style>
</head>
<body>
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
                </ul>
                <div class="d-flex gap-2">
                    <?php if ($user): ?>
                    <span class="navbar-text me-2"><?= htmlspecialchars($user['name']) ?></span>
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
                    <form id="loginForm">
                        <div class="mb-3">
                            <label class="form-label">E-mail *</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Пароль *</label>
                            <input type="password" name="password" class="form-control" required>
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
                    <form id="registerForm">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/vue@3.4.21/dist/vue.global.prod.js"></script>
    <script src="/assets/app.js"></script>
</body>
</html>
