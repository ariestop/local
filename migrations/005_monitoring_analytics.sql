-- Миграция 005: мониторинг и аналитика (просмотры + ошибки клиента)

-- post.view_count (если отсутствует)
SET @has_view_count := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'post'
      AND COLUMN_NAME = 'view_count'
);
SET @sql := IF(
    @has_view_count = 0,
    'ALTER TABLE post ADD COLUMN view_count int UNSIGNED NOT NULL DEFAULT 0 AFTER new_house',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- События просмотров объявлений
CREATE TABLE IF NOT EXISTS post_view_event (
    id int UNSIGNED NOT NULL AUTO_INCREMENT,
    post_id int UNSIGNED NOT NULL,
    user_id int UNSIGNED NULL,
    session_hash char(64) NOT NULL,
    ip_hash char(64) NOT NULL,
    user_agent varchar(255) NOT NULL DEFAULT '',
    viewed_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_post_view_event_post_id (post_id),
    KEY idx_post_view_event_viewed_at (viewed_at),
    KEY idx_post_view_event_user_id (user_id),
    CONSTRAINT fk_post_view_event_post FOREIGN KEY (post_id) REFERENCES post(id) ON DELETE CASCADE,
    CONSTRAINT fk_post_view_event_user FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ошибки клиента (аналог Sentry в БД)
CREATE TABLE IF NOT EXISTS app_error_event (
    id int UNSIGNED NOT NULL AUTO_INCREMENT,
    level varchar(20) NOT NULL DEFAULT 'error',
    message varchar(500) NOT NULL,
    context_json text NULL,
    url varchar(255) NOT NULL DEFAULT '',
    user_id int UNSIGNED NULL,
    ip_hash char(64) NOT NULL,
    user_agent varchar(255) NOT NULL DEFAULT '',
    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_app_error_event_created_at (created_at),
    KEY idx_app_error_event_user_id (user_id),
    CONSTRAINT fk_app_error_event_user FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
