-- Миграция 007: индексы для частых фильтров/сортировок и аналитики

-- post: выборки главной страницы и кабинета
SET @idx_exists := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'post'
      AND INDEX_NAME = 'idx_post_city_action_room_cost_created'
);
SET @sql := IF(
    @idx_exists = 0,
    'CREATE INDEX idx_post_city_action_room_cost_created ON post (city_id, action_id, room, cost, created_at)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'post'
      AND INDEX_NAME = 'idx_post_created_at'
);
SET @sql := IF(
    @idx_exists = 0,
    'CREATE INDEX idx_post_created_at ON post (created_at)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'post'
      AND INDEX_NAME = 'idx_post_cost'
);
SET @sql := IF(
    @idx_exists = 0,
    'CREATE INDEX idx_post_cost ON post (cost)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'post'
      AND INDEX_NAME = 'idx_post_user_created'
);
SET @sql := IF(
    @idx_exists = 0,
    'CREATE INDEX idx_post_user_created ON post (user_id, created_at)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'post'
      AND INDEX_NAME = 'idx_post_view_count_created'
);
SET @sql := IF(
    @idx_exists = 0,
    'CREATE INDEX idx_post_view_count_created ON post (view_count, created_at)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- post_view_event: отчёты активности/просмотров
SET @tbl_exists := (
    SELECT COUNT(*)
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'post_view_event'
);

SET @idx_exists := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'post_view_event'
      AND INDEX_NAME = 'idx_pve_viewed_at'
);
SET @sql := IF(
    @tbl_exists > 0 AND @idx_exists = 0,
    'CREATE INDEX idx_pve_viewed_at ON post_view_event (viewed_at)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'post_view_event'
      AND INDEX_NAME = 'idx_pve_post_viewed_at'
);
SET @sql := IF(
    @tbl_exists > 0 AND @idx_exists = 0,
    'CREATE INDEX idx_pve_post_viewed_at ON post_view_event (post_id, viewed_at)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
