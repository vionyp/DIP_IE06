<?php
/**
 * StockSense — price fetch job (Route 3: historical data).
 *
 * Run this manually or on a schedule (e.g. Windows Task Scheduler with
 * XAMPP's php.exe, or a cron job). It should NEVER be called directly
 * from a page load — front-end pages only ever read from price_history.
 *
 * Usage:
 *   php api/fetch_prices.php
 *   (or visit http://localhost/stocksense/api/fetch_prices.php in a
 *    browser while developing locally — remove that access once the
 *    site is exposed beyond localhost)
 */

require_once __DIR__ . '/../config/db.php';

$pdo = get_db();

$tickers = $pdo->query('SELECT ticker FROM companies')->fetchAll(PDO::FETCH_COLUMN);

$insert = $pdo->prepare('
    INSERT INTO price_history (ticker, trade_date, close_price, pct_change)
    VALUES (:ticker, :date, :close, :pct)
    ON DUPLICATE KEY UPDATE close_price = VALUES(close_price), pct_change = VALUES(pct_change)
');

$results = [];

foreach ($tickers as $ticker) {
    // Yahoo Finance's unofficial "chart" endpoint — no key required, but
    // rate-limited and can change without notice. range=1mo/interval=1d
    // keeps each request small.
    $url = "https://query1.finance.yahoo.com/v8/finance/chart/{$ticker}?range=1mo&interval=1d";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (StockSense educational app)',
    ]);
    $raw = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($raw === false || $err) {
        $results[$ticker] = "FAILED: $err";
        continue;
    }

    $data = json_decode($raw, true);
    $timestamps = $data['chart']['result'][0]['timestamp'] ?? null;
    $closes     = $data['chart']['result'][0]['indicators']['quote'][0]['close'] ?? null;

    if (!$timestamps || !$closes) {
        $results[$ticker] = 'FAILED: unexpected response shape (Yahoo endpoint may have changed)';
        continue;
    }

    $rowsInserted = 0;
    $prevClose = null;

    foreach ($timestamps as $i => $ts) {
        $close = $closes[$i] ?? null;
        if ($close === null) {
            continue;
        }
        $date = (new DateTime("@$ts"))->format('Y-m-d');
        $pct = $prevClose ? round((($close - $prevClose) / $prevClose) * 100, 3) : 0;

        $insert->execute([
            'ticker' => $ticker,
            'date'   => $date,
            'close'  => round($close, 2),
            'pct'    => $pct,
        ]);
        $prevClose = $close;
        $rowsInserted++;
    }

    $results[$ticker] = "OK: {$rowsInserted} rows upserted";
}

header('Content-Type: text/plain');
foreach ($results as $ticker => $status) {
    echo "{$ticker}: {$status}\n";
}
