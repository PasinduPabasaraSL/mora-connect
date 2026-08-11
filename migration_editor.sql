-- =========================================================
-- Upgrades articles from a plain textarea to the rich editor.
--
-- Nothing here is destructive. Every column is nullable or has a
-- default, and the backfill at the bottom marks existing articles as
-- published plain text, so they keep rendering exactly as before.
--
-- HOW TO USE:
--   phpMyAdmin -> moraconnect -> Import -> choose this file
--   or: mysql -u root moraconnect < migration_editor.sql
--
-- Safe to run more than once (IF NOT EXISTS on every column).
-- =========================================================

USE moraconnect;

ALTER TABLE blogPost
    -- Deck / standfirst shown under the title
    ADD COLUMN IF NOT EXISTS subtitle VARCHAR(300) NULL DEFAULT NULL AFTER title,

    -- content_format tells the article page how to render `content`.
    -- 'text' is the legacy plain-text body kept as-is with pre-wrap;
    -- 'html' is sanitised markup from the rich editor.
    ADD COLUMN IF NOT EXISTS content_format VARCHAR(8) NOT NULL DEFAULT 'text' AFTER content,

    -- URL slug. Nullable because legacy rows have none and articles are
    -- still reachable by id; unique so two articles cannot collide.
    ADD COLUMN IF NOT EXISTS slug VARCHAR(255) NULL DEFAULT NULL AFTER category,

    -- Search/social summary. Falls back to an excerpt of the body when empty.
    ADD COLUMN IF NOT EXISTS description VARCHAR(500) NULL DEFAULT NULL AFTER slug,

    -- Free-form tags, stored comma separated
    ADD COLUMN IF NOT EXISTS tags VARCHAR(255) NULL DEFAULT NULL AFTER description,

    -- draft | published. Defaults to draft so a half-written autosave can
    -- never appear on the homepage.
    ADD COLUMN IF NOT EXISTS status VARCHAR(12) NOT NULL DEFAULT 'draft' AFTER tags,

    -- public | unlisted. Unlisted articles are readable by link but are
    -- left out of listings, topics and search.
    ADD COLUMN IF NOT EXISTS visibility VARCHAR(12) NOT NULL DEFAULT 'public' AFTER status,

    ADD COLUMN IF NOT EXISTS comments_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER visibility,

    -- Counts are stored at save time so listings do not have to parse every
    -- body to show "6 min read".
    ADD COLUMN IF NOT EXISTS word_count INT NOT NULL DEFAULT 0 AFTER comments_enabled,
    ADD COLUMN IF NOT EXISTS reading_minutes INT NOT NULL DEFAULT 0 AFTER word_count,

    -- Set the first time an article is published, then left alone, so
    -- re-publishing an edited article does not move it up the homepage.
    ADD COLUMN IF NOT EXISTS published_at DATETIME NULL DEFAULT NULL AFTER reading_minutes;

-- Markup costs more room than plain text did, and TEXT tops out at 64 KB.
ALTER TABLE blogPost MODIFY content MEDIUMTEXT NOT NULL;

-- Slug lookups happen on every article page opened by slug
CREATE UNIQUE INDEX IF NOT EXISTS uniq_blogpost_slug ON blogPost(slug);
CREATE INDEX IF NOT EXISTS idx_blogpost_status ON blogPost(status, visibility);

-- ---------------------------------------------------------
-- Backfill: everything written before this migration was live, so it
-- stays live. published_at mirrors created_at to preserve ordering.
-- ---------------------------------------------------------
UPDATE blogPost
   SET status         = 'published',
       published_at   = created_at,
       content_format = 'text'
 WHERE published_at IS NULL;

-- Word counts for existing bodies. Counting spaces is an approximation,
-- which is all a "min read" label needs.
UPDATE blogPost
   SET word_count      = CHAR_LENGTH(TRIM(content)) - CHAR_LENGTH(REPLACE(TRIM(content), ' ', '')) + 1,
       reading_minutes = GREATEST(1, ROUND((CHAR_LENGTH(TRIM(content)) - CHAR_LENGTH(REPLACE(TRIM(content), ' ', '')) + 1) / 200))
 WHERE word_count = 0 AND TRIM(content) <> '';
