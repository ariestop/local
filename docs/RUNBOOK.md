# Release Runbook и Rollback

## Роли
- Release owner — координирует выпуск.
- DBA owner — бэкап/миграции/проверки БД.
- QA owner — smoke и go/no-go подтверждение.

## Pre-release checklist

1. Подтвержден backup БД и `public_html/images`.
2. Проверен `APP_ENV=production`.
3. На staging применены миграции и пройден smoke из `docs/TEST_PLAN.md`.
4. CI quality gates зелёные (`composer validate/audit + unit/integration`), без `PHPUnit notice/deprecation`.
5. Зафиксирован список миграций и версия релиза.

## Release steps

1. Перевести приложение в окно релиза (минимальный трафик).
2. Обновить код.
3. Запустить миграции (`php install.php`).
   - Если установка уже выполнялась ранее, baseline-дамп повторно не импортируется; применяются только pending-миграции.
4. Запустить cron-задачу автоархивации объявлений:
   - `php cron.php expire-posts`
   - Рекомендуется ежедневный запуск по расписанию (Task Scheduler/cron).
   - Если scheduler временно недоступен, выполните fallback через `/admin`:
     - укажите требуемое количество объявлений к обработке;
     - запуск идёт пакетами по 100 автоматически;
     - контролируйте прогресс на странице и дождитесь итогового статуса.
5. Выполнить smoke-check на production.
6. Подписать go/no-go.

## SEO smoke-check (после релиза)

1. Проверить `GET /robots.txt`:
   - код ответа 200;
   - присутствует `Sitemap: /sitemap.xml`.
2. Проверить `GET /sitemap.xml`:
   - код ответа 200;
   - валидный XML;
   - есть URL главной, активных карточек и whitelist-фильтров.
3. Проверить исходник `/`:
   - присутствуют `title`, `meta description`, `meta robots`, `canonical`.
4. Проверить исходник `/detail/{id}`:
   - присутствуют `title`, `meta description`, `canonical`;
   - есть JSON-LD блоки (`BreadcrumbList`, `Product`, `Offer`).
5. Проверить служебную страницу (например `/add` или `/favorites`):
   - `meta robots` должен быть `noindex,nofollow`.

## Stabilization window (60 минут)

- Мониторинг:
  - ошибки 5xx;
  - скорость ответов критичных endpoint;
  - успешность login/add/edit/archive/restore;
  - успешность cron-задачи `expire-posts` и почтовых уведомлений.
  - для fallback-запуска из `/admin`: сверить итоговые значения `processed/archived/notified` и `pending_before/pending_after`.
- Если появляются критичные ошибки, запускаем rollback.

## Rollback trigger

- Sev1/Sev2 инцидент > 10 минут без подтверждённого обхода.
- Невозможность завершить критичный пользовательский сценарий.

## Rollback steps

1. Откат кода на предыдущую стабильную версию.
2. Откат БД по подготовленному backup-плану.
3. Проверка smoke после отката.
4. Фиксация инцидента: причина, таймлайн, corrective actions.
