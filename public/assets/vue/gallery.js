/**
 * Vue methods responsible for detail gallery and lightbox.
 */
(function () {
    'use strict';

    window.VueAppModules = window.VueAppModules || {};
    window.VueAppModules.gallery = {
        bindDetailGallery() {
            const photos = window.detailPhotos;
            if (!photos || !photos.length) return;

            let idx = 0;
            const modal = document.getElementById('photoModal');
            const detailImg = document.getElementById('detailPhoto');
            const lightboxImg = document.getElementById('lightboxImg');
            const thumbs = document.getElementById('lightboxThumbs');
            const counter = document.getElementById('lightboxCounter');
            const detailCounter = document.getElementById('detailCounter');
            const detailPrev = document.getElementById('detailPrev');
            const detailNext = document.getElementById('detailNext');
            const detailThumbs = document.getElementById('detailThumbs');
            const prev = document.getElementById('lightboxPrev');
            const next = document.getElementById('lightboxNext');

            const renderDetailThumbs = () => {
                if (!detailThumbs) return;
                detailThumbs.innerHTML = '';
                photos.forEach((p, i) => {
                    const a = document.createElement('a');
                    a.href = '#';
                    a.className = 'detail-thumb' + (i === idx ? ' border border-2 border-primary' : ' opacity-75');
                    a.style.cssText = 'width:60px;height:45px;display:block;overflow:hidden;border-radius:4px;flex-shrink:0';
                    a.innerHTML = '<img src="' + p.thumbSmall + '" style="width:100%;height:100%;object-fit:cover">';
                    a.onclick = (e) => {
                        e.preventDefault();
                        idx = i;
                        updateDetail();
                    };
                    detailThumbs.appendChild(a);
                });
            };

            const updateDetail = () => {
                if (detailImg) detailImg.src = photos[idx].thumb;
                if (detailCounter) detailCounter.textContent = idx + 1 + ' / ' + photos.length;
                renderDetailThumbs();
            };

            const showLightbox = () => {
                if (lightboxImg) lightboxImg.src = photos[idx].large;
                if (counter) counter.textContent = idx + 1 + ' / ' + photos.length;
                if (!thumbs) return;
                thumbs.innerHTML = '';
                photos.forEach((p, i) => {
                    const a = document.createElement('a');
                    a.href = '#';
                    a.className = 'lightbox-thumb' + (i === idx ? ' border border-2 border-white' : ' opacity-60');
                    a.style.cssText = 'width:60px;height:45px;display:block;overflow:hidden;border-radius:4px;flex-shrink:0';
                    a.innerHTML = '<img src="' + p.thumbSmall + '" style="width:100%;height:100%;object-fit:cover">';
                    a.onclick = (e) => {
                        e.preventDefault();
                        idx = i;
                        showLightbox();
                    };
                    thumbs.appendChild(a);
                });
            };

            const prevImg = () => {
                idx = (idx - 1 + photos.length) % photos.length;
            };
            const nextImg = () => {
                idx = (idx + 1) % photos.length;
            };

            renderDetailThumbs();
            if (detailImg) detailImg.onclick = () => {
                bootstrap.Modal.getOrCreateInstance(modal).show();
                showLightbox();
            };
            if (detailPrev) detailPrev.onclick = () => {
                prevImg();
                updateDetail();
            };
            if (detailNext) detailNext.onclick = () => {
                nextImg();
                updateDetail();
            };
            if (prev) prev.onclick = () => {
                prevImg();
                showLightbox();
            };
            if (next) next.onclick = () => {
                nextImg();
                showLightbox();
            };

            modal?.addEventListener('show.bs.modal', () => showLightbox());
            modal?.addEventListener('hidden.bs.modal', () => updateDetail());
            document.addEventListener('keydown', (e) => {
                if (modal?.classList.contains('show')) {
                    if (e.key === 'ArrowLeft') {
                        prevImg();
                        showLightbox();
                    } else if (e.key === 'ArrowRight') {
                        nextImg();
                        showLightbox();
                    }
                }
            });
        },
    };
})();
