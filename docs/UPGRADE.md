# План улучшения и развития проекта

## Цель
Сформировать практичный roadmap на 3-4 спринта для повышения качества кода, безопасности, стабильности релизов и добавления продуктовых фич без критичных регрессий.

## Принципы приоритизации
- **Impact:** влияние на стабильность, безопасность, конверсию и скорость разработки.
- **Risk:** вероятность регрессий и сложность отката.
- **Effort:** трудоемкость внедрения относительно результата.
- **Rule:** сначала исправляем фундамент (данные/безопасность/контракты), потом масштабируем фичи.

## Ключевые риски (на входе)
- Legacy-авторизация (`plaintext/md5/password_hash`) в `app/models/User.php`.
- Неполная транзакционность CRUD-операций в `app/services/PostService.php`.
- Разрозненная обработка API-ошибок на фронтенде (`public/assets/api.js`, `public/assets/vue/*.js`).
- Потенциальные расхождения схемы БД и миграций (`migrations/*`, `public/infosee2_m2sar.sql`).
- Недостаточно формализованный релизный/rollback процесс (`docs`).

## Roadmap этапов

### Этап 1 — Stability & Data Integrity
**Цель:** устранить источники неконсистентных данных и сделать БД предсказуемой.

**Задачи:**
1. Ввести транзакционные границы для `create/update/delete` в `app/services/PostService.php`.
2. Проверить и усилить ограничения целостности в БД (FK/индексы/каскады) через новые миграции в `migrations/`.
3. Ввести явный трекинг примененных миграций (`schema_migrations`) и обновить `install.php`.
4. Описать фактическую схему и инварианты в `docs/DATABASE.md`.

**Затрагиваемые модули/файлы:**
- `app/services/PostService.php`
- `app/models/Post.php`
- `app/models/PostPhoto.php`
- `app/models/Favorite.php`
- `install.php`
- `migrations/*`
- `docs/DATABASE.md`

**DoD:**
- Чистый разворот БД проходит без ручных правок.
- Нет «полусохраненных» объявлений/фото при ошибках.
- Проверки целостности не показывают orphan-записей.

---

### Этап 2 — Security & Auth Hardening
**Цель:** закрыть критичные auth-риски и повысить устойчивость к злоупотреблениям.

**Задачи:**
1. Реализовать staged-миграцию `md5 -> password_hash`:
   - расширение поля до безопасной длины;
   - on-login rehash;
   - флаг/дата отключения legacy fallback.
2. Добавить rate limiting для `/login`, `/forgot-password`, `/api/check-email`.
3. Довести до единообразия security-ответы API (`success/error/code`) на всех auth-endpoint.
4. Уточнить политику CSRF и контроль покрытия POST/AJAX маршрутов.

**Затрагиваемые модули/файлы:**
- `app/models/User.php`
- `app/services/AuthService.php`
- `app/controllers/UserController.php`
- `app/controllers/ApiController.php`
- `app/core/Controller.php`
- `migrations/*`
- `docs/CONVENTIONS.md`

**DoD:**
- Новые и обновленные пароли сохраняются только как `password_hash`.
- Legacy fallback отключаем по плану без массовых блокировок.
- Brute-force риски снижены (ограничения по IP/endpoint).

---

### Этап 3 — Frontend Reliability & UX
**Цель:** сделать фронтенд предсказуемым, прозрачным по ошибкам и удобным для расширения.

**Задачи:**
1. Нормализовать API-клиент:
   - отказ от «тихого» парсинга;
   - единый обработчик сетевых/серверных ошибок;
   - унифицированный контракт UI-ошибок.
2. Убрать silent-fail в `public/assets/vue/forms.js` и `public/assets/vue/favorites.js`.
3. Внедрить page-aware инициализацию модулей Vue (`public/assets/vue-app.js`).
4. Доработать a11y: `aria-live`, клавиатурная навигация, явные состояния ошибок.
5. Согласовать UX-паттерны (toasts/modals вместо `alert/confirm`).

**Затрагиваемые модули/файлы:**
- `public/assets/api.js`
- `public/assets/vue-app.js`
- `public/assets/vue/shared.js`
- `public/assets/vue/forms.js`
- `public/assets/vue/favorites.js`
- `public/assets/vue/gallery.js`
- `app/views/layout.php`
- `app/views/main/*.php`

**DoD:**
- Критичные пользовательские ошибки не теряются и видны в UI.
- Основные интерактивные сценарии доступны с клавиатуры.
- Frontend поведение одинаково для однотипных API-ошибок.

---

### Этап 4 — Feature Expansion
**Цель:** добавить фичи с максимальной бизнес-ценностью и низким риском.

**Фичи приоритета A:**
1. **Сохраненные поиски + уведомления** (email/web).
2. **Черновики объявления** (автосохранение add/edit).
3. **Сравнение объявлений** (до 3-4 карточек).

**Фичи приоритета B:**
4. **История просмотров + похожие объекты**.
5. **Улучшенный контактный сценарий** (click-to-call, события лида).

**Зависимости:**
- Этапы 1-3 завершены (данные, безопасность, стабильные API-контракты).

**Затрагиваемые модули/файлы (ожидаемо):**
- `app/controllers/MainController.php`
- `app/controllers/ApiController.php`
- `app/services/PostService.php`
- `app/services/MailService.php`
- `app/Repositories/*`
- `app/models/*`
- `public/assets/vue/*.js`
- `app/views/main/*.php`
- новые миграции в `migrations/`

**DoD:**
- Каждая фича имеет сценарий, метрику успеха и smoke-кейс.
- Нет блокирующих регрессий в существующем CRUD/auth flow.

---

### Этап 5 — Testing & Observability
**Цель:** обнаруживать регрессии до релиза и быстрее диагностировать инциденты.

**Задачи:**
1. Зафиксировать smoke-набор (auth, add/edit/delete, favorites, reset password).
2. Добавить API contract checks для ключевых endpoint.
3. Ввести стандартизованное логирование по слоям (`Controller/Service/Repository`).
4. Определить минимальные SLO/пороги для релиза (error-rate, latency, auth success).

**Затрагиваемые модули/файлы:**
- `app/core/Controller.php`
- `app/core/Router.php`
- `app/core/Database.php`
- `app/services/*`
- `app/controllers/*`
- `docs/PLAN.md`
- `docs/UPGRADE.md`

**DoD:**
- Smoke-регресс выполняется перед каждым релизом.
- Ошибки имеют достаточный контекст для диагностики.
- Есть понятные release gates по качеству.

---

### Этап 6 — Release & Rollback Operations
**Цель:** обеспечить предсказуемый выпуск и управляемый откат.

**Задачи:**
1. Подготовить pre-release checklist (конфиг, миграции, бэкап, проверка окружения).
2. Описать runbook выпуска и post-release stabilization window.
3. Формализовать rollback-триггеры и пошаговый rollback код+БД.
4. Зафиксировать артефакты релиза: changelog, migration list, QA report, go/no-go approvals.

**DoD:**
- Любой релиз выполняется по чеклисту, без импровизаций.
- Процедура rollback проверена и воспроизводима.
- Команда знает ответственность и последовательность действий.

## Тестовая стратегия (минимум)
- **Smoke:** login/register, add/edit/delete, favorites, forgot/reset password.
- **Security:** CSRF, авторизация доступа к чужим объявлениям, auth edge-cases.
- **Contract:** единообразие JSON-ответов `success/error/code`.
- **Regression:** фото (лимиты/сортировка/удаление), пагинация/фильтры, галерея.

## Release checklist (кратко)
1. Backups БД и файлов подтверждены.
2. Миграции проверены на staging.
3. Smoke + security + contract checks пройдены.
4. `APP_ENV=production`, dev-инструменты выключены.
5. Подписан go/no-go.

## Rollback checklist (кратко)
1. Зафиксировать trigger и время решения об откате.
2. Откатить код до последней стабильной версии.
3. Применить план rollback БД (или restore из backup).
4. Выполнить smoke после отката.
5. Зафиксировать инцидент и корректирующие действия.

## Приоритет на старт реализации
1. Этап 1 -> Этап 2
2. Этап 3
3. Этап 4 (итеративно по фичам A, затем B)
4. Этап 5 и Этап 6 как обязательные release gates
