<?php $page = (int)($page ?? 1); $totalPages = (int)($totalPages ?? 1); $total = (int)($total ?? 0); ?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="h4 mb-0">Продажа недвижимости</h1>
    <?php if ($user): ?>
    <a href="/add" class="btn btn-primary">Добавить объявление</a>
    <?php else: ?>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#loginModal" data-bs-whatever="add">Добавить объявление</button>
    <?php endif; ?>
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
                </tr>
            </thead>
            <tbody>
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
            <a class="page-link" href="?page=1" title="Первая страница">1</a>
        </li>
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= $page > 1 ? '?page=' . ($page - 1) : '#' ?>">‹</a>
        </li>
        <li class="page-item disabled"><span class="page-link"><?= $page ?> / <?= $totalPages ?></span></li>
        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= $page < $totalPages ? '?page=' . ($page + 1) : '#' ?>">›</a>
        </li>
    </ul>
    <div class="d-flex align-items-center gap-1">
        <label class="form-label mb-0 small text-muted">Страница:</label>
        <input type="number" id="pageInput" class="form-control form-control-sm" style="width:70px" min="1" max="<?= $totalPages ?>" value="<?= $page ?>">
        <button type="button" class="btn btn-sm btn-outline-secondary" id="pageGoBtn">Перейти</button>
    </div>
</nav>
<script>
(function() {
    var input = document.getElementById('pageInput');
    var btn = document.getElementById('pageGoBtn');
    if (!input || !btn) return;
    function go() {
        var p = parseInt(input.value, 10);
        var max = parseInt(input.max, 10);
        if (p >= 1 && p <= max) location.href = '?page=' + p;
    }
    btn.onclick = go;
    input.onkeydown = function(e) { if (e.key === 'Enter') { e.preventDefault(); go(); } };
})();
</script>
<?php endif; ?>
