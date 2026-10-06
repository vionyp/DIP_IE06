-- =====================================================================
-- StockSense — login + saved answers (pages/login.php)
--
-- Run once, AFTER sql/schema.sql, on an existing database (fresh installs
-- of sql/schema.sql already include this table). Safe to re-run.
--
--   phpMyAdmin: select the stocksense database -> Import -> this file.
--   CLI: mysql --default-character-set=utf8mb4 -u root stocksense < sql/migrations/003_login_answers.sql
--
-- No change is needed to `users`: it already has username (UNIQUE) and
-- password_hash, which the Register form fills with password_hash() output.
-- =====================================================================

USE stocksense;

-- ---------------------------------------------------------------------
-- Answers — each user's CURRENT answer to each quiz question.
-- One row per (user, question): answering the same question again updates
-- that row (INSERT ... ON DUPLICATE KEY UPDATE in pages/quiz.php) instead
-- of adding a new one. quiz_attempts still keeps the full attempt history.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS answers (
    user_id      INT NOT NULL,
    question_id  INT NOT NULL,
    answer       CHAR(1) NOT NULL,
    is_correct   TINYINT(1) NOT NULL,
    answered_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, question_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES quiz_items(id) ON DELETE CASCADE
) ENGINE=InnoDB;
