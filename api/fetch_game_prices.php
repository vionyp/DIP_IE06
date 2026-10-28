<?php
/**
 * StockSense — refresh the real SOXX history used by the Trading Game.
 *
 * Adds any new trading days to game_soxx_daily (open/high/low/close) from
 * Yahoo Finance. Like fetch_prices.php, run it by hand or on a schedule,
 * never from a page load:
 *
 *   php api/fetch_game_prices.php
 *   (or C:\xampp\php\php.exe api\fetch_game_prices.php on Windows)
 *
 * Requires sql/migrations/010_game_real_soxx.sql to have been imported.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Run this from the command line: php api/fetch_game_prices.php\n");
}

require_once __DIR__ . '/../config/db.php';

$pdo = get_db();

$last = $pdo->query('SELECT MAX(trade_date) FROM game_soxx_daily')->fetchColumn();
$from = $last ? strtotime($last . ' -5 days') : strtotime('2022-01-01');
$url = 'https://query1.finance.yahoo.com/v8/finance/chart/SOXX?interval=1d'
     . '&period1=' . $from . '&period2=' . time();

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_USERAGENT      => 'Mozilla/5.0 (StockSense educational app)',
]);
$raw = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

if ($raw === false || $err) {
    exit("FAILED: $err\n");
}

$data = json_decode($raw, true);
$result = $data['chart']['result'][0] ?? null;
$quote = $result['indicators']['quote'][0] ?? null;
if (!$result || !$quote || empty($result['timestamp'])) {
    exit("FAILED: unexpected response shape (Yahoo endpoint may have changed)\n");
}

$insert = $pdo->prepare('
    INSERT INTO game_soxx_daily (trade_date, open_price, high_price, low_price, close_price)
    VALUES (:d, :o, :h, :l, :c)
    ON DUPLICATE KEY UPDATE open_price = VALUES(open_price), high_price = VALUES(high_price),
                            low_price = VALUES(low_price), close_price = VALUES(close_price)
');

$rows = 0;
$tz = new DateTimeZone('America/New_York');
foreach ($result['timestamp'] as $i => $ts) {
    $o = $quote['open'][$i] ?? null;
    $h = $quote['high'][$i] ?? null;
    $l = $quote['low'][$i] ?? null;
    $c = $quote['close'][$i] ?? null;
    if ($o === null || $h === null || $l === null || $c === null) {
        continue;
    }
    $date = (new DateTime('@' . $ts))->setTimezone($tz)->format('Y-m-d');
    $insert->execute([
        'd' => $date,
        'o' => round($o, 2),
        'h' => round(max($h, $o, $c), 2),
        'l' => round(min($l, $o, $c), 2),
        'c' => round($c, 2),
    ]);
    $rows++;
}

echo "OK: $rows SOXX days written (latest " . $pdo->query('SELECT MAX(trade_date) FROM game_soxx_daily')->fetchColumn() . ")\n";
