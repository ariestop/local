/**
 * API и UI-хелперы
 */
(function() {
    'use strict';
    let clientErrorLock = false;
    let lastClientErrorAt = 0;

    function appBasePath() {
        const raw = document.querySelector('meta[name="app-base"]')?.content || '';
        return (!raw || raw === '/') ? '' : raw.replace(/\/+$/, '');
    }

    function useFrontControllerUrls() {
        return (document.querySelector('meta[name="app-front-controller"]')?.content || '') === '1';
    }

    function appRequestPrefix() {
        const serverPrefix = (document.querySelector('meta[name="app-request-prefix"]')?.content || '').trim();
        if (serverPrefix) {
            return serverPrefix.replace(/\/+$/, '');
        }
        if (!useFrontControllerUrls()) {
            return appBasePath();
        }
        const entry = (document.querySelector('meta[name="app-entry"]')?.content || '').trim();
        if (entry) {
            return entry.replace(/\/+$/, '');
        }
        return appBasePath();
    }

    function withBasePath(url) {
        const target = String(url || '');
        if (!target) return appBasePath() || '/';
        if (/^(?:[a-z]+:)?\/\//i.test(target) || target.startsWith('data:') || target.startsWith('blob:')) {
            return target;
        }
        const base = appRequestPrefix();
        if (!base) return target;
        if (target === base || target.startsWith(base + '/')) return target;
        if (target.startsWith('/')) return base + target;
        return target;
    }

    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    async function reportClientError(payload) {
        const now = Date.now();
        if (clientErrorLock || now - lastClientErrorAt < 3000) return;
        lastClientErrorAt = now;
        clientErrorLock = true;
        try {
            await fetch(withBasePath('/api/client-error'), {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': csrfToken()
                },
                body: JSON.stringify(payload)
            });
        } catch (_e) {
            // ignore recursive errors from reporter
        } finally {
            clientErrorLock = false;
        }
    }

    function normalizeApiError(status, fallbackMessage, extra) {
        return Object.assign({
            success: false,
            error: fallbackMessage || 'Ошибка запроса',
            code: status || 500
        }, extra || {});
    }

    async function apiPost(url, formData) {
        const headers = { 'X-Requested-With': 'XMLHttpRequest' };
        const csrf = csrfToken();
        if (csrf) headers['X-CSRF-Token'] = csrf;

        let response;
        try {
            response = await fetch(withBasePath(url), {
                method: 'POST',
                body: formData || new FormData(),
                credentials: 'same-origin',
                headers
            });
        } catch (e) {
            reportClientError({
                level: 'error',
                message: 'Network error in apiPost',
                url: url,
                context: { error: String(e?.message || e) }
            });
            return normalizeApiError(0, 'Ошибка сети');
        }

        const contentType = (response.headers.get('content-type') || '').toLowerCase();
        let data = null;
        if (contentType.includes('application/json')) {
            try {
                data = await response.json();
            } catch {
                data = null;
            }
        } else {
            const text = await response.text();
            try {
                data = JSON.parse(text);
            } catch {
                data = null;
            }
            if (!data && !response.ok) {
                reportClientError({
                    level: 'error',
                    message: 'Non-JSON server response',
                    url: url,
                    context: { status: response.status, preview: (text || '').slice(0, 200) }
                });
                return normalizeApiError(
                    response.status,
                    'Сервер вернул неожиданный ответ',
                    { details: (text || '').trim().slice(0, 200) }
                );
            }
        }

        if (!data || typeof data !== 'object') {
            return response.ok
                ? { success: true }
                : normalizeApiError(response.status, 'Ошибка сервера');
        }

        if (!response.ok) {
            return normalizeApiError(
                response.status,
                data.error || data.message || 'Ошибка сервера',
                { retry_after: data.retry_after ?? undefined }
            );
        }

        return data;
    }

    function showToast(msg, type) {
        const container = document.getElementById('toastContainer');
        if (!container) return;
        const t = document.createElement('div');
        t.className = 'toast align-items-center text-bg-' + (type || 'success') + ' border-0 show';
        t.innerHTML = '<div class="d-flex"><div class="toast-body">' + msg + '</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>';
        container.appendChild(t);
        const bs = bootstrap.Toast.getOrCreateInstance(t, { delay: 4000 });
        t.addEventListener('hidden.bs.toast', () => t.remove());
        bs.show();
    }

    function showError(elId, msg) {
        const el = document.getElementById(elId);
        if (el) {
            el.textContent = msg;
            el.classList.remove('d-none');
        }
    }

    function hideError(elId) {
        const el = document.getElementById(elId);
        if (el) el.classList.add('d-none');
    }

    function setButtonLoading(btn, loading) {
        if (!btn) return;
        if (loading) {
            btn.classList.add('btn-loading');
            btn.dataset.originalHtml = btn.innerHTML;
            btn.innerHTML = '<span class="btn-spinner"></span> Отправка...';
        } else {
            btn.classList.remove('btn-loading');
            btn.innerHTML = btn.dataset.originalHtml || btn.innerHTML;
        }
    }

    function validateCostInForm(form, errorElId) {
        const maxPrice = parseInt(form?.dataset?.maxPrice || '999000000', 10);
        const costEl = form?.querySelector('input[name="cost"]');
        if (!costEl || maxPrice <= 0) return true;
        const cost = parseInt(String(costEl.value).replace(/\D/g, '') || '0', 10);
        if (cost <= maxPrice) return true;
        showError(errorElId, 'Цена не должна превышать ' + maxPrice.toLocaleString('ru-RU') + ' руб.');
        return false;
    }

    window.apiPost = apiPost;
    window.showToast = showToast;
    window.showError = showError;
    window.hideError = hideError;
    window.setButtonLoading = setButtonLoading;
    window.validateCostInForm = validateCostInForm;
    window.reportClientError = reportClientError;
    window.withBasePath = withBasePath;

    window.addEventListener('error', function(e) {
        reportClientError({
            level: 'error',
            message: e.message || 'Unhandled client error',
            url: window.location.pathname,
            context: {
                file: e.filename || '',
                line: e.lineno || 0,
                column: e.colno || 0
            }
        });
    });

    window.addEventListener('unhandledrejection', function(e) {
        const reason = e.reason;
        reportClientError({
            level: 'error',
            message: 'Unhandled promise rejection',
            url: window.location.pathname,
            context: {
                reason: typeof reason === 'string' ? reason : (reason?.message || JSON.stringify(reason || {}))
            }
        });
    });
})();
