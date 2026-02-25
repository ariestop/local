# Руководство для AI-агентов

> Обновлено: CSRF, валидация, пагинация, UX, .env, миграции.

Документ для быстрого понимания проекта при работе с ним в качестве ИИ-ассистента.

## Обзор проекта

**Доска объявлений о продаже недвижимости** (аналог m2saratov.ru). MVC-сайт на PHP 8.5+, Bootstrap 5, MySQL 8.0.

## Технологический стек

| Компонент | Технология |
|-----------|------------|
| Backend | PHP 8.5+ (strict_types) |
| Frontend | Bootstrap 5, Bootstrap Icons, Vue.js (частично) |
| БД | MySQL 8.0, PDO |
| Сессии | PHP Session, имя `m2saratov_sess` |
| Хранилище фото | `public/images/{user_id}/{post_id}/` |

## Структура каталогов

```
app/
  config/config.php      — настройки БД и приложения
  core/                  — Router, Database, Controller
  models/                — Post, User, Reference, PostPhoto
  controllers/           — MainController, UserController, ApiController
  views/                 — layout.php + main/*.php
  services/ImageService  — загрузка и обработка фото
  helpers.php            — photo_thumb_url(), photo_large_url()
public/
  index.php              — точка входа, роутинг
  assets/app.js          — логика форм (логин, регистрация, add/edit)
  images/                — загруженные фото (в .gitignore)
```

## Маршруты

| Метод | Путь | Контроллер | Описание |
|-------|------|------------|----------|
| GET | / | MainController::index | Главная, список объявлений |
| GET | /detail/{id} | MainController::detail | Страница объявления |
| GET | /add | MainController::add | Форма добавления |
| POST | /add | MainController::addSubmit | Создание объявления (JSON) |
| GET | /edit-advert | MainController::myPosts | Список объявлений пользователя |
| GET | /edit/{id} | MainController::edit | Форма редактирования |
| POST | /edit/{id} | MainController::editSubmit | Сохранение (JSON) |
| POST | /delete/{id} | MainController::delete | Удаление (JSON) |
| POST | /login | UserController::login | Вход (JSON) |
| POST | /register | UserController::register | Регистрация (JSON) |
| GET | /logout | UserController::logout | Выход |
| GET | /api/check-email | ApiController | Проверка email |
| GET | /api/captcha | ApiController | Капча |

## Основные модели

- **Post** — объявления. Связан с action, objectsale, city, area, user.
- **PostPhoto** — фото объявлений. Таблица `post_photo`: post_id, filename, sort_order.
- **User** — пользователи. Пароль: md5 или password_hash.
- **Reference** — справочники: action, objectsale, city, area.

## Хелперы

- `photo_thumb_url($userId, $postId, $filename, $w, $h)` — URL миниатюры (например 200×150, 400×300).
- `photo_large_url($userId, $postId, $filename)` — URL полноразмерного (1200×675).

## Хранение фото

Путь: `public/images/{user_id}/{post_id}/`  
Файлы: `{base}_{w}x{h}.{ext}` — например `1_xxx_200x150.jpg`, `1_xxx_1200x675.jpg`.  
Разрешения: JPEG, PNG, GIF, WebP. До 5 фото на объявление, до 5 МБ на файл.

## AJAX

Запросы с заголовком `X-Requested-With: XMLHttpRequest` — JSON. Ответы: `{success: true/false, error?: string}`.

## Аутентификация

- `$_SESSION['user']` — массив `{id, email, name}`.
- `Controller::requireAuth()` — редирект или JSON 401 при неавторизованном доступе.
- Только свои объявления редактируются/удаляются.

## Соглашения

- Все PHP-файлы: `declare(strict_types=1);`
- PSR-4 автозагрузка, namespace `App\`
- Без Composer-зависимостей (чистый PHP)
- Цена в БД — целое число; в форме — строка с пробелами (number_format)
- `preg_replace('/\D/', '', $cost)` при сохранении цены

## Безопасность

- CSRF: meta `csrf-token`, формы — скрытое поле, AJAX — заголовок `X-CSRF-Token`
- `Controller::validateCsrf()` для POST
- Хелпер `h($s)` — htmlspecialchars ENT_QUOTES

## Конфиг и миграции

- `.env` / `.env.local`: DB_HOST, DB_NAME, DB_USER, DB_PASSWORD
- `php scripts/migrate.php` — post_photo, user.password

## Тестовый вход

- Email: seobot@qip.ru
- Пароль: 12345
