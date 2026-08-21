-- =========================================================
-- MoraConnect — complete database schema
-- A technical publishing platform for University of Moratuwa students.
--
-- This is the whole schema. Importing this one file gives you a database the
-- application runs against, with no further steps.
--
-- HOW TO USE:
--   phpMyAdmin -> Import -> choose this file
--   or: mysql -u root < database.sql
--
-- The tables are created empty. Register an account through the site to get
-- started, and run `php import_radar.php` to fill the Radar section.
--
-- The migration_*.sql files alongside this one are history: each was written to
-- add one feature's columns to a database that already had data in it. Every
-- change they make is already folded in below, so a fresh install never needs
-- them. They matter only if you are upgrading a copy of the database that was
-- created before the feature in question.
-- =========================================================

USE if0_42674303_moraconnect;

-- ---------------------------------------------------------
-- Table: users
--
-- Accounts, and the profile shown beside everything they publish. An account
-- needs a username and an email; every profile field is optional.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,

    -- Identifies the account and forms the URL of its public page
    -- (/authors/{username}), which is why the application never lets it change.
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    role VARCHAR(50) NOT NULL DEFAULT 'student',

    -- Sign-in. An account can have a password, a Google identity, or both.
    -- password is null for an account created through Google that has never set
    -- one; a placeholder hash there would be a lie.
    google_id VARCHAR(64) NULL UNIQUE,      -- Google's subject id, stable across email changes
    password VARCHAR(255) NULL,             -- password_hash() output, never plain text

    -- Profile
    display_name VARCHAR(80) NULL,          -- shown on bylines in place of the username
    headline VARCHAR(160) NULL,             -- one line, under the name
    bio VARCHAR(600) NULL,

    -- Academic details
    faculty VARCHAR(120) NULL,
    programme VARCHAR(120) NULL,
    study_year VARCHAR(24) NULL,            -- from a fixed list, e.g. '3rd year'

    -- Links. github holds a bare username printed into a github.com URL; the
    -- other two hold full addresses, checked for an http(s) scheme on the way in.
    website VARCHAR(255) NULL,
    github VARCHAR(64) NULL,
    linkedin VARCHAR(255) NULL,

    -- Comma-separated category names, validated against the configured list.
    -- A join table would be the textbook answer, but this is a short fixed
    -- vocabulary that is only ever read as a whole.
    interests VARCHAR(255) NULL,

    -- Avatar. Both sources are kept rather than one column, because they are
    -- different things: avatar_path is a file this server owns and must delete
    -- when it is replaced, google_avatar is a URL belonging to Google. Keeping
    -- both means switching between them is reversible.
    avatar_path VARCHAR(160) NULL,          -- file name within uploads/avatars
    google_avatar VARCHAR(600) NULL,
    avatar_source VARCHAR(12) NOT NULL DEFAULT 'initials',  -- upload | google | initials

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Table: blogPost
--
-- Every article, draft or published, linked to its author via user_id.
--
-- An article is only public when status = 'published' AND visibility = 'public'.
-- Those two are separate on purpose: a draft is unfinished, while an unlisted
-- article is finished but deliberately kept off the homepage, its topic and
-- search, readable by anyone holding the link.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS blogPost (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,

    title VARCHAR(255) NOT NULL,
    subtitle VARCHAR(300) NULL,

    -- MEDIUMTEXT rather than TEXT: the rich editor stores markup, and TEXT's
    -- 64 KB ceiling is reachable by a long article with embedded code.
    content MEDIUMTEXT NOT NULL,
    -- 'html' for anything written in the rich editor, 'text' for articles that
    -- predate it. The renderer escapes and wraps the plain ones instead of
    -- printing them as markup.
    content_format VARCHAR(8) NOT NULL DEFAULT 'text',

    category VARCHAR(100) NOT NULL,
    slug VARCHAR(255) NULL UNIQUE,          -- null falls back to the numeric id in URLs
    description VARCHAR(500) NULL,          -- the author's own summary, used in listings
    tags VARCHAR(255) NULL,                 -- comma-separated

    status VARCHAR(12) NOT NULL DEFAULT 'draft',        -- draft | published
    visibility VARCHAR(12) NOT NULL DEFAULT 'public',   -- public | unlisted
    comments_enabled TINYINT(1) NOT NULL DEFAULT 1,

    -- Counted once when the article is saved, so every listing and the article
    -- itself quote the same figures instead of each deriving their own.
    word_count INT NOT NULL DEFAULT 0,
    reading_minutes INT NOT NULL DEFAULT 0,

    image_url VARCHAR(500) NULL,            -- optional cover image

    -- When it first went live, which is not the same as when it was written.
    -- Listings order by this and fall back to created_at.
    published_at DATETIME NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_blogpost_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_blogpost_created_at ON blogPost(created_at);
CREATE INDEX idx_blogpost_user_id ON blogPost(user_id);
-- Both columns together, because every public listing filters on both
CREATE INDEX idx_blogpost_status ON blogPost(status, visibility);

-- ---------------------------------------------------------
-- Table: radar_posts
--
-- Curated technical articles published elsewhere, written by import_radar.php
-- and never by the application. Only a title, summary, cover image and link are
-- stored — never the article text — so every entry sends readers to the
-- original, credited to its author.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS radar_posts (
    id INT PRIMARY KEY AUTO_INCREMENT,

    title VARCHAR(300) NOT NULL,
    summary TEXT NOT NULL,

    url VARCHAR(500) NOT NULL,
    -- The uniqueness check is on a hash, not the URL: a 500-character column is
    -- too long to index, and a fixed-width hash makes re-importing idempotent.
    url_hash CHAR(40) NOT NULL UNIQUE,

    image_url VARCHAR(500) NULL,

    author VARCHAR(150) NOT NULL,
    author_url VARCHAR(300) NULL,
    source VARCHAR(50) NOT NULL DEFAULT 'dev.to',

    -- Mapped from the source's own tags onto this site's categories
    category VARCHAR(100) NOT NULL DEFAULT 'Other',
    tags VARCHAR(255) NULL,

    reading_minutes INT NOT NULL DEFAULT 0,
    reactions INT NOT NULL DEFAULT 0,       -- how the list is ordered

    published_at DATETIME NULL,
    fetched_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE INDEX idx_radar_category ON radar_posts(category);
CREATE INDEX idx_radar_published ON radar_posts(published_at);
