<div class="mb-4">
    <a href="/" class="text-muted small text-decoration-none"><i class="bi bi-arrow-left"></i> К списку</a>
</div>
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h2 class="h5 mb-4"><?= htmlspecialchars($post['action_name'] . ' ' . $post['object_name']) ?></h2>
        <p class="text-muted small mb-2"><?= date('d.m.Y', strtotime($post['created_at'])) ?></p>
        <dl class="row mb-0">
            <dt class="col-sm-3">Адрес</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($post['city_name'] . ', ' . $post['area_name'] . ' р-н., ' . $post['street']) ?></dd>
            <dt class="col-sm-3">Комнат</dt>
            <dd class="col-sm-9"><?= (int)$post['room'] ?></dd>
            <dt class="col-sm-3">Площадь</dt>
            <dd class="col-sm-9"><?= (int)$post['m2'] ?> м²</dd>
            <dt class="col-sm-3">Цена</dt>
            <dd class="col-sm-9 fw-bold"><?= number_format((int)$post['cost'], 0, '', ' ') ?> руб.</dd>
            <dt class="col-sm-3">Телефон</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($post['phone']) ?></dd>
            <dt class="col-sm-3">Описание</dt>
            <dd class="col-sm-9"><?= nl2br(htmlspecialchars($post['descr_post'])) ?></dd>
        </dl>
    </div>
</div>
