-- Widens quiz_items.question_text / option_a/b/c for the refreshed question bank
-- (Semiconductor_News (1).xlsx). Safe to re-run.
--
--   mysql --default-character-set=utf8mb4 -u root stocksense < sql/migrations/005_quiz_question_bank.sql
--
-- The content update itself (new question text, options, answers, plus news id 154 /
-- quiz id 159) comes from re-importing sql/schema.sql AFTER this migration: its
-- ON DUPLICATE KEY UPDATE upserts refresh the existing rows.

USE stocksense;

ALTER TABLE quiz_items
    MODIFY question_text TEXT NOT NULL,
    MODIFY option_a VARCHAR(255) NOT NULL,
    MODIFY option_b VARCHAR(255) NOT NULL,
    MODIFY option_c VARCHAR(255) NOT NULL;
