<div class="mb-4">
    <a href="/" class="text-muted small text-decoration-none"><i class="bi bi-arrow-left"></i> К списку</a>
</div>
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h2 class="h5 mb-4">Добавить объявление</h2>
        <div id="addError" class="alert alert-danger d-none"></div>
        <form id="addForm" enctype="multipart/form-data" data-max-price="<?= (int)($max_price ?? 999000000) ?>"><?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Действие *</label>
                    <select name="action_id" class="form-select" required>
                        <option value="">Выберите...</option>
                        <?php foreach ($actions as $a): ?>
                        <option value="<?= (int)$a['id'] ?>"><?= htmlspecialchars($a['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Объект *</label>
                    <select name="object_id" class="form-select" required>
                        <option value="">Выберите...</option>
                        <?php foreach ($objects as $o): ?>
                        <option value="<?= (int)$o['id'] ?>"><?= htmlspecialchars($o['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Город *</label>
                    <select name="city_id" id="citySelect" class="form-select" required>
                        <option value="">Выберите...</option>
                        <?php foreach ($cities as $c): ?>
                        <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
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
                    <input type="text" name="street" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Комнат</label>
                    <input type="number" name="room" class="form-control" min="0" value="1">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Площадь (м²)</label>
                    <input type="number" name="m2" class="form-control" min="0">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Новостройка?</label>
                    <select name="new_house" class="form-select">
                        <option value="0">Нет</option>
                        <option value="1">Да</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Описание *</label>
                    <textarea name="descr_post" class="form-control" rows="4" required></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Фотографии (до 5 шт.)</label>
                    <input type="file" name="photos[]" id="addPhotosInput" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp" multiple>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Телефон *</label>
                    <input type="text" name="phone" class="form-control" placeholder="89001112233" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Цена (руб.) *</label>
                    <input type="text" name="cost" id="costInput" class="form-control" placeholder="1250000" required
                           data-max="<?= (int)($max_price ?? 999000000) ?>">
                    <div id="costHint" class="form-text">Макс. <?= number_format((int)($max_price ?? 999000000), 0, '', ' ') ?> руб.</div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Добавить</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const areasByCity = <?= json_encode($areasByCity ?? []) ?>;
    const maxPrice = parseInt(document.getElementById('costInput')?.dataset.max || '999000000', 10);
    document.getElementById('citySelect').addEventListener('change', function() {
        const sid = this.value;
        const sel = document.getElementById('areaSelect');
        sel.innerHTML = '<option value="">Выберите...</option>';
        if (sid && areasByCity[sid]) {
            areasByCity[sid].forEach(a => {
                const opt = document.createElement('option');
                opt.value = a.id;
                opt.textContent = a.name;
                sel.appendChild(opt);
            });
        }
    });

});
</script>
