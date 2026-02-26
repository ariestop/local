-- Миграция 001: таблица post_photo, расширение колонки user.password

CREATE TABLE IF NOT EXISTS post_photo (
    id int UNSIGNED NOT NULL AUTO_INCREMENT,
    post_id int UNSIGNED NOT NULL,
    filename varchar(100) NOT NULL,
    sort_order int NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY post_id (post_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE user MODIFY COLUMN password varchar(255) NOT NULL;
