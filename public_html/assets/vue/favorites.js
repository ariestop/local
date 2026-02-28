/**
 * Vue methods responsible for favorites and deletion actions.
 */
(function () {
    'use strict';

    function renderDetailFavoriteButton(btn, added) {
        btn.textContent = '';
        const icon = document.createElement('i');
        icon.className = added ? 'bi bi-heart-fill' : 'bi bi-heart';
        btn.appendChild(icon);
        btn.appendChild(document.createTextNode(added ? ' В избранном' : ' В избранное'));
    }

    window.VueAppModules = window.VueAppModules || {};
    window.VueAppModules.favorites = {
        bindFavoriteButtons() {
            document.querySelectorAll('.btn-favorite, .btn-favorite-detail').forEach((btn) => {
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
                            btn.setAttribute('aria-label', data.added ? 'Убрать из избранного' : 'Добавить в избранное');
                            if (btn.classList.contains('btn-favorite-detail')) {
                                renderDetailFavoriteButton(btn, !!data.added);
                            }
                            window.showToast?.(data.added ? 'Добавлено в избранное' : 'Убрано из избранного');
                        } else if (data.code === 401) {
                            window.showToast?.('Требуется авторизация', 'warning');
                        } else {
                            window.showToast?.(data.error || 'Не удалось изменить избранное', 'warning');
                        }
                    } catch (err) {
                        window.showToast?.('Сетевая ошибка при работе с избранным', 'warning');
                    }
                });
            });
        },

        bindRemoveFavorite() {
            document.querySelectorAll('.btn-remove-favorite').forEach((btn) => {
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
                        } else if (!data.success) {
                            window.showToast?.(data.error || 'Не удалось убрать из избранного', 'warning');
                        }
                    } catch (err) {
                        window.showToast?.('Сетевая ошибка при работе с избранным', 'warning');
                    }
                });
            });
        },

        bindDeleteButtons() {
            document.querySelectorAll('.btn-delete-post').forEach((btn) => {
                btn.addEventListener('click', async () => {
                    if (!confirm('Перенести это объявление в архив?')) return;
                    const id = btn.dataset.id;
                    const fd = new FormData();
                    fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
                    try {
                        const data = await this.apiPost('/delete/' + id, fd);
                        if (data.success) {
                            window.showToast?.('Объявление перемещено в архив');
                            location.reload();
                        }
                        else window.showToast?.(data.error || 'Ошибка удаления', 'warning');
                    } catch (err) {
                        window.showToast?.('Ошибка сети при удалении', 'warning');
                    }
                });
            });
        },

        bindRestoreButtons() {
            document.querySelectorAll('.btn-restore-post').forEach((btn) => {
                btn.addEventListener('click', async () => {
                    if (!confirm('Восстановить это объявление и активировать на 30 дней?')) return;
                    const id = btn.dataset.id;
                    const fd = new FormData();
                    fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
                    try {
                        const data = await this.apiPost('/restore/' + id, fd);
                        if (data.success) {
                            window.showToast?.('Объявление восстановлено');
                            location.reload();
                        } else {
                            window.showToast?.(data.error || 'Ошибка восстановления', 'warning');
                        }
                    } catch (err) {
                        window.showToast?.('Ошибка сети при восстановлении', 'warning');
                    }
                });
            });
        },

        bindAdminPostActions() {
            const modalEl = document.getElementById('adminPostActionModal');
            const triggerButtons = document.querySelectorAll('.btn-admin-post-action');
            if (!modalEl || !triggerButtons.length || typeof bootstrap === 'undefined') return;

            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            const idLabel = document.getElementById('adminPostActionIdLabel');
            const archiveBtn = document.getElementById('adminArchiveBtn');
            const hardDeleteBtn = document.getElementById('adminHardDeleteBtn');
            let selectedId = 0;

            triggerButtons.forEach((btn) => {
                btn.addEventListener('click', () => {
                    selectedId = parseInt(btn.dataset.id || '0', 10) || 0;
                    if (idLabel) idLabel.textContent = '#' + selectedId;
                    modal.show();
                });
            });

            archiveBtn?.addEventListener('click', async () => {
                if (!selectedId) return;
                try {
                    const fd = new FormData();
                    fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
                    const data = await this.apiPost('/delete/' + selectedId, fd);
                    if (data.success) {
                        modal.hide();
                        window.showToast?.('Объявление перемещено в архив');
                        location.reload();
                    } else {
                        window.showToast?.(data.error || 'Ошибка архивации', 'warning');
                    }
                } catch (_err) {
                    window.showToast?.('Ошибка сети при архивации', 'warning');
                }
            });

            hardDeleteBtn?.addEventListener('click', async () => {
                if (!selectedId) return;
                if (!confirm('Удалить объявление с сервера безвозвратно?')) return;
                try {
                    const fd = new FormData();
                    fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
                    const data = await this.apiPost('/delete-hard/' + selectedId, fd);
                    if (data.success) {
                        modal.hide();
                        window.showToast?.('Объявление удалено');
                        location.reload();
                    } else {
                        window.showToast?.(data.error || 'Ошибка удаления', 'warning');
                    }
                } catch (_err) {
                    window.showToast?.('Ошибка сети при удалении', 'warning');
                }
            });
        },
    };
})();
