<?php
/**
 * StockSense — the scrolling ticker strip under the navbar.
 *
 * The strip shows the newest real headlines from the news_articles table
 * (the same table api/fetch_news.php adds to), so it updates by itself when
 * new articles are stored. It is rendered by includes/ticker.php on any page
 * that sets $show_ticker = true before including includes/header.php.
 */

// How many headlines scroll past, newest first.
const TICKER_NEWS_LIMIT = 10;

// Scroll speed: seconds of scrolling per headline. Larger is slower.
const TICKER_SECONDS_PER_ITEM = 9;

// Shown instead when news_articles has no headlines to show.
const TICKER_FALLBACK_ITEMS = [
    '5 news categories, from export controls to supply chains',
    '6 chip makers tracked against the SOXX benchmark',
    'Daily streaks to keep the habit going',
    'An educational tool, not financial advice',
];
