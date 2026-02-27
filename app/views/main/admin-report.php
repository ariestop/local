<?php
$adminAuthorized = (bool) ($adminAuthorized ?? false);
$error = $error ?? null;
$summary = $summary ?? [];
$popular = $popular ?? [];
$activity = $activity ?? [];
$errors = $errors ?? [];
?>

<div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h1 class="h4 mb-0">Админ-отчёт</h1>
    <a href="/" class="btn btn-sm btn-outline-secondary">На главную</a>
</div>

<?php if (!$adminAuthorized): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h2 class="h6 mb-3">Недостаточно прав</h2>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger py-2"><?= htmlspecialchars((string) $error) ?></div>
            <?php endif; ?>
            <p class="mb-0 text-muted">Страница доступна только пользователю с ролью администратора (`is_admin = 1`).</p>
        </div>
    </div>
<?php else: ?>
    <div class="row g-3 mb-3">
        <div class="col-md-2 col-6">
            <div class="card border-0 shadow-sm"><div class="card-body"><div class="small text-muted">Постов</div><div class="h5 mb-0"><?= (int) ($summary['posts_total'] ?? 0) ?></div></div></div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card border-0 shadow-sm"><div class="card-body"><div class="small text-muted">Пользователей</div><div class="h5 mb-0"><?= (int) ($summary['users_total'] ?? 0) ?></div></div></div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card border-0 shadow-sm"><div class="card-body"><div class="small text-muted">Просмотры</div><div class="h5 mb-0"><?= (int) ($summary['views_total'] ?? 0) ?></div></div></div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card border-0 shadow-sm"><div class="card-body"><div class="small text-muted">Ошибки</div><div class="h5 mb-0"><?= (int) ($summary['errors_total'] ?? 0) ?></div></div></div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card border-0 shadow-sm"><div class="card-body"><div class="small text-muted">Просмотры 24ч</div><div class="h5 mb-0"><?= (int) ($summary['views_24h'] ?? 0) ?></div></div></div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card border-0 shadow-sm"><div class="card-body"><div class="small text-muted">Ошибки 24ч</div><div class="h5 mb-0"><?= (int) ($summary['errors_24h'] ?? 0) ?></div></div></div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 mb-3">Популярные объявления</h2>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr><th>ID</th><th>Объект</th><th>Просм.</th></tr>
                            </thead>
                            <tbody>
                            <?php foreach ($popular as $p): ?>
                                <tr>
                                    <td><?= (int) $p['id'] ?></td>
                                    <td>
                                        <a href="/detail/<?= (int) $p['id'] ?>" class="text-decoration-none">
                                            <?= htmlspecialchars($p['action_name'] . ' ' . $p['object_name']) ?>
                                        </a>
                                        <div class="small text-muted"><?= htmlspecialchars($p['city_name'] . ', ' . $p['area_name']) ?></div>
                                    </td>
                                    <td><?= (int) ($p['view_count'] ?? 0) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($popular)): ?>
                                <tr><td colspan="3" class="text-muted small">Нет данных</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 mb-3">Активность (7 дней)</h2>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Дата</th><th>Просм.</th><th>Новые посты</th></tr></thead>
                            <tbody>
                            <?php foreach ($activity as $a): ?>
                                <tr>
                                    <td><?= htmlspecialchars(date('d.m.Y', strtotime((string) $a['date']))) ?></td>
                                    <td><?= (int) ($a['views'] ?? 0) ?></td>
                                    <td><?= (int) ($a['new_posts'] ?? 0) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($activity)): ?>
                                <tr><td colspan="3" class="text-muted small">Нет данных</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mt-3">
        <div class="card-body">
            <h2 class="h6 mb-3">Последние клиентские ошибки</h2>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Время</th><th>Уровень</th><th>Сообщение</th><th>URL</th></tr></thead>
                    <tbody>
                    <?php foreach ($errors as $e): ?>
                        <tr>
                            <td class="small"><?= htmlspecialchars(date('d.m.Y H:i', strtotime((string) $e['created_at']))) ?></td>
                            <td class="small"><?= htmlspecialchars((string) $e['level']) ?></td>
                            <td class="small"><?= htmlspecialchars((string) $e['message']) ?></td>
                            <td class="small text-muted"><?= htmlspecialchars((string) $e['url']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($errors)): ?>
                        <tr><td colspan="4" class="text-muted small">Нет ошибок</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>
