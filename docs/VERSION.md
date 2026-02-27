## 2026-02-27

### Refactor по roadmap (`docs/PLAN.md`)

- Приведена автозагрузка к устойчивой схеме: в `composer.json` добавлены явные PSR-4 маппинги для `app/controllers`, `app/core`, `app/models`, `app/services`, `app/Repositories`, `app/Log`.
- Унифицирован backend JSON-контракт ошибок: `Controller::jsonError()`, `Controller::jsonResult()`, `Controller::requireAuth()` теперь возвращают `{success:false,error,code}`.
- Обновлён `ApiController::checkEmail()` на расширенный формат `{success:true, exists:boolean}`.
- Проведена декомпозиция `public/assets/vue-app.js`:
  - `public/assets/vue/shared.js`
  - `public/assets/vue/forms.js`
  - `public/assets/vue/favorites.js`
  - `public/assets/vue/gallery.js`
  - `public/assets/vue-app.js` оставлен как bootstrap-компоновщик.
- Удалён legacy-скрипт `public/assets/app.js`.

### Обновление документации

- Синхронизированы `README.md`, `AGENTS.md`, `docs/ARCHITECTURE.md`, `docs/CONVENTIONS.md`, `docs/CHANGELOG.md`, `docs/PLAN.md`.
- Уточнена матрица `APP_ENV` и описание frontend-модулей Vue.

### Этап 1 (`docs/UPGRADE.md`) — Stability & Data Integrity

- В `app/services/PostService.php` добавлены транзакционные границы для `create/update/delete` с rollback при ошибках.
- В `app/core/Container.php` в `PostService` внедрён `PDO` для управления транзакциями на уровне сервиса.
- В `install.php` добавлен трекинг выполненных миграций через `schema_migrations`; повторный запуск теперь пропускает уже применённые миграции.
- Добавлена миграция `migrations/004_schema_integrity_fk.sql`:
  - очистка orphan-записей,
  - FK `post_photo.post_id -> post.id (ON DELETE CASCADE)`,
  - FK `user_favorite.user_id -> user.id (ON DELETE CASCADE)`,
  - FK `user_favorite.post_id -> post.id (ON DELETE CASCADE)`.
- Обновлён `docs/DATABASE.md`: описаны `user_favorite`, `schema_migrations`, связи и инварианты целостности.

### Этап 2 (`docs/UPGRADE.md`) — Security & Auth Hardening

- Добавлен флаг `AUTH_ALLOW_LEGACY_PASSWORD` в `app/config/config.php` и `.env.example` для staged-миграции legacy-паролей.
- В `app/models/User.php` реализован on-login rehash: при успешном legacy-входе пароль автоматически обновляется до `password_hash`.
- В `app/Repositories/UserRepository.php` и `app/services/AuthService.php` добавлена явная передача режима legacy-auth.
- Добавлен `app/services/RateLimiter.php` (session-based throttling по ключу/IP/окну).
- В `app/controllers/UserController.php` добавлен rate limiting для:
  - `/login` (10 попыток / 10 минут),
  - `/forgot-password` (5 запросов / 15 минут).
- В `app/controllers/ApiController.php` добавлен rate limiting для `/api/check-email` (60 запросов / 60 секунд).
- Обновлены `README.md` и `docs/CONVENTIONS.md` с описанием legacy-auth флага и контракта ответа `retry_after` для `429`.

### Этап 3 (`docs/UPGRADE.md`) — Frontend Reliability & UX

- В `public/assets/api.js` переписан `apiPost()`:
  - корректный разбор `application/json`,
  - нормализация ошибок в формат `{success:false,error,code,retry_after?}`,
  - отказ от «тихого» regex-парсинга ответа.
- В `public/assets/vue/forms.js` и `public/assets/vue/favorites.js` убраны silent-fail сценарии, добавлены явные пользовательские уведомления (`toast`/ошибки формы).
- В `public/assets/vue-app.js` реализована page-aware инициализация модулей через `body[data-page]`.
- В `app/views/layout.php` добавлен `data-page` и `aria-live` для контейнера уведомлений.
- Документация синхронизирована в `docs/ARCHITECTURE.md` и `docs/CONVENTIONS.md`.

### Этап 4 (`docs/UPGRADE.md`) — Feature Expansion

- Реализованы черновики объявлений с автосохранением в `localStorage` для форм:
  - `add` (`public/assets/vue/forms.js`),
  - `edit` (`public/assets/vue/forms.js`, ключ по `postId`).
- Добавлено восстановление черновика при загрузке формы и автоочистка после успешного submit.
- Добавлен MVP **сравнения объявлений** (до 4 карточек) в `localStorage`:
  - кнопки сравнения в `app/views/main/index.php` и `app/views/main/detail.php`,
  - панель сравнения на главной (рендер в `public/assets/vue/shared.js`).
- Добавлен MVP **истории просмотров**:
  - запись текущего объявления на detail (`window.currentDetailPost`),
  - блок «Недавно просмотренные» на главной с очисткой истории.
- Полировка UX:
  - добавлены кнопки «Очистить черновик» в формах `add/edit`,
  - лимит истории просмотров вынесен в конфиг `HISTORY_LIMIT` (`app/config/config.php`, `.env.example`, meta `app-history-limit`).

### Мониторинг и аналитика

- Добавлена миграция `migrations/005_monitoring_analytics.sql`:
  - колонка `post.view_count`,
  - таблица `post_view_event` для событий просмотров,
  - таблица `app_error_event` для клиентских ошибок.
- Реализован учёт просмотров объявления:
  - `MainController::detail()` вызывает трекинг просмотра,
  - `PostService::registerView()` инкрементирует `view_count` и пишет событие (с anti-spam cooldown по сессии),
  - на странице detail отображается метрика «Просмотров».
- Добавлена простая аналитика на главной:
  - блок популярных объявлений (по `view_count`),
  - блок активности за 7 дней (просмотры + новые объявления).
- Реализован «аналог Sentry»:
  - endpoint `POST /api/client-error`,
  - сервис `AppErrorService` сохраняет ошибки в `app_error_event` и в лог,
  - frontend (`public/assets/api.js`) отправляет `window.onerror`, `unhandledrejection` и сетевые/неожиданные API ошибки.

### Админ-отчёт

- Добавлена страница `GET /admin-report` (контроллер `AdminController`, view `app/views/main/admin-report.php`).
- Доступ переведён на ролевую модель из БД:
  - колонка `user.is_admin` (`0` — пользователь, `1` — администратор),
  - пользователю `seolool@yandex.ru` назначается `is_admin = 1` миграцией.
- Удалена отдельная реализация логина/пароля для админ-отчёта.
- Отчёт показывает:
  - summary-метрики (посты/пользователи/просмотры/ошибки, включая 24ч),
  - популярные объявления,
  - активность по дням (7 дней),
  - последние клиентские ошибки.
- Для отчёта добавлен `AdminReportService`.

### Этап 5 (`docs/UPGRADE.md`) — Testing & Observability

- Добавлен документ `docs/TEST_PLAN.md`:
  - smoke-пакет для критичных сценариев,
  - API contract checks (`success/error/code/retry_after`),
  - regression-набор по фото/галерее/черновикам.

### Этап 6 (`docs/UPGRADE.md`) — Release & Rollback Operations

- Добавлен `docs/RUNBOOK.md` с пошаговыми процедурами:
  - pre-release checklist,
  - release steps,
  - stabilization window,
  - rollback trigger и rollback steps.
- В `README.md` обновлён раздел документации и добавлены ссылки на `docs/TEST_PLAN.md` и `docs/RUNBOOK.md`.
