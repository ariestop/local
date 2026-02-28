# SEO-план под Яндекс (чеклист внедрения)

Документ фиксирует практичный files-first план внедрения SEO в текущем проекте.
Принятая стратегия индексации каталога: `whitelist` (индексируем только разрешенные фильтры).

## Режимы URL

В проекте поддерживаются два режима маршрутизации:
- `APP_USE_FRONT_CONTROLLER_URLS=0` — clean URL (например, `/detail/{id}`), при рабочем `mod_rewrite`.
- `APP_USE_FRONT_CONTROLLER_URLS=1` — front-controller URL (например, `/index.php/detail/{id}`), если rewrite недоступен.

Ниже в таблицах используется канонический путь `/...`; в front-controller режиме ему соответствует вариант `/index.php/...`.

## 1) Цели и KPI

- Рост целевого органического трафика из Яндекса на страницы каталога и карточек.
- Снижение дублей URL и "мусорной" индексации фильтров/служебных страниц.
- Повышение CTR за счет качественных `title` и `meta description`.
- Ускорение переобхода новых и обновленных объявлений через `sitemap.xml`.

KPI для контроля:
- Количество валидных индексируемых страниц в Яндекс.Вебмастере.
- CTR и показы по страницам каталога и карточек (включая `/index.php/...` в front-controller режиме).
- Доля исключенных дублей и soft-404.
- Ошибки в "Индексирование -> Страницы в поиске" и "Диагностика сайта".

## 2) Точки интеграции в текущем коде

- Рендер `<head>` и глобальные мета-теги: `app/views/layout.php`.
- Подготовка page-data для главной и карточки: `app/Controllers/MainController.php`.
- Источник фильтров, пагинации и данных объявлений: `app/services/PostService.php`, `app/models/Post.php`.
- Роутинг для дополнительных SEO-эндпоинтов: `app/config/routes.php`.
- Базовый URL для абсолютных ссылок (canonical/sitemap): `app/config/config.php` (`app.url`).
- Контент, из которого формируются шаблоны title/description:
  - `app/views/main/index.php`
  - `app/views/main/detail.php`

## 3) SEO-матрица страниц (Яндекс)

| Тип страницы | Пример | Index | Canonical | Robots meta | JSON-LD |
|---|---|---|---|---|---|
| Главная без фильтров | `/` (или `/index.php`) | yes | self | `index,follow` | `WebSite`, `Organization`, `BreadcrumbList` |
| Главная + whitelist-фильтры | `/?city_id=1&action_id=2&room=3` | yes | self (нормализованный URL) | `index,follow` | `BreadcrumbList`, опционально `ItemList` |
| Главная + не-whitelist-фильтры | `/?price_min=...`, `sort=...`, `post_id=...` | no | на ближайший whitelist/self | `noindex,follow` | без обязательного JSON-LD |
| Пагинация whitelist-выдачи | `/?city_id=1&page=2` | no | на страницу 1 того же набора фильтров | `noindex,follow` | без обязательного JSON-LD |
| Карточка объявления | `/detail/123` (или `/index.php/detail/123`) | yes (если active) | self | `index,follow` | `BreadcrumbList`, `Product`, `Offer` |
| Архив/недоступное объявление | 404 или скрыто для не-владельца | no | без canonical | `noindex,nofollow` | нет |
| Служебные/личные/админ | `/add`, `/edit/*`, `/favorites`, `/admin*`, `/api/*`, `/login` | no | не задавать | `noindex,nofollow` | нет |

## 4) Правила whitelist-индексации фильтров

Разрешенные query-параметры (индексируемые):
- `city_id`
- `action_id`
- `room`

Параметры, которые всегда выводят страницу в `noindex,follow`:
- `price_min`
- `price_max`
- `sort`
- `post_id`
- любые неизвестные GET-параметры

Дополнительные правила:
- `page>1` всегда `noindex,follow`, canonical на `page=1` с тем же whitelist-набором.
- Порядок whitelist-параметров в canonical фиксированный: `city_id`, `action_id`, `room`.
- Пустые значения параметров в canonical не включаются.
- Если запрос содержит whitelist + не-whitelist параметры: canonical строим только из whitelist, robots = `noindex,follow`.

## 5) Реализация по файлам (пошагово)

### Шаг 1. Базовый SEO-контейнер в layout

Файл: `app/views/layout.php`

Сделать:
- Поддержку передаваемого массива `$seo` из контроллеров.
- Вывод в `<head>`:
  - динамический `<title>`
  - `<meta name="description">`
  - `<meta name="robots">`
  - `<link rel="canonical">`
  - опциональные `<meta property="og:*">`
  - JSON-LD блоки (если переданы).
- Оставить дефолтные безопасные значения на случай отсутствия `$seo`:
  - title: название сайта
  - robots: `noindex,follow` для неизвестных/служебных страниц.

Критерий готовности:
- Любая страница может централизованно задавать SEO через `$seo` без дублирования `<head>`-логики.

### Шаг 2. Формирование SEO для каталога и карточки

Файл: `app/Controllers/MainController.php`

Сделать:
- В `index()` собрать структуру `$seo`:
  - рассчитать, относится ли запрос к whitelist-индексации;
  - выставить `robots` (`index,follow` или `noindex,follow`);
  - сформировать canonical (нормализованный URL по правилам выше);
  - сформировать title/description по выбранным фильтрам.
- В `detail()` собрать `$seo` для карточки:
  - title: объект + локация + цена;
  - description: краткое описание (обрезка до адекватной длины);
  - canonical: абсолютный URL карточки;
  - robots: `index,follow` только для доступной активной карточки.
- Передавать `$seo` в `render()` вместе с остальными данными.

Критерий готовности:
- На `/` и `/detail/{id}` формируются релевантные title/description/canonical/robots без ручных вставок в views.

### Шаг 3. Методы данных для sitemap и SEO-шаблонов

Файлы:
- `app/services/PostService.php`
- `app/models/Post.php`

Сделать:
- Добавить read-only методы для sitemap:
  - список активных объявлений (id + updated/published timestamp);
  - агрегаты/списки по whitelist-фильтрам (по желанию: только если есть выдача > 0).
- Обеспечить выборку только `status='active'`.
- Ограничить объем данных в запросах (батчи/лимиты), чтобы sitemap не создавал нагрузку.

Критерий готовности:
- Контроллер sitemap получает данные из service/repository слоя без SQL в контроллере.

### Шаг 4. Sitemap endpoint и маршрут

Файлы:
- `app/config/routes.php`
- (новый) `app/Controllers/SeoController.php` или отдельный метод в существующем контроллере

Сделать:
- Добавить GET-маршрут для sitemap (например, `/sitemap.xml`).
- Отдавать XML с `Content-Type: application/xml; charset=utf-8`.
- Включить в sitemap:
  - `/`
  - карточки `/detail/{id}` для активных объявлений
  - опционально whitelist-категории (только полезные страницы).
- Не включать:
  - пагинацию
  - служебные URL
  - API/админ/личный кабинет.

Критерий готовности:
- `https://site/sitemap.xml` отдается 200, валидный XML, содержит только SEO-целевые URL.

### Шаг 5. robots.txt

Файл: `public_html/robots.txt` (новый)

Сделать:
- Разрешить индексацию публичной части.
- Закрыть служебные разделы и API:
  - `/admin`
  - `/admin-migrations`
  - `/add`
  - `/edit-advert`
  - `/edit/`
  - `/favorites`
  - `/api/`
  - технические query-параметры (по необходимости шаблонами).
- Добавить строку `Sitemap: https://<host>/sitemap.xml`.
- Для front-controller режима допустимо добавить дополнительную строку `Sitemap: https://<host>/index.php/sitemap.xml`.

Рекомендуемый шаблон:
- `User-agent: *`
- `Disallow: /admin`
- `Disallow: /admin-migrations`
- `Disallow: /add`
- `Disallow: /edit-advert`
- `Disallow: /edit/`
- `Disallow: /favorites`
- `Disallow: /api/`
- `Sitemap: ...`

Критерий готовности:
- robots доступен по `/robots.txt`, и не блокирует целевые страницы `/` и `/detail/*`.

### Шаг 6. JSON-LD схемы

Файлы:
- `app/Controllers/MainController.php` (подготовка данных)
- `app/views/layout.php` (безопасный вывод `<script type=\"application/ld+json\">`)

Сделать:
- Главная:
  - `WebSite` (name, url)
  - `Organization` (name, url, logo при наличии)
  - `BreadcrumbList` (Главная)
- Карточка:
  - `BreadcrumbList` (Главная -> Каталог -> Объявление)
  - `Product` (название, описание, фото)
  - `Offer` (price, priceCurrency, availability, url)
- Все JSON-LD отдавать в UTF-8 и через `json_encode(..., JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)`.

Критерий готовности:
- Структурированные данные проходят проверку (Яндекс/валидаторы schema.org) без синтаксических ошибок.

### Шаг 6.1. Политика хлебных крошек (UI + JSON-LD)

Файлы:
- `app/services/SeoService.php`
- `app/controllers/MainController.php`
- `app/views/main/index.php`
- `app/views/main/detail.php`

Сделать:
- Использовать единый источник данных `breadcrumbs` (позиция, название, URL) для:
  - видимой навигации (`<nav aria-label="breadcrumb">`);
  - `BreadcrumbList` в JSON-LD.
- Главная: один элемент `Главная`.
- Карточка: минимум два элемента — `Главная` -> текущая карточка.
- URL крошек строить с учетом режима роутинга (`route_url` / front-controller).

Критерий готовности:
- Видимые крошки и JSON-LD не расходятся по названиям и ссылкам.

### Шаг 7. Контентные шаблоны title/description

Файлы:
- `app/views/main/index.php`
- `app/views/main/detail.php`

Сделать:
- Убедиться, что источники текста не пустые:
  - у карточки использовать `title`, `city_name`, `area_name`, `street`, `room`, `m2`, `cost`.
- Для пустых/нестандартных полей задать fallback-фразы в контроллере (а не во view).
- Привести длину мета-описаний к рабочему диапазону (примерно 120-180 символов).

Критерий готовности:
- Нет пустых title/description на индексируемых страницах.

## 6) Очередность rollout (безопасное внедрение)

1. Внедрить `$seo`-контейнер в `layout.php`.
2. Подключить SEO-формирование в `MainController` (`index`, `detail`).
3. Реализовать whitelist-правила и canonical-нормализацию.
4. Добавить `sitemap.xml` endpoint и только после этого `robots.txt` со строкой `Sitemap`.
5. Добавить JSON-LD.
6. Провести валидацию, затем выкатывать в production.

## 7) Чек проверки после релиза (Яндекс)

Техническая проверка:
- `/robots.txt` = 200, корректные Disallow, есть `Sitemap`.
- `/sitemap.xml` (или `/index.php/sitemap.xml` в front-controller режиме) = 200, валидный XML, без закрытых URL.
- У страниц каталога и карточек (в текущем URL-режиме) присутствуют корректные:
  - `title`
  - `meta description`
  - `canonical`
  - `robots`
- Для страниц с не-whitelist фильтрами и `page>1`:
  - `robots=noindex,follow`
  - canonical на базовую whitelist-страницу.

Проверка структурированных данных:
- JSON-LD не дублируется и не ломает HTML.
- Для карточек присутствуют `Product` + `Offer` + `BreadcrumbList`.
- Для главной и карточки `BreadcrumbList` совпадает с видимыми крошками в интерфейсе.

Проверка в Яндекс.Вебмастер:
- Отправить sitemap URL, который реально используется в окружении (`/sitemap.xml` или `/index.php/sitemap.xml`).
- Проверить "Индексирование -> Страницы в поиске":
  - рост целевых страниц;
  - снижение дублей/исключенных нецелевых URL.
- Проверить "Диагностика сайта" на критические SEO-ошибки.
- Проверить "Поисковые запросы" и CTR по основным посадочным.

## 8) Риски и ограничения

- Без отдельного генератора sitemap (крон/кэш) динамическая сборка может нагружать БД.
- Aggressive-индексация фильтров без whitelist быстро создаёт дубли и каннибализацию.
- Если `APP_URL` некорректен, canonical и sitemap будут с неверным хостом.
- Любые служебные страницы, случайно получившие `index,follow`, ухудшат качество индекса.
