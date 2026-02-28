<div class="mb-4">
    <a href="<?= route_url('/') ?>" class="text-muted small text-decoration-none"><i class="bi bi-arrow-left"></i> К списку</a>
</div>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 mb-0">Избранное</h1>
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
                    <th>Цена</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($posts)): ?>
                <tr>
                    <td colspan="8" class="text-muted text-center py-4">Нет избранных объявлений. <a href="<?= route_url('/') ?>">Добавьте</a> их со страницы объявлений.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($posts as $p): ?>
                <tr>
                    <td data-label="Фото">
                        <?php if (!empty($firstPhotos[(int)$p['id']])): ?>
                            <img src="<?= photo_thumb_url((int)$p['user_id'], (int)$p['id'], (string)$firstPhotos[(int)$p['id']], 200, 150) ?>" alt="<?= htmlspecialchars('Фото объявления: ' . $p['object_name'] . ', ' . $p['street']) ?>" loading="lazy" decoding="async" style="width:80px;height:60px;object-fit:cover;border-radius:6px;display:block">
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
                    <td data-label="">
                        <button type="button" class="btn btn-outline-danger btn-sm btn-remove-favorite" data-id="<?= (int)$p['id'] ?>" title="Убрать из избранного" aria-label="Убрать объявление #<?= (int)$p['id'] ?> из избранного"><i class="bi bi-heart-fill"></i></button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
