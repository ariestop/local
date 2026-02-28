# Тест-план (Smoke + Regression + Contract)

## Цель
Снизить риск регрессий перед релизом и сделать проверки воспроизводимыми.

## 1) Smoke (обязательно перед релизом)

Перед запуском smoke уточнить URL-режим окружения:
- rewrite режим: `APP_USE_FRONT_CONTROLLER_URLS=0` (маршруты вида `/detail/{id}`)
- front-controller режим: `APP_USE_FRONT_CONTROLLER_URLS=1` (маршруты вида `/index.php/detail/{id}`)

1. Auth:
   - login успешный/неуспешный;
   - register с валидной/невалидной капчей;
   - forgot/reset password.
2. Объявления:
   - add (включая фото);
   - edit (изменение полей, удаление/добавление фото);
   - delete.
3. Избранное:
   - toggle на списке и detail;
   - удаление из `/favorites`.
4. Базовая навигация:
   - главная, фильтры, пагинация, detail (в активном URL-режиме).
5. SEO-хлебные крошки:
   - на главной есть `<nav aria-label="breadcrumb">` с пунктом `Главная`;
   - на detail есть крошки `Главная -> текущая карточка`;
   - переход по ссылке `Главная` из detail работает в текущем URL-режиме.

## 2) Contract checks (API)

Для ключевых endpoint проверяем JSON-контракт:
- обязательное поле `success`;
- при ошибке: `error`, `code`;
- при rate-limit: `code=429` и `retry_after`.

Endpoint-минимум:
- `POST /login` (или `/index.php/login`)
- `POST /forgot-password` (или `/index.php/forgot-password`)
- `GET /api/check-email` (или `/index.php/api/check-email`)
- `POST /add` (или `/index.php/add`)
- `POST /edit/{id}` (или `/index.php/edit/{id}`)
- `POST /delete/{id}` (или `/index.php/delete/{id}`)
- `POST /api/favorite/toggle` (или `/index.php/api/favorite/toggle`)

## 3) Regression checks

- Галерея detail и переключение фото с клавиатуры.
- Валидация цены и лимитов фото.
- Черновики add/edit (автосохранение/восстановление/очистка после успешного submit).
- UI-ошибки при сетевых сбоях и `429`.
- JSON-LD на главной/detail содержит валидный `BreadcrumbList` и совпадает с видимыми крошками.

## 4) Exit criteria (Go/No-Go)

- 100% smoke кейсов успешно.
- Нет открытых дефектов P1/P2.
- Contract checks зелёные по всем критичным endpoint.
- Для известных minor-багов есть workaround и зафиксирован owner.

## 5) Автотесты и quality gates

- CI workflow: `.github/workflows/ci.yml` (job `Quality Gates`) запускается на `pull_request` и `push` в `main`.
- В CI обязательно выполняются:
  - `composer validate --strict`
  - `composer install --no-interaction --prefer-dist`
  - `composer audit`
  - `composer test:unit`
  - `composer test:integration`
- Quality gate усилен: `phpunit.xml` блокирует сборку при `PHPUnit notice/deprecation` (`failOnPhpunitNotice`, `failOnPhpunitDeprecation`).
- Если любой шаг падает, merge/release блокируется до устранения причины.
- Для нового багфикса добавляется regression-тест в соответствующий suite (`unit` или `integration`).
