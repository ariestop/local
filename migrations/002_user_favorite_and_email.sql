-- Миграция 002: избранное (остальное — в 002_user_columns.php)
CREATE TABLE IF NOT EXISTS user_favorite (
    id int UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id int UNSIGNED NOT NULL,
    post_id int UNSIGNED NOT NULL,
    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY user_post (user_id, post_id),
    KEY user_id (user_id),
    KEY post_id (post_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
