/**
 * UX: skeleton, lazy load, photo preview, drag & drop, copy phone
 */
(function() {
    'use strict';
    const MAX_PHOTOS = 10;
    const MAX_BYTES = 5 * 1024 * 1024;

    function showPhotoSizeError(msg) {
        var existing = document.getElementById('photo-size-overlay');
        if (existing) existing.remove();
        var box = document.createElement('div');
        box.id = 'photo-size-overlay';
        box.setAttribute('role', 'alert');
        box.setAttribute('aria-live', 'assertive');
        box.style.cssText = 'position:fixed;top:20px;left:50%;transform:translateX(-50%);z-index:99999;max-width:90%;padding:16px 24px;background:#dc3545;color:#fff;border-radius:8px;box-shadow:0 4px 20px rgba(0,0,0,0.3);font-size:14px;font-family:system-ui,sans-serif;';
        box.textContent = msg;
        document.body.appendChild(box);
        setTimeout(function() {
            if (box.parentNode) box.remove();
        }, 5000);
    }

    function filterOversizeFiles(input) {
        var maxBytes = parseInt(input && input.getAttribute && input.getAttribute('data-max-bytes') || String(MAX_BYTES), 10);
        if (!input || !input.files || !input.files.length) return;
        var rejected = [];
        var dt = new DataTransfer();
        for (var i = 0; i < input.files.length; i++) {
            if (input.files[i].size > maxBytes) {
                rejected.push(input.files[i].name);
            } else {
                dt.items.add(input.files[i]);
            }
        }
        if (rejected.length) {
            var maxMb = maxBytes / 1024 / 1024;
            var msg = dt.files.length
                ? 'Файл(ы) ' + rejected.slice(0, 3).join(', ') + (rejected.length > 3 ? ' и др.' : '') + ' не добавлены (превышают ' + maxMb + ' МБ). Остальные загружены.'
                : (rejected.length === 1
                    ? 'Файл превышает макс. размер (' + maxMb + ' МБ).'
                    : 'Все файлы превышают макс. размер (' + maxMb + ' МБ).');
            showPhotoSizeError(msg);
            input.files = dt.files;
        }
    }

    // Skeleton: fade-in for table rows
    function initTableSkeleton() {
        document.querySelectorAll('.posts-table tbody tr, .table-responsive .table tbody tr').forEach((row, i) => {
            if (row.querySelector('td[colspan]')) return;
            row.classList.add('skeleton-row');
        });
    }

    // Lazy load images
    function initLazyImages() {
        document.querySelectorAll('img[data-src]').forEach(img => {
            const src = img.dataset.src;
            if (src) {
                img.loading = 'lazy';
                img.addEventListener('load', () => img.classList.remove('img-loading'));
                const placeholder = img.dataset.placeholder === '1';
                if (placeholder) img.src = src;
            }
        });
        document.querySelectorAll('.gallery-img, #detailPhoto, .detail-thumb img').forEach(img => {
            img.loading = 'lazy';
        });
    }

    // Photo preview (add form)
    function createPreviewItem(file, onRemove) {
        const div = document.createElement('div');
        div.className = 'photo-preview-item';
        div.draggable = true;
        div._file = file;

        const img = document.createElement('img');
        img.src = URL.createObjectURL(file);
        img.alt = '';
        img.draggable = false;

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'photo-preview-remove';
        removeBtn.title = 'Удалить';
        removeBtn.setAttribute('aria-label', 'Удалить фото');
        const icon = document.createElement('i');
        icon.className = 'bi bi-x-lg';
        removeBtn.appendChild(icon);
        removeBtn.addEventListener('click', onRemove);

        div.appendChild(img);
        div.appendChild(removeBtn);
        return div;
    }

    function initAddPhotoPreview() {
        const form = document.getElementById('addForm');
        const input = form?.querySelector('input[name="photos[]"]');
        if (!form || !input) return;
        const wrap = document.createElement('div');
        wrap.className = 'photo-preview-area';
        const listEl = document.createElement('div');
        listEl.className = 'photo-preview-list';
        listEl.id = 'addPhotoPreviewList';
        const hint = document.createElement('p');
        hint.className = 'form-text';
        hint.textContent = 'До 10 фото. Перетащите для сортировки.';
        wrap.appendChild(listEl);
        wrap.appendChild(hint);
        input.parentNode.insertBefore(wrap, input);

        input.addEventListener('change', function() {
            const list = document.getElementById('addPhotoPreviewList');
            list.innerHTML = '';
            const files = Array.from(this.files || []).slice(0, MAX_PHOTOS);
            files.forEach((file, i) => {
                if (!file.type.match(/^image\/(jpeg|png)$/)) return;
                const div = createPreviewItem(file, () => {
                    div.remove();
                    syncAddFormFiles(form);
                });
                div.dataset.index = i;
                list.appendChild(div);
            });
            initSortable(form, list, false);
            syncAddFormFiles(form);
        });
    }

    // Photo preview (edit form) — enhance existing
    function initEditPhotoPreview() {
        const form = document.getElementById('editForm');
        const input = form?.querySelector('input[name="photos[]"]');
        const list = form?.querySelector('.photo-preview-list-edit');
        if (!form || !input) return;

        let container = form.querySelector('.photo-preview-area-edit');
        if (!container) {
            container = document.createElement('div');
            container.className = 'photo-preview-area-edit';
            const oldBlock = form.querySelector('.d-flex.flex-wrap.gap-2.mb-3');
            const newList = document.createElement('div');
            newList.className = 'photo-preview-list photo-preview-list-edit';
            const hint = document.createElement('p');
            hint.className = 'form-text';
            hint.textContent = 'Перетащите для сортировки. Первое — главное.';
            container.appendChild(newList);
            container.appendChild(hint);
            if (oldBlock) {
                oldBlock.querySelectorAll('.photo-item').forEach(el => {
                    const img = el.querySelector('img');
                    const cb = el.querySelector('.photo-delete');
                    if (img && cb) {
                        const div = document.createElement('div');
                        div.className = 'photo-preview-item photo-preview-existing';
                        div.dataset.filename = cb.value;
                        div.draggable = true;

                        const previewImg = document.createElement('img');
                        previewImg.src = img.src;
                        previewImg.alt = '';
                        previewImg.draggable = false;

                        const label = document.createElement('label');
                        label.className = 'photo-preview-delete';
                        const checkbox = document.createElement('input');
                        checkbox.type = 'checkbox';
                        checkbox.className = 'photo-delete';
                        checkbox.value = cb.value;
                        label.appendChild(checkbox);
                        label.appendChild(document.createTextNode(' Удалить'));

                        div.appendChild(previewImg);
                        div.appendChild(label);
                        newList.appendChild(div);
                    }
                });
                oldBlock.replaceWith(container);
            } else {
                input.parentNode.insertBefore(container, input);
            }
        }
        const listEl = form.querySelector('.photo-preview-list-edit');
        input.addEventListener('change', function() {
            const existing = listEl.querySelectorAll('.photo-preview-existing');
            const toDel = listEl.querySelectorAll('.photo-delete:checked').length;
            const newItems = listEl.querySelectorAll('[data-new]');
            const slots = MAX_PHOTOS - (existing.length - toDel) - newItems.length;
            const files = Array.from(this.files || []).slice(0, Math.max(0, slots));
            files.forEach((file, i) => {
                if (!file.type.match(/^image\/(jpeg|png)$/)) return;
                const div = createPreviewItem(file, () => {
                    div.remove();
                    syncEditFormFiles(form);
                });
                div.dataset.new = '1';
                div.dataset.index = i;
                listEl.appendChild(div);
            });
            initSortable(form, listEl, true);
            syncEditFormFiles(form);
        });
        initSortable(form, listEl, true);
    }

    function initEditSelectAllPhotos() {
        const form = document.getElementById('editForm');
        const selectAll = document.getElementById('selectAllEditPhotos');
        if (!form || !selectAll) return;

        const getBoxes = () => Array.from(form.querySelectorAll('.photo-delete'));
        const updateMaster = () => {
            const boxes = getBoxes();
            if (!boxes.length) {
                selectAll.checked = false;
                selectAll.indeterminate = false;
                return;
            }
            const checked = boxes.filter((cb) => cb.checked).length;
            selectAll.checked = checked === boxes.length;
            selectAll.indeterminate = checked > 0 && checked < boxes.length;
        };

        selectAll.addEventListener('change', () => {
            const checked = selectAll.checked;
            getBoxes().forEach((cb) => {
                cb.checked = checked;
            });
            selectAll.indeterminate = false;
        });

        form.addEventListener('change', (e) => {
            if (e.target && e.target.classList && e.target.classList.contains('photo-delete')) {
                updateMaster();
            }
        });

        updateMaster();
    }

    function getDragAfter(container, x, y) {
        const items = [...container.querySelectorAll('.photo-preview-item:not(.dragging)')];
        if (items.length === 0) return null;
        let closest = null;
        let closestDist = Infinity;
        for (const item of items) {
            const rect = item.getBoundingClientRect();
            const cx = rect.left + rect.width / 2;
            const cy = rect.top + rect.height / 2;
            const dist = Math.hypot(x - cx, y - cy);
            if (dist < closestDist) {
                closestDist = dist;
                closest = item;
            }
        }
        return closest;
    }

    function initSortable(form, list, isEdit) {
        if (list.dataset.sortableInit) return;
        list.dataset.sortableInit = '1';
        let dragged = null;
        list.querySelectorAll('.photo-preview-item').forEach(el => { el.draggable = true; });
        list.addEventListener('dragstart', e => {
            const item = e.target.closest('.photo-preview-item');
            if (item) {
                dragged = item;
                item.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', '');
            }
        });
        list.addEventListener('dragend', e => {
            const item = e.target.closest('.photo-preview-item');
            if (item) {
                item.classList.remove('dragging');
                dragged = null;
            }
        });
        list.addEventListener('dragover', e => {
            e.preventDefault();
            if (!dragged) return;
            const after = getDragAfter(list, e.clientX, e.clientY);
            if (after) list.insertBefore(dragged, after);
            else list.appendChild(dragged);
            if (isEdit) syncEditFormFiles(form);
            else syncAddFormFiles(form);
        });
    }

    function syncAddFormFiles(form) {
        const list = form?.querySelector('#addPhotoPreviewList');
        const input = form?.querySelector('input[name="photos[]"]');
        if (!list || !input) return;
        const dt = new DataTransfer();
        list.querySelectorAll('.photo-preview-item').forEach(el => {
            if (el._file) dt.items.add(el._file);
        });
        input.files = dt.files;
    }

    function syncEditFormFiles(form) {
        const list = form?.querySelector('.photo-preview-list-edit');
        const input = form?.querySelector('input[name="photos[]"]');
        if (!list || !input) return;
        const dt = new DataTransfer();
        list.querySelectorAll('.photo-preview-item[data-new]').forEach(el => {
            if (el._file) dt.items.add(el._file);
        });
        input.files = dt.files;
    }

    window.syncAddFormFiles = syncAddFormFiles;
    window.syncEditFormFiles = syncEditFormFiles;

    // Copy phone
    function initCopyPhone() {
        document.querySelectorAll('[data-copy-phone]').forEach(btn => {
            btn.addEventListener('click', async function() {
                const text = this.dataset.copyPhone || '';
                if (!text) return;
                try {
                    await navigator.clipboard.writeText(text);
                    if (window.showToast) window.showToast('Телефон скопирован');
                    else alert('Скопировано');
                } catch {
                    if (window.showToast) window.showToast('Не удалось скопировать', 'warning');
                }
            });
        });
    }

    function initCaptchaRefresh() {
        document.querySelectorAll('.js-captcha-refresh').forEach((btn) => {
            btn.addEventListener('click', () => {
                const targetId = btn.getAttribute('data-captcha-target') || '';
                const base = btn.getAttribute('data-captcha-base') || '';
                const img = targetId ? document.getElementById(targetId) : null;
                if (!img || !base) return;
                img.src = base + Date.now();
            });
        });
    }

    function initConfirmSubmitButtons() {
        document.querySelectorAll('.js-confirm-submit').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                const message = btn.getAttribute('data-confirm') || 'Подтвердите действие';
                if (!confirm(message)) {
                    e.preventDefault();
                }
            });
        });
    }

    document.addEventListener('change', function(e) {
        var input = e.target;
        if (input && input.type === 'file' && input.hasAttribute && input.hasAttribute('data-max-bytes')) {
            filterOversizeFiles(input);
        }
    }, true);

    document.addEventListener('DOMContentLoaded', function() {
        initTableSkeleton();
        initLazyImages();
        initAddPhotoPreview();
        initEditPhotoPreview();
        initEditSelectAllPhotos();
        initCopyPhone();
        initCaptchaRefresh();
        initConfirmSubmitButtons();
    });
})();
