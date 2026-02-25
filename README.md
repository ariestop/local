# Доска объявлений — продажа недвижимости

MVC-сайт на PHP 8.5+, Bootstrap 5, MySQL 8.0. Аналог m2saratov.ru. Саратов и Энгельс.

## Установка

1. **Развернуть базу данных**
   - Импортируйте `public/infosee2_m2sar.sql` в MySQL 8.0
   - Выполните `php scripts/migrate.php` (создаёт post_photo, расширяет user.password)

2. **Настройка**
   - Скопируйте `.env.example` в `.env` и укажите DB_HOST, DB_NAME, DB_USER, DB_PASSWORD
   - Либо измените `app/config/config.php` напрямую

3. **Веб-сервер**
   - Корень сайта — папка `public/`
   - Точка входа: `public/index.php`

## Тестовый вход

- Логин: seobot@qip.ru
- Пароль: 12345

## Функциональность

- **Главная** — таблица объявлений
- **Регистрация и вход** — модальные окна, капча, проверка email в реальном времени
- **Добавление объявления** — только для авторизованных, до 5 фото
- **Страница объявления** — галерея, лайтбокс, миниатюры
- **Личный кабинет** (`/edit-advert`) — список объявлений пользователя
- **Редактирование** — поля и фотографии
- **Удаление** — объявление, фото в БД и папка на диске

## Структура проекта

```
app/
  config/       — конфигурация
  core/         — Router, Database, Controller
  models/       — Post, User, Reference, PostPhoto
  controllers/  — MainController, UserController, ApiController
  views/        — layout + main/*.php
  services/     — ImageService (фото)
  helpers.php   — photo_thumb_url, photo_large_url
public/         — точка входа, assets, images
docs/           — документация
```

## Документация

| Файл | Назначение |
|------|------------|
| [AGENTS.md](AGENTS.md) | Для AI-агентов — быстрое понимание проекта |
| [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) | Архитектура и потоки данных |
| [docs/DATABASE.md](docs/DATABASE.md) | Схема БД |
| [docs/CONVENTIONS.md](docs/CONVENTIONS.md) | Соглашения по коду |
