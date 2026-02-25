# Схема базы данных

## Таблицы из дампа (infosee2_m2sar.sql)

### user

| Поле | Тип | Описание |
|------|-----|----------|
| id | int UNSIGNED | PK |
| email | varchar(45) | Уникальный |
| password | varchar(32) | md5 или password_hash (для новых — varchar(255)) |
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

## Связи

```
user 1 — N post
post N — 1 action, objectsale, city, area
post 1 — N post_photo
```
