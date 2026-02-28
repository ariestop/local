# Руководство для AI-агентов

> Обновлено: PSR-4, PHP Debug Bar, APP_ENV, Vue.js frontend.

Документ для быстрого понимания проекта при работе с ним в качестве ИИ-ассистента.

## Обзор проекта

**Доска объявлений о продаже недвижимости** (аналог m2saratov.ru). MVC-сайт на PHP 8.5+, Bootstrap 5, MySQL 8.0.

## Технологический стек

| Компонент | Технология |
|-----------|------------|
| Backend | PHP 8.5+ (strict_types) |
| Frontend | Bootstrap 5, Bootstrap Icons, Vue.js 3 |
| БД | MySQL 8.0, PDO |
| Сессии | PHP Session, имя `m2saratov_sess` |
| Хранилище фото | `public/images/{user_id}/{post_id}/` |

## Структура каталогов (PSR-4)

```
app/
  config/         — config.php, routes.php
  core/           — Router, Database, Controller, Container
  models/         — Post, User, Reference, PostPhoto, Favorite
  Repositories/   — PostRepository, UserRepository, PostPhotoRepository, ReferenceRepository, FavoriteRepository
  services/       — PostService, AuthService, ImageService, MailService
  Log/            — LoggerInterface, NullLogger (MonologAdapter при composer)
  controllers/    — MainController, UserController, ApiController
  bootstrap.php   — инициализация, контейнер
  debugbar.php    — PHP Debug Bar (при APP_ENV=dev)
  views/          — layout.php + main/*.php
  helpers.php     — photo_thumb_url(), photo_large_url()
public/
  index.php              — точка входа, роутинг
  assets/api.js          — apiPost, showToast, showError, hideError, setButtonLoading, validateCostInForm
  assets/ux.js           — skeleton, lazy load, превью фото, syncAddFormFiles, syncEditFormFiles
  assets/vue/*.js        — модули Vue (shared/forms/favorites/gallery)
  assets/vue-app.js      — bootstrap Vue, объединяет модули
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
| GET | /favorites | MainController::favorites | Избранные объявления |
| GET | /edit/{id} | MainController::edit | Форма редактирования |
| POST | /edit/{id} | MainController::editSubmit | Сохранение (JSON) |
| POST | /delete/{id} | MainController::delete | Удаление (JSON) |
| POST | /login | UserController::login | Вход (JSON) |
| POST | /register | UserController::register | Регистрация (JSON) |
| GET | /logout | UserController::logout | Выход |
| GET | /forgot-password | UserController | Форма восстановления пароля |
| POST | /forgot-password | UserController | Отправка письма |
| GET | /reset-password | UserController | Форма нового пароля (по токену) |
| POST | /reset-password | UserController | Сохранение нового пароля |
| POST | /api/favorite/toggle | ApiController | Добавить/убрать из избранного |
| GET | /api/check-email | ApiController | Проверка email |
| GET | /api/captcha | ApiController | Капча |
| GET | /admin | AdminController::report | Главная страница админ-панели |
| GET | /admin-migrations | AdminController::migrations | Страница миграций в админке |
| POST | /admin-migrations/apply-next | AdminController::applyNextMigration | Применить следующую миграцию |
| POST | /admin-migrations/apply | AdminController::applyMigration | Применить выбранную миграцию |

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

## AJAX и frontend

- `apiPost(url, formData)` — fetch с X-Requested-With, X-CSRF-Token, парсинг JSON
- Vue монтируется на скрытый `#vue-app`, в `mounted()` привязывает обработчики к формам/кнопкам
- Данные из PHP: `window.areasByCity`, `window.editCityId`, `window.editAreaId` (add/edit), `window.detailPhotos` (detail)

## Аутентификация

- `$_SESSION['user']` — массив `{id, email, name}`.
- `Controller::requireAuth()` — редирект или JSON 401 при неавторизованном доступе.
- Только свои объявления редактируются/удаляются.

## Соглашения

- Все PHP-файлы: `declare(strict_types=1);`
- PSR-4 автозагрузка, namespace `App\`
- Composer опционален: без него работает fallback autoload/.env и NullLogger
- Цена в БД — целое число; в форме — строка с пробелами (number_format)
- `preg_replace('/\D/', '', $cost)` при сохранении цены

## Безопасность

- CSRF: meta `csrf-token`, формы — скрытое поле, AJAX — заголовок `X-CSRF-Token`
- `Controller::validateCsrf()` для POST
- Хелпер `h($s)` — htmlspecialchars ENT_QUOTES

## Конфиг и миграции

- `.env` / `.env.local`: APP_ENV (dev/production), DB_HOST, DB_NAME, DB_USER, DB_PASSWORD, APP_URL
- `APP_ENV=dev` — включает PHP Debug Bar (только для разработки!)
- `php install.php` — безопасный install/update скрипт:
  - 1-й запуск: создаёт БД, импортирует `public/infosee2_m2sar.sql`, затем применяет миграции;
  - последующие запуски: baseline-импорт пропускается, применяются только pending-миграции.
- Новые миграции: добавляйте файлы `NNN_название.sql` или `NNN_название.php` в `migrations/` — install.php применяет только те, которых нет в `schema_migrations`

## Тестовый вход

- Email: seobot@qip.ru
- Пароль: 12345
