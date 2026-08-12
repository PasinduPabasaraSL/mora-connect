-- Google sign-in.
--
-- Run with: mysql -uroot moraconnect < migration_google.sql
-- Safe to run more than once.

ALTER TABLE users
    -- Google's subject id, not the email: an account's email can change, its
    -- subject id cannot, so this is what an existing user is matched on.
    ADD COLUMN IF NOT EXISTS google_id VARCHAR(64) NULL DEFAULT NULL AFTER email;

-- Two accounts must never claim the same Google identity.
ALTER TABLE users ADD UNIQUE INDEX IF NOT EXISTS users_google_id (google_id);

-- Somebody who only ever signs in with Google has no password to store, and a
-- NOT NULL column would force a meaningless placeholder hash into the row.
ALTER TABLE users MODIFY password VARCHAR(255) NULL DEFAULT NULL;
