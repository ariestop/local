/**
 * Vue methods responsible for auth and ad forms.
 */
(function () {
    'use strict';

    window.VueAppModules = window.VueAppModules || {};
    window.VueAppModules.forms = {
        getDraftKey(scope, suffix) {
            return 'draft:' + scope + ':' + (suffix || 'default');
        },

        initFormDraft(form, draftKey) {
            if (!form || !draftKey || !window.localStorage) return;
            let restored = false;
            const raw = localStorage.getItem(draftKey);
            if (raw) {
                try {
                    const data = JSON.parse(raw);
                    this.applyDraftToForm(form, data);
                    restored = true;
                } catch (_e) {
                    // ignore corrupted draft
                }
            }

            let t;
            const saveDraft = () => {
                clearTimeout(t);
                t = setTimeout(() => {
                    const payload = this.collectDraftFromForm(form);
                    localStorage.setItem(draftKey, JSON.stringify(payload));
                }, 250);
            };

            form.addEventListener('input', saveDraft);
            form.addEventListener('change', saveDraft);

            if (restored) {
                window.showToast?.('Черновик формы восстановлен', 'info');
            }
        },

        clearFormDraft(draftKey) {
            if (!draftKey || !window.localStorage) return;
            localStorage.removeItem(draftKey);
        },

        bindClearDraftButton(buttonId, draftKey, form) {
            const btn = document.getElementById(buttonId);
            if (!btn || !draftKey || !form) return;
            btn.addEventListener('click', () => {
                this.clearFormDraft(draftKey);
                form.reset();
                form.querySelectorAll('[name="city_id"]').forEach((el) => el.dispatchEvent(new Event('change')));
                window.showToast?.('Черновик очищен', 'info');
            });
        },

        collectDraftFromForm(form) {
            const data = {};
            form.querySelectorAll('input, select, textarea').forEach((el) => {
                const name = el.name;
                if (!name) return;
                if (name === 'csrf_token' || name === 'delete_photos' || name === 'photo_order') return;
                if (el.type === 'file' || el.type === 'password' || el.disabled) return;
                if ((el.type === 'checkbox' || el.type === 'radio') && !el.checked) return;
                data[name] = el.value;
            });
            return data;
        },

        applyDraftToForm(form, data) {
            if (!data || typeof data !== 'object') return;
            const cityValue = data.city_id;
            const areaValue = data.area_id;

            Object.entries(data).forEach(([name, value]) => {
                if (name === 'area_id') return;
                const el = form.querySelector('[name="' + name + '"]');
                if (!el) return;
                if (el.type === 'checkbox' || el.type === 'radio') {
                    el.checked = String(el.value) === String(value);
                    return;
                }
                el.value = value;
                if (name === 'city_id') {
                    el.dispatchEvent(new Event('change'));
                }
            });

            if (areaValue !== undefined) {
                setTimeout(() => {
                    const areaEl = form.querySelector('[name="area_id"]');
                    if (areaEl) areaEl.value = areaValue;
                }, cityValue !== undefined ? 50 : 0);
            }
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
                        const msg = data.code === 429
                            ? (data.error || 'Слишком много попыток. Повторите позже.')
                            : (data.error || 'Ошибка');
                        window.showError?.('loginError', msg);
                    }
                } catch (_err) {
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
                } catch (_err) {
                    window.showError?.('registerError', 'Ошибка сети');
                } finally {
                    window.setButtonLoading?.(btn, false);
                }
            });
        },

        bindAddForm() {
            const form = document.getElementById('addForm');
            if (!form) return;
            const draftKey = this.getDraftKey('add');
            this.initFormDraft(form, draftKey);
            this.bindClearDraftButton('clearAddDraftBtn', draftKey, form);
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
                        this.clearFormDraft(draftKey);
                        window.showToast?.('Объявление добавлено');
                        const detailPath = '/detail/' + data.id;
                        window.location.href = window.withBasePath ? window.withBasePath(detailPath) : detailPath;
                    } else {
                        window.showError?.('addError', data.error || data.message || 'Ошибка сервера');
                        window.showToast?.(data.error || 'Не удалось добавить объявление', 'warning');
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
            const draftKey = this.getDraftKey('edit', postId || 'unknown');
            this.initFormDraft(form, draftKey);
            this.bindClearDraftButton('clearEditDraftBtn', draftKey, form);
            const trackedFieldNames = ['action_id', 'object_id', 'city_id', 'area_id', 'street', 'room', 'm2', 'new_house', 'descr_post', 'phone', 'cost', 'title'];
            const getTrackedState = () => {
                const state = {};
                trackedFieldNames.forEach((name) => {
                    const el = form.elements.namedItem(name);
                    if (!el) return;
                    const value = (el.value ?? '').toString().trim();
                    state[name] = value;
                });
                return state;
            };
            let initialTrackedState = getTrackedState();
            setTimeout(() => {
                initialTrackedState = getTrackedState();
            }, 250);

            const deleteInfo = document.getElementById('editDeleteInfo');
            const updateDeleteInfo = () => {
                if (!deleteInfo) return;
                const checkedCount = form.querySelectorAll('.photo-delete:checked').length;
                deleteInfo.textContent = checkedCount > 0 ? ('Удаляются: ' + checkedCount + ' фото') : '';
            };
            form.addEventListener('change', (event) => {
                if (event.target && event.target.classList?.contains('photo-delete')) {
                    updateDeleteInfo();
                }
            }, true);
            updateDeleteInfo();

            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                window.hideError?.('editError');
                if (window.validateCostInForm && !window.validateCostInForm(form, 'editError')) return;
                if (window.syncEditFormFiles) window.syncEditFormFiles(form);
                const delInput = document.getElementById('deletePhotos');
                const list = form.querySelector('.photo-preview-list-edit');
                if (delInput) {
                    const toDel = [...form.querySelectorAll('.photo-delete:checked')].map((cb) => cb.value);
                    delInput.value = toDel.join(',');
                }
                const hasDeleteByCheckbox = (delInput?.value || '') !== '';
                const hasNewPhotos = (form.querySelector('input[name="photos[]"]')?.files?.length || 0) > 0;
                const currentTrackedState = getTrackedState();
                const hasFormFieldChanges = trackedFieldNames.some((name) => {
                    return String(currentTrackedState[name] ?? '') !== String(initialTrackedState[name] ?? '');
                });

                if (list) {
                    const order = [];
                    list.querySelectorAll('.photo-preview-item').forEach((el) => {
                        if (el.dataset.filename && !(delInput?.value || '').split(',').includes(el.dataset.filename)) order.push(el.dataset.filename);
                        if (el.dataset.new && el._file) order.push('__new__');
                    });
                    let hi = form.querySelector('input[name="photo_order"]');
                    if (!hi) {
                        hi = document.createElement('input');
                        hi.type = 'hidden';
                        hi.name = 'photo_order';
                        form.appendChild(hi);
                    }
                    hi.value = order.join(',');
                }
                const btn = form.querySelector('button[type="submit"]');
                window.setButtonLoading?.(btn, true);
                try {
                    const data = await this.apiPost('/edit/' + postId, new FormData(form));
                    if (data.success) {
                        this.clearFormDraft(draftKey);
                        window.showToast?.('Изменения сохранены');
                        if (hasDeleteByCheckbox && !hasFormFieldChanges && !hasNewPhotos) {
                            window.location.reload();
                        } else {
                            const myPostsPath = '/edit-advert';
                            window.location.href = window.withBasePath ? window.withBasePath(myPostsPath) : myPostsPath;
                        }
                    } else {
                        window.showError?.('editError', data.error || 'Ошибка');
                        window.showToast?.(data.error || 'Не удалось сохранить изменения', 'warning');
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
                if (err) {
                    err.classList.add('d-none');
                    err.textContent = '';
                }
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
                    } else if (err) {
                        err.textContent = data.error || 'Ошибка';
                        err.classList.remove('d-none');
                        window.showToast?.(data.error || 'Ошибка восстановления пароля', 'warning');
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
                    if (err) {
                        err.textContent = 'Пароли не совпадают';
                        err.classList.remove('d-none');
                    }
                    return;
                }
                const fd = new FormData(form);
                try {
                    const data = await this.apiPost(window.location.pathname, fd);
                    if (data.success) {
                        window.showToast?.('Пароль изменён');
                        const homePath = '/';
                        window.location.href = window.withBasePath ? window.withBasePath(homePath) : homePath;
                    } else if (err) {
                        err.textContent = data.error || 'Ошибка';
                        err.classList.remove('d-none');
                        window.showToast?.(data.error || 'Не удалось сменить пароль', 'warning');
                    }
                } catch (_ex) {
                    if (err) {
                        err.textContent = 'Ошибка сети';
                        err.classList.remove('d-none');
                    }
                }
            });
        },
    };
})();
