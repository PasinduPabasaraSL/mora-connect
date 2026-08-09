-- =========================================================
-- Radar: curated technical articles from other platforms.
--
-- Only a summary, cover image and link are stored — never the
-- article text — so every entry sends readers to the original.
--
-- HOW TO USE:
--   phpMyAdmin -> moraconnect -> Import -> choose this file
--   or: mysql -u root moraconnect < migration_radar.sql
--
-- Then populate it:
--   php import_radar.php
-- =========================================================

USE moraconnect;

CREATE TABLE IF NOT EXISTS radar_posts (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    title           VARCHAR(300)  NOT NULL,
    summary         TEXT          NOT NULL,
    url             VARCHAR(500)  NOT NULL,
    -- The unique index is on a hash rather than the URL itself: a utf8mb4
    -- VARCHAR(500) is 2000 bytes, which does not fit an InnoDB index key on
    -- older MySQL builds. The hash is a fixed 40 bytes and re-runs of the
    -- importer rely on it to update instead of duplicating.
    url_hash        CHAR(40)      NOT NULL,
    image_url       VARCHAR(500)  NULL DEFAULT NULL,
    author          VARCHAR(150)  NOT NULL,
    author_url      VARCHAR(300)  NULL DEFAULT NULL,
    source          VARCHAR(50)   NOT NULL DEFAULT 'dev.to',
    category        VARCHAR(100)  NOT NULL DEFAULT 'Other',
    tags            VARCHAR(255)  NULL DEFAULT NULL,
    reading_minutes INT           NOT NULL DEFAULT 0,
    reactions       INT           NOT NULL DEFAULT 0,
    published_at    DATETIME      NULL DEFAULT NULL,
    fetched_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uniq_radar_url (url_hash),
    KEY idx_radar_category (category),
    KEY idx_radar_published (published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
