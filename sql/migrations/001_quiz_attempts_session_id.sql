-- Adds quiz_attempts.session_id to an existing `stocksense` database.
-- Run once (not needed for fresh installs of sql/schema.sql):
--   mysql -u root stocksense < sql/migrations/001_quiz_attempts_session_id.sql

USE stocksense;

-- Add as nullable first so existing rows are accepted.
ALTER TABLE quiz_attempts ADD COLUMN session_id VARCHAR(40) NULL AFTER user_id;

-- Legacy rows have no known grouping: each counts as its own 1-question session.
UPDATE quiz_attempts SET session_id = CONCAT('legacy_', id) WHERE session_id IS NULL;

ALTER TABLE quiz_attempts MODIFY session_id VARCHAR(40) NOT NULL;
