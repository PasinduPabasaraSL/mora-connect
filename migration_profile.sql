-- Profiles: the details a writer can put on their own page.
--
-- Run with: mysql -uroot moraconnect < migration_profile.sql
-- Safe to run more than once.
--
-- username is deliberately absent from everything below. It identifies the
-- account, appears in author URLs, and is the one thing a person cannot change.

ALTER TABLE users
    -- Shown on bylines in place of the username when it is set. Left null
    -- rather than copied from the username, so the fallback stays in one place.
    ADD COLUMN IF NOT EXISTS display_name VARCHAR(80)  NULL DEFAULT NULL AFTER username,
    ADD COLUMN IF NOT EXISTS headline     VARCHAR(160) NULL DEFAULT NULL AFTER display_name,
    ADD COLUMN IF NOT EXISTS bio          VARCHAR(600) NULL DEFAULT NULL AFTER headline,

    -- Academic details
    ADD COLUMN IF NOT EXISTS faculty      VARCHAR(120) NULL DEFAULT NULL AFTER bio,
    ADD COLUMN IF NOT EXISTS programme    VARCHAR(120) NULL DEFAULT NULL AFTER faculty,
    ADD COLUMN IF NOT EXISTS study_year   VARCHAR(24)  NULL DEFAULT NULL AFTER programme,

    -- Links. github holds a username, the other two hold full URLs.
    ADD COLUMN IF NOT EXISTS website      VARCHAR(255) NULL DEFAULT NULL AFTER study_year,
    ADD COLUMN IF NOT EXISTS github       VARCHAR(64)  NULL DEFAULT NULL AFTER website,
    ADD COLUMN IF NOT EXISTS linkedin     VARCHAR(255) NULL DEFAULT NULL AFTER github,

    -- Comma-separated category names, validated against the configured list on
    -- the way in. A join table would be the textbook answer, but this is a
    -- short fixed vocabulary that is only ever read as a whole.
    ADD COLUMN IF NOT EXISTS interests    VARCHAR(255) NULL DEFAULT NULL AFTER linkedin,

    -- Avatar. Three separate columns because the two sources are genuinely
    -- different things: one is a file this server owns and must delete when it
    -- is replaced, the other is a URL belonging to Google.
    ADD COLUMN IF NOT EXISTS avatar_path   VARCHAR(160) NULL DEFAULT NULL AFTER interests,
    ADD COLUMN IF NOT EXISTS google_avatar VARCHAR(600) NULL DEFAULT NULL AFTER avatar_path,
    -- 'upload', 'google' or 'initials' — which of the above to actually show
    ADD COLUMN IF NOT EXISTS avatar_source VARCHAR(12) NOT NULL DEFAULT 'initials' AFTER google_avatar;

-- Author pages are looked up by username on every visit.
ALTER TABLE users ADD INDEX IF NOT EXISTS users_username_lookup (username);
