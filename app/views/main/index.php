<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 mb-0">Продажа недвижимости</h1>
    <?php if ($user): ?>
    <a href="/add" class="btn btn-primary">Добавить объявление</a>
    <?php else: ?>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#loginModal" data-bs-whatever="add">Добавить объявление</button>
    <?php endif; ?>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Дата</th>
                    <th>Действие</th>
                    <th>Объект</th>
                    <th>Город / Район</th>
                    <th>Комнат</th>
                    <th>М²</th>
                    <th>Цена (руб.)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($posts as $p): ?>
                <tr>
                    <td class="text-nowrap text-muted small"><?= date('d/m', strtotime($p['created_at'])) ?></td>
                    <td><?= htmlspecialchars($p['action_name']) ?></td>
                    <td><?= htmlspecialchars($p['object_name']) ?></td>
                    <td>
                        <a href="/detail/<?= (int)$p['id'] ?>" class="text-dark text-decoration-none"><?= htmlspecialchars($p['city_name'] . ', ' . $p['area_name'] . ' р-н., ' . $p['street']) ?></a>
                    </td>
                    <td><?= (int)$p['room'] ?></td>
                    <td><?= (int)$p['m2'] ?></td>
                    <td class="cost"><?= number_format((int)$p['cost'], 0, '', ' ') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
