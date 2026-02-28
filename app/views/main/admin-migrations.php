<?php
$adminAuthorized = (bool) ($adminAuthorized ?? false);
$error = $error ?? null;
$status = $status ?? ['rows' => [], 'pending_count' => 0, 'total_count' => 0];
$runs = $runs ?? [];
$flashSuccess = trim((string) ($flashSuccess ?? ''));
$flashError = trim((string) ($flashError ?? ''));
?>

<div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h1 class="h4 mb-0">Миграции базы данных</h1>
    <div class="d-flex gap-2">
        <a href="<?= route_url('/admin') ?>" class="btn btn-sm btn-outline-secondary">Админ-панель</a>
        <a href="<?= route_url('/') ?>" class="btn btn-sm btn-outline-secondary">На главную</a>
    </div>
</div>

<?php if (!$adminAuthorized): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h2 class="h6 mb-3">Недостаточно прав</h2>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger py-2 mb-0"><?= htmlspecialchars((string) $error) ?></div>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <?php if ($flashSuccess !== ''): ?>
        <div class="alert alert-success py-2"><?= htmlspecialchars($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if ($flashError !== ''): ?>
        <div class="alert alert-danger py-2"><?= htmlspecialchars($flashError) ?></div>
    <?php endif; ?>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="small text-muted">Всего миграций</div>
                    <div class="h5 mb-0"><?= (int) ($status['total_count'] ?? 0) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="small text-muted">Ожидают применения</div>
                    <div class="h5 mb-0"><?= (int) ($status['pending_count'] ?? 0) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form method="post" action="<?= route_url('/admin-migrations/apply-next') ?>" class="m-0">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <button type="submit" class="btn btn-primary w-100 js-confirm-submit" data-confirm="Применить следующую миграцию?" <?= ((int) ($status['pending_count'] ?? 0) === 0) ? 'disabled' : '' ?>>
                            Применить следующую миграцию
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h2 class="h6 mb-3">Список миграций</h2>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                    <tr>
                        <th>Миграция</th>
                        <th>Статус</th>
                        <th class="text-end">Действие</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach (($status['rows'] ?? []) as $row): ?>
                        <?php $isApplied = !empty($row['applied']); ?>
                        <tr>
                            <td><code><?= htmlspecialchars((string) ($row['name'] ?? '')) ?></code></td>
                            <td>
                                <?php if ($isApplied): ?>
                                    <span class="badge bg-success-subtle text-success-emphasis">Применена</span>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning-emphasis">Ожидает</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?php if (!$isApplied): ?>
                                    <form method="post" action="<?= route_url('/admin-migrations/apply') ?>" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                        <input type="hidden" name="migration" value="<?= htmlspecialchars((string) ($row['name'] ?? '')) ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-primary js-confirm-submit" data-confirm="<?= htmlspecialchars('Применить миграцию ' . (string) ($row['name'] ?? '') . '?') ?>">Применить</button>
                                    </form>
                                <?php else: ?>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" disabled>Готово</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($status['rows'])): ?>
                        <tr><td colspan="3" class="text-muted small">Файлы миграций не найдены</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mt-3">
        <div class="card-body">
            <h2 class="h6 mb-3">Журнал запусков</h2>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                    <tr>
                        <th>Время</th>
                        <th>Миграция</th>
                        <th>Пользователь</th>
                        <th>Статус</th>
                        <th>Сообщение</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($runs as $run): ?>
                        <?php $isSuccess = (string) ($run['status'] ?? '') === 'success'; ?>
                        <tr>
                            <td class="small"><?= htmlspecialchars(date('d.m.Y H:i', strtotime((string) ($run['created_at'] ?? 'now')))) ?></td>
                            <td><code><?= htmlspecialchars((string) ($run['migration'] ?? '')) ?></code></td>
                            <td class="small">
                                <?= htmlspecialchars((string) ($run['user_email'] ?? 'system')) ?>
                                <?php if (!empty($run['user_id'])): ?>
                                    <span class="text-muted">(ID: <?= (int) $run['user_id'] ?>)</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($isSuccess): ?>
                                    <span class="badge bg-success-subtle text-success-emphasis">Успех</span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger-emphasis">Ошибка</span>
                                <?php endif; ?>
                            </td>
                            <td class="small text-muted"><?= htmlspecialchars((string) ($run['message'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($runs)): ?>
                        <tr><td colspan="5" class="text-muted small">Запусков пока нет</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

