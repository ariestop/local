# История изменений

## Рефакторинг roadmap (2026-02)

- **PSR-4/Autoload** — `composer.json` дополнен явными маппингами для `controllers/core/models/services/Repositories/Log` (устойчиво для текущей структуры каталогов).
- **JSON-контракты API** — унифицированы backend-ошибки в `Controller::jsonError/jsonResult`, `requireAuth()` для AJAX всегда возвращает `{success:false,error,code}`.
- **API check-email** — endpoint возвращает расширенный контракт `{success:true, exists:boolean}` без изменения клиентского поведения.
- **Vue decomposition** — `public_html/assets/vue-app.js` разбит на модули:
  - `public_html/assets/vue/shared.js`
  - `public_html/assets/vue/forms.js`
  - `public_html/assets/vue/favorites.js`
  - `public_html/assets/vue/gallery.js`
  - `public_html/assets/vue-app.js` оставлен как bootstrap-компоновщик.
- **Legacy cleanup** — удалён неиспользуемый `public_html/assets/app.js`.

## PHP Debug Bar и PSR-4 (2025)

### PHP Debug Bar
- `composer require --dev php-debugbar/php-debugbar`
- Включение при `APP_ENV=dev` в .env; `config.php` — `app.env`, fallback через `getenv()`
- `app/debugbar.php` — инициализация, `layout.php` — `renderHead()` и `render()`
- Раздача ассетов через `index.php` (GET /debugbar/*), путь `vendor/.../resources`

### PSR-4
- Папки приведены в соответствие: `Controllers/`, `Core/`, `Models/`, `Services/`
- `Repositories/` и `Log/` уже соответствовали

## Фронтенд: Vue.js и централизация JS (2025)

- **Vue.js 3** — все формы и кнопки управляются через vue-app.js
- **api.js** — общие хелперы: apiPost, showToast, showError, hideError, setButtonLoading, validateCostInForm
- **ux.js** — skeleton, lazy load, превью фото, drag & drop; экспорт syncAddFormFiles, syncEditFormFiles
- **vue-app.js** — bindLogin, bindRegister, bindAddForm, bindEditForm, bindForgotForm, bindResetForm, bindFavoriteButtons, bindRemoveFavorite, bindDeleteButtons, bindRegEmailCheck, bindCityArea, bindPagination, bindDetailGallery
- **app.js** — удалён из layout (дублирующийся код перенесён в api.js + vue-app.js)
- Inline-скрипты удалены из index, detail, favorites, edit-advert, add, edit, forgot-password, reset-password

## Архитектура и качество кода

- **Composer** — composer.json с vlucas/phpdotenv, monolog; PSR-4 autoload
- **Repositories** — PostRepository, UserRepository, PostPhotoRepository, ReferenceRepository
- **Services** — PostService (CRUD объявлений), AuthService (логин, регистрация)
- **DI Container** — App\Core\Container для разрешения зависимостей
- **Logging** — App\Log\LoggerInterface, NullLogger, MonologAdapter
- **bootstrap.php** — инициализация, загрузка .env (Composer или встроенная)

## Улучшения (ранее)

### Безопасность
- **CSRF-защита** — токены для форм (login, register, add, edit, delete)
- **Хелпер h()** — htmlspecialchars с ENT_QUOTES для вывода

### Архитектура
- **Validation** — класс валидации форм (required, email, minLength и др.)
- **Конфиг маршрутов** — `app/config/routes.php`, `Router::fromConfig()`
- **404** — отдельная страница 404 при неизвестном маршруте

### Функциональность
- **Пагинация** на главной (20 объявлений на страницу)
- **Установка БД** — `php install.php` (импорт дампа + миграции из `migrations/`)

### Конфигурация
- **.env** — поддержка .env и .env.local для настроек БД (DB_HOST, DB_NAME, DB_USER, DB_PASSWORD)
- **.env.example** — пример конфигурации

### UX
- **Индикатор загрузки** — спиннер на кнопках при отправке форм
- **Toast-уведомления** — «Регистрация успешна», «Вход выполнен», «Объявление добавлено», «Изменения сохранены»
