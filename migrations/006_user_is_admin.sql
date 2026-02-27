-- Миграция 006: роль пользователя (0 = user, 1 = admin)

SET @has_is_admin := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'user'
      AND COLUMN_NAME = 'is_admin'
);
SET @sql := IF(
    @has_is_admin = 0,
    'ALTER TABLE user ADD COLUMN is_admin tinyint(1) NOT NULL DEFAULT 0 AFTER email_verified',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE user
SET is_admin = 1
WHERE email = 'seolool@yandex.ru';
