-- =========================================================
-- Adds optional cover images to articles.
--
-- HOW TO USE:
--   phpMyAdmin -> moraconnect -> Import -> choose this file
--   or: mysql -u root moraconnect < migration_add_image.sql
--
-- Nullable on purpose: every existing article keeps working and
-- simply falls back to a coloured topic block on cards.
-- =========================================================

USE moraconnect;

ALTER TABLE blogPost
    ADD COLUMN image_url VARCHAR(500) NULL DEFAULT NULL AFTER category;
