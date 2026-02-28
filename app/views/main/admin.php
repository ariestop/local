<?php
$adminAuthorized = (bool) ($adminAuthorized ?? false);
$error = $error ?? null;
$summary = $summary ?? [];
$popular = $popular ?? [];
$activity = $activity ?? [];
$errors = $errors ?? [];
$expiryTotals = $expiryTotals ?? [];
$flashSuccess = (string) ($flashSuccess ?? '');
$flashError = (string) ($flashError ?? '');
$runStats = $runStats ?? null;
$pendingExpireCount = max(0, (int) ($pendingExpireCount ?? 0));
$runLimit = (int) (($runStats['target'] ?? $pendingExpireCount));
?>

<div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h1 class="h4 mb-0">Админ-панель</h1>
    <div class="d-flex gap-2">
        <a href="/admin-migrations" class="btn btn-sm btn-outline-secondary">Миграции</a>
        <a href="/" class="btn btn-sm btn-outline-secondary">На главную</a>
    </div>
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
    <?php if ($flashSuccess !== ''): ?>
        <div class="alert alert-success py-2"><?= htmlspecialchars($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if ($flashError !== ''): ?>
        <div class="alert alert-danger py-2"><?= htmlspecialchars($flashError) ?></div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <h2 class="h6 mb-3">Ручной запуск автоархивации (fallback cron)</h2>
            <p class="small text-muted mb-2">
                Сейчас к обработке: <strong><?= $pendingExpireCount ?></strong>.
                Алгоритм запускается пакетами по <strong>100</strong> автоматически до достижения лимита или окончания очереди.
            </p>
            <form id="expirePostsForm" method="post" action="/admin/expire-posts" class="row g-2 align-items-end">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                <div class="col-sm-4 col-md-3">
                    <label for="expireLimit" class="form-label form-label-sm mb-1">Сколько обработать</label>
                    <input
                        id="expireLimit"
                        name="limit"
                        type="number"
                        class="form-control form-control-sm"
                        min="0"
                        max="10000"
                        value="<?= max(0, $runLimit) ?>"
                    >
                </div>
                <div class="col-sm-auto">
                    <button id="expireRunBtn" type="submit" class="btn btn-sm btn-primary">Запустить алгоритм cron</button>
                    <button id="expireStopBtn" type="button" class="btn btn-sm btn-outline-danger d-none">Остановить</button>
                </div>
            </form>
            <div id="expireProgressWrap" class="mt-3 d-none">
                <div class="progress" role="progressbar" aria-label="Cron progress" aria-valuemin="0" aria-valuemax="100">
                    <div id="expireProgressBar" class="progress-bar progress-bar-striped progress-bar-animated" style="width: 0%">0%</div>
                </div>
                <div id="expireProgressText" class="small text-muted mt-2">Подготовка к запуску...</div>
            </div>
            <?php if (is_array($runStats)): ?>
                <div class="mt-3 small text-muted">
                    Последний ручной запуск: обработано — <strong><?= (int) ($runStats['processed'] ?? 0) ?></strong>,
                    архивировано — <strong><?= (int) ($runStats['archived'] ?? 0) ?></strong>,
                    писем отправлено — <strong><?= (int) ($runStats['notified'] ?? 0) ?></strong>,
                    батчей — <strong><?= (int) ($runStats['batches'] ?? 0) ?></strong>,
                    очередь до/после — <strong><?= (int) ($runStats['pending_before'] ?? 0) ?></strong>/<strong><?= (int) ($runStats['pending_after'] ?? 0) ?></strong>.
                </div>
            <?php endif; ?>
        </div>
    </div>

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
    <div class="row g-3 mb-3">
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="small text-muted">Всего автоархивировано</div>
                    <div class="h5 mb-0"><?= (int) ($expiryTotals['archived_total'] ?? 0) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="small text-muted">Всего отправлено писем</div>
                    <div class="h5 mb-0"><?= (int) ($expiryTotals['notified_total'] ?? 0) ?></div>
                </div>
            </div>
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

<?php if ($adminAuthorized): ?>
<script>
(() => {
    const form = document.getElementById('expirePostsForm');
    const limitInput = document.getElementById('expireLimit');
    const runButton = document.getElementById('expireRunBtn');
    const stopButton = document.getElementById('expireStopBtn');
    const progressWrap = document.getElementById('expireProgressWrap');
    const progressBar = document.getElementById('expireProgressBar');
    const progressText = document.getElementById('expireProgressText');
    if (!form || !limitInput || !runButton || !stopButton || !progressWrap || !progressBar || !progressText) {
        return;
    }

    const pendingExpireCount = <?= $pendingExpireCount ?>;
    let cancelRequested = false;

    const setProgress = (percent, text) => {
        const safePercent = Math.max(0, Math.min(100, percent));
        progressBar.style.width = safePercent + '%';
        progressBar.textContent = Math.round(safePercent) + '%';
        progressText.textContent = text;
    };

    const runOneBatch = async (csrfToken, limit) => {
        const body = new URLSearchParams();
        body.set('csrf_token', csrfToken);
        body.set('limit', String(limit));

        const response = await fetch('/admin/expire-posts-batch', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: body.toString(),
        });

        const data = await response.json();
        if (!response.ok || !data || data.success !== true) {
            const errorMessage = (data && data.error) ? data.error : 'Не удалось выполнить batch-запуск.';
            throw new Error(errorMessage);
        }
        return data;
    };

    form.addEventListener('submit', async (event) => {
        const limit = Number.parseInt(limitInput.value || '0', 10);
        const normalizedLimit = Number.isInteger(limit) ? Math.max(0, Math.min(10000, limit)) : 0;
        limitInput.value = String(normalizedLimit);
        event.preventDefault();

        const ok = window.confirm('Запустить ручной алгоритм автоархивации объявлений?');
        if (!ok) {
            return;
        }

        if (normalizedLimit === 0) {
            window.alert('Укажите количество объявлений для обработки больше 0.');
            return;
        }

        const csrfTokenInput = form.querySelector('input[name="csrf_token"]');
        const csrfToken = csrfTokenInput ? String(csrfTokenInput.value || '') : '';
        if (!csrfToken) {
            window.alert('CSRF токен не найден. Обновите страницу.');
            return;
        }

        runButton.disabled = true;
        limitInput.readOnly = true;
        stopButton.disabled = false;
        stopButton.classList.remove('d-none');
        cancelRequested = false;
        progressWrap.classList.remove('d-none');
        setProgress(0, 'Запуск обработки...');

        let processedTotal = 0;
        let archivedTotal = 0;
        let notifiedTotal = 0;
        let batches = 0;
        let pendingAfter = pendingExpireCount;

        try {
            while (processedTotal < normalizedLimit) {
                if (cancelRequested) {
                    break;
                }

                const batchLimit = Math.min(100, normalizedLimit - processedTotal);
                const batch = await runOneBatch(csrfToken, batchLimit);

                const batchProcessed = Number.parseInt(String(batch.processed || 0), 10) || 0;
                const batchArchived = Number.parseInt(String(batch.archived || 0), 10) || 0;
                const batchNotified = Number.parseInt(String(batch.notified || 0), 10) || 0;
                pendingAfter = Number.parseInt(String(batch.pending_after || 0), 10) || 0;

                processedTotal += batchProcessed;
                archivedTotal += batchArchived;
                notifiedTotal += batchNotified;
                batches += 1;

                const percent = normalizedLimit > 0 ? (processedTotal / normalizedLimit) * 100 : 100;
                setProgress(
                    percent,
                    `Батч ${batches}: обработано ${processedTotal}/${normalizedLimit}, архивировано ${archivedTotal}, писем ${notifiedTotal}, осталось в очереди ${pendingAfter}`
                );

                if (batchProcessed === 0 || pendingAfter <= 0) {
                    break;
                }
            }

            const successText = cancelRequested
                ? 'Ручной запуск автоархивации остановлен администратором.'
                : 'Ручной запуск автоархивации выполнен.';
            const query = new URLSearchParams({
                success: successText,
                processed: String(processedTotal),
                archived: String(archivedTotal),
                notified: String(notifiedTotal),
                target: String(normalizedLimit),
                batches: String(batches),
                pending_before: String(pendingExpireCount),
                pending_after: String(pendingAfter),
            });
            window.location.assign('/admin?' + query.toString());
        } catch (error) {
            const message = error instanceof Error ? error.message : 'Ошибка запуска.';
            setProgress(0, 'Ошибка: ' + message);
            runButton.disabled = false;
            limitInput.readOnly = false;
            stopButton.disabled = true;
            stopButton.classList.add('d-none');
        }
    });

    stopButton.addEventListener('click', () => {
        cancelRequested = true;
        stopButton.disabled = true;
        setProgress(
            Number.parseFloat(progressBar.style.width || '0') || 0,
            'Остановка запрошена. Дождитесь завершения текущего батча...'
        );
    });
})();
</script>
<?php endif; ?>
