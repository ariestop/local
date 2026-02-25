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

    document.getElementById('registerForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        hideError('registerError');
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
                location.reload();
            } else {
                showError('registerError', data.error || 'Ошибка');
            }
        } catch (err) {
            showError('registerError', 'Ошибка сети');
        }
    });

    document.getElementById('loginForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        hideError('loginError');
        const fd = new FormData(e.target);
        try {
            const r = await fetch('/login', {
                method: 'POST',
                body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await r.json();
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('loginModal'))?.hide();
                location.reload();
            } else {
                showError('loginError', data.error || 'Ошибка');
            }
        } catch (err) {
            showError('loginError', 'Ошибка сети');
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
        const fd = new FormData(e.target);
        try {
            const r = await fetch('/add', {
                method: 'POST',
                body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await r.json();
            if (data.success) {
                window.location.href = '/detail/' + data.id;
            } else {
                showError('addError', data.error || 'Ошибка');
            }
        } catch (err) {
            showError('addError', 'Ошибка сети');
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
