# StockSense

An educational web app that teaches students how semiconductor-sector news
affects stock prices, through short lessons paired with headline-to-price
quizzes that compare a stock's actual move to the sector benchmark (SOXX).
Not a trading simulator, not financial advice — see `pages/disclaimer.php`.

Stack: **XAMPP (Apache + PHP + MySQL) + plain HTML/CSS/JS**, no framework, no build step.

---

## 1. Folder structure

Place the entire `stocksense` folder inside your XAMPP `htdocs` directory, so the path looks like:

```text
xampp/
└── htdocs/
    └── stocksense/
        ├── index.php
        ├── api/
        │   ├── fetch_prices.php
        │   ├── fetch_news.php
        │   └── save_game.php
        ├── assets/
        │   ├── css/style.css
        │   ├── css/game.css
        │   ├── js/app.js
        │   ├── js/trading-game.js
        │   └── img/
        ├── config/
        │   ├── db.php
        │   ├── categories.php
        │   └── api_keys.php.example
        ├── includes/
        │   ├── header.php
        │   ├── footer.php
        │   ├── auth.php
        │   ├── auth_card.php
        │   └── streak_helper.php
        ├── pages/
        │   ├── login.php
        │   ├── logout.php
        │   ├── education.php
        │   ├── quiz.php
        │   ├── dashboard.php
        │   ├── disclaimer.php
        │   └── game.php
        └── sql/
            └── schema.sql
```

On Windows this typically means: `C:\xampp\htdocs\stocksense\`
On Mac: `/Applications/XAMPP/htdocs/stocksense/`

---

## 2. Database setup

1. Start Apache **and** MySQL from the XAMPP control panel.
2. Open `http://localhost/phpMyAdmin`.
3. Go to **Import**, choose `sql/schema.sql`, **set "Character set of the file"
   to `utf8mb4`** before running it, and run it.
   - This creates the `stocksense` database, 9 tables, and seeds it with:
     6 watchlist companies (NVDA, TSM, ASML, Samsung, Intel, Micron) plus the
     SOXX sector benchmark, sample price history for both, 6 news articles
     with stock-vs-sector price deltas, 5 lessons, and an 11-question quiz
     pool spread across the 5 categories.
   - Importing from the command line instead? Use
     `mysql --default-character-set=utf8mb4 -u root -p stocksense < sql/schema.sql`
     — **not** a plain `mysql -u root -p stocksense < schema.sql`. Without
     `--default-character-set=utf8mb4` the import relies on your client's
     default charset, and text like "SOXX —" can come out garbled even
     though `sql/schema.sql` itself is correct UTF-8.
4. Check `config/db.php` — the defaults (`root` / empty password / `127.0.0.1`)
   match a stock XAMPP install. If you set a MySQL root password, or created
   a dedicated `stocksense` DB user, update the constants at the top of that file.
5. **Existing database only** (imported `sql/schema.sql` before login was added):
   select the `stocksense` database → **Import** → `sql/migrations/003_login_answers.sql`.
   This creates the `answers` table. Fresh imports of `sql/schema.sql` already have it.

### Accounts and saved answers

The dashboard, lessons, quizzes and trading game need an account: visiting
them while logged out sends you to `pages/login.php`, which has a **Log in** and a
**Register** tab in one card (`includes/auth_card.php`). On the public pages the
same card opens as a popup. Session handling lives in `includes/auth.php`.

- `users` stores `username` and `password_hash` — the output of PHP's
  `password_hash()`, never the plain password. Login checks it with `password_verify()`.
- `answers` stores each user's current answer per question
  (`user_id`, `question_id`, `answer`, `is_correct`, `answered_at`), with
  `(user_id, question_id)` as the primary key. Answering the same question again
  **updates** that row (`INSERT ... ON DUPLICATE KEY UPDATE` in `pages/quiz.php`)
  instead of adding a new one. `quiz_attempts` still logs every attempt, which is
  what the dashboard scores are built from.

---

## 3. Running it

Visit `http://localhost/stocksense/`. You'll land on the hero page with a
one-time disclaimer popup, then "Enter the dashboard" — register or log in
first — to see the 5 category cards, your streak, and your per-category score.

### The updated flow

```text
Landing (disclaimer popup)
    → Dashboard (category cards + scores)
        → pick a category → choose "lesson first" or "quiz first"
            → lesson trail  ⇄  quiz (3 random questions from that
              category's pool each attempt)
                → each answer reveals the correct option, an
                  explanation, and — for real-news questions — a
                  stock-vs-sector bar comparison
```

The lesson-first/quiz-first choice is remembered **per category**, not
globally — a user might read the lesson for "Earnings" but jump straight to
quizzes for "Supply Chain." The dashboard shows a "Continue" link once a
category's path has been chosen, or a lesson-first/quiz-first prompt for a
category the user hasn't started yet.

---

## 4. Trading game (`pages/game.php`)

A trading game in the style of *The Invisible Hand*. Players get $100,000 of
play money and trade a simulated **SOXX** (semiconductor ETF) chart for up to one
trading year, while anonymized headlines appear in a Newswire on the left.

**Setup (one time, after `sql/schema.sql`):** in phpMyAdmin, select the
`stocksense` database → **Import** → `sql/migrations/002_trading_game.sql`
(character set `utf8mb4`). This creates `game_news` (153 headlines) and
`game_results` (high scores). The file is safe to re-import.

**Playing:** the chart moves forward on its own (pause, 1x/2x/4x speed), with
Daily, Weekly and Yearly views. Use **Long**, **Short** and **Close position**,
or the keyboard shortcuts Space (pause), L (long), S (short) and C (close).
The game ends at day 252, or earlier if you click **End game**. The end screen
compares your return with simply holding SOXX, reviews every headline you saw,
and saves the result to the high-score table.

**How prices move** (`assets/js/trading-game.js`):
- Between headlines, the price follows a random walk.
- Each `game_news` row has an `impact` from −3 (very bearish) to +3 (very bullish)
  for the whole sector, judged from the lessons.
- The headline's category decides *how* the price reacts (`IMPACT_PROFILES`):
  geopolitical news is a sudden shock that partly reverses, earnings news gaps
  and then drifts, macro news hits the whole sector and keeps rippling, supply-chain
  news starts small and builds, and corporate news is a small move that fades.
- Speed, volatility, news frequency and impact size are constants at the top
  of that file.

**News data:** `game_news` holds the same articles as `news_articles`, but real
company names are swapped for fictional ones (e.g. Micron → Company ABC), and
product names, people and years are made generic. The full name key is in the
comments of the migration file. To add a headline, insert a row with a
`category`, `impact` and one-line `explanation`. Set `is_active = 0` to hide one.

---

## 5. Forum (`pages/forum.php`)

A question-and-answer forum grouped into boards: Beginner Questions, the five
news categories, and Trading Game. Anyone can read it; asking, replying and
liking need an account.

**Setup (one time, after `sql/schema.sql`):** in phpMyAdmin, select the
`stocksense` database → **Import** → `sql/migrations/004_forum.sql`
(character set `utf8mb4`). This creates `forum_topics`, `forum_posts` and
`forum_likes`, and adds a few **sample** discussions so the forum is not empty.
The sample accounts have no password, so nobody can log in as them; the comment
in the migration file shows how to remove them. The file is safe to re-import.

- **Boards** are the fixed list in `config/forum.php` (not a table). Add an
  entry there to add a board.
- **Moderators** are the usernames in `FORUM_MODERATORS` in `config/forum.php`
  (default: `admin`). A moderator can pin topics and delete any topic or reply;
  everyone else can delete only what they wrote. Register the `admin` account
  yourself on a new install, before anyone else takes the name.
- A topic's question is its first row in `forum_posts`; deleting a topic removes
  its replies and likes with it.

---

## 6. Keeping data fresh (optional, not required to demo the core loop)

The site **never** calls Yahoo Finance / Finnhub / Alpha Vantage directly from a
page load — it only ever reads from MySQL. To refresh the data:

```bash
php api/fetch_prices.php
php api/fetch_news.php
```

`fetch_prices.php` reads every ticker from the `companies` table automatically
— including SOXX — so no code changes are needed when the watchlist changes.

For news:
1. Copy `config/api_keys.php.example` to `config/api_keys.php`.
2. Add your Finnhub and Alpha Vantage keys.
3. Run `php api/fetch_news.php`. This drops raw headlines into `news_articles`
   with a placeholder category — **someone still needs to review each one,
   assign the correct category, calculate/enter its `price_change_stock` and
   `price_change_sector` values, and write a matching row in `quiz_items`
   by hand** (or with AI assistance). The fetch script does not auto-generate
   quiz questions or price deltas.

Alpha Vantage's free tier is capped around 25 requests/day — the script only
uses one call per run for that reason.

---

## 7. What's already built vs. what's next

**Working end-to-end right now:**
- Landing page with a one-time disclaimer popup
- Dashboard: streak banner, 5 category cards, per-category score, and the
  lesson-first/quiz-first choice (remembered per category)
- 5 category lesson pages
- 5 category quizzes, each drawing 3 random questions from a pool that mixes
  real-news questions and pure-concept questions
- Stock-vs-sector comparison bars shown after every real-news question
- Daily streak tracking (increments once per day, resets on a missed day)
- Disclaimer page, including the stock-vs-sector framing note
- Trading game: simulated SOXX chart, anonymized Newswire, long/short trading, high scores
- User accounts: register, log in, log out; quiz answers, streaks and game scores are saved per user

**Deliberately left for you to extend:**
- A larger historical dataset — the charter's target is 20+ years of
  news/price/sector-benchmark data across all 5 categories; the seed data
  here is a small illustrative starting pool, not that full dataset
- More quiz questions per category (schema already supports any number —
  add rows to `quiz_items` and, for real-news items, to `news_articles`)
- Reminder notifications for the streak feature
- Light entrance/scroll animations beyond the current mascot float/pop
  effects, if more motion is wanted
- The stretch `/company/{ticker}` real-time chart page
