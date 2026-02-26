<div class="mb-4">
    <a href="/" class="text-muted small text-decoration-none"><i class="bi bi-arrow-left"></i> К списку</a>
</div>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 mb-0">Избранное</h1>
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
                    <th>Цена</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($posts)): ?>
                <tr>
                    <td colspan="8" class="text-muted text-center py-4">Нет избранных объявлений. <a href="/">Добавьте</a> их со страницы объявлений.</td>
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
                    <td data-label="">
                        <button type="button" class="btn btn-outline-danger btn-sm btn-remove-favorite" data-id="<?= (int)$p['id'] ?>" title="Убрать из избранного"><i class="bi bi-heart-fill"></i></button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.querySelectorAll('.btn-remove-favorite').forEach(function(btn) {
    btn.addEventListener('click', async function() {
        const id = this.dataset.id;
        try {
            const fd = new FormData();
            fd.append('post_id', id);
            fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
            const r = await fetch('/api/favorite/toggle', {
                method: 'POST',
                body: fd,
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await r.json();
            if (data.success && !data.added) {
                this.closest('tr').remove();
                if (window.showToast) window.showToast('Убрано из избранного');
            }
        } catch (e) {}
    });
});
</script>
