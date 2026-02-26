/**
 * Vue.js приложение — формы, кнопки
 */
(function() {
    'use strict';
    if (typeof Vue === 'undefined') return;
    const { createApp } = Vue;

    const app = createApp({
        mounted() {
            this.bindLoginModalHint();
            this.bindLogin();
            this.bindRegister();
            this.bindAddForm();
            this.bindEditForm();
            this.bindForgotForm();
            this.bindResetForm();
            this.bindFavoriteButtons();
            this.bindRemoveFavorite();
            this.bindDeleteButtons();
            this.bindRegEmailCheck();
            this.bindCityArea();
            this.bindPagination();
            this.bindDetailGallery();
        },
        methods: {
            bindLoginModalHint() {
                document.getElementById('loginModal')?.addEventListener('show.bs.modal', (ev) => {
                    if (ev.relatedTarget?.dataset?.bsWhatever === 'add') {
                        document.querySelector('#loginModal .modal-body small.text-muted')?.remove();
                        const hint = document.createElement('small');
                        hint.className = 'text-muted d-block mb-2';
                        hint.textContent = 'Войдите, чтобы добавить объявление. Логин: seobot@qip.ru, пароль: 12345';
                        document.getElementById('loginForm')?.prepend(hint);
                    }
                });
            },
            apiPost(url, formData) {
                return window.apiPost ? window.apiPost(url, formData) : Promise.resolve({});
            },
            bindLogin() {
                const form = document.getElementById('loginForm');
                if (!form) return;
                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    window.hideError?.('loginError');
                    const btn = form.querySelector('button[type="submit"]');
                    window.setButtonLoading?.(btn, true);
                    try {
                        const data = await this.apiPost('/login', new FormData(form));
                        if (data.success) {
                            bootstrap.Modal.getInstance(document.getElementById('loginModal'))?.hide();
                            window.showToast?.('Вход выполнен');
                            location.reload();
                        } else {
                            window.showError?.('loginError', data.error || 'Ошибка');
                        }
                    } catch (err) {
                        window.showError?.('loginError', 'Ошибка сети');
                    } finally {
                        window.setButtonLoading?.(btn, false);
                    }
                });
            },
            bindRegister() {
                const form = document.getElementById('registerForm');
                if (!form) return;
                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    window.hideError?.('registerError');
                    const btn = form.querySelector('button[type="submit"]');
                    window.setButtonLoading?.(btn, true);
                    try {
                        const data = await this.apiPost('/register', new FormData(form));
                        if (data.success) {
                            bootstrap.Modal.getInstance(document.getElementById('registerModal'))?.hide();
                            window.showToast?.(data.message || 'Регистрация успешна');
                            location.reload();
                        } else {
                            window.showError?.('registerError', data.error || 'Ошибка');
                        }
                    } catch (err) {
                        window.showError?.('registerError', 'Ошибка сети');
                    } finally {
                        window.setButtonLoading?.(btn, false);
                    }
                });
            },
            bindRegEmailCheck() {
                const input = document.getElementById('regEmail');
                const status = document.getElementById('emailStatus');
                if (!input || !status) return;
                let timeout;
                input.addEventListener('input', () => {
                    clearTimeout(timeout);
                    const email = input.value.trim();
                    if (!email || email.length < 5) {
                        status.textContent = '';
                        status.className = 'form-text';
                        return;
                    }
                    timeout = setTimeout(async () => {
                        try {
                            const r = await fetch('/api/check-email?email=' + encodeURIComponent(email));
                            const data = await r.json();
                            if (data.exists) {
                                status.textContent = 'Этот email уже зарегистрирован';
                                status.className = 'form-text text-danger';
                            } else {
                                status.textContent = 'Email свободен';
                                status.className = 'form-text text-success';
                            }
                        } catch {
                            status.textContent = '';
                        }
                    }, 400);
                });
            },
            bindAddForm() {
                const form = document.getElementById('addForm');
                if (!form) return;
                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    window.hideError?.('addError');
                    if (window.validateCostInForm && !window.validateCostInForm(form, 'addError')) return;
                    if (window.syncAddFormFiles) window.syncAddFormFiles(form);
                    const list = form.querySelector('#addPhotoPreviewList');
                    if (list) {
                        let hi = form.querySelector('input[name="photo_order"]');
                        if (!hi && list.querySelectorAll('.photo-preview-item').length) {
                            hi = document.createElement('input');
                            hi.type = 'hidden';
                            hi.name = 'photo_order';
                            form.appendChild(hi);
                        }
                        if (hi) hi.value = list.querySelectorAll('.photo-preview-item').length ? '1' : '';
                    }
                    const btn = form.querySelector('button[type="submit"]');
                    window.setButtonLoading?.(btn, true);
                    try {
                        const data = await this.apiPost('/add', new FormData(form));
                        if (data.success) {
                            window.showToast?.('Объявление добавлено');
                            window.location.href = '/detail/' + data.id;
                        } else {
                            window.showError?.('addError', data.error || data.message || 'Ошибка сервера');
                        }
                    } catch (err) {
                        window.showError?.('addError', 'Ошибка сети: ' + (err?.message || ''));
                    } finally {
                        window.setButtonLoading?.(btn, false);
                    }
                });
            },
            bindEditForm() {
                const form = document.getElementById('editForm');
                if (!form) return;
                const postId = form.dataset.postId || form.closest('[data-post-id]')?.dataset?.postId || form.action?.match(/\/edit\/(\d+)/)?.[1];
                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    window.hideError?.('editError');
                    if (window.validateCostInForm && !window.validateCostInForm(form, 'editError')) return;
                    if (window.syncEditFormFiles) window.syncEditFormFiles(form);
                    const delInput = document.getElementById('deletePhotos');
                    const list = form.querySelector('.photo-preview-list-edit');
                    if (delInput) {
                        const toDel = [...form.querySelectorAll('.photo-delete:checked')].map(cb => cb.value);
                        delInput.value = toDel.join(',');
                    }
                    if (list) {
                        const order = [];
                        list.querySelectorAll('.photo-preview-item').forEach(el => {
                            if (el.dataset.filename && !(delInput?.value || '').split(',').includes(el.dataset.filename)) order.push(el.dataset.filename);
                            if (el.dataset.new && el._file) order.push('__new__');
                        });
                        let hi = form.querySelector('input[name="photo_order"]');
                        if (!hi) { hi = document.createElement('input'); hi.type = 'hidden'; hi.name = 'photo_order'; form.appendChild(hi); }
                        hi.value = order.join(',');
                    }
                    const btn = form.querySelector('button[type="submit"]');
                    window.setButtonLoading?.(btn, true);
                    try {
                        const data = await this.apiPost('/edit/' + postId, new FormData(form));
                        if (data.success) {
                            window.showToast?.('Изменения сохранены');
                            window.location.href = '/detail/' + data.id;
                        } else {
                            window.showError?.('editError', data.error || 'Ошибка');
                        }
                    } catch (err) {
                        window.showError?.('editError', 'Ошибка сети: ' + (err?.message || ''));
                    } finally {
                        window.setButtonLoading?.(btn, false);
                    }
                });
            },
            bindForgotForm() {
                const form = document.getElementById('forgotForm');
                if (!form) return;
                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const err = document.getElementById('forgotError');
                    if (err) { err.classList.add('d-none'); err.textContent = ''; }
                    const fd = new FormData(form);
                    fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
                    try {
                        const data = await this.apiPost(window.location.pathname, fd);
                        if (data.success) {
                            if (err) {
                                err.classList.remove('alert-danger');
                                err.classList.add('alert-success');
                                err.textContent = data.message || 'Проверьте почту';
                                err.classList.remove('d-none');
                            }
                            form.querySelector('button[type="submit"]').disabled = true;
                        } else {
                            if (err) {
                                err.textContent = data.error || 'Ошибка';
                                err.classList.remove('d-none');
                            }
                        }
                    } catch (ex) {
                        if (err) {
                            err.textContent = 'Ошибка сети: ' + (ex?.message || '');
                            err.classList.remove('d-none');
                        }
                    }
                });
            },
            bindResetForm() {
                const form = document.getElementById('resetForm');
                if (!form) return;
                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const err = document.getElementById('resetError');
                    if (err) err.classList.add('d-none');
                    const p1 = form.querySelector('[name=password]')?.value;
                    const p2 = form.querySelector('[name=password2]')?.value;
                    if (p1 !== p2) {
                        if (err) { err.textContent = 'Пароли не совпадают'; err.classList.remove('d-none'); }
                        return;
                    }
                    const fd = new FormData(form);
                    try {
                        const data = await this.apiPost(window.location.pathname, fd);
                        if (data.success) {
                            window.showToast?.('Пароль изменён');
                            window.location.href = '/';
                        } else {
                            if (err) {
                                err.textContent = data.error || 'Ошибка';
                                err.classList.remove('d-none');
                            }
                        }
                    } catch (ex) {
                        if (err) {
                            err.textContent = 'Ошибка сети';
                            err.classList.remove('d-none');
                        }
                    }
                });
            },
            bindFavoriteButtons() {
                document.querySelectorAll('.btn-favorite, .btn-favorite-detail').forEach(btn => {
                    btn.addEventListener('click', async () => {
                        const id = btn.dataset.id;
                        const fd = new FormData();
                        fd.append('post_id', id);
                        fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
                        try {
                            const data = await this.apiPost('/api/favorite/toggle', fd);
                            if (data.success) {
                                btn.classList.toggle('btn-danger', data.added);
                                btn.classList.toggle('btn-outline-secondary', !data.added);
                                const icon = btn.querySelector('i');
                                if (icon) {
                                    icon.classList.toggle('bi-heart-fill', data.added);
                                    icon.classList.toggle('bi-heart', !data.added);
                                }
                                btn.title = data.added ? 'Убрать из избранного' : 'В избранное';
                                if (btn.classList.contains('btn-favorite-detail')) {
                                    btn.innerHTML = (data.added ? '<i class="bi bi-heart-fill"></i> В избранном' : '<i class="bi bi-heart"></i> В избранное');
                                }
                                window.showToast?.(data.added ? 'Добавлено в избранное' : 'Убрано из избранного');
                            }
                        } catch (err) {}
                    });
                });
            },
            bindRemoveFavorite() {
                document.querySelectorAll('.btn-remove-favorite').forEach(btn => {
                    btn.addEventListener('click', async () => {
                        const id = btn.dataset.id;
                        const fd = new FormData();
                        fd.append('post_id', id);
                        fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
                        try {
                            const data = await this.apiPost('/api/favorite/toggle', fd);
                            if (data.success && !data.added) {
                                btn.closest('tr')?.remove();
                                window.showToast?.('Убрано из избранного');
                            }
                        } catch (err) {}
                    });
                });
            },
            bindPagination() {
                const input = document.getElementById('pageInput');
                const btn = document.getElementById('pageGoBtn');
                if (!input || !btn) return;
                const go = () => {
                    const p = parseInt(input.value, 10);
                    const max = parseInt(input.max, 10);
                    if (p >= 1 && p <= max) {
                        const params = new URLSearchParams(location.search);
                        params.set('page', String(p));
                        location.href = '?' + params.toString();
                    }
                };
                btn.addEventListener('click', go);
                input.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); go(); } });
            },
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
                        a.onclick = (e) => { e.preventDefault(); idx = i; updateDetail(); };
                        detailThumbs.appendChild(a);
                    });
                };
                const updateDetail = () => {
                    if (detailImg) detailImg.src = photos[idx].thumb;
                    if (detailCounter) detailCounter.textContent = (idx + 1) + ' / ' + photos.length;
                    renderDetailThumbs();
                };
                renderDetailThumbs();
                const showLightbox = () => {
                    lightboxImg.src = photos[idx].large;
                    counter.textContent = (idx + 1) + ' / ' + photos.length;
                    thumbs.innerHTML = '';
                    photos.forEach((p, i) => {
                        const a = document.createElement('a');
                        a.href = '#';
                        a.className = 'lightbox-thumb' + (i === idx ? ' border border-2 border-white' : ' opacity-60');
                        a.style.cssText = 'width:60px;height:45px;display:block;overflow:hidden;border-radius:4px;flex-shrink:0';
                        a.innerHTML = '<img src="' + p.thumbSmall + '" style="width:100%;height:100%;object-fit:cover">';
                        a.onclick = (e) => { e.preventDefault(); idx = i; showLightbox(); };
                        thumbs.appendChild(a);
                    });
                };
                const prevImg = () => { idx = (idx - 1 + photos.length) % photos.length; };
                const nextImg = () => { idx = (idx + 1) % photos.length; };
                if (detailImg) detailImg.onclick = () => { bootstrap.Modal.getOrCreateInstance(modal).show(); showLightbox(); };
                if (detailPrev) detailPrev.onclick = () => { prevImg(); updateDetail(); };
                if (detailNext) detailNext.onclick = () => { nextImg(); updateDetail(); };
                if (prev) prev.onclick = () => { prevImg(); showLightbox(); };
                if (next) next.onclick = () => { nextImg(); showLightbox(); };
                modal?.addEventListener('show.bs.modal', () => showLightbox());
                modal?.addEventListener('hidden.bs.modal', () => updateDetail());
                document.addEventListener('keydown', (e) => {
                    if (modal?.classList.contains('show')) {
                        if (e.key === 'ArrowLeft') { prevImg(); showLightbox(); }
                        else if (e.key === 'ArrowRight') { nextImg(); showLightbox(); }
                    }
                });
            },
            bindCityArea() {
                const citySelect = document.getElementById('citySelect');
                const areaSelect = document.getElementById('areaSelect');
                const areasByCity = window.areasByCity || {};
                const cityId = parseInt(window.editCityId || '0', 10);
                const areaId = parseInt(window.editAreaId || '0', 10);
                if (!citySelect || !areaSelect) return;
                citySelect.addEventListener('change', function() {
                    const sid = this.value;
                    areaSelect.innerHTML = '<option value="">Выберите...</option>';
                    if (sid && areasByCity[sid]) {
                        areasByCity[sid].forEach(a => {
                            const opt = document.createElement('option');
                            opt.value = a.id;
                            opt.textContent = a.name;
                            if (cityId && areaId && sid == cityId && a.id == areaId) opt.selected = true;
                            areaSelect.appendChild(opt);
                        });
                    }
                });
                if (cityId && areasByCity[cityId]) {
                    citySelect.dispatchEvent(new Event('change'));
                }
            },
            bindDeleteButtons() {
                document.querySelectorAll('.btn-delete-post').forEach(btn => {
                    btn.addEventListener('click', async () => {
                        if (!confirm('Удалить это объявление? Фотографии и папка будут удалены.')) return;
                        const id = btn.dataset.id;
                        const fd = new FormData();
                        fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
                        try {
                            const data = await this.apiPost('/delete/' + id, fd);
                            if (data.success) location.reload();
                            else alert(data.error || 'Ошибка удаления');
                        } catch (err) {
                            alert('Ошибка сети');
                        }
                    });
                });
            }
        },
        template: '<div></div>'
    });

    const mountPoint = document.createElement('div');
    mountPoint.id = 'vue-app';
    document.body.appendChild(mountPoint);
    app.mount('#vue-app');
})();
