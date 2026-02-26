<?php
$uid = (int)($post['user_id'] ?? 0);
$pid = (int)($post['id'] ?? 0);
$photos = $photos ?? [];
?>
<div class="mb-4">
    <a href="/edit-advert" class="text-muted small text-decoration-none"><i class="bi bi-arrow-left"></i> К моим объявлениям</a>
</div>
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h2 class="h5 mb-4">Редактировать объявление</h2>
        <div id="editError" class="alert alert-danger d-none"></div>
        <form id="editForm" enctype="multipart/form-data" data-max-price="<?= (int)($max_price ?? 999000000) ?>"><?= csrf_field() ?>
            <input type="hidden" name="delete_photos" id="deletePhotos" value="">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Действие *</label>
                    <select name="action_id" class="form-select" required>
                        <option value="">Выберите...</option>
                        <?php foreach ($actions as $a): ?>
                        <option value="<?= (int)$a['id'] ?>" <?= (int)($post['action_id'] ?? 0) === (int)$a['id'] ? 'selected' : '' ?>><?= htmlspecialchars($a['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Объект *</label>
                    <select name="object_id" class="form-select" required>
                        <option value="">Выберите...</option>
                        <?php foreach ($objects as $o): ?>
                        <option value="<?= (int)$o['id'] ?>" <?= (int)($post['object_id'] ?? 0) === (int)$o['id'] ? 'selected' : '' ?>><?= htmlspecialchars($o['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Город *</label>
                    <select name="city_id" id="citySelect" class="form-select" required>
                        <option value="">Выберите...</option>
                        <?php foreach ($cities as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= (int)($post['city_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Район *</label>
                    <select name="area_id" id="areaSelect" class="form-select" required>
                        <option value="">Сначала выберите город</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Улица *</label>
                    <input type="text" name="street" class="form-control" value="<?= htmlspecialchars($post['street'] ?? '') ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Комнат</label>
                    <input type="number" name="room" class="form-control" min="0" value="<?= (int)($post['room'] ?? 1) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Площадь (м²)</label>
                    <input type="number" name="m2" class="form-control" min="0" value="<?= (int)($post['m2'] ?? 0) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Новостройка?</label>
                    <select name="new_house" class="form-select">
                        <option value="0" <?= empty($post['new_house']) ? 'selected' : '' ?>>Нет</option>
                        <option value="1" <?= !empty($post['new_house']) ? 'selected' : '' ?>>Да</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Описание *</label>
                    <textarea name="descr_post" class="form-control" rows="4" required><?= htmlspecialchars($post['descr_post'] ?? '') ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Фотографии (до 5 шт.)</label>
                    <?php if (!empty($photos)): ?>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <?php foreach ($photos as $ph): ?>
                        <div class="photo-item border rounded p-2" data-filename="<?= htmlspecialchars($ph['filename']) ?>">
                            <img src="<?= photo_thumb_url($uid, $pid, $ph['filename'], 200, 150) ?>" alt="" style="width:80px;height:60px;object-fit:cover;display:block">
                            <label class="d-block mt-1 small">
                                <input type="checkbox" class="photo-delete" value="<?= htmlspecialchars($ph['filename']) ?>"> Удалить
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <input type="file" name="photos[]" id="photosInput" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp" multiple data-max-bytes="<?= (int)($max_photo_bytes ?? 5242880) ?>">
                    <div class="form-text">Макс. <?= round((int)($max_photo_bytes ?? 5242880) / 1024 / 1024) ?> МБ на файл</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Телефон *</label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($post['phone'] ?? '') ?>" placeholder="89001112233" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Цена (руб.) *</label>
                    <input type="text" name="cost" id="editCostInput" class="form-control" value="<?= number_format((int)($post['cost'] ?? 0), 0, '', ' ') ?>" placeholder="1250000" required>
                    <div class="form-text">Макс. <?= number_format((int)($max_price ?? 999000000), 0, '', ' ') ?> руб.</div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                    <a href="/edit-advert" class="btn btn-outline-secondary ms-2">Отмена</a>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const areasByCity = <?= json_encode($areasByCity ?? []) ?>;
    const cityId = <?= (int)($post['city_id'] ?? 0) ?>;
    const areaId = <?= (int)($post['area_id'] ?? 0) ?>;

    document.getElementById('citySelect').addEventListener('change', function() {
        const sid = this.value;
        const sel = document.getElementById('areaSelect');
        sel.innerHTML = '<option value="">Выберите...</option>';
        if (sid && areasByCity[sid]) {
            areasByCity[sid].forEach(a => {
                const opt = document.createElement('option');
                opt.value = a.id;
                opt.textContent = a.name;
                if (sid == cityId && a.id == areaId) opt.selected = true;
                sel.appendChild(opt);
            });
        }
    });
    if (cityId && areasByCity[cityId]) {
        document.getElementById('citySelect').dispatchEvent(new Event('change'));
    }

    document.getElementById('editForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const form = e.target;
        const errEl = document.getElementById('editError');
        errEl.classList.add('d-none');
        const maxPrice = parseInt(form.dataset.maxPrice || '999000000', 10);
        const costEl = form.querySelector('input[name="cost"]');
        if (costEl && maxPrice > 0) {
            const cost = parseInt(String(costEl.value).replace(/\D/g, '') || '0', 10);
            if (cost > maxPrice) {
                errEl.textContent = 'Цена не должна превышать ' + maxPrice.toLocaleString('ru-RU') + ' руб.';
                errEl.classList.remove('d-none');
                return;
            }
        }
        const btn = form.querySelector('button[type="submit"]');
        if (window.setButtonLoading) window.setButtonLoading(btn, true);
        const toDelete = [];
        document.querySelectorAll('.photo-delete:checked').forEach(cb => toDelete.push(cb.value));
        document.getElementById('deletePhotos').value = toDelete.join(',');

        const fd = new FormData(form);
        try {
            const r = await fetch('/edit/<?= $pid ?>', {
                method: 'POST',
                body: fd,
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const text = await r.text();
            let data = {};
            try {
                var m = text.match(/\{[\s\S]*\}/);
                if (m) data = JSON.parse(m[0]);
            } catch (x) {}
            if (data.success) {
                if (window.showToast) window.showToast('Изменения сохранены');
                window.location.href = '/detail/' + data.id;
            } else {
                errEl.textContent = data.error || 'Ошибка';
                errEl.classList.remove('d-none');
            }
        } catch (err) {
            errEl.textContent = 'Ошибка сети: ' + (err.message || '');
            errEl.classList.remove('d-none');
        } finally {
            if (window.setButtonLoading) window.setButtonLoading(btn, false);
        }
    });
});
</script>
