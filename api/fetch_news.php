<?php
/**
 * StockSense — news fetch job.
 *
 * Pulls raw headlines from Finnhub (primary) and Alpha Vantage
 * (supplementary — free tier is heavily rate-limited to ~25 requests/day,
 * so this script only calls it for one ticker per run).
 *
 * IMPORTANT: this script only inserts raw headlines into news_articles
 * with a NULL category. Someone still needs to review each headline,
 * assign it to one of the 5 fixed categories, and (if it's going to be
 * used in a quiz) write the matching quiz_items row by hand — this
 * script does not auto-generate quiz questions.
 *
 * Requires config/api_keys.php (copy config/api_keys.php.example first).
 */

require_once __DIR__ . '/../config/db.php';

$keysFile = __DIR__ . '/../config/api_keys.php';
if (!file_exists($keysFile)) {
    die("Missing config/api_keys.php — copy config/api_keys.php.example and add your keys first.\n");
}
require_once $keysFile;

$pdo = get_db();
$tickers = $pdo->query('SELECT ticker FROM companies')->fetchAll(PDO::FETCH_COLUMN);

$insertRaw = $pdo->prepare('
    INSERT INTO news_articles (ticker, category, headline, summary, source, published_at, is_simulated)
    VALUES (:ticker, :category, :headline, :summary, :source, :published_at, 0)
');

// A placeholder category is required by the schema; "macro" is used as a
// safe default for unreviewed items — re-tag these manually afterward.
const UNREVIEWED_CATEGORY = 'macro';

$log = [];

// ---------------------------------------------------------------
// Finnhub — company news, one call per ticker
// ---------------------------------------------------------------
foreach ($tickers as $ticker) {
    $from = (new DateTime('-7 days'))->format('Y-m-d');
    $to   = (new DateTime())->format('Y-m-d');
    $url  = "https://finnhub.io/api/v1/company-news?symbol={$ticker}&from={$from}&to={$to}&token=" . FINNHUB_API_KEY;

    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
    $raw = curl_exec($ch);
    curl_close($ch);

    $items = json_decode($raw, true);
    if (!is_array($items)) {
        $log[] = "Finnhub {$ticker}: FAILED or empty response";
        continue;
    }

    $count = 0;
    foreach (array_slice($items, 0, 5) as $article) {
        if (empty($article['headline'])) {
            continue;
        }
        $insertRaw->execute([
            'ticker'       => $ticker,
            'category'     => UNREVIEWED_CATEGORY,
            'headline'     => mb_substr($article['headline'], 0, 255),
            'summary'      => $article['summary'] ?? '',
            'source'       => 'finnhub',
            'published_at' => (new DateTime('@' . ($article['datetime'] ?? time())))->format('Y-m-d H:i:s'),
        ]);
        $count++;
    }
    $log[] = "Finnhub {$ticker}: inserted {$count} raw headlines (needs manual category review)";
}

// ---------------------------------------------------------------
// Alpha Vantage — sector/news sentiment, ONE call this run
// (free tier is ~25 requests/day total — spend it carefully)
// ---------------------------------------------------------------
$avTicker = $tickers[0] ?? null;
if ($avTicker) {
    $url = "https://www.alphavantage.co/query?function=NEWS_SENTIMENT&tickers={$avTicker}&apikey=" . ALPHA_VANTAGE_API_KEY;
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
    $raw = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($raw, true);
    $feed = $data['feed'] ?? [];

    $count = 0;
    foreach (array_slice($feed, 0, 5) as $article) {
        if (empty($article['title'])) {
            continue;
        }
        $insertRaw->execute([
            'ticker'       => $avTicker,
            'category'     => UNREVIEWED_CATEGORY,
            'headline'     => mb_substr($article['title'], 0, 255),
            'summary'      => $article['summary'] ?? '',
            'source'       => 'alphavantage',
            'published_at' => date('Y-m-d H:i:s'),
        ]);
        $count++;
    }
    $log[] = "Alpha Vantage {$avTicker}: inserted {$count} raw headlines (needs manual category review)";
} else {
    $log[] = 'Alpha Vantage: skipped, no tickers in companies table';
}

header('Content-Type: text/plain');
echo implode("\n", $log) . "\n";
