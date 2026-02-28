# План улучшения логики проекта (Senior Roadmap v2)

## 1) Итог по этапу 1-10
- ✅ P1.1 Router hardening
- ✅ P1.2 Atomic DB + FS
- ✅ P1.3 Upload security hardening
- ✅ P1.4 Auth session hardening
- ✅ P1.5 Unified URL logic
- ✅ P1.6 Unified migration core
- ✅ P2.7 XSS/CSP baseline hardening
- ✅ P2.8 UX/A11y baseline
- ✅ P2.9 CI + quality gates
- ✅ P2.10 Critical flow test expansion

## 2) Архив выполненных задач (спринт 1)
1. ✅ `Router.php`: безопасная сборка regex + проверка action перед dispatch.
2. ✅ `ImageService.php`: server-side MIME + строгая обработка `UPLOAD_ERR_*`.
3. ✅ `UserController.php`: `session_regenerate_id(true)` после успешных auth-flow.
4. ✅ `PostService.php` + `ImageService.php`: staging/commit/compensation для файлов.
5. ✅ `tests/` + `phpunit.xml`: smoke/integration на критичные сценарии.
6. ✅ Документация `docs/TEST_PLAN.md`: актуализирован checklist.

---

## 3) Next Wave (после закрытия 1-10)

### NW-1: CSP Tightening (приоритет: High, оценка: M)
**Цель:** перейти от baseline CSP к строгой политике без `unsafe-inline`/`unsafe-eval`.

- [ ] Ввести `Content-Security-Policy-Report-Only` для финальной калибровки.
- [ ] Собрать и устранить нарушения (scripts/styles/events).
- [ ] Перейти на production CSP enforce.
- [ ] Убрать `unsafe-inline` и `unsafe-eval` из `SECURITY_CSP`.

**DoD:**
- 0 критичных CSP violations в отчётах.
- `script-src/style-src` без `unsafe-inline` и `unsafe-eval`.
- Все ключевые UI-флоу работают без fallback-послаблений.

---

### NW-2: Shared Rate Limiting Storage (приоритет: High, оценка: M/L)
**Цель:** отказаться от session-only лимитинга, обеспечить консистентность между инстансами.

- [ ] Вынести хранилище лимитов в DB/Redis abstraction.
- [ ] Реализовать адаптер и миграционный план.
- [ ] Подключить `UserController`/`ApiController` к shared backend.
- [ ] Добавить контрактные тесты для multi-request/multi-session сценариев.

**DoD:**
- Лимиты одинаково применяются между сессиями/инстансами.
- Есть fallback-режим и наблюдаемость по hit/blocked/retry_after.
- Покрытие тестами основных rate-limit веток.

---

### NW-3: Observability & Ops Readiness (приоритет: Medium, оценка: M)
**Цель:** повысить эксплуатационную зрелость.

- [ ] Структурировать app-логи (уровни, контекст, correlation id).
- [ ] Добавить базовые метрики (ошибки, 5xx, latency critical endpoints).
- [ ] Описать rollback/restore drill в runbook.
- [ ] Проверить админ-потоки и cron-флоу на деградацию/повторные запуски.

**DoD:**
- Есть минимальный набор метрик и понятные алерты.
- Runbook покрывает rollback/restore и аварийные процедуры.
- Регламент операционных проверок закреплён.

---

## 4) KPI/метрики готовности

- **Security:**
  - `0` inline JS handlers в шаблонах;
  - CSP violations trend стабильно снижается до `0` критичных.
- **Quality:**
  - CI обязателен для merge;
  - unit + integration зелёные на PR.
- **Reliability:**
  - нет рассинхронизации DB/FS в негативных сценариях;
  - install/migrations проходят повторно (idempotent path).
- **UX/A11y:**
  - критические сценарии доступны с клавиатуры;
  - интерактивные controls имеют корректные `aria-label`.
- **Runtime:**
  - базовые smoke-флоу без регрессий в двух URL-режимах.

---

## 5) Риски и зависимости

- Внешние CDN/инлайн-паттерны могут тормозить CSP hard-enforce.
- Переход rate limiter на shared storage требует аккуратной миграции и rollback-плана.
- Для observability нужен минимум инфраструктурной поддержки (лог-хранилище/метрики).
