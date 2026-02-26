# Доска объявлений — продажа недвижимости

MVC-сайт на PHP 8.5+, Bootstrap 5, MySQL 8.0. Аналог m2saratov.ru. Саратов и Энгельс.

## Установка

1. **Composer** (опционально, для .env и логирования)
   ```bash
   composer install
   ```
   Без Composer проект работает с встроенной загрузкой .env и NullLogger.

2. **Развернуть базу данных**
   - Выполните `php install.php` — создаёт БД, импортирует `public/infosee2_m2sar.sql` и запускает миграции из `migrations/`

2. **Настройка**
   - Скопируйте `.env.example` в `.env` и укажите DB_HOST, DB_NAME, DB_USER, DB_PASSWORD
   - Либо измените `app/config/config.php` напрямую

3. **Веб-сервер**
   - Корень сайта — папка `public/`
   - Точка входа: `public/index.php`

4. **PHP Debug Bar** (опционально, только для разработки)
   - Установка: `composer update` (подключит php-debugbar как dev-зависимость)
   - В `.env` укажите `APP_ENV=dev` — панель появится внизу страницы
   - В продакшене обязательно используйте `APP_ENV=production` (или не указывайте) — Debug Bar не загружается

## Тестовый вход

- Логин: seobot@qip.ru
- Пароль: 12345

## Функциональность

- **Главная** — таблица объявлений, фильтры, пагинация
- **Регистрация и вход** — модальные окна, капча, проверка email в реальном времени
- **Добавление объявления** — только для авторизованных, до 5 фото
- **Страница объявления** — галерея, лайтбокс, кнопка «В избранное»
- **Личный кабинет** (`/edit-advert`) — список объявлений пользователя
- **Избранное** (`/favorites`) — сохранённые объявления
- **Редактирование** — поля и фотографии
- **Удаление** — объявление, фото в БД и папка на диске

## Структура проекта

```
app/
  config/         — конфигурация, routes.php
  core/           — Router, Database, Controller, Container
  models/         — Post, User, Reference, PostPhoto
  Repositories/   — PostRepository, UserRepository, PostPhotoRepository, ReferenceRepository
  Services/       — PostService, AuthService, ImageService
  Log/            — LoggerInterface, NullLogger, MonologAdapter
  controllers/    — MainController, UserController, ApiController
  views/          — layout + main/*.php
  bootstrap.php   — загрузка, контейнер
public/
  index.php       — точка входа
  assets/         — api.js, ux.js, vue-app.js
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
