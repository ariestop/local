/**
 * Shared Vue methods: base helpers and neutral UI bindings.
 */
(function () {
    'use strict';

    window.VueAppModules = window.VueAppModules || {};
    window.VueAppModules.shared = {
        getHistoryKey() {
            return 'history:recent-posts';
        },

        getHistoryLimit() {
            const meta = document.querySelector('meta[name="app-history-limit"]');
            const parsed = parseInt(meta?.content || '10', 10);
            return Number.isFinite(parsed) && parsed > 0 ? parsed : 10;
        },

        readStorageArray(key) {
            if (!window.localStorage) return [];
            const raw = localStorage.getItem(key);
            if (!raw) return [];
            try {
                const parsed = JSON.parse(raw);
                return Array.isArray(parsed) ? parsed : [];
            } catch {
                return [];
            }
        },

        writeStorageArray(key, value) {
            if (!window.localStorage) return;
            localStorage.setItem(key, JSON.stringify(value));
        },

        trackCurrentDetailInHistory() {
            const current = window.currentDetailPost;
            if (!current || !current.id) return;
            const key = this.getHistoryKey();
            const items = this.readStorageArray(key).filter((x) => x && x.id && x.id !== current.id);
            const next = [{ ...current, viewedAt: Date.now() }, ...items].slice(0, this.getHistoryLimit());
            this.writeStorageArray(key, next);
        },

        bindHistoryPanel() {
            const panel = document.getElementById('recentHistoryPanel');
            const list = document.getElementById('recentHistoryList');
            if (!panel || !list) return;

            const render = () => {
                const items = this.readStorageArray(this.getHistoryKey());
                list.innerHTML = '';
                if (!items.length) {
                    panel.classList.add('d-none');
                    return;
                }
                panel.classList.remove('d-none');
                items.slice(0, 5).forEach((item) => {
                    const a = document.createElement('a');
                    a.className = 'list-group-item list-group-item-action';
                    a.href = item.url || '#';
                    const ts = item.viewedAt ? new Date(item.viewedAt).toLocaleString('ru-RU') : '';
                    a.innerHTML = `
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <div class="fw-semibold">${item.title || 'Объявление #' + item.id}</div>
                                <div class="small text-muted">${item.address || ''}</div>
                            </div>
                            <div class="text-end">
                                <div class="fw-semibold">${Number(item.cost || 0).toLocaleString('ru-RU')} ₽</div>
                                <div class="small text-muted">${ts}</div>
                            </div>
                        </div>
                    `;
                    list.appendChild(a);
                });
            };

            document.getElementById('historyClearBtn')?.addEventListener('click', () => {
                this.writeStorageArray(this.getHistoryKey(), []);
                render();
                window.showToast?.('История очищена', 'info');
            });

            render();
        },

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
                        const endpoint = (window.withBasePath ? window.withBasePath('/api/check-email') : '/api/check-email')
                            + '?email=' + encodeURIComponent(email);
                        const r = await fetch(endpoint);
                        const data = await r.json();
                        const exists = !!data.exists;
                        if (exists) {
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

        bindCityArea() {
            const citySelect = document.getElementById('citySelect');
            const areaSelect = document.getElementById('areaSelect');
            const areasByCity = window.areasByCity || {};
            const cityId = parseInt(window.editCityId || '0', 10);
            const areaId = parseInt(window.editAreaId || '0', 10);
            if (!citySelect || !areaSelect) return;

            citySelect.addEventListener('change', function () {
                const sid = this.value;
                areaSelect.innerHTML = '<option value="">Выберите...</option>';
                if (sid && areasByCity[sid]) {
                    areasByCity[sid].forEach((a) => {
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
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    go();
                }
            });
        },
    };
})();
