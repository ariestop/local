# Схема базы данных

## Таблицы из дампа (infosee2_m2sar.sql)

### user

| Поле | Тип | Описание |
|------|-----|----------|
| id | int UNSIGNED | PK |
| email | varchar(45) | Уникальный |
| password | varchar(32) | md5 или password_hash (для новых — varchar(255)) |
| is_admin | tinyint(1) | Роль: 0 — обычный пользователь, 1 — администратор |
| name | varchar(45) | Имя |
| registration_date | timestamp | |
| last_visit | timestamp | |
| user_ip | int UNSIGNED | |

### post

| Поле | Тип | Описание |
|------|-----|----------|
| id | int UNSIGNED | PK, AUTO_INCREMENT |
| created_at | timestamp | DEFAULT CURRENT_TIMESTAMP |
| user_id | int UNSIGNED | FK → user.id |
| action_id | smallint | FK → action.id |
| object_id | smallint | FK → objectsale.id |
| city_id | smallint | FK → city.id |
| area_id | tinyint | FK → area.id |
| room | tinyint | Комнат |
| m2 | smallint | Площадь |
| street | varchar(45) | Улица |
| phone | varchar(45) | |
| cost | int UNSIGNED | Цена в рублях |
| title | varchar(45) | Заголовок |
| descr_post | text | Описание |
| client_ip | int UNSIGNED | |
| new_house | tinyint | 0/1 |
| status | enum('active','archived') | Статус объявления |
| published_at | datetime | Дата публикации |
| expires_at | datetime | Дата истечения (по умолчанию +30 дней при создании) |
| archived_at | datetime NULL | Когда перенесено в архив |
| archived_by_user_id | int UNSIGNED NULL | Кто архивировал |
| archive_reason | enum('manual_owner','manual_admin','expired') NULL | Причина архивации |
| expiry_notified_at | datetime NULL | Когда отправлено письмо о завершении срока |

### action

Справочник: Продам, Сдам, Куплю и т.д.

### objectsale

Справочник: Квартира, Дом, Комната и т.д.

### city

Справочник городов.

### area

Районы. Связан с city_id.

## Дополнительная таблица post_photo

Создаётся отдельно (если не в дампе):

```sql
CREATE TABLE post_photo (
  id int UNSIGNED NOT NULL AUTO_INCREMENT,
  post_id int UNSIGNED NOT NULL,
  filename varchar(100) NOT NULL,
  sort_order int NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY post_id (post_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

| Поле | Описание |
|------|----------|
| post_id | FK → post.id |
| filename | Имя файла (напр. 1_xxx.jpg) |
| sort_order | Порядок показа |

## Таблица user_favorite

```sql
CREATE TABLE user_favorite (
  id int UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id int UNSIGNED NOT NULL,
  post_id int UNSIGNED NOT NULL,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY user_post (user_id, post_id),
  KEY user_id (user_id),
  KEY post_id (post_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

| Поле | Описание |
|------|----------|
| user_id | FK → user.id (ON DELETE CASCADE) |
| post_id | FK → post.id (ON DELETE CASCADE) |
| created_at | Дата добавления в избранное |

## Таблица schema_migrations

Используется `install.php` для трекинга примененных миграций. Скрипт применяет только pending-миграции и не повторяет baseline-импорт после первичной установки.

| Поле | Тип | Описание |
|------|-----|----------|
| id | int UNSIGNED | PK |
| migration | varchar(190) | Имя файла миграции (уникально) |
| applied_at | datetime | Время применения |

## Метрики и мониторинг

### post.view_count

| Поле | Тип | Описание |
|------|-----|----------|
| view_count | int UNSIGNED | Агрегированное количество просмотров объявления |

### post_view_event

| Поле | Тип | Описание |
|------|-----|----------|
| id | int UNSIGNED | PK |
| post_id | int UNSIGNED | FK -> post.id (ON DELETE CASCADE) |
| user_id | int UNSIGNED NULL | FK -> user.id (ON DELETE SET NULL) |
| session_hash | char(64) | Хеш сессии |
| ip_hash | char(64) | Хеш IP |
| user_agent | varchar(255) | User-Agent клиента |
| viewed_at | datetime | Время просмотра |

### app_error_event

| Поле | Тип | Описание |
|------|-----|----------|
| id | int UNSIGNED | PK |
| level | varchar(20) | Уровень ошибки (`error`, `warning`) |
| message | varchar(500) | Текст ошибки |
| context_json | text NULL | Доп.контекст JSON |
| url | varchar(255) | URL страницы |
| user_id | int UNSIGNED NULL | FK -> user.id (ON DELETE SET NULL) |
| ip_hash | char(64) | Хеш IP |
| user_agent | varchar(255) | User-Agent клиента |
| created_at | datetime | Время события |

## Связи

```
user 1 — N post
post N — 1 action, objectsale, city, area
post 1 — N post_photo
user N — N post (через user_favorite)
post 1 — N post_view_event
user 1 — N app_error_event
user 1 — N post (archived_by_user_id)
```

## Инварианты целостности

- `post_photo.post_id` ссылается на `post.id`, удаление объявления удаляет все фото (`ON DELETE CASCADE`).
- `user_favorite.user_id` и `user_favorite.post_id` ссылаются на `user.id` и `post.id`, удаление пользователя/объявления удаляет соответствующие строки избранного.
- Перед добавлением FK в миграциях выполняется очистка orphan-строк.
- В публичной выдаче используются только объявления `status='active'`.
