# SemiSense

An educational web app that teaches students how semiconductor-sector news
affects stock prices, through short lessons paired with headline-to-price-move
quizzes. Not a trading simulator, not financial advice — see `pages/disclaimer.php`.

Stack: **XAMPP (Apache + PHP + MySQL) + plain HTML/CSS/JS**, no framework, no build step.

---

## 1. Folder structure

Place the entire `semisense` folder inside your XAMPP `htdocs` directory, so the path looks like:

```text
xampp/
└── htdocs/
    └── semisense/
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

On Windows this typically means: `C:\xampp\htdocs\semisense\`
On Mac: `/Applications/XAMPP/htdocs/semisense/`

---

## 2. Database setup

1. Start Apache **and** MySQL from the XAMPP control panel.
2. Open `http://localhost/phpMyAdmin`.
3. Go to **Import**, choose `sql/schema.sql`, and run it.
   - This creates the `semisense` database, all 8 tables, and seeds it with
     5 companies, sample price history, one news article + lesson + quiz
     question per category, a `guest` user, and an empty streak row.
4. Check `config/db.php` — the defaults (`root` / empty password / `127.0.0.1`)
   match a stock XAMPP install. If you set a MySQL root password, or created
   a dedicated `semisense` DB user, update the constants at the top of that file.

---

## 3. Running it

Visit:

```text
http://localhost/semisense/
```

You should see the landing page with the mascot, the "learn first / quiz first"
choice, and the 5 category cards.

---

## 4. Keeping data fresh (optional, not required to demo the core loop)

The site **never** calls Yahoo Finance / Finnhub / Alpha Vantage directly from a
page load — it only ever reads from MySQL. To refresh the data:

```bash
php api/fetch_prices.php
php api/fetch_news.php
```

For news:
1. Copy `config/api_keys.php.example` to `config/api_keys.php`.
2. Add your Finnhub and Alpha Vantage keys.
3. Run `php api/fetch_news.php`. This drops raw headlines into `news_articles`
   with a placeholder category — **someone still needs to review each one,
   assign the correct category, and write a matching row in `quiz_items`
   by hand** (or with AI assistance). The fetch script does not auto-generate
   quiz questions.

Alpha Vantage's free tier is capped around 25 requests/day — the script only
uses one call per run for that reason.

---

## 5. What's already built vs. what's next

**Working end-to-end right now:**
- Landing page with learn-first/quiz-first choice (saved per user)
- 5 category lesson pages, pulling from the `lessons` table
- 5 category quizzes (1 question each, seeded) with scoring, explanations, and
  a correct/incorrect mascot reaction
- Daily streak tracking (increments once per day, resets on a missed day)
- Dashboard showing streak + per-category accuracy
- Disclaimer page

**Deliberately left for you to extend:**
- More lessons/quiz questions per category (the schema and pages already support any number — just add rows to `lessons` and `quiz_items`)
- User accounts/login (currently a single `guest` user, id = 1, by design — see `config/db.php`)
- The Route 2 "ML-supported simulation" mode and the stretch `/company/{ticker}` real-time chart page, both intentionally out of scope for v1
- Scheduling `fetch_prices.php` / `fetch_news.php` (Windows Task Scheduler or cron)
