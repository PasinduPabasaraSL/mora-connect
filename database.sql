-- =========================================================
-- MoraConnect Database Schema
-- A Medium-style publishing platform for University of
-- Moratuwa students.
--
-- HOW TO USE:
--   1. Log in to phpMyAdmin (InfinityFree / 000WebHost / local XAMPP)
--   2. Create a database named `moraconnect` (or import this file directly,
--      it creates the database for you)
--   3. Import this file, or paste it into the SQL tab
-- =========================================================

CREATE DATABASE IF NOT EXISTS moraconnect
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE moraconnect;

-- ---------------------------------------------------------
-- Table: users
-- Stores registered students. Default role is "student".
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    -- Identifies the account and appears in author URLs, so it can never change
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    google_id VARCHAR(64) NULL UNIQUE,   -- Google's subject id, set for accounts that sign in with Google
    password VARCHAR(255) NULL,          -- password_hash() output, never plain text; null for Google-only accounts
    role VARCHAR(50) NOT NULL DEFAULT 'student',

    -- Profile. All optional: an account works with none of it filled in.
    display_name VARCHAR(80) NULL,       -- shown on bylines in place of the username
    headline VARCHAR(160) NULL,
    bio VARCHAR(600) NULL,
    faculty VARCHAR(120) NULL,
    programme VARCHAR(120) NULL,
    study_year VARCHAR(24) NULL,
    website VARCHAR(255) NULL,
    github VARCHAR(64) NULL,             -- username only, printed into a github.com URL
    linkedin VARCHAR(255) NULL,
    interests VARCHAR(255) NULL,         -- comma-separated category names

    -- Avatar. Both sources are kept so switching between them is reversible.
    avatar_path VARCHAR(160) NULL,       -- file in uploads/avatars, owned by this server
    google_avatar VARCHAR(600) NULL,     -- URL owned by Google
    avatar_source VARCHAR(12) NOT NULL DEFAULT 'initials',  -- upload | google | initials

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Table: blogPost
-- Stores every article. Linked to the author via user_id.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS blogPost (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    category VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_blogpost_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- Helpful index for "latest articles" queries and per-author lookups
CREATE INDEX idx_blogpost_created_at ON blogPost(created_at DESC);
CREATE INDEX idx_blogpost_user_id ON blogPost(user_id);

-- ---------------------------------------------------------
-- Optional: a couple of sample rows so you have something to
-- look at immediately after import. Safe to delete later.
-- Sample password for both users is: Password123
-- (hash generated with PHP's password_hash, bcrypt algorithm)
-- ---------------------------------------------------------
INSERT INTO users (username, email, password, role) VALUES
('alex_chen', 'alex.chen@uom.lk', '$2y$10$92xI3dQaMxpr6BknkCzQF.KcE1wU9y4ZssTU1uJTe9Sg9Zji5v8nu', 'student'),
('priya_patel', 'priya.patel@uom.lk', '$2y$10$92xI3dQaMxpr6BknkCzQF.KcE1wU9y4ZssTU1uJTe9Sg9Zji5v8nu', 'student');

INSERT INTO blogPost (user_id, title, content, category) VALUES
(1, 'The Future of Campus Innovation', 'University campuses are increasingly becoming testbeds for emerging technology. From smart lighting in lecture halls to student-built IoT projects, the modern campus is quietly turning into a living laboratory for innovation...', 'Technology'),
(2, 'Stoicism as a Framework for Exam Anxiety', 'Ancient Stoic philosophy offers surprisingly practical tools for modern student stress. By focusing only on what is within our control...', 'Philosophy');
