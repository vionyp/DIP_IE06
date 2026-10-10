<?php
/**
 * Scrolling ticker strip. Included by header.php when a page sets
 * $show_ticker = true. Settings live in config/ticker.php.
 *
 * Shows the newest real headlines from news_articles, one per source
 * article (several rows can share a source_url), each linking to its source.
 * The list is printed twice so the CSS animation (see "Ticker" in
 * assets/css/style.css) can loop with no gap; the second copy is hidden
 * from screen readers and skipped by the Tab key.
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/ticker.php';

// Fetch a few extra rows so there are still enough after dropping repeats.
$stmt = get_db()->prepare("
    SELECT ticker, headline, source_url, published_at
    FROM news_articles
    WHERE is_simulated = 0 AND source <> 'manual_simulated'
    ORDER BY published_at DESC, id DESC
    LIMIT :lim
");
$stmt->bindValue('lim', TICKER_NEWS_LIMIT * 4, PDO::PARAM_INT);
$stmt->execute();

$ticker_news = [];
$seen_urls = [];
foreach ($stmt->fetchAll() as $row) {
    $url = (string)$row['source_url'];
    if ($url !== '' && isset($seen_urls[$url])) {
        continue;
    }
    $seen_urls[$url] = true;
    // Only link out to ordinary web addresses.
    $row['link'] = preg_match('#^https?://#i', $url) ? $url : null;
    $ticker_news[] = $row;
    if (count($ticker_news) === TICKER_NEWS_LIMIT) {
        break;
    }
}

$ticker_count = $ticker_news ? count($ticker_news) : count(TICKER_FALLBACK_ITEMS);
?>
<div class="ticker" role="region" aria-label="<?= $ticker_news ? 'Recent semiconductor headlines' : 'StockSense at a glance' ?>"
     style="--ticker-duration: <?= $ticker_count * TICKER_SECONDS_PER_ITEM ?>s">
    <div class="ticker-fade">
        <div class="ticker-track">
            <?php for ($copy = 0; $copy < 2; $copy++): ?>
            <ul class="ticker-group"<?= $copy === 1 ? ' aria-hidden="true"' : '' ?>>
                <?php foreach ($ticker_news as $news): ?>
                <li>
                    <?php if ($news['ticker'] !== null): ?>
                    <span class="ticker-symbol"><?= htmlspecialchars($news['ticker']) ?></span>
                    <?php endif; ?>
                    <?php if ($news['link'] !== null): ?>
                    <a href="<?= htmlspecialchars($news['link']) ?>" target="_blank" rel="noopener noreferrer"<?= $copy === 1 ? ' tabindex="-1"' : '' ?>><?= htmlspecialchars($news['headline']) ?></a>
                    <?php else: ?>
                    <span><?= htmlspecialchars($news['headline']) ?></span>
                    <?php endif; ?>
                    <time class="ticker-date" datetime="<?= htmlspecialchars(date('Y-m-d', strtotime($news['published_at']))) ?>"><?= htmlspecialchars(date('j M Y', strtotime($news['published_at']))) ?></time>
                </li>
                <?php endforeach; ?>
                <?php if (!$ticker_news): ?>
                <?php foreach (TICKER_FALLBACK_ITEMS as $item): ?>
                <li><?= htmlspecialchars($item) ?></li>
                <?php endforeach; ?>
                <?php endif; ?>
            </ul>
            <?php endfor; ?>
        </div>
    </div>
</div>
