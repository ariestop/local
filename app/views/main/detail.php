<?php
$uid = (int) ($post['user_id'] ?? 0);
$pid = (int) ($post['id'] ?? 0);
$photos = $photos ?? [];
$breadcrumbs = is_array($breadcrumbs ?? null) ? $breadcrumbs : [];
$rawPhone = (string) ($post['phone'] ?? '');
$phoneDigits = preg_replace('/\D+/', '', $rawPhone) ?? '';
$displayPhone = $rawPhone;
if (strlen($phoneDigits) === 11) {
    $displayPhone = sprintf(
        '%s-%s-%s-%s-%s',
        substr($phoneDigits, 0, 1),
        substr($phoneDigits, 1, 3),
        substr($phoneDigits, 4, 3),
        substr($phoneDigits, 7, 2),
        substr($phoneDigits, 9, 2)
    );
}
$address = trim(($post['city_name'] ?? '') . ', ' . ($post['area_name'] ?? '') . ' р-н., ' . ($post['street'] ?? ''));
$pageTitle = trim((int) ($post['room'] ?? 0) . '-комнатная ' . mb_strtolower((string) ($post['object_name'] ?? 'недвижимость')) . ', ' . ($post['m2'] ?? 0) . ' м², ' . ($post['street'] ?? ''));
if ($pageTitle === '') {
    $pageTitle = trim(($post['action_name'] ?? '') . ' ' . ($post['object_name'] ?? ''));
}
?>

<style>
    .detail-shell { background: #fff; border: 1px solid #e6ecf2; border-radius: 10px; padding: 1.5rem; }
    .detail-breadcrumbs { color: #8a97a6; font-size: 0.875rem; margin-bottom: 0.45rem; }
    .detail-title { font-size: 1.9rem; font-weight: 700; line-height: 1.25; color: #1f2a36; margin: 0 0 0.85rem; max-width: 900px; }
    .detail-price { font-size: 2rem; font-weight: 700; color: #1f2a36; margin: 0 0 1.1rem; }
    .detail-meta { color: #8a97a6; font-size: 0.9rem; margin-bottom: 0.75rem; }
    .detail-facts { margin: 0; }
    .detail-facts dt, .detail-facts dd { border-bottom: 1px solid #edf2f7; padding-top: 0.12rem; padding-bottom: 0.55rem; margin-bottom: 0.4rem; font-weight: 400; }
    .detail-facts dt { color: #8a97a6; font-size: 0.82rem; }
    .detail-facts dd { color: #1f2a36; }
    .detail-gallery-main { background: #f6f8fb; border: 1px solid #e1e8ef; border-radius: 8px; width: 750px; height: 470px; max-width: 100%; margin: 0 auto; display: flex; align-items: center; justify-content: center; overflow: hidden; }
    .detail-gallery-main img { width: 100%; height: 100%; object-fit: cover; }
    .detail-thumbs-grid { display: flex; justify-content: center; align-items: center; flex-wrap: wrap; gap: 0.35rem; width: fit-content; max-width: 100%; margin: 0.5rem auto 0; }
    .detail-thumbs-grid .detail-thumb { border-radius: 6px; overflow: hidden; transition: all 0.15s ease; }
    .detail-thumbs-grid .detail-thumb img { display: block; width: 100%; height: 100%; object-fit: cover; }
    .detail-thumbs-grid .detail-thumb:hover { opacity: 1 !important; transform: translateY(-1px); }
    .detail-owner-card { border: 1px solid #e5ecf3; border-radius: 10px; padding: 1rem; background: #fcfdff; }
    .detail-owner-name { font-weight: 700; color: #1f2a36; margin-bottom: 0.25rem; }
    .detail-owner-role { color: #6f7f90; font-size: 0.875rem; margin-bottom: 0.65rem; }
    .detail-owner-phone { font-size: 1.3rem; font-weight: 400; color: #1f2a36; letter-spacing: 0.01em; line-height: 1.1; }
    .detail-views { color: #6f7f90; font-size: 0.875rem; }
    .detail-top-actions { display: flex; align-items: center; gap: 0.5rem; }
    .detail-action-btn { border: 1px solid #d9e2ec; background: #fff; color: #4b5c6b; border-radius: 6px; font-size: 0.875rem; padding: 0.35rem 0.7rem; line-height: 1; }
    .detail-action-btn:hover { border-color: #bfd0e0; background: #f8fbff; color: #2f4254; }
    .detail-fav-btn.btn-outline-secondary { border-color: #d9e2ec; color: #4b5c6b; background: #fff; }
    .detail-fav-btn.btn-outline-secondary:hover { border-color: #bfd0e0; background: #f8fbff; color: #2f4254; }
    .detail-fav-btn.btn-danger { background: #ef4056; border-color: #ef4056; color: #fff; }
    .detail-fav-btn.btn-danger:hover { background: #d73549; border-color: #d73549; color: #fff; }
    .detail-toolbar-sep { width: 1px; height: 22px; background: #e6edf3; }
    @media (max-width: 991px) {
        .detail-title, .detail-price { font-size: 1.5rem; }
        .detail-thumbs-grid { width: 100%; justify-content: center; }
        .detail-gallery-main { width: 100%; height: auto; aspect-ratio: 750 / 470; }
        .detail-shell { padding: 1rem; }
        .detail-top-actions { width: 100%; justify-content: flex-end; }
    }
</style>

<div class="mb-3">
    <a href="<?= route_url('/') ?>" class="text-muted small text-decoration-none"><i class="bi bi-arrow-left"></i> К списку</a>
</div>

<div class="detail-shell shadow-sm">
    <?php if ($breadcrumbs !== []): ?>
    <nav aria-label="breadcrumb" class="detail-breadcrumbs">
        <ol class="breadcrumb mb-0">
            <?php foreach ($breadcrumbs as $idx => $crumb): ?>
                <?php
                $isLast = $idx === array_key_last($breadcrumbs);
                $crumbName = htmlspecialchars((string) ($crumb['name'] ?? ''));
                $crumbUrl = (string) ($crumb['url'] ?? '');
                ?>
                <?php if ($isLast || $crumbUrl === ''): ?>
            <li class="breadcrumb-item active" aria-current="page"><?= $crumbName ?></li>
                <?php else: ?>
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($crumbUrl) ?>"><?= $crumbName ?></a></li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ol>
    </nav>
    <?php endif; ?>
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-2">
        <h1 class="detail-title"><?= htmlspecialchars($pageTitle) ?></h1>
        <div class="detail-top-actions flex-wrap justify-content-end">
            <button type="button" class="detail-action-btn">
                <i class="bi bi-pencil-square me-1"></i> Оставить заметку
            </button>
            <div class="detail-toolbar-sep"></div>
            <div class="detail-views">Просмотров: <?= number_format((int) ($post['view_count'] ?? 0), 0, '', ' ') ?></div>
            <?php if ($user): ?>
            <button type="button" class="btn btn-sm detail-fav-btn <?= !empty($isFavorite) ? 'btn-danger' : 'btn-outline-secondary' ?> btn-favorite-detail" data-id="<?= (int) $post['id'] ?>" title="<?= !empty($isFavorite) ? 'Убрать из избранного' : 'В избранное' ?>">
                <i class="bi bi-heart<?= !empty($isFavorite) ? '-fill' : '' ?>"></i> <?= !empty($isFavorite) ? 'В избранном' : 'В избранное' ?>
            </button>
            <?php endif; ?>
        </div>
    </div>
    <div class="detail-meta">Опубликовано: <?= date('d.m.Y', strtotime((string) ($post['created_at'] ?? 'now'))) ?></div>

    <div class="row g-4">
        <div class="col-lg-4 order-2 order-lg-1">
            <p class="detail-price"><?= number_format((int) ($post['cost'] ?? 0), 0, '', ' ') ?> руб.</p>
            <dl class="row detail-facts">
                <dt class="col-5">Город</dt>
                <dd class="col-7"><?= htmlspecialchars((string) ($post['city_name'] ?? '')) ?></dd>
                <dt class="col-5">Район</dt>
                <dd class="col-7"><?= htmlspecialchars((string) ($post['area_name'] ?? '')) ?></dd>
                <dt class="col-5">Адрес</dt>
                <dd class="col-7"><?= htmlspecialchars((string) ($post['street'] ?? '')) ?></dd>
                <dt class="col-5">Комнат</dt>
                <dd class="col-7"><?= (int) ($post['room'] ?? 0) ?></dd>
                <dt class="col-5">Площадь</dt>
                <dd class="col-7"><?= (int) ($post['m2'] ?? 0) ?> м²</dd>
                <dt class="col-5">Адрес</dt>
                <dd class="col-7"><?= htmlspecialchars($address) ?></dd>
            </dl>

            <div class="detail-owner-card mt-3 d-none d-lg-block">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center" style="width:54px;height:54px;">
                        <i class="bi bi-person text-muted"></i>
                    </div>
                    <div>
                        <div class="detail-owner-name"><?= htmlspecialchars((string) ($post['user_name'] ?? 'Продавец')) ?></div>
                        <div class="detail-owner-role">Автор объявления</div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="detail-owner-phone"><?= htmlspecialchars($displayPhone) ?></span>
                </div>
            </div>
        </div>

        <div class="col-lg-8 order-1 order-lg-2">
            <?php if (!empty($photos)): ?>
            <div class="detail-gallery-main detail-photo-wrap">
                <img id="detailPhoto" class="gallery-img" src="<?= photo_thumb_url($uid, $pid, $photos[0]['filename'], 750, 470) ?>" alt="" loading="lazy" decoding="async" style="cursor:pointer">
            </div>
            <div class="d-flex justify-content-between align-items-center mt-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="detailPrev">‹ Пред</button>
                <span class="text-muted small" id="detailCounter">1 / <?= count($photos) ?></span>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="detailNext">След ›</button>
            </div>
            <div class="detail-thumbs-grid" id="detailThumbs"></div>
            <?php else: ?>
            <div class="detail-gallery-main detail-photo-wrap p-2">
                <svg viewBox="0 0 1200 675" role="img" aria-label="Заглушка: фото отсутствуют" style="width:100%;max-height:420px;display:block">
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
            <?php endif; ?>
        </div>
    </div>

    <?php if (trim((string) ($post['descr_post'] ?? '')) !== ''): ?>
    <hr class="my-4">
    <h2 class="h6 mb-2 text-uppercase text-muted">Описание</h2>
    <p class="mb-0"><?= nl2br(htmlspecialchars((string) ($post['descr_post'] ?? ''))) ?></p>
    <?php endif; ?>

    <div class="detail-owner-card mt-4 d-lg-none">
        <div class="d-flex align-items-center gap-3 mb-2">
            <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center" style="width:54px;height:54px;">
                <i class="bi bi-person text-muted"></i>
            </div>
            <div>
                <div class="detail-owner-name"><?= htmlspecialchars((string) ($post['user_name'] ?? 'Продавец')) ?></div>
                <div class="detail-owner-role">Автор объявления</div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="detail-owner-phone"><?= htmlspecialchars($displayPhone) ?></span>
        </div>
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
    url: <?= json_encode(route_url('/detail/' . (int)($post['id'] ?? 0))) ?>
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
        'thumb' => photo_thumb_url($uid, $pid, $ph['filename'], 750, 470),
        'large' => photo_large_url($uid, $pid, $ph['filename']),
        'thumbSmall' => photo_thumb_url($uid, $pid, $ph['filename'], 200, 150)
    ], $photos)) ?>;</script>
<?php endif; ?>

