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
<?php endif; ?>
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

<?php if (!empty($photos)): ?>
<div class="modal fade" id="photoModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content bg-dark border-0">
            <div class="modal-header border-0">
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0 text-center">
                <img id="lightboxImg" src="" alt="" class="img-fluid" style="max-height:70vh;object-fit:contain">
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
<script>
(function(){
    var photos = <?= json_encode(array_map(fn($ph) => [
        'thumb' => photo_thumb_url($uid, $pid, $ph['filename'], 400, 300),
        'large' => photo_large_url($uid, $pid, $ph['filename']),
        'thumbSmall' => photo_thumb_url($uid, $pid, $ph['filename'], 200, 150)
    ], $photos)) ?>;
    var idx = 0;
    var modal = document.getElementById('photoModal');
    var detailImg = document.getElementById('detailPhoto');
    var lightboxImg = document.getElementById('lightboxImg');
    var thumbs = document.getElementById('lightboxThumbs');
    var counter = document.getElementById('lightboxCounter');
    var detailCounter = document.getElementById('detailCounter');
    var detailPrev = document.getElementById('detailPrev');
    var detailNext = document.getElementById('detailNext');
    var detailThumbs = document.getElementById('detailThumbs');
    var prev = document.getElementById('lightboxPrev');
    var next = document.getElementById('lightboxNext');
    function renderDetailThumbs() {
        if (!detailThumbs) return;
        detailThumbs.innerHTML = '';
        photos.forEach(function(p, i) {
            var a = document.createElement('a');
            a.href = '#';
            a.className = 'detail-thumb' + (i === idx ? ' border border-2 border-primary' : ' opacity-75');
            a.style.cssText = 'width:60px;height:45px;display:block;overflow:hidden;border-radius:4px;flex-shrink:0';
            a.innerHTML = '<img src="' + p.thumbSmall + '" style="width:100%;height:100%;object-fit:cover">';
            a.onclick = function(e) { e.preventDefault(); idx = i; updateDetail(); };
            detailThumbs.appendChild(a);
        });
    }
    function updateDetail() {
        if (detailImg) detailImg.src = photos[idx].thumb;
        if (detailCounter) detailCounter.textContent = (idx + 1) + ' / ' + photos.length;
        renderDetailThumbs();
    }
    (function init() { renderDetailThumbs(); })();
    function showLightbox() {
        lightboxImg.src = photos[idx].large;
        counter.textContent = (idx + 1) + ' / ' + photos.length;
        thumbs.innerHTML = '';
        photos.forEach(function(p, i) {
            var a = document.createElement('a');
            a.href = '#';
            a.className = 'lightbox-thumb' + (i === idx ? ' border border-2 border-white' : ' opacity-60');
            a.style.cssText = 'width:60px;height:45px;display:block;overflow:hidden;border-radius:4px;flex-shrink:0';
            a.innerHTML = '<img src="' + p.thumbSmall + '" style="width:100%;height:100%;object-fit:cover">';
            a.onclick = function(e) { e.preventDefault(); idx = i; showLightbox(); };
            thumbs.appendChild(a);
        });
    }
    function prevImg() { idx = (idx - 1 + photos.length) % photos.length; }
    function nextImg() { idx = (idx + 1) % photos.length; }
    if (detailImg) detailImg.onclick = function() { bootstrap.Modal.getOrCreateInstance(modal).show(); showLightbox(); };
    if (detailPrev) detailPrev.onclick = function() { prevImg(); updateDetail(); };
    if (detailNext) detailNext.onclick = function() { nextImg(); updateDetail(); };
    prev.onclick = function() { prevImg(); showLightbox(); };
    next.onclick = function() { nextImg(); showLightbox(); };
    modal.addEventListener('show.bs.modal', function() { showLightbox(); });
    modal.addEventListener('hidden.bs.modal', function() { updateDetail(); });
    document.addEventListener('keydown', function(e) {
        if (modal.classList.contains('show')) {
            if (e.key === 'ArrowLeft') { prevImg(); showLightbox(); }
            else if (e.key === 'ArrowRight') { nextImg(); showLightbox(); }
        }
    });
})();
</script>
<?php endif; ?>
