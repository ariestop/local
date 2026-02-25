# История изменений

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
- **Скрипт миграций** — `php scripts/migrate.php` (post_photo, user.password)

### Конфигурация
- **.env** — поддержка .env и .env.local для настроек БД (DB_HOST, DB_NAME, DB_USER, DB_PASSWORD)
- **.env.example** — пример конфигурации

### UX
- **Индикатор загрузки** — спиннер на кнопках при отправке форм
- **Toast-уведомления** — «Регистрация успешна», «Вход выполнен», «Объявление добавлено», «Изменения сохранены»
