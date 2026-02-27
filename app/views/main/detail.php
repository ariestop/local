<div class="mb-4">
    <a href="/" class="text-muted small text-decoration-none"><i class="bi bi-arrow-left"></i> К списку</a>
</div>
<?php
$uid = (int)($post['user_id'] ?? 0);
$pid = (int)($post['id'] ?? 0);
$photos = $photos ?? [];
?>
<?php if (!empty($photos)): ?>
<div class="mb-4 position-relative detail-photo-wrap">
    <div class="text-center bg-dark rounded overflow-hidden">
        <img id="detailPhoto" class="gallery-img" src="<?= photo_thumb_url($uid, $pid, $photos[0]['filename'], 400, 300) ?>" alt="" loading="lazy" style="max-width:100%;max-height:400px;object-fit:contain;cursor:pointer">
    </div>
    <div class="d-flex justify-content-between align-items-center mt-2">
        <button type="button" class="btn btn-outline-secondary btn-sm" id="detailPrev">‹ Пред</button>
        <span class="text-muted small" id="detailCounter">1 / <?= count($photos) ?></span>
        <button type="button" class="btn btn-outline-secondary btn-sm" id="detailNext">След ›</button>
    </div>
    <div class="d-flex gap-1 overflow-auto justify-content-center mt-2 py-2" id="detailThumbs"></div>
</div>
<?php else: ?>
<div class="mb-4 position-relative detail-photo-wrap">
    <div class="text-center bg-light rounded overflow-hidden p-2">
        <svg viewBox="0 0 1200 675" role="img" aria-label="Заглушка: фото отсутствуют" style="width:100%;max-height:250px;display:block">
            <rect width="1200" height="675" rx="24" fill="#F3F4F6"></rect>
            <rect x="286" y="160" width="628" height="355" rx="16" fill="#E5E7EB"></rect>
            <rect x="340" y="228" width="190" height="220" rx="10" fill="#D1D5DB"></rect>
            <rect x="560" y="228" width="95" height="95" rx="10" fill="#D1D5DB"></rect>
            <rect x="675" y="228" width="95" height="95" rx="10" fill="#D1D5DB"></rect>
            <rect x="790" y="228" width="70" height="220" rx="10" fill="#D1D5DB"></rect>
            <path d="M252 268L600 92L948 268" stroke="#9CA3AF" stroke-width="24" stroke-linecap="round" stroke-linejoin="round"></path>
            <circle cx="600" cy="370" r="56" fill="#9CA3AF"></circle>
            <path d="M600 345L610 365H634L614 378L622 401L600 388L578 401L586 378L566 365H590L600 345Z" fill="#F9FAFB"></path>
            <text x="600" y="590" text-anchor="middle" fill="#6B7280" font-family="Segoe UI, Arial, sans-serif" font-size="38" font-weight="600">Нет фото объявления</text>
        </svg>
    </div>
</div>
<?php endif; ?>
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <h2 class="h5 mb-0"><?= htmlspecialchars($post['action_name'] . ' ' . $post['object_name']) ?></h2>
            <div class="d-flex flex-column align-items-end gap-2">
                <div class="small text-muted">Просмотров: <?= number_format((int)($post['view_count'] ?? 0), 0, '', ' ') ?></div>
                <?php if ($user): ?>
                <button type="button" class="btn btn-sm <?= !empty($isFavorite) ? 'btn-danger' : 'btn-outline-secondary' ?> btn-favorite-detail" data-id="<?= (int)$post['id'] ?>" title="<?= !empty($isFavorite) ? 'Убрать из избранного' : 'В избранное' ?>">
                    <i class="bi bi-heart<?= !empty($isFavorite) ? '-fill' : '' ?>"></i> <?= !empty($isFavorite) ? 'В избранном' : 'В избранное' ?>
                </button>
                <?php endif; ?>
            </div>
        </div>
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
            <dd class="col-sm-9 d-flex align-items-center gap-2 flex-wrap">
                <span><?= htmlspecialchars($post['phone']) ?></span>
                <button type="button" class="btn btn-outline-secondary btn-sm copy-phone-btn" data-copy-phone="<?= htmlspecialchars($post['phone']) ?>" title="Скопировать">
                    <i class="bi bi-clipboard"></i> Копировать
                </button>
            </dd>
            <dt class="col-sm-3">Описание</dt>
            <dd class="col-sm-9"><?= nl2br(htmlspecialchars($post['descr_post'])) ?></dd>
        </dl>
    </div>
</div>
<script>
window.currentDetailPost = {
    id: <?= (int)($post['id'] ?? 0) ?>,
    title: <?= json_encode(trim(($post['action_name'] ?? '') . ' ' . ($post['object_name'] ?? ''))) ?>,
    address: <?= json_encode(trim(($post['city_name'] ?? '') . ', ' . ($post['area_name'] ?? '') . ' р-н., ' . ($post['street'] ?? ''))) ?>,
    room: <?= (int)($post['room'] ?? 0) ?>,
    m2: <?= (int)($post['m2'] ?? 0) ?>,
    cost: <?= (int)($post['cost'] ?? 0) ?>,
    url: <?= json_encode('/detail/' . (int)($post['id'] ?? 0)) ?>
};
</script>

<?php if (!empty($photos)): ?>
<div class="modal fade" id="photoModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content bg-dark border-0">
            <div class="modal-header border-0">
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0 text-center">
                <img id="lightboxImg" src="" alt="" class="img-fluid" loading="lazy" decoding="async" style="max-height:70vh;object-fit:contain">
                <div class="d-flex justify-content-between align-items-center px-3 py-2">
                    <button type="button" class="btn btn-outline-light btn-sm" id="lightboxPrev">‹ Пред</button>
                    <span class="text-white-50 small" id="lightboxCounter"></span>
                    <button type="button" class="btn btn-outline-light btn-sm" id="lightboxNext">След ›</button>
                </div>
                <div class="d-flex gap-1 overflow-auto justify-content-center pb-2 px-2" id="lightboxThumbs"></div>
            </div>
        </div>
    </div>
</div>
<script>window.detailPhotos = <?= json_encode(array_map(fn($ph) => [
        'thumb' => photo_thumb_url($uid, $pid, $ph['filename'], 400, 300),
        'large' => photo_large_url($uid, $pid, $ph['filename']),
        'thumbSmall' => photo_thumb_url($uid, $pid, $ph['filename'], 200, 150)
    ], $photos)) ?>;</script>
<?php endif; ?>

