# Тест-план (Smoke + Regression + Contract)

## Цель
Снизить риск регрессий перед релизом и сделать проверки воспроизводимыми.

## 1) Smoke (обязательно перед релизом)

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
   - главная, фильтры, пагинация, detail.

## 2) Contract checks (API)

Для ключевых endpoint проверяем JSON-контракт:
- обязательное поле `success`;
- при ошибке: `error`, `code`;
- при rate-limit: `code=429` и `retry_after`.

Endpoint-минимум:
- `POST /login`
- `POST /forgot-password`
- `GET /api/check-email`
- `POST /add`
- `POST /edit/{id}`
- `POST /delete/{id}`
- `POST /api/favorite/toggle`

## 3) Regression checks

- Галерея detail и переключение фото с клавиатуры.
- Валидация цены и лимитов фото.
- Черновики add/edit (автосохранение/восстановление/очистка после успешного submit).
- UI-ошибки при сетевых сбоях и `429`.

## 4) Exit criteria (Go/No-Go)

- 100% smoke кейсов успешно.
- Нет открытых дефектов P1/P2.
- Contract checks зелёные по всем критичным endpoint.
- Для известных minor-багов есть workaround и зафиксирован owner.
