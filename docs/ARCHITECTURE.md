# Архитектура проекта

## Общая схема

```
[Браузер]
    ↓
[public/index.php] → [bootstrap.php] — точка входа, контейнер
    ↓
[Router] + [Container] — сопоставление маршрута, разрешение зависимостей
    ↓
[Controller] → [Service] → [Repository] → [Model] → [PDO/MySQL]
    ↓              ↓
[View] ← данные
    ↓
[layout.php] — обёртка
```

## Слои

- **Controller** — HTTP-слой, валидация CSRF, вызов сервисов
- **Service** — бизнес-логика (PostService, AuthService)
- **Repository** — доступ к данным, фасад над Model
- **Model** — работа с БД (Post, User, PostPhoto, Reference, Favorite)

## Компоненты

### Точка входа

`public/index.php`:
- Загружает bootstrap (autoload, .env, контейнер)
- При `APP_ENV=dev` — инициализирует Debug Bar, раздаёт ассеты GET /debugbar/*
- Регистрирует маршруты и вызывает `Router::dispatch()`

### Роутинг

- Метод: GET / POST
- Путь с параметрами: `{id}` → `([^/]+)`
- Обработчик: `[Controller::class, 'actionName']`
- Контроллер и action вызываются динамически

### Контроллеры

Базовый `Controller`:
- `$this->db` — PDO
- `$this->config` — массив из config.php
- `getLoggedUser()` — `$_SESSION['user']`
- `requireAuth()` — проверка авторизации
- `render($view, $data)` — вывод layout + view
- `json($data, $status)` — JSON-ответ

### Модели

- Получают PDO через конструктор
- Используют prepared statements
- Возвращают ассоциативные массивы

### Представления

- Layout: `app/views/layout.php` — navbar, footer, модальные окна
- View задаётся через `$view` (например `main/index`)
- Файл: `app/views/main/index.php`
- В layout: `include $viewFile`

### Сервисы

`ImageService` — независимый класс:
- Конструктор: `basePath` (путь к `public/images`)
- `upload()` — загрузка, ресайз, создание миниатюр
- `deletePhoto()` — удаление файлов одного фото
- `deletePostFolder()` — удаление папки объявления

## Поток данных

### Добавление объявления

1. GET /add → MainController::add → render add.php
2. POST /add (FormData) → MainController::addSubmit
3. Post::create() → id
4. ImageService::upload() → массив filename/sort_order
5. PostPhoto::addBatch()
6. JSON {success, id} → redirect /detail/{id}

### Редактирование

1. GET /edit/{id} → проверка владельца → render edit.php
2. POST /edit/{id} (FormData + delete_photos)
3. Post::update()
4. Удаление выбранных фото (PostPhoto + ImageService)
5. Загрузка новых фото (с учётом лимита 5)
6. JSON {success, id} → redirect /detail/{id}

### Удаление

1. POST /delete/{id} (AJAX)
2. Проверка владельца
3. PostPhoto::deleteByPostId()
4. ImageService::deletePostFolder()
5. Post::delete()
6. JSON {success}

## Фронтенд

**Vue.js 3** + Bootstrap 5. Один корень Vue монтируется на `#vue-app`, в `mounted()` привязываются обработчики к формам и кнопкам.

### Подключаемые скрипты (layout.php)

| Файл | Назначение |
|------|------------|
| api.js | `apiPost()`, `showToast()`, `showError()`, `hideError()`, `setButtonLoading()`, `validateCostInForm()` |
| ux.js | Skeleton, lazy load, превью фото (add/edit), drag & drop, `syncAddFormFiles`, `syncEditFormFiles` |
| vue-app.js | Все формы (login, register, add, edit, forgot, reset), кнопки избранного/удаления, пагинация, галерея, city/area |

### Формы

- **loginForm, registerForm** — модалки в layout, submit через `apiPost`
- **addForm** — валидация цены, sync фото, `apiPost('/add')`
- **editForm** — sync фото, `delete_photos`, `photo_order`, `apiPost('/edit/{id}')`
- **forgotForm, resetForm** — восстановление пароля
- **Районы по городу** — `window.areasByCity`, `window.editCityId`, `window.editAreaId` (add.php, edit.php)

### Кнопки

- `.btn-favorite`, `.btn-favorite-detail` — toggle избранного (`/api/favorite/toggle`)
- `.btn-remove-favorite` — убрать из избранного (на странице /favorites)
- `.btn-delete-post` — удаление объявления (`/delete/{id}`)

### Дополнительно

- Пагинация — `pageInput`, `pageGoBtn`
- Галерея на detail — `window.detailPhotos`, лайтбокс
