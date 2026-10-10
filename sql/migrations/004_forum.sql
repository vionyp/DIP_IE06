-- =====================================================================
-- StockSense — forum (pages/forum.php)
--
-- Run once, AFTER sql/schema.sql. Safe to re-run.
--
--   phpMyAdmin: select the stocksense database -> Import -> this file,
--               with "Character set of the file" set to utf8mb4.
--   CLI: mysql --default-character-set=utf8mb4 -u root stocksense < sql/migrations/004_forum.sql
--
-- The boards themselves are not a table: they are the fixed list in
-- config/forum.php, and forum_topics.board stores the board's key.
-- =====================================================================

USE stocksense;

-- ---------------------------------------------------------------------
-- Topics — one row per question. The question text itself is the topic's
-- first row in forum_posts. last_activity_at is bumped on every reply and
-- is what the topic lists sort by.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS forum_topics (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    board            VARCHAR(30) NOT NULL,
    user_id          INT NOT NULL,
    title            VARCHAR(150) NOT NULL,
    is_pinned        TINYINT(1) NOT NULL DEFAULT 0,
    views            INT NOT NULL DEFAULT 0,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_activity_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_board_activity (board, last_activity_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Posts — the question (lowest id in a topic) and every reply to it.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS forum_posts (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    topic_id    INT NOT NULL,
    user_id     INT NOT NULL,
    body        TEXT NOT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_topic (topic_id, id),
    FOREIGN KEY (topic_id) REFERENCES forum_topics(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Likes — one row per (post, user), so a user can like a post only once.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS forum_likes (
    post_id   INT NOT NULL,
    user_id   INT NOT NULL,
    liked_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (post_id, user_id),
    FOREIGN KEY (post_id) REFERENCES forum_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Starter content — SAMPLE discussions so the forum is not empty.
-- The accounts below are made up and have no password_hash, so (like the
-- seeded "guest") nobody can log in as them. To start with an empty forum
-- instead, delete everything from here down before importing, or run:
--   DELETE FROM users WHERE username IN ('stocksense_team', 'wafer_wanderer',
--     'chipcurious', 'fabnotes', 'eee_y2_ling', 'iem_darren', 'policy_and_pcbs');
-- (their topics and posts are removed with them).
-- ---------------------------------------------------------------------
INSERT IGNORE INTO users (username) VALUES
    ('stocksense_team'),
    ('wafer_wanderer'),
    ('chipcurious'),
    ('fabnotes'),
    ('eee_y2_ling'),
    ('iem_darren'),
    ('policy_and_pcbs');

INSERT IGNORE INTO forum_topics (id, board, user_id, title, is_pinned, views, created_at, last_activity_at)
SELECT 1, 'beginner', id, 'About the Beginner Questions board', 1, 412, '2026-09-01 09:00:00', '2026-09-04 08:30:00'
FROM users WHERE username = 'stocksense_team';
INSERT IGNORE INTO forum_posts (id, topic_id, user_id, body, created_at)
SELECT 1, 1, id, 'This board is for anyone starting out. If a lesson or quiz explanation did not click, ask here and say which category it came from. Please keep it about understanding the news, not about what to buy or sell.', '2026-09-01 09:00:00'
FROM users WHERE username = 'stocksense_team';
INSERT IGNORE INTO forum_posts (id, topic_id, user_id, body, created_at)
SELECT 2, 1, id, 'Good to have this. Is it fine to ask about terms from the lessons, like "guidance"?', '2026-09-03 20:14:00'
FROM users WHERE username = 'wafer_wanderer';
INSERT IGNORE INTO forum_posts (id, topic_id, user_id, body, created_at)
SELECT 3, 1, id, 'Yes, exactly that kind of question.', '2026-09-04 08:30:00'
FROM users WHERE username = 'stocksense_team';

INSERT IGNORE INTO forum_topics (id, board, user_id, title, is_pinned, views, created_at, last_activity_at)
SELECT 2, 'beginner', id, 'What is SOXX and why does every quiz compare against it?', 0, 236, '2026-10-05 13:02:00', '2026-10-05 14:05:00'
FROM users WHERE username = 'chipcurious';
INSERT IGNORE INTO forum_posts (id, topic_id, user_id, body, created_at)
SELECT 4, 2, id, 'After each real-news question there is a bar for the stock and a bar for SOXX. I get that SOXX is "the sector" but what is it actually, and what am I supposed to read from the two bars?', '2026-10-05 13:02:00'
FROM users WHERE username = 'chipcurious';
INSERT IGNORE INTO forum_posts (id, topic_id, user_id, body, created_at)
SELECT 5, 2, id, 'SOXX is an exchange-traded fund that holds a basket of semiconductor companies, so its price is a rough average of the sector. If the stock moved a lot and SOXX barely moved, the news was mostly about that one company. If both moved together, it was a sector-wide story.', '2026-10-05 13:40:00'
FROM users WHERE username = 'fabnotes';
INSERT IGNORE INTO forum_posts (id, topic_id, user_id, body, created_at)
SELECT 6, 2, id, 'That makes the bars much easier to read. Thanks!', '2026-10-05 14:05:00'
FROM users WHERE username = 'chipcurious';

INSERT IGNORE INTO forum_topics (id, board, user_id, title, is_pinned, views, created_at, last_activity_at)
SELECT 3, 'beginner', id, 'Foundry vs fabless: which of the tracked companies is which?', 0, 158, '2026-10-07 21:18:00', '2026-10-07 22:01:00'
FROM users WHERE username = 'eee_y2_ling';
INSERT IGNORE INTO forum_posts (id, topic_id, user_id, body, created_at)
SELECT 7, 3, id, 'I keep mixing these up. Can someone sort the watchlist companies for me?', '2026-10-07 21:18:00'
FROM users WHERE username = 'eee_y2_ling';
INSERT IGNORE INTO forum_posts (id, topic_id, user_id, body, created_at)
SELECT 8, 3, id, 'Short version: a fabless company designs chips and pays someone else to make them (NVIDIA). A foundry makes chips for other companies (TSMC). An IDM does both itself (Intel, Samsung, and Micron for memory). ASML is none of these: it builds the lithography machines the factories need.', '2026-10-07 22:01:00'
FROM users WHERE username = 'fabnotes';

INSERT IGNORE INTO forum_topics (id, board, user_id, title, is_pinned, views, created_at, last_activity_at)
SELECT 4, 'earnings', id, 'Why would a stock fall after beating earnings?', 0, 301, '2026-10-08 10:44:00', '2026-10-08 12:15:00'
FROM users WHERE username = 'iem_darren';
INSERT IGNORE INTO forum_posts (id, topic_id, user_id, body, created_at)
SELECT 9, 4, id, 'Got an earnings question wrong because the company beat estimates and I picked "up". The answer was "down". How does that make sense?', '2026-10-08 10:44:00'
FROM users WHERE username = 'iem_darren';
INSERT IGNORE INTO forum_posts (id, topic_id, user_id, body, created_at)
SELECT 10, 4, id, 'Two usual reasons. First, the price before the report already assumed a beat, so a small beat is a disappointment. Second, the guidance for next quarter matters more than the quarter that just ended. A beat with weak guidance often gets sold.', '2026-10-08 11:10:00'
FROM users WHERE username = 'wafer_wanderer';
INSERT IGNORE INTO forum_posts (id, topic_id, user_id, body, created_at)
SELECT 11, 4, id, 'So I should read the guidance line before the headline number. Noted.', '2026-10-08 11:32:00'
FROM users WHERE username = 'iem_darren';
INSERT IGNORE INTO forum_posts (id, topic_id, user_id, body, created_at)
SELECT 12, 4, id, 'Worth re-reading the Earnings Calls lesson with this in mind before the next quiz.', '2026-10-08 12:15:00'
FROM users WHERE username = 'fabnotes';

INSERT IGNORE INTO forum_topics (id, board, user_id, title, is_pinned, views, created_at, last_activity_at)
SELECT 5, 'geopolitical', id, 'Do export controls hit ASML and NVIDIA in the same way?', 0, 189, '2026-10-06 16:27:00', '2026-10-06 17:03:00'
FROM users WHERE username = 'policy_and_pcbs';
INSERT IGNORE INTO forum_posts (id, topic_id, user_id, body, created_at)
SELECT 13, 5, id, 'Both come up in the geopolitical quizzes but the reactions look different. Is there a simple way to think about it?', '2026-10-06 16:27:00'
FROM users WHERE username = 'policy_and_pcbs';
INSERT IGNORE INTO forum_posts (id, topic_id, user_id, body, created_at)
SELECT 14, 5, id, 'They sell different things. ASML sells machines to chip factories, so a restriction blocks equipment sales to factories in the restricted country. NVIDIA sells finished chips, so a restriction blocks sales to customers there. Same kind of headline, different part of the business affected.', '2026-10-06 17:03:00'
FROM users WHERE username = 'wafer_wanderer';

INSERT IGNORE INTO forum_topics (id, board, user_id, title, is_pinned, views, created_at, last_activity_at)
SELECT 6, 'supply_chain', id, 'How far does one fab outage really spread?', 0, 97, '2026-10-02 19:40:00', '2026-10-02 20:22:00'
FROM users WHERE username = 'eee_y2_ling';
INSERT IGNORE INTO forum_posts (id, topic_id, user_id, body, created_at)
SELECT 15, 6, id, 'The lesson says one supplier problem "spreads forward". In the quiz, how do I tell whether it is a one-company story or a sector story?', '2026-10-02 19:40:00'
FROM users WHERE username = 'eee_y2_ling';
INSERT IGNORE INTO forum_posts (id, topic_id, user_id, body, created_at)
SELECT 16, 6, id, 'Ask who depends on that factory. If it makes something many companies need and there is no quick substitute, expect customers to react too. If it is one of several sources, the move tends to stay with the company that owns it.', '2026-10-02 20:22:00'
FROM users WHERE username = 'fabnotes';

INSERT IGNORE INTO forum_topics (id, board, user_id, title, is_pinned, views, created_at, last_activity_at)
SELECT 7, 'macro', id, 'What does "the cycle" mean when people talk about memory chips?', 0, 64, '2026-09-28 15:11:00', '2026-09-28 16:45:00'
FROM users WHERE username = 'chipcurious';
INSERT IGNORE INTO forum_posts (id, topic_id, user_id, body, created_at)
SELECT 17, 7, id, 'Several macro questions mention the memory cycle. Is that just prices going up and down?', '2026-09-28 15:11:00'
FROM users WHERE username = 'chipcurious';
INSERT IGNORE INTO forum_posts (id, topic_id, user_id, body, created_at)
SELECT 18, 7, id, 'Roughly, yes. When demand is strong, makers add capacity. New capacity takes a long time to build, so it often arrives after demand has cooled, prices fall, makers cut back, and the pattern repeats.', '2026-09-28 16:45:00'
FROM users WHERE username = 'policy_and_pcbs';

INSERT IGNORE INTO forum_topics (id, board, user_id, title, is_pinned, views, created_at, last_activity_at)
SELECT 8, 'game', id, 'When is it worth going short in the Trading Game?', 0, 143, '2026-10-09 22:05:00', '2026-10-09 22:31:00'
FROM users WHERE username = 'iem_darren';
INSERT IGNORE INTO forum_posts (id, topic_id, user_id, body, created_at)
SELECT 19, 8, id, 'I only ever go long and my score is mediocre. Any tips on reading the Newswire for shorts?', '2026-10-09 22:05:00'
FROM users WHERE username = 'iem_darren';
INSERT IGNORE INTO forum_posts (id, topic_id, user_id, body, created_at)
SELECT 20, 8, id, 'I pause as soon as a headline appears, decide which of the five categories it belongs to, and only then pick a side. It is play money, so it is a good place to practise being wrong.', '2026-10-09 22:31:00'
FROM users WHERE username = 'eee_y2_ling';

INSERT IGNORE INTO forum_topics (id, board, user_id, title, is_pinned, views, created_at, last_activity_at)
SELECT 9, 'corporate', id, 'Why does the buyer often drop when a merger is announced?', 0, 52, '2026-09-25 11:20:00', '2026-09-25 11:20:00'
FROM users WHERE username = 'policy_and_pcbs';
INSERT IGNORE INTO forum_posts (id, topic_id, user_id, body, created_at)
SELECT 21, 9, id, 'Saw this pattern in a corporate actions question and it surprised me.', '2026-09-25 11:20:00'
FROM users WHERE username = 'policy_and_pcbs';

