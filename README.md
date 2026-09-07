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
        │   └── fetch_news.php
        ├── assets/
        │   ├── css/style.css
        │   ├── js/app.js
        │   └── img/
        ├── config/
        │   ├── db.php
        │   ├── categories.php
        │   └── api_keys.php.example
        ├── includes/
        │   ├── header.php
        │   ├── footer.php
        │   └── streak_helper.php
        ├── pages/
        │   ├── education.php
        │   ├── quiz.php
        │   ├── dashboard.php
        │   └── disclaimer.php
        └── sql/
            └── schema.sql
```

On Windows this typically means: `C:\xampp\htdocs\stocksense\`
On Mac: `/Applications/XAMPP/htdocs/stocksense/`

---

## 2. Database setup

1. Start Apache **and** MySQL from the XAMPP control panel.
2. Open `http://localhost/phpMyAdmin`.
3. Go to **Import**, choose `sql/schema.sql`, and run it.
   - This creates the `stocksense` database, 9 tables, and seeds it with:
     6 watchlist companies (NVDA, TSM, ASML, Samsung, Intel, Micron) plus the
     SOXX sector benchmark, sample price history for both, 6 news articles
     with stock-vs-sector price deltas, 5 lessons, and an 11-question quiz
     pool spread across the 5 categories.
4. Check `config/db.php` — the defaults (`root` / empty password / `127.0.0.1`)
   match a stock XAMPP install. If you set a MySQL root password, or created
   a dedicated `stocksense` DB user, update the constants at the top of that file.

---

## 3. Running it

Visit `http://localhost/stocksense/`. You'll land on the hero page with a
one-time disclaimer popup, then "Enter the dashboard" to see the 5 category
cards, your streak, and your per-category score.

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

## 4. Keeping data fresh (optional, not required to demo the core loop)

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

## 5. What's already built vs. what's next

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

**Deliberately left for you to extend:**
- A larger historical dataset — the charter's target is 20+ years of
  news/price/sector-benchmark data across all 5 categories; the seed data
  here is a small illustrative starting pool, not that full dataset
- More quiz questions per category (schema already supports any number —
  add rows to `quiz_items` and, for real-news items, to `news_articles`)
- User accounts/login (currently a single `guest` user, id = 1, by design —
  see `config/db.php`)
- Reminder notifications for the streak feature
- Light entrance/scroll animations beyond the current mascot float/pop
  effects, if more motion is wanted
- The stretch `/company/{ticker}` real-time chart page
