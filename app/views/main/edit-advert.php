<div class="mb-4">
    <a href="/" class="text-muted small text-decoration-none"><i class="bi bi-arrow-left"></i> К списку</a>
</div>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 mb-0">Мои объявления</h1>
    <a href="/add" class="btn btn-primary">Добавить объявление</a>
</div>

<div class="card border-0 shadow-sm posts-mobile">
    <div class="table-responsive">
        <table class="table table-hover mb-0 posts-table">
            <thead>
                <tr>
                    <th>Дата</th>
                    <th>Действие</th>
                    <th>Объект</th>
                    <th>Город / Район</th>
                    <th>Комнат</th>
                    <th>М²</th>
                    <th>Цена (руб.)</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($posts)): ?>
                <tr>
                    <td colspan="8" class="text-muted text-center py-4">У вас пока нет объявлений. <a href="/add">Добавить объявление</a></td>
                </tr>
                <?php else: ?>
                <?php foreach ($posts as $p): ?>
                <tr>
                    <td class="text-nowrap text-muted small" data-label="Дата"><?= date('d/m', strtotime($p['created_at'])) ?></td>
                    <td data-label="Действие"><?= htmlspecialchars($p['action_name']) ?></td>
                    <td data-label="Объект"><?= htmlspecialchars($p['object_name']) ?></td>
                    <td data-label="Адрес">
                        <a href="/detail/<?= (int)$p['id'] ?>" class="text-dark text-decoration-none"><?= htmlspecialchars($p['city_name'] . ', ' . $p['area_name'] . ' р-н., ' . $p['street']) ?></a>
                    </td>
                    <td data-label="Комнат"><?= (int)$p['room'] ?></td>
                    <td data-label="М²"><?= (int)$p['m2'] ?></td>
                    <td class="cost" data-label="Цена"><?= number_format((int)$p['cost'], 0, '', ' ') ?> ₽</td>
                    <td data-label="Действия" class="d-flex gap-1 flex-wrap">
                        <a href="/edit/<?= (int)$p['id'] ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-pencil"></i> Редактировать</a>
                        <button type="button" class="btn btn-outline-danger btn-sm btn-delete-post" data-id="<?= (int)$p['id'] ?>"><i class="bi bi-trash"></i> Удалить</button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
