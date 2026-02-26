/**
 * API и UI-хелперы
 */
(function() {
    'use strict';

    async function apiPost(url, formData) {
        const headers = { 'X-Requested-With': 'XMLHttpRequest' };
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
        if (csrf) headers['X-CSRF-Token'] = csrf;
        const r = await fetch(url, {
            method: 'POST',
            body: formData || new FormData(),
            credentials: 'same-origin',
            headers
        });
        const text = await r.text();
        try {
            const m = text.match(/\{[\s\S]*\}/);
            return m ? JSON.parse(m[0]) : {};
        } catch {
            return {};
        }
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
})();
