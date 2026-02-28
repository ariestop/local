# Release Runbook и Rollback

## Роли
- Release owner — координирует выпуск.
- DBA owner — бэкап/миграции/проверки БД.
- QA owner — smoke и go/no-go подтверждение.

## Pre-release checklist

1. Подтвержден backup БД и `public/images`.
2. Проверен `APP_ENV=production`.
3. На staging применены миграции и пройден smoke из `docs/TEST_PLAN.md`.
4. Зафиксирован список миграций и версия релиза.

## Release steps

1. Перевести приложение в окно релиза (минимальный трафик).
2. Обновить код.
3. Запустить миграции (`php install.php`).
   - Если установка уже выполнялась ранее, baseline-дамп повторно не импортируется; применяются только pending-миграции.
4. Запустить cron-задачу автоархивации объявлений:
   - `php cron.php expire-posts`
   - Рекомендуется ежедневный запуск по расписанию (Task Scheduler/cron).
5. Выполнить smoke-check на production.
6. Подписать go/no-go.

## Stabilization window (60 минут)

- Мониторинг:
  - ошибки 5xx;
  - скорость ответов критичных endpoint;
  - успешность login/add/edit/archive/restore;
  - успешность cron-задачи `expire-posts` и почтовых уведомлений.
- Если появляются критичные ошибки, запускаем rollback.

## Rollback trigger

- Sev1/Sev2 инцидент > 10 минут без подтверждённого обхода.
- Невозможность завершить критичный пользовательский сценарий.

## Rollback steps

1. Откат кода на предыдущую стабильную версию.
2. Откат БД по подготовленному backup-плану.
3. Проверка smoke после отката.
4. Фиксация инцидента: причина, таймлайн, corrective actions.
