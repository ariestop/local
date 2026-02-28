<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5">
        <?php if (!empty($success)): ?>
        <div class="text-success mb-3"><i class="bi bi-check-circle" style="font-size:3rem"></i></div>
        <h2 class="h5 mb-2">Email подтверждён</h2>
        <p class="text-muted">Вы успешно зарегистрированы. <a href="<?= route_url('/') ?>">Перейти к объявлениям</a></p>
        <?php else: ?>
        <div class="text-danger mb-3"><i class="bi bi-x-circle" style="font-size:3rem"></i></div>
        <h2 class="h5 mb-2">Ошибка</h2>
        <p class="text-muted"><?= htmlspecialchars($error ?? 'Ссылка недействительна') ?></p>
        <a href="<?= route_url('/') ?>" class="btn btn-primary">На главную</a>
        <?php endif; ?>
    </div>
</div>
