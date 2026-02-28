# План улучшения логики проекта (Senior Roadmap)

## Цели
- Снизить риск регрессий и прод-инцидентов.
- Усилить безопасность и предсказуемость поведения в разных окружениях (`mod_rewrite` и front-controller).
- Сократить техдолг в маршрутизации, миграциях и слоях приложения.
- Ввести минимальные engineering-gates: тесты, CI, наблюдаемость.

## Текущее состояние (сильные стороны)
- Понятная MVC-структура и рабочий сервисный слой.
- Есть install/миграционный контур, `cron.php`, runbook.
- Реализованы базовые security-механизмы (CSRF, заголовки, контроль доступа).
- Поддерживаются оба URL-режима и в PHP, и в JS.

## Приоритеты улучшений

### P1 — надёжность и безопасность
1. ✅ Усилить маршрутизацию в `app/core/Router.php` (оценка: **M**, выполнено):
   - безопасная компиляция route-regex,
   - проверка существования action до вызова,
   - единая обработка 404/405.
2. ✅ Исправить неатомарность «БД + файловая система» в `app/services/PostService.php` и `app/services/ImageService.php` (оценка: **L**, выполнено):
   - staging для файлов,
   - перенос/удаление только после успешного commit,
   - компенсационные действия при ошибках.
3. Усилить upload-security в `app/services/ImageService.php` (оценка: **S**):
   - серверная проверка MIME (`finfo`/`exif_imagetype`),
   - строгая обработка `UPLOAD_ERR_*`,
   - не доверять клиентскому `$_FILES['type']`.
4. ✅ Укрепить auth-session flow в `app/controllers/UserController.php` (оценка: **S**, выполнено):
   - `session_regenerate_id(true)` после успешной аутентификации.

### P1 — архитектурная консистентность
5. ✅ Унифицировать URL-логику (оценка: **M**, выполнено):
   - PHP: `app/helpers.php`, `public_html/index.php`, `app/services/SeoService.php`, `app/controllers/SeoController.php`,
   - JS: `public_html/assets/api.js`,
   - цель: единый resolver режима URL и единые правила построения ссылок.
6. ✅ Убрать дубли миграционной логики между `install.php` и `app/services/MigrationService.php` (оценка: **L**, выполнено):
   - общий SQL parser/executor,
   - общий preflight и защита от параллельного запуска.

### P2 — frontend, тесты, эксплуатация
7. Снизить XSS/CSP-риски (оценка: **M**):
   - убрать опасные `innerHTML` в JS-модулях,
   - вынести inline JS/CSS из `app/views/*`.
8. Улучшить UX/A11y (оценка: **M**):
   - `aria-label`, корректные `alt`, keyboard-friendly взаимодействия.
9. Ввести базовый CI и quality gates (оценка: **S**):
   - `composer validate/install/audit`,
   - `phpunit` как обязательная проверка.
10. Расширить тесты критического контура (оценка: **M**):
   - auth, CRUD объявлений, URL mode, router-contract, install/migrations smoke.

## Этапный план внедрения

### Итерация 1 (1–2 недели): Risk Burn-Down
- Router hardening.
- Upload security + session hardening.
- Консистентность «БД + файлы» для create/update/delete.
- Smoke-тесты: login, add/edit/delete, detail, favorites.

### Конкретные задачи спринта 1
1. `Router.php`: добавить безопасную сборку regex для маршрутов и проверку `method_exists` перед dispatch.
2. `ImageService.php`: внедрить серверную MIME-проверку и строгую обработку `UPLOAD_ERR_*`.
3. `UserController.php`: добавить `session_regenerate_id(true)` после успешных auth-сценариев.
4. `PostService.php` + `ImageService.php`: выделить staging-поток для файлов и переносить изменения только после commit.
5. `tests/` + `phpunit.xml`: добавить smoke/integration тесты на login, add/edit/delete, detail, favorites.
6. Документация: обновить `docs/TEST_PLAN.md` чеклистом проверки после каждого пункта 1-5.

### Итерация 2 (2–3 недели): Platform Consistency
- Единый URL resolver для PHP/JS/SEO/sitemap.
- Объединение install/migrations core.
- Перевод rate limiting на shared storage (DB/Redis).
- Упрощение границ data-access слоёв.

### Итерация 3 (2–4 недели): Engineering Maturity
- CI pipeline + обязательные проверки merge.
- Рост unit/integration/contract покрытия.
- CSP-совместимый фронтенд (без inline-паттернов).
- Логи/метрики/алерты + регулярный rollback/restore drill.

## Критерии готовности
- Router не выполняет небезопасный dynamic dispatch.
- Нет рассинхронизации между БД и файлами после ошибок.
- Upload проходит серверную валидацию типа и ошибок.
- После auth-flow всегда регенерируется session id.
- URL-режим одинаково корректен в PHP и JS (контрактно проверено).
- CI обязателен для merge, критичные сценарии покрыты тестами.
