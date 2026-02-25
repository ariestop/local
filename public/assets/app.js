(function() {
    let emailCheckTimeout;

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
            btn.innerHTML = '<span class="btn-spinner"></span>' + (btn.dataset.loadingText || 'Отправка...');
        } else {
            btn.classList.remove('btn-loading');
            btn.innerHTML = btn.dataset.originalHtml || btn.innerHTML;
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
    window.setButtonLoading = setButtonLoading;
    window.showToast = showToast;

    document.getElementById('registerForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        hideError('registerError');
        const btn = this.querySelector('button[type="submit"]');
        setButtonLoading(btn, true);
        const form = e.target;
        const fd = new FormData(form);
        try {
            const r = await fetch('/register', {
                method: 'POST',
                body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await r.json();
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('registerModal'))?.hide();
                showToast('Регистрация успешна');
                location.reload();
            } else {
                showError('registerError', data.error || 'Ошибка');
            }
        } catch (err) {
            showError('registerError', 'Ошибка сети');
        } finally {
            setButtonLoading(btn, false);
        }
    });

    document.getElementById('loginForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        hideError('loginError');
        const btn = e.target.querySelector('button[type="submit"]');
        setButtonLoading(btn, true);
        const fd = new FormData(e.target);
        try {
            const r = await fetch('/login', {
                method: 'POST',
                body: fd,
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const text = await r.text();
            let data;
            try { data = JSON.parse(text); } catch { data = {}; }
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('loginModal'))?.hide();
                showToast('Вход выполнен');
                location.reload();
            } else {
                showError('loginError', data.error || 'Ошибка');
            }
        } catch (err) {
            showError('loginError', 'Ошибка сети');
        } finally {
            setButtonLoading(btn, false);
        }
    });

    document.getElementById('regEmail')?.addEventListener('input', function() {
        clearTimeout(emailCheckTimeout);
        const status = document.getElementById('emailStatus');
        const email = this.value.trim();
        if (!email || email.length < 5) {
            status.textContent = '';
            status.className = 'form-text';
            return;
        }
        emailCheckTimeout = setTimeout(async () => {
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

    document.getElementById('addForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        hideError('addError');
        const btn = e.target.querySelector('button[type="submit"]');
        setButtonLoading(btn, true);
        const fd = new FormData(e.target);
        try {
            const r = await fetch('/add', {
                method: 'POST',
                body: fd,
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const text = await r.text();
            let data = {};
            try {
                var m = text.match(/\{[\s\S]*\}/);
                if (m) data = JSON.parse(m[0]);
            } catch (e) {}
            if (data.success) {
                showToast('Объявление добавлено');
                window.location.href = '/detail/' + data.id;
            } else {
                var msg = data.error || data.message || (r.ok ? '' : 'Код ' + r.status);
                showError('addError', msg || text.trim().substring(0, 200) || 'Ошибка сервера');
            }
        } catch (err) {
            showError('addError', 'Ошибка сети: ' + (err.message || ''));
        } finally {
            setButtonLoading(btn, false);
        }
    });

    document.getElementById('loginModal')?.addEventListener('show.bs.modal', function(ev) {
        if (ev.relatedTarget?.dataset?.bsWhatever === 'add') {
            document.querySelector('#loginModal .modal-body small')?.remove();
            const hint = document.createElement('small');
            hint.className = 'text-muted d-block mb-2';
            hint.textContent = 'Войдите, чтобы добавить объявление. Логин: seobot@qip.ru, пароль: 12345';
            document.getElementById('loginForm')?.prepend(hint);
        }
    });
})();
