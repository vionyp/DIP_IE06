-- =====================================================================
-- SemiSense — database schema + starter seed data
-- Import this into a database called `semisense` via phpMyAdmin
-- (or `mysql -u root -p semisense < schema.sql` from a terminal).
-- =====================================================================

CREATE DATABASE IF NOT EXISTS semisense CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE semisense;

-- ---------------------------------------------------------------------
-- Companies tracked by the app
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS companies (
    ticker      VARCHAR(10) PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    segment     VARCHAR(50)  NOT NULL   -- foundry | fabless | equipment | idm
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Daily price history (seeded with a handful of illustrative rows;
-- the real fetch script in api/fetch_prices.php should extend this)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS price_history (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    ticker      VARCHAR(10) NOT NULL,
    trade_date  DATE NOT NULL,
    close_price DECIMAL(10,2) NOT NULL,
    pct_change  DECIMAL(6,3) NOT NULL,
    UNIQUE KEY uniq_ticker_date (ticker, trade_date),
    FOREIGN KEY (ticker) REFERENCES companies(ticker) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- News articles (real or simulated), tagged to one of the 5 categories
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS news_articles (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    ticker        VARCHAR(10) NULL,       -- NULL = sector-wide / macro story
    category      ENUM('geopolitical','earnings','macro','corporate','supply_chain') NOT NULL,
    headline      VARCHAR(255) NOT NULL,
    summary       TEXT NOT NULL,
    source        VARCHAR(50) NOT NULL,   -- finnhub | alphavantage | manual_simulated
    published_at  DATETIME NOT NULL,
    is_simulated  TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (ticker) REFERENCES companies(ticker) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Lessons — the "Education" trail content, grouped by category
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS lessons (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    category    ENUM('geopolitical','earnings','macro','corporate','supply_chain') NOT NULL,
    title       VARCHAR(150) NOT NULL,
    body_html   TEXT NOT NULL,
    sort_order  INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Quiz items — MCQ, 3 options, tied back to a news article
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS quiz_items (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    category         ENUM('geopolitical','earnings','macro','corporate','supply_chain') NOT NULL,
    news_article_id  INT NULL,
    question_text    VARCHAR(255) NOT NULL,
    option_a         VARCHAR(120) NOT NULL,
    option_b         VARCHAR(120) NOT NULL,
    option_c         VARCHAR(120) NOT NULL,
    correct_option   CHAR(1) NOT NULL,      -- 'a' | 'b' | 'c'
    explanation      TEXT NOT NULL,
    FOREIGN KEY (news_article_id) REFERENCES news_articles(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Users — v1 has no login/signup flow; a single "guest" row (id = 1)
-- is used so streaks/attempts always have somewhere to write to.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    username       VARCHAR(50) UNIQUE NOT NULL,
    password_hash  VARCHAR(255) NULL,
    preferred_flow ENUM('education_first','quiz_first') NULL,
    created_at     DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Quiz attempt log — every answer a user submits
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS quiz_attempts (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT NOT NULL,
    quiz_item_id  INT NOT NULL,
    chosen_option CHAR(1) NOT NULL,
    is_correct    TINYINT(1) NOT NULL,
    attempted_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (quiz_item_id) REFERENCES quiz_items(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Streaks — one row per user
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS streaks (
    user_id          INT PRIMARY KEY,
    current_streak   INT NOT NULL DEFAULT 0,
    longest_streak   INT NOT NULL DEFAULT 0,
    last_active_date DATE NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- SEED DATA
-- =====================================================================

INSERT INTO users (id, username, preferred_flow) VALUES
    (1, 'guest', NULL)
ON DUPLICATE KEY UPDATE username = username;

INSERT INTO streaks (user_id, current_streak, longest_streak, last_active_date) VALUES
    (1, 0, 0, NULL)
ON DUPLICATE KEY UPDATE user_id = user_id;

INSERT INTO companies (ticker, name, segment) VALUES
    ('NVDA', 'NVIDIA Corporation',              'fabless'),
    ('TSM',  'Taiwan Semiconductor Mfg. Co.',   'foundry'),
    ('ASML', 'ASML Holding',                    'equipment'),
    ('INTC', 'Intel Corporation',                'idm'),
    ('AMD',  'Advanced Micro Devices',           'fabless')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- ---- Sample price history (illustrative, replace with real fetched data) ----
INSERT INTO price_history (ticker, trade_date, close_price, pct_change) VALUES
    ('NVDA', '2025-11-19', 148.20,  2.10),
    ('NVDA', '2025-11-20', 152.80,  3.10),
    ('TSM',  '2025-10-16', 214.50,  4.60),
    ('TSM',  '2025-10-17', 210.10, -2.05),
    ('ASML', '2025-10-15', 780.00, -5.30),
    ('INTC', '2025-09-18',  23.40,  6.70),
    ('AMD',  '2025-11-04', 140.10, -3.40)
ON DUPLICATE KEY UPDATE close_price = VALUES(close_price);

-- ---- News articles (one real-style example per category, marked as seed) ----
INSERT INTO news_articles (id, ticker, category, headline, summary, source, published_at, is_simulated) VALUES
(1, 'NVDA', 'geopolitical',
 'US tightens export rules on advanced AI chips to China',
 'New export controls restrict sales of NVIDIA''s most advanced AI accelerators to Chinese customers, raising questions about how much revenue is exposed to the region.',
 'manual_simulated', '2025-11-19 08:00:00', 0),

(2, 'TSM', 'earnings',
 'TSMC beats quarterly estimates on strong AI chip demand',
 'Taiwan Semiconductor reported revenue and margins ahead of analyst expectations, citing sustained demand for advanced-node chips used in AI accelerators.',
 'manual_simulated', '2025-10-16 06:00:00', 0),

(3, NULL, 'macro',
 'Global semiconductor sales rise for the eighth straight month',
 'Industry data shows sector-wide sales climbing again, driven mainly by AI-related demand offsetting softer consumer electronics demand.',
 'manual_simulated', '2025-11-03 09:00:00', 0),

(4, 'INTC', 'corporate',
 'Intel announces new foundry partnership and cost-cutting plan',
 'Intel unveiled a restructuring plan alongside a new external foundry partnership intended to accelerate its turnaround in advanced manufacturing.',
 'manual_simulated', '2025-09-18 07:30:00', 0),

(5, 'ASML', 'supply_chain',
 'ASML flags delayed shipments after key component shortage',
 'ASML said a shortage of a critical component from a sub-supplier will delay some lithography machine shipments into the next quarter.',
 'manual_simulated', '2025-10-15 07:00:00', 0)
ON DUPLICATE KEY UPDATE headline = VALUES(headline);

-- ---- Lessons (one starter lesson per category) ----
INSERT INTO lessons (id, category, title, body_html, sort_order) VALUES
(1, 'geopolitical', 'Why politics moves chip stocks',
 '<p>Semiconductors are treated as a strategic technology by governments, not just a business product. When one country restricts the export or import of chips or chip-making tools, it can immediately cut off a company\'s access to a major market or a critical supplier.</p><p><strong>What to watch for:</strong> export control announcements, tariffs on chips or chip equipment, and diplomatic tension between countries that host major chip manufacturing (like the US, China, Taiwan, and the Netherlands).</p><p><strong>Real example:</strong> when the US tightened export rules on advanced AI chips to China, NVIDIA stock reacted because a meaningful share of its revenue was tied to Chinese customers who could no longer buy the restricted products.</p>',
 1),

(2, 'earnings', 'Reading an earnings reaction',
 '<p>An "earnings call" is when a company reports how much money it made last quarter and gives guidance on what it expects next. Stocks often move more on whether results beat or miss <em>expectations</em> than on whether the company did well in an absolute sense.</p><p><strong>What to watch for:</strong> revenue vs. analyst estimates, profit margins, and forward guidance — a company can report record profit and still fall if its guidance disappoints.</p><p><strong>Real example:</strong> TSMC beating quarterly estimates on strong AI chip demand pushed the stock up, because it confirmed that AI-driven demand was still accelerating, not just holding steady.</p>',
 1),

(3, 'macro', 'The semiconductor demand cycle',
 '<p>Chip demand moves in cycles tied to what the world is building right now: PCs and phones one era, cloud data centers and AI accelerators another. When industry-wide sales data comes in stronger or weaker than expected, it moves nearly every chip stock at once, not just one company.</p><p><strong>What to watch for:</strong> monthly/quarterly industry sales reports, inventory levels at chipmakers, and broad statements about "AI demand" or "PC demand" from multiple companies at once.</p><p><strong>Real example:</strong> a report showing global semiconductor sales rising for an eighth straight month lifted sentiment across the sector, even for companies that hadn\'t reported their own results yet.</p>',
 1),

(4, 'corporate', 'Corporate actions: M&A, restructuring, and buybacks',
 '<p>"Corporate actions" cover the big structural decisions a company makes about itself: mergers, acquisitions, spin-offs, restructuring plans, executive changes, or share buybacks. These change the company\'s future shape, not just its quarterly numbers.</p><p><strong>What to watch for:</strong> restructuring announcements, new partnerships, executive departures/hires, and buyback or dividend announcements.</p><p><strong>Real example:</strong> Intel announcing a new foundry partnership alongside a cost-cutting plan signaled a strategic pivot, which investors reacted to independently of that quarter\'s actual earnings.</p>',
 1),

(5, 'supply_chain', 'Why a supplier problem becomes your problem',
 '<p>Chipmaking is one of the most complex supply chains in the world — a single advanced chip might depend on materials, tools, and sub-components from dozens of specialized suppliers across multiple countries. A shortage or delay anywhere in that chain can ripple forward.</p><p><strong>What to watch for:</strong> fab outages, raw material shortages, shipping/logistics disruption, and any single-supplier dependency being called out in a company\'s own filings.</p><p><strong>Real example:</strong> ASML flagging delayed shipments due to a shortage of a critical component from a sub-supplier signaled that customers waiting on ASML\'s lithography machines would also see their own production timelines slip.</p>',
 1)
ON DUPLICATE KEY UPDATE title = VALUES(title);

-- ---- Quiz items (one per category, tied to the news articles above) ----
INSERT INTO quiz_items (id, category, news_article_id, question_text, option_a, option_b, option_c, correct_option, explanation) VALUES
(1, 'geopolitical', 1,
 'The US tightened export rules on advanced AI chips to China. Which direction is NVDA most likely to move?',
 'Up, because restrictions reduce competition', 'Down, because a major revenue market becomes harder to sell into', 'No meaningful reaction expected',
 'b',
 'Export restrictions cut off or shrink access to a market NVIDIA relied on for AI chip sales, so the stock typically falls on reduced near-term revenue expectations, even if the long-term technology outlook is unchanged.'),

(2, 'earnings', 2,
 'TSMC beat quarterly revenue and margin estimates, citing strong AI chip demand. Which direction is TSM most likely to move?',
 'Up, because results and guidance beat expectations', 'Down, because expectations were already high', 'Flat, since foundry results don\'t affect the stock',
 'a',
 'Stocks usually react to results relative to expectations. Beating both revenue and margin estimates, plus confirming strong AI demand, is a positive surprise that typically pushes the stock up.'),

(3, 'macro', 3,
 'Global semiconductor sales rose for an eighth straight month. What is the most likely sector-wide effect?',
 'Broad positive sentiment across most chip stocks', 'No effect, since this is old news by the time it\'s published', 'Only the top 1-2 companies react',
 'a',
 'Industry-wide sales data is a macro signal that tends to lift sentiment across the whole sector, not just one company, since it suggests the demand cycle is still expanding.'),

(4, 'corporate', 4,
 'Intel announced a new foundry partnership alongside a cost-cutting restructuring plan. What is a reasonable initial market reaction?',
 'A muted or mixed reaction as investors weigh the turnaround plan\'s credibility', 'An automatic large rally with no uncertainty', 'An automatic large decline with no uncertainty',
 'a',
 'Restructuring and turnaround announcements are inherently uncertain — investors often react cautiously at first, weighing execution risk against the potential long-term benefit, rather than reacting in one obvious direction.'),

(5, 'supply_chain', 5,
 'ASML flagged delayed shipments due to a shortage of a critical component from a sub-supplier. Which is the most likely effect on ASML customers who depend on its lithography machines?',
 'Their production timelines may also slip', 'No effect on their timelines at all', 'Their timelines automatically speed up',
 'a',
 'When a key equipment supplier like ASML delays shipments, customers waiting on that equipment for their own chip production are likely to see downstream delays too — supply chain problems tend to propagate forward.')
ON DUPLICATE KEY UPDATE question_text = VALUES(question_text);
