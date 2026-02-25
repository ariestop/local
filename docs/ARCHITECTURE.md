# Архитектура проекта

## Общая схема

```
[Браузер]
    ↓
[public/index.php] — точка входа
    ↓
[Router] — сопоставление маршрута
    ↓
[Controller] → [Model] → [PDO/MySQL]
    ↓              ↓
[View] ← данные
    ↓
[layout.php] — обёртка
```

## Компоненты

### Точка входа

`public/index.php`:
- Подключает autoload, helpers
- Запускает сессию
- Регистрирует маршруты
- Вызывает `Router::dispatch()`

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

- Формы логина/регистрации: `app.js` → fetch с FormData
- Добавление объявления: `addForm` → fetch /add
- Редактирование: `editForm` → fetch /edit/{id}
- Удаление: кнопка → confirm → fetch /delete/{id}
- Районы по городу: JS в add.php, edit.php — areasByCity из PHP
