# Доска объявлений — продажа недвижимости

MVC-сайт на PHP 8.5+, Bootstrap 5, MySQL 8.0. Аналог m2saratov.ru. Саратов и Энгельс.

## Установка

1. **Composer** (опционально, для .env и логирования)
   ```bash
   composer install
   ```
   Без Composer проект работает с встроенной загрузкой .env и NullLogger.

2. **Развернуть базу данных**
   - Выполните `php install.php`
   - На первом запуске скрипт создаёт БД, импортирует `public_html/infosee2_m2sar.sql` и применяет миграции.
   - На последующих запусках скрипт пропускает baseline-импорт и применяет только новые миграции из `migrations/`.
   - При необходимости имя/путь дампа можно изменить через `.env`: `DB_DUMP_PATH=public_html/your_dump.sql`

2. **Настройка**
   - Скопируйте `.env.example` в `.env` и укажите DB_HOST, DB_NAME, DB_USER, DB_PASSWORD
   - Либо измените `app/config/config.php` напрямую

3. **Веб-сервер**
   - Корень сайта — папка `public_html/`
   - Точка входа: `public_html/index.php`

4. **PHP Debug Bar** (опционально, только для разработки)
   - Установка: `composer update` (подключит php-debugbar как dev-зависимость)
   - В `.env` укажите `APP_ENV=dev` — панель появится внизу страницы
   - В продакшене обязательно используйте `APP_ENV=production` (или не указывайте) — Debug Bar не загружается

5. **Планировщик автоархивации**
   - Настройте ежедневный запуск:
   ```bash
   php cron.php expire-posts
   ```
   - Команда архивирует объявления с истёкшим сроком размещения и отправляет email-уведомления владельцам.
   - Если cron/Task Scheduler недоступен, админ может запустить тот же алгоритм вручную на странице `/admin`:
     - указывается количество объявлений к обработке;
     - выполнение идёт пакетами по 100;
     - показывается live-прогресс, есть кнопка остановки;
     - после завершения отображается итог (обработано/архивировано/отправлено писем).

6. **SEO (Yandex-friendly)**
   - Динамические `title`, `description`, `canonical`, `robots` формируются на страницах:
     - `/`
     - `/detail/{id}`
   - Для служебных страниц по умолчанию используется `noindex,nofollow`.
   - Карта сайта доступна по `GET /sitemap.xml`:
     - главная `/`
     - страницы активных объявлений `/detail/{id}`
     - whitelist-фильтры каталога (`city_id`, `action_id`, `room`).
   - `robots.txt` расположен в `public_html/robots.txt` и включает `Sitemap: /sitemap.xml`.

## Профили окружения (APP_ENV)

- `APP_ENV=dev` — включается Debug Bar и dev-ассеты `/debugbar/*`
- `APP_ENV=production` — Debug Bar и dev-обвязка отключены
- Значение по умолчанию при отсутствии переменной: `production`

## Тесты

- Запуск всех тестов:
  ```bash
  vendor/bin/phpunit
  ```
- Запуск только SEO-тестов:
  ```bash
  vendor/bin/phpunit tests/SeoServiceTest.php
  ```

## Auth migration flag

- `AUTH_ALLOW_LEGACY_PASSWORD=1` — временно разрешает вход legacy-пользователей (plaintext/md5) с автоматическим переводом на `password_hash` при успешном логине.
- `AUTH_ALLOW_LEGACY_PASSWORD=0` — отключает legacy-вход (использовать после завершения миграции паролей).

## Тестовый вход

- Логин: seobot@qip.ru
- Пароль: 12345

## Функциональность

- **Главная** — таблица объявлений, фильтры, пагинация
- **Регистрация и вход** — модальные окна, капча, проверка email в реальном времени
- **Добавление объявления** — только для авторизованных, до 10 фото
- **Страница объявления** — галерея, лайтбокс, кнопка «В избранное»
- **Личный кабинет** (`/edit-advert`) — список объявлений пользователя
- **Избранное** (`/favorites`) — сохранённые объявления
- **Редактирование** — поля и фотографии
- **Жизненный цикл объявлений**:
  - новые объявления активны 30 дней;
  - после истечения срока автоматически уходят в архив;
  - владелец может только архивировать/восстанавливать;
  - админ может архивировать или удалить объявление полностью с сервера.
- **Админ-панель** (`/admin`) — метрики просмотров, популярные объявления, активность и клиентские ошибки (доступ только для пользователя с `is_admin = 1`)
- **Fallback автоархивации в админке** (`/admin`) — ручной запуск алгоритма истечения объявлений с пакетной обработкой по 100, прогрессом и итоговой статистикой.

## Структура проекта

```
app/
  config/         — конфигурация, routes.php
  core/           — Router, Database, Controller, Container
  models/         — Post, User, Reference, PostPhoto, Favorite
  Repositories/   — PostRepository, UserRepository, PostPhotoRepository, ReferenceRepository, FavoriteRepository
  services/       — PostService, AuthService, ImageService, MailService
  Log/            — LoggerInterface, NullLogger, MonologAdapter
  controllers/    — MainController, UserController, ApiController
  views/          — layout + main/*.php
  bootstrap.php   — загрузка, контейнер
  debugbar.php    — PHP Debug Bar (только при APP_ENV=dev)
public_html/
  index.php       — точка входа
  assets/         — api.js, ux.js, vue-app.js + vue/* (forms/favorites/gallery/shared)
  images/         — загруженные фото
storage/logs/     — логи (при Monolog)
docs/             — документация
```

## Документация

| Файл | Назначение |
|------|------------|
| [AGENTS.md](AGENTS.md) | Для AI-агентов — быстрое понимание проекта |
| [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) | Архитектура и потоки данных |
| [docs/DATABASE.md](docs/DATABASE.md) | Схема БД |
| [docs/CONVENTIONS.md](docs/CONVENTIONS.md) | Соглашения по коду |
| [docs/TEST_PLAN.md](docs/TEST_PLAN.md) | Smoke/regression/contract проверки |
| [docs/RUNBOOK.md](docs/RUNBOOK.md) | Release и rollback процедуры |
