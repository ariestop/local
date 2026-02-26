# Соглашения по коду

## PHP

- `declare(strict_types=1);` в начале каждого файла
- PSR-4 автозагрузка: namespace `App\`, файлы в `app/`
- Контроллеры наследуют `App\Core\Controller`
- Модели получают PDO в конструкторе

## Именование

- Классы: PascalCase
- Методы: camelCase
- Файлы: соответствуют классам (Post → Post.php)
- Маршруты: kebab-case (/edit-advert, не editAdvert)
- Сессии: `$_SESSION['user']` — массив пользователя

## Безопасность

- Все SQL-запросы через prepared statements
- Вывод в HTML: `htmlspecialchars()` (или `<?= htmlspecialchars($x) ?>`)
- Проверка владельца при редактировании/удалении
- `requireAuth()` для защищённых страниц

## Формы и AJAX

- Формы: `method="POST"`, `enctype="multipart/form-data"` при загрузке файлов
- AJAX: заголовок `X-Requested-With: XMLHttpRequest`
- Ответы: JSON `{success: true|false, error?: string}`

## Работа с ценами

- В БД: целое число (рубли)
- В форме: строка с пробелами, например `1 250 000`
- При сохранении: `(int) preg_replace('/\D/', '', $_POST['cost'])`

## Фотографии

- Разрешения: JPEG, PNG, GIF, WebP
- Макс. размер файла: 5 МБ
- Макс. количество: 5 на объявление
- Путь: `public/images/{user_id}/{post_id}/{base}_{w}x{h}.{ext}`
- Размеры: 200×150, 400×300, 1200×675

## JavaScript и Vue

- Скрипты: api.js (утилиты), ux.js (UX-логика), vue-app.js (формы и кнопки)
- Vue 3: один корень на `#vue-app`, обработчики в `mounted()`
- Данные из PHP: `window.areasByCity`, `window.editCityId`, `window.editAreaId`, `window.detailPhotos`
- Формы: submit через `apiPost`, ошибки через `showError`/`hideError`

## Конфигурация

- `app/config/config.php` — возвращает массив
- Ключи: db (host, dbname, charset, user, password), app (name, url, timezone), session (name, lifetime)
- Не коммитить пароли в репозиторий; использовать .env при необходимости
