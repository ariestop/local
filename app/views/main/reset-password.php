<?php $token = $token ?? ''; $error = $error ?? null; ?>
<div class="mb-4">
    <a href="<?= route_url('/') ?>" class="text-muted small text-decoration-none"><i class="bi bi-arrow-left"></i> На главную</a>
</div>
<div class="card border-0 shadow-sm mx-auto" style="max-width:400px">
    <div class="card-body">
        <h2 class="h5 mb-4">Новый пароль</h2>
        <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <a href="<?= route_url('/forgot-password') ?>" class="btn btn-outline-primary">Запросить снова</a>
        <?php else: ?>
        <div id="resetError" class="alert alert-danger d-none"></div>
        <form id="resetForm"><?= csrf_field() ?>
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            <div class="mb-3">
                <label class="form-label">Новый пароль</label>
                <input type="password" name="password" class="form-control" required minlength="5">
            </div>
            <div class="mb-3">
                <label class="form-label">Повторите пароль</label>
                <input type="password" name="password2" class="form-control" required minlength="5">
            </div>
            <button type="submit" class="btn btn-primary w-100">Сохранить</button>
        </form>
        <?php endif; ?>
    </div>
</div>
