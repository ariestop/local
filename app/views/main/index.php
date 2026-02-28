<?php
$page = (int)($page ?? 1);
$totalPages = (int)($totalPages ?? 1);
$total = (int)($total ?? 0);
$filters = $filters ?? [];
$sort = $sort ?? 'date_desc';
$actions = $actions ?? [];
$cities = $cities ?? [];
$popularPosts = $popularPosts ?? [];
$activity = $activity ?? [];
$firstPhotos = $firstPhotos ?? [];
$isAdmin = (bool) ($isAdmin ?? false);
$qs = fn($over = []) => http_build_query(array_merge($filters, ['sort' => $sort], $over));
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="h4 mb-0">Продажа недвижимости</h1>
    <?php if ($user): ?>
    <a href="<?= route_url('/add') ?>" class="btn btn-primary">Добавить объявление</a>
    <?php else: ?>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#loginModal" data-bs-whatever="add">Добавить объявление</button>
    <?php endif; ?>
</div>

<div class="row g-3">
    <aside class="col-lg-3">
        <div class="card border-0 shadow-sm mb-3 d-none" id="recentHistoryPanel">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                    <h2 class="h6 mb-0">Недавно просмотренные</h2>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="historyClearBtn">Очистить</button>
                </div>
                <div class="list-group list-group-flush" id="recentHistoryList"></div>
            </div>
        </div>
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <h2 class="h6 mb-3">Популярные объявления</h2>
                <?php if (!empty($popularPosts)): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($popularPosts as $pp): ?>
                            <a class="list-group-item list-group-item-action px-0" href="<?= route_url('/detail/' . (int)$pp['id']) ?>">
                                <div class="small fw-semibold"><?= htmlspecialchars($pp['action_name'] . ' ' . $pp['object_name']) ?></div>
                                <div class="small text-muted"><?= htmlspecialchars($pp['city_name'] . ', ' . $pp['street']) ?></div>
                                <div class="small">Просмотры: <?= number_format((int)($pp['view_count'] ?? 0), 0, '', ' ') ?></div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-muted small">Пока нет данных</div>
                <?php endif; ?>
            </div>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h2 class="h6 mb-3">Активность (7 дней)</h2>
                <?php if (!empty($activity)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Дата</th>
                                    <th>Просм.</th>
                                    <th>Новые</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activity as $a): ?>
                                    <tr>
                                        <td class="small"><?= htmlspecialchars(date('d.m', strtotime((string)$a['date']))) ?></td>
                                        <td class="small"><?= (int)($a['views'] ?? 0) ?></td>
                                        <td class="small"><?= (int)($a['new_posts'] ?? 0) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-muted small">Пока нет данных</div>
                <?php endif; ?>
            </div>
        </div>
    </aside>
    <div class="col-lg-9">
        <!-- Фильтры и сортировка -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body py-3">
                <form method="get" action="<?= route_url('/') ?>" class="row g-2 align-items-end" id="filterForm">
                    <div class="col-md-2 col-6">
                        <label class="form-label small mb-0">Тип</label>
                        <select name="action_id" class="form-select form-select-sm">
                            <option value="">Все</option>
                            <?php foreach ($actions as $a): ?>
                            <option value="<?= (int)$a['id'] ?>" <?= ($filters['action_id'] ?? '') == $a['id'] ? 'selected' : '' ?>><?= htmlspecialchars($a['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 col-6">
                        <label class="form-label small mb-0">Город</label>
                        <select name="city_id" class="form-select form-select-sm">
                            <option value="">Все</option>
                            <?php foreach ($cities as $c): ?>
                            <option value="<?= (int)$c['id'] ?>" <?= ($filters['city_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-1 col-4">
                        <label class="form-label small mb-0">Комнат</label>
                        <select name="room" class="form-select form-select-sm">
                            <option value="">—</option>
                            <?php for ($r = 1; $r <= 5; $r++): ?>
                            <option value="<?= $r ?>" <?= ($filters['room'] ?? '') === (string)$r ? 'selected' : '' ?>><?= $r ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-2 col-4">
                        <label class="form-label small mb-0">Цена от</label>
                        <input type="number" name="price_min" class="form-control form-control-sm" placeholder="0" value="<?= htmlspecialchars($filters['price_min'] ?? '') ?>">
                    </div>
                    <div class="col-md-2 col-4">
                        <label class="form-label small mb-0">Цена до</label>
                        <input type="number" name="price_max" class="form-control form-control-sm" placeholder="∞" value="<?= htmlspecialchars($filters['price_max'] ?? '') ?>">
                    </div>
                    <?php if ($isAdmin): ?>
                    <div class="col-md-1 col-4">
                        <label class="form-label small mb-0">ID</label>
                        <input type="number" name="post_id" class="form-control form-control-sm" placeholder="ID" value="<?= htmlspecialchars((string)($filters['post_id'] ?? '')) ?>">
                    </div>
                    <?php endif; ?>
                    <div class="col-md-2 col-6">
                        <label class="form-label small mb-0">Сортировка</label>
                        <select name="sort" class="form-select form-select-sm">
                            <option value="date_desc" <?= $sort === 'date_desc' ? 'selected' : '' ?>>Сначала новые</option>
                            <option value="date_asc" <?= $sort === 'date_asc' ? 'selected' : '' ?>>Сначала старые</option>
                            <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Цена ↑</option>
                            <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Цена ↓</option>
                        </select>
                    </div>
                    <div class="col-md-1 col-6">
                        <button type="submit" class="btn btn-primary btn-sm w-100">Найти</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm posts-mobile">
            <div class="table-responsive">
                <table class="table table-hover mb-0 posts-table">
                    <thead>
                        <tr>
                            <th>Фото</th>
                            <th>Дата</th>
                            <th>Действие</th>
                            <th>Объект</th>
                            <th>Город / Район</th>
                            <th>Комнат</th>
                            <th>М²</th>
                            <th>Цена (руб.)</th>
                            <?php if ($user): ?><th></th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($posts as $p): ?>
                        <tr>
                            <td data-label="Фото">
                                <?php if (!empty($firstPhotos[(int)$p['id']])): ?>
                                    <img src="<?= photo_thumb_url((int)$p['user_id'], (int)$p['id'], (string)$firstPhotos[(int)$p['id']], 200, 150) ?>" alt="" loading="lazy" decoding="async" style="width:80px;height:60px;object-fit:cover;border-radius:6px;display:block">
                                <?php else: ?>
                                    <svg viewBox="0 0 120 80" role="img" aria-label="Нет фото" style="width:80px;height:60px;display:block;border-radius:6px;background:#f3f4f6">
                                        <rect x="12" y="24" width="96" height="44" rx="6" fill="#e5e7eb"></rect>
                                        <path d="M8 30L60 6L112 30" stroke="#9ca3af" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"></path>
                                        <rect x="49" y="42" width="22" height="24" rx="4" fill="#d1d5db"></rect>
                                    </svg>
                                <?php endif; ?>
                            </td>
                            <td class="text-nowrap text-muted small" data-label="Дата"><?= date('d/m', strtotime($p['created_at'])) ?></td>
                            <td data-label="Действие"><?= htmlspecialchars($p['action_name']) ?></td>
                            <td data-label="Объект"><?= htmlspecialchars($p['object_name']) ?></td>
                            <td data-label="Адрес">
                                <a href="<?= route_url('/detail/' . (int)$p['id']) ?>" class="text-dark text-decoration-none"><?= htmlspecialchars($p['city_name'] . ', ' . $p['area_name'] . ' р-н., ' . $p['street']) ?></a>
                            </td>
                            <td data-label="Комнат"><?= (int)$p['room'] ?></td>
                            <td data-label="М²"><?= (int)$p['m2'] ?></td>
                            <td class="cost" data-label="Цена"><?= number_format((int)$p['cost'], 0, '', ' ') ?> ₽</td>
                            <?php if ($user): ?>
                            <td data-label="">
                                <div class="d-flex gap-1 justify-content-end">
                                <button type="button" class="btn btn-sm btn-favorite <?= in_array((int)$p['id'], $favoriteIds ?? []) ? 'btn-danger' : 'btn-outline-secondary' ?>" data-id="<?= (int)$p['id'] ?>" title="<?= in_array((int)$p['id'], $favoriteIds ?? []) ? 'Убрать из избранного' : 'В избранное' ?>">
                                    <i class="bi bi-heart<?= in_array((int)$p['id'], $favoriteIds ?? []) ? '-fill' : '' ?>"></i>
                                </button>
                                <?php if ($isAdmin): ?>
                                <button type="button" class="btn btn-sm btn-outline-warning btn-admin-post-action" data-id="<?= (int)$p['id'] ?>" title="Действия с объявлением">
                                    <i class="bi bi-gear"></i>
                                </button>
                                <?php endif; ?>
                                </div>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if ($totalPages > 1): ?>
        <nav class="mt-3 d-flex flex-wrap align-items-center justify-content-center gap-2" aria-label="Пагинация">
            <ul class="pagination mb-0">
                <li class="page-item">
                    <a class="page-link" href="?<?= $qs(['page' => 1]) ?>" title="Первая страница">1</a>
                </li>
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= $page > 1 ? '?' . $qs(['page' => $page - 1]) : '#' ?>">‹</a>
                </li>
                <li class="page-item disabled"><span class="page-link"><?= $page ?> / <?= $totalPages ?></span></li>
                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= $page < $totalPages ? '?' . $qs(['page' => $page + 1]) : '#' ?>">›</a>
                </li>
            </ul>
            <div class="d-flex align-items-center gap-1">
                <label class="form-label mb-0 small text-muted">Страница:</label>
                <input type="number" id="pageInput" class="form-control form-control-sm" style="width:70px" min="1" max="<?= $totalPages ?>" value="<?= $page ?>">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="pageGoBtn">Перейти</button>
            </div>
        </nav>
        <?php endif; ?>
    </div>
 </div>

<?php if ($isAdmin): ?>
<div class="modal fade" id="adminPostActionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Действие с объявлением</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Выберите действие для объявления <strong id="adminPostActionIdLabel">#0</strong>.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-outline-warning" id="adminArchiveBtn">В архив</button>
                <button type="button" class="btn btn-danger" id="adminHardDeleteBtn">Удалить с сервера</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

