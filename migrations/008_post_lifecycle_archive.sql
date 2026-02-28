-- Миграция 008: жизненный цикл объявлений (active/archive, срок публикации, метаданные архива)

ALTER TABLE post
    ADD COLUMN status ENUM('active', 'archived') NOT NULL DEFAULT 'active' AFTER user_id,
    ADD COLUMN published_at DATETIME NULL AFTER created_at,
    ADD COLUMN expires_at DATETIME NULL AFTER published_at,
    ADD COLUMN archived_at DATETIME NULL AFTER expires_at,
    ADD COLUMN archived_by_user_id INT UNSIGNED NULL AFTER archived_at,
    ADD COLUMN archive_reason ENUM('manual_owner', 'manual_admin', 'expired') NULL AFTER archived_by_user_id,
    ADD COLUMN expiry_notified_at DATETIME NULL AFTER archive_reason;

UPDATE post
SET published_at = COALESCE(created_at, NOW())
WHERE published_at IS NULL;

UPDATE post
SET expires_at = DATE_ADD(COALESCE(created_at, NOW()), INTERVAL 30 DAY)
WHERE expires_at IS NULL;

ALTER TABLE post
    MODIFY COLUMN published_at DATETIME NOT NULL,
    MODIFY COLUMN expires_at DATETIME NOT NULL;

ALTER TABLE post
    ADD KEY idx_post_status_created_at (status, created_at),
    ADD KEY idx_post_status_expires_at (status, expires_at),
    ADD KEY idx_post_user_status_created_at (user_id, status, created_at),
    ADD KEY idx_post_archived_by_user_id (archived_by_user_id);

ALTER TABLE post
    ADD CONSTRAINT fk_post_archived_by_user
        FOREIGN KEY (archived_by_user_id) REFERENCES user(id) ON DELETE SET NULL;

