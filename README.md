# Доска объявлений — продажа недвижимости

MVC-сайт на PHP 8.5+, Bootstrap 5, Vue.js, MySQL 8.0. Аналог m2saratov.ru.

## Установка

1. **Развернуть базу данных**
   - Откройте в браузере: `http://localhost/test/install.php`
   - Либо импортируйте `public/infosee2_m2sar.sql` через phpMyAdmin в БД `infosee2_m2sar`

2. **Настройка БД** (если нужно)
   - Файл: `app/config/config.php`
   - Параметры: host, dbname, user, password

3. **Удалить install.php** после успешной установки

## Вход для теста

- Логин: seobot@qip.ru
- Пароль: 12345

## Структура

```
app/
  config/     — конфигурация
  core/       — Router, Database, Controller
  models/     — Post, User, Reference
  controllers/
  views/
public/       — точка входа, assets
```

## Функции

- Главная: таблица объявлений
- Регистрация и вход во всплывающих окнах
- Капча при регистрации
- Проверка email в реальном времени
- Добавление объявления (только для авторизованных)
- Детальная страница объявления
