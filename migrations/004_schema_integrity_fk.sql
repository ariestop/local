-- Миграция 004: усиливаем целостность связей через FK и каскадное удаление.
-- Перед применением рекомендуется очистить orphan-записи.

-- post_photo -> post (каскад при удалении объявления)
DELETE pp
FROM post_photo pp
LEFT JOIN post p ON p.id = pp.post_id
WHERE p.id IS NULL;

SET @fk_post_photo_exists := (
    SELECT COUNT(*)
    FROM information_schema.REFERENTIAL_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND CONSTRAINT_NAME = 'fk_post_photo_post'
      AND TABLE_NAME = 'post_photo'
);
SET @sql := IF(
    @fk_post_photo_exists = 0,
    'ALTER TABLE post_photo ADD CONSTRAINT fk_post_photo_post FOREIGN KEY (post_id) REFERENCES post(id) ON DELETE CASCADE',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- user_favorite -> user/post (каскад при удалении пользователя/объявления)
DELETE uf
FROM user_favorite uf
LEFT JOIN user u ON u.id = uf.user_id
WHERE u.id IS NULL;

DELETE uf
FROM user_favorite uf
LEFT JOIN post p ON p.id = uf.post_id
WHERE p.id IS NULL;

SET @fk_user_favorite_user_exists := (
    SELECT COUNT(*)
    FROM information_schema.REFERENTIAL_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND CONSTRAINT_NAME = 'fk_user_favorite_user'
      AND TABLE_NAME = 'user_favorite'
);
SET @sql := IF(
    @fk_user_favorite_user_exists = 0,
    'ALTER TABLE user_favorite ADD CONSTRAINT fk_user_favorite_user FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_user_favorite_post_exists := (
    SELECT COUNT(*)
    FROM information_schema.REFERENTIAL_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND CONSTRAINT_NAME = 'fk_user_favorite_post'
      AND TABLE_NAME = 'user_favorite'
);
SET @sql := IF(
    @fk_user_favorite_post_exists = 0,
    'ALTER TABLE user_favorite ADD CONSTRAINT fk_user_favorite_post FOREIGN KEY (post_id) REFERENCES post(id) ON DELETE CASCADE',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
