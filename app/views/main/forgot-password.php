<div class="mb-4">
    <a href="<?= route_url('/') ?>" class="text-muted small text-decoration-none"><i class="bi bi-arrow-left"></i> На главную</a>
</div>
<div class="card border-0 shadow-sm mx-auto" style="max-width:400px">
    <div class="card-body">
        <h2 class="h5 mb-4">Восстановление пароля</h2>
        <p class="text-muted small mb-3">Введите email — мы отправим ссылку для сброса пароля.</p>
        <div id="forgotError" class="alert alert-danger d-none"></div>
        <form id="forgotForm"><?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required placeholder="your@email.com">
            </div>
            <button type="submit" class="btn btn-primary w-100">Отправить</button>
        </form>
    </div>
</div>
