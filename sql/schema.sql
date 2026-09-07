-- =====================================================================
-- StockSense — database schema + starter seed data
-- Import via phpMyAdmin, or: mysql -u root -p < sql/schema.sql
-- =====================================================================

CREATE DATABASE IF NOT EXISTS stocksense CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE stocksense;

-- ---------------------------------------------------------------------
-- Companies tracked by the app, plus the sector benchmark (SOXX ETF),
-- stored the same way (segment = 'benchmark') so its price history
-- can be queried with the exact same code path as any stock.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS companies (
    ticker      VARCHAR(10) PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    segment     VARCHAR(50)  NOT NULL,   -- foundry | fabless | equipment | idm | memory | benchmark
    is_benchmark TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Daily price history — used for both individual stocks and SOXX.
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
-- News articles — real or simulated, tagged to one of the 5 categories.
-- Carries the extra fields called out in the charter's minimum schema
-- (source_url, notes, graph) plus the stock/sector percentage moves
-- used for the stock-vs-sector comparison shown after each quiz answer.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS news_articles (
    id                   INT AUTO_INCREMENT PRIMARY KEY,
    ticker               VARCHAR(10) NULL,
    category             ENUM('geopolitical','earnings','macro','corporate','supply_chain') NOT NULL,
    headline             VARCHAR(255) NOT NULL,
    summary              TEXT NOT NULL,
    source               VARCHAR(50) NOT NULL,     -- finnhub | alphavantage | manual_simulated
    source_url           VARCHAR(500) NULL,
    published_at         DATETIME NOT NULL,
    is_simulated         TINYINT(1) NOT NULL DEFAULT 0,
    price_change_stock   DECIMAL(6,3) NULL,        -- stock's % move around this event
    price_change_sector  DECIMAL(6,3) NULL,        -- SOXX's % move over the same window
    notes                TEXT NULL,                -- internal authoring notes
    graph_path            VARCHAR(255) NULL,        -- optional path to a saved chart image
    FOREIGN KEY (ticker) REFERENCES companies(ticker) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Lessons — the "Education" trail content, grouped by category.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS lessons (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    category    ENUM('geopolitical','earnings','macro','corporate','supply_chain') NOT NULL,
    title       VARCHAR(150) NOT NULL,
    body_html   TEXT NOT NULL,
    sort_order  INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Quiz items — a POOL per category. quiz.php pulls a random subset each
-- time, so redoing a category serves different questions. Two flavors,
-- both allowed in the same pool:
--   'real_news' -> tied to a news_articles row, answer = what actually happened
--   'concept'   -> tests lesson theory directly, no news_article_id needed
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS quiz_items (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    category         ENUM('geopolitical','earnings','macro','corporate','supply_chain') NOT NULL,
    question_type    ENUM('real_news','concept') NOT NULL DEFAULT 'real_news',
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
    created_at     DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Per-category learning-path preference. Unlike a single global choice,
-- the charter's flow lets the user pick quiz-first or lesson-first
-- separately for EACH category, so this is keyed on (user, category).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS user_category_prefs (
    user_id        INT NOT NULL,
    category       ENUM('geopolitical','earnings','macro','corporate','supply_chain') NOT NULL,
    preferred_flow ENUM('education_first','quiz_first') NOT NULL,
    PRIMARY KEY (user_id, category),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Quiz attempt log — every answer a user submits.
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
-- Streaks — one row per user.
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

INSERT INTO users (id, username) VALUES (1, 'guest')
ON DUPLICATE KEY UPDATE username = username;

INSERT INTO streaks (user_id, current_streak, longest_streak, last_active_date) VALUES
    (1, 0, 0, NULL)
ON DUPLICATE KEY UPDATE user_id = user_id;

-- ---- Companies: fixed watchlist per the charter, plus SOXX as the sector benchmark ----
INSERT INTO companies (ticker, name, segment, is_benchmark) VALUES
    ('NVDA',  'NVIDIA Corporation',            'fabless',   0),
    ('TSM',   'Taiwan Semiconductor Mfg. Co.', 'foundry',   0),
    ('ASML',  'ASML Holding',                  'equipment', 0),
    ('SSNLF', 'Samsung Electronics',           'idm',       0),
    ('INTC',  'Intel Corporation',             'idm',       0),
    ('MU',    'Micron Technology',             'memory',    0),
    ('SOXX',  'iShares Semiconductor ETF',     'benchmark', 1)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- ---- Sample price history, stock + matching SOXX benchmark rows for the same dates ----
INSERT INTO price_history (ticker, trade_date, close_price, pct_change) VALUES
    ('NVDA', '2025-11-19', 148.20,  2.10),
    ('NVDA', '2025-11-20', 152.80,  3.10),
    ('SOXX', '2025-11-19', 268.40,  1.20),
    ('SOXX', '2025-11-20', 271.10,  1.00),

    ('TSM',  '2025-10-16', 214.50,  4.60),
    ('TSM',  '2025-10-17', 210.10, -2.05),
    ('SOXX', '2025-10-16', 255.30,  2.80),
    ('SOXX', '2025-10-17', 253.90, -0.55),

    ('ASML', '2025-10-15', 780.00, -5.30),
    ('SOXX', '2025-10-15', 248.10, -1.40),

    ('INTC', '2025-09-18',  23.40,  6.70),
    ('SOXX', '2025-09-18', 240.00,  0.90),

    ('MU',   '2025-09-25', 108.60,  5.40),
    ('SOXX', '2025-09-25', 246.70,  1.50),

    ('SSNLF','2025-08-12',  32.10, -1.80),
    ('SOXX', '2025-08-12', 235.20, -0.30)
ON DUPLICATE KEY UPDATE close_price = VALUES(close_price);

-- ---- News articles, one real-style example per category, with stock-vs-sector deltas ----
INSERT INTO news_articles
    (id, ticker, category, headline, summary, source, source_url, published_at, is_simulated, price_change_stock, price_change_sector, notes) VALUES
(1, 'NVDA', 'geopolitical',
 'US tightens export rules on advanced AI chips to China',
 'New export controls restrict sales of NVIDIA''s most advanced AI accelerators to Chinese customers, raising questions about how much revenue is exposed to the region.',
 'manual_simulated', NULL, '2025-11-19 08:00:00', 0, 3.10, 1.00,
 'Stock rose alongside the sector both days; illustrates that even negative-sounding headlines can be outweighed by broader sector momentum.'),

(2, 'TSM', 'earnings',
 'TSMC beats quarterly estimates on strong AI chip demand',
 'Taiwan Semiconductor reported revenue and margins ahead of analyst expectations, citing sustained demand for advanced-node chips used in AI accelerators.',
 'manual_simulated', NULL, '2025-10-16 06:00:00', 0, -2.05, -0.55,
 'TSM fell more than the sector the next day despite the earnings beat.'),

(3, NULL, 'macro',
 'Global semiconductor sales rise for the eighth straight month',
 'Industry data shows sector-wide sales climbing again, driven mainly by AI-related demand offsetting softer consumer electronics demand.',
 'manual_simulated', NULL, '2025-11-03 09:00:00', 0, NULL, NULL,
 'Sector-wide story with no single-stock anchor; used as a concept-only quiz item.'),

(4, 'INTC', 'corporate',
 'Intel announces new foundry partnership and cost-cutting plan',
 'Intel unveiled a restructuring plan alongside a new external foundry partnership intended to accelerate its turnaround in advanced manufacturing.',
 'manual_simulated', NULL, '2025-09-18 07:30:00', 0, 6.70, 0.90,
 'Intel far outperformed the sector on this news.'),

(5, 'ASML', 'supply_chain',
 'ASML flags delayed shipments after key component shortage',
 'ASML said a shortage of a critical component from a sub-supplier will delay some lithography machine shipments into the next quarter.',
 'manual_simulated', NULL, '2025-10-15 07:00:00', 0, -5.30, -1.40,
 'ASML dropped well beyond the sector average.'),

(6, 'MU', 'earnings',
 'Micron guidance beats expectations on memory pricing recovery',
 'Micron raised its forward guidance, citing a recovering pricing environment for memory chips used in both PCs and data centers.',
 'manual_simulated', NULL, '2025-09-25 06:30:00', 0, 5.40, 1.50,
 'Second earnings-category real_news item so the pool has more than one question.')
ON DUPLICATE KEY UPDATE headline = VALUES(headline);

-- ---- Lessons (one starter lesson per category) ----
INSERT INTO lessons (id, category, title, body_html, sort_order) VALUES
(1, 'geopolitical', 'Why politics moves chip stocks',
 '<p>Semiconductors are treated as a strategic technology by governments, not just a business product. When one country restricts the export or import of chips or chip-making tools, it can immediately cut off a company\'s access to a major market or a critical supplier.</p><p><strong>What to watch for:</strong> export control announcements, tariffs on chips or chip equipment, and diplomatic tension between countries that host major chip manufacturing (like the US, China, Taiwan, and the Netherlands).</p><p><strong>Judging the reaction:</strong> always compare the stock\'s move to the sector benchmark (SOXX) over the same period — a stock that falls alongside the whole sector tells a different story than one that falls alone.</p>',
 1),

(2, 'earnings', 'Reading an earnings reaction',
 '<p>An "earnings call" is when a company reports how much money it made last quarter and gives guidance on what it expects next. Stocks often move more on whether results beat or miss <em>expectations</em> than on whether the company did well in an absolute sense.</p><p><strong>What to watch for:</strong> revenue vs. analyst estimates, profit margins, and forward guidance — a company can report record profit and still fall if its guidance disappoints.</p><p><strong>Judging the reaction:</strong> a beat that still underperforms the sector benchmark suggests the market cared more about something else that day — check the sector move before assuming the earnings were the whole story.</p>',
 1),

(3, 'macro', 'The semiconductor demand cycle',
 '<p>Chip demand moves in cycles tied to what the world is building right now: PCs and phones one era, cloud data centers and AI accelerators another. When industry-wide sales data comes in stronger or weaker than expected, it moves nearly every chip stock at once, not just one company.</p><p><strong>What to watch for:</strong> monthly/quarterly industry sales reports, inventory levels at chipmakers, and broad statements about "AI demand" or "PC demand" from multiple companies at once.</p><p><strong>Judging the reaction:</strong> macro news tends to move individual stocks and the SOXX benchmark by similar amounts — if you see a big gap between a stock and the sector on macro-only news, look for a stock-specific reason too.</p>',
 1),

(4, 'corporate', 'Corporate actions: M&A, restructuring, and buybacks',
 '<p>"Corporate actions" cover the big structural decisions a company makes about itself: mergers, acquisitions, spin-offs, restructuring plans, executive changes, or share buybacks. These change the company\'s future shape, not just its quarterly numbers.</p><p><strong>What to watch for:</strong> restructuring announcements, new partnerships, executive departures/hires, and buyback or dividend announcements.</p><p><strong>Judging the reaction:</strong> corporate actions are almost always stock-specific — expect a bigger gap between the stock\'s move and the sector benchmark than you would from macro or industry-wide news.</p>',
 1),

(5, 'supply_chain', 'Why a supplier problem becomes your problem',
 '<p>Chipmaking is one of the most complex supply chains in the world — a single advanced chip might depend on materials, tools, and sub-components from dozens of specialized suppliers across multiple countries. A shortage or delay anywhere in that chain can ripple forward.</p><p><strong>What to watch for:</strong> fab outages, raw material shortages, shipping/logistics disruption, and any single-supplier dependency being called out in a company\'s own filings.</p><p><strong>Judging the reaction:</strong> a supply-chain problem specific to one company should show up as a stock move well beyond the sector benchmark — if the whole sector drops equally, the cause is probably broader than one company\'s supply chain.</p>',
 1)
ON DUPLICATE KEY UPDATE title = VALUES(title);

-- ---- Quiz item POOL: 2+ questions per category, mixing real_news and concept types ----
INSERT INTO quiz_items (id, category, question_type, news_article_id, question_text, option_a, option_b, option_c, correct_option, explanation) VALUES

(1, 'geopolitical', 'real_news', 1,
 'The US tightened export rules on advanced AI chips to China. Which direction did NVDA move over the following days?',
 'Up, roughly in line with the broader sector', 'Down sharply, well below the sector', 'Flat, no reaction at all',
 'a',
 'NVDA rose about 3.1% while the SOXX sector benchmark rose about 1.0% over the same period — the stock moved with (and somewhat ahead of) the sector, not against it.'),

(2, 'geopolitical', 'concept', NULL,
 'Which of these is the clearest example of a geopolitical news event for a semiconductor stock?',
 'A country announcing new export controls on chip technology', 'A company beating its quarterly earnings estimate', 'A factory reporting a temporary equipment breakdown',
 'a',
 'Export controls, tariffs, and diplomatic tension between chip-manufacturing nations are the defining feature of the geopolitical category.'),

(3, 'earnings', 'real_news', 2,
 'TSMC beat quarterly revenue and margin estimates, citing strong AI chip demand. How did TSM perform against the sector benchmark the next day?',
 'It underperformed the sector, falling more than SOXX', 'It outperformed the sector by a wide margin', 'It matched the sector exactly',
 'a',
 'TSM fell about 2.05% while SOXX fell only about 0.55% — TSM underperformed its own sector despite beating estimates.'),

(4, 'earnings', 'real_news', 6,
 'Micron raised its forward guidance on a memory-pricing recovery. How did MU move relative to the sector?',
 'It outperformed the sector benchmark', 'It underperformed the sector benchmark', 'It moved in the opposite direction of the sector',
 'a',
 'MU rose about 5.4% versus roughly 1.5% for SOXX — a clear outperformance, consistent with a company-specific guidance beat.'),

(5, 'earnings', 'concept', NULL,
 'Why can a stock fall even after reporting record profit?',
 'Because the market reacts to results relative to expectations and guidance, not just absolute profit', 'Because earnings reports never affect stock prices', 'Because record profit always causes an automatic sell-off',
 'a',
 'Markets price in expectations ahead of the report. If guidance disappoints or margins miss forecasts, a stock can fall even on record headline profit.'),

(6, 'macro', 'real_news', 3,
 'Global semiconductor sales rose for an eighth straight month. What is the most likely sector-wide effect?',
 'Broad positive sentiment across most chip stocks, moving with the sector benchmark', 'No effect, since this is old news by the time it''s published', 'Only the single largest company reacts',
 'a',
 'Industry-wide sales data is a macro signal that tends to lift sentiment across the whole sector at once, which is why it shows up in the SOXX benchmark rather than just one stock.'),

(7, 'macro', 'concept', NULL,
 'If a stock and the SOXX sector benchmark move by almost the same percentage on the same day, what does that suggest about the news driving it?',
 'The news is more likely sector-wide (macro) than stock-specific', 'The news is definitely a corporate action', 'The news had no real effect on the market',
 'a',
 'When a stock tracks the benchmark closely, the driver is usually something affecting the whole industry rather than something unique to that one company.'),

(8, 'corporate', 'real_news', 4,
 'Intel announced a new foundry partnership alongside a cost-cutting restructuring plan. How did INTC move compared to the sector?',
 'It significantly outperformed the sector benchmark', 'It significantly underperformed the sector benchmark', 'It moved identically to the sector',
 'a',
 'INTC rose about 6.7% versus roughly 0.9% for SOXX — a large stock-specific outperformance, typical of a company-level strategic announcement.'),

(9, 'corporate', 'concept', NULL,
 'Which of these counts as a "corporate action" rather than a macro or earnings event?',
 'A company announcing a restructuring plan and new executive hires', 'An industry-wide report on global chip sales', 'A country imposing new tariffs on chip imports',
 'a',
 'Corporate actions are decisions a company makes about its own structure — restructuring, M&A, executive changes, buybacks — distinct from sector-wide macro data or a scheduled earnings report.'),

(10, 'supply_chain', 'real_news', 5,
 'ASML flagged delayed shipments due to a shortage of a critical component from a sub-supplier. How did ASML move relative to the sector?',
 'It fell well beyond the sector benchmark''s decline', 'It fell by about the same amount as the sector', 'It rose while the sector fell',
 'a',
 'ASML fell about 5.3% while SOXX fell only about 1.4% — a much larger drop than the sector, consistent with a problem specific to ASML''s own supply chain.'),

(11, 'supply_chain', 'concept', NULL,
 'Why might a shortage at one supplier affect multiple companies'' stock prices?',
 'Because many chipmakers depend on the same specialized suppliers, so a delay can ripple forward to their customers', 'Because supply chain issues only ever affect the supplier itself', 'Because supply chain news never affects stock prices',
 'a',
 'Chipmaking supply chains are highly interconnected — a shortage at one critical supplier can delay production for every company that depends on it.')
ON DUPLICATE KEY UPDATE question_text = VALUES(question_text);
