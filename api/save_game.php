<?php
/**
 * StockSense — save a finished trading game (called by assets/js/trading-game.js).
 *
 * POST JSON: { final_value, buy_hold_pct, days_played, trades, news_seen }
 * Returns JSON: { ok, id, rank, high_scores: [...] }
 *
 * The game runs in the browser, so these numbers come from the client.
 * They are range-checked here, which is enough for a classroom leaderboard
 * but would not stop a determined cheater.
 */
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

const SAVE_START_CASH = 100000;
const SAVE_MAX_DAYS = 252;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'POST only']);
    exit;
}

$in = json_decode(file_get_contents('php://input'), true);
if (!is_array($in)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid JSON']);
    exit;
}

$finalValue = filter_var($in['final_value'] ?? null, FILTER_VALIDATE_FLOAT);
$buyHold    = filter_var($in['buy_hold_pct'] ?? null, FILTER_VALIDATE_FLOAT);
$days       = filter_var($in['days_played'] ?? null, FILTER_VALIDATE_INT);
$trades     = filter_var($in['trades'] ?? null, FILTER_VALIDATE_INT);
$newsSeen   = filter_var($in['news_seen'] ?? null, FILTER_VALIDATE_INT);

$valid = $finalValue !== false && $finalValue >= 0 && $finalValue <= SAVE_START_CASH * 100
    && $buyHold !== false && abs($buyHold) <= 10000
    && $days !== false && $days >= 1 && $days <= SAVE_MAX_DAYS
    && $trades !== false && $trades >= 0 && $trades <= 100000
    && $newsSeen !== false && $newsSeen >= 0 && $newsSeen <= 1000;

if (!$valid) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Values out of range']);
    exit;
}

$returnPct = ($finalValue - SAVE_START_CASH) / SAVE_START_CASH * 100;

try {
    $pdo = get_db();

    $stmt = $pdo->prepare('
        INSERT INTO game_results
            (user_id, start_cash, final_value, return_pct, buy_hold_pct, days_played, trades, news_seen)
        VALUES (:uid, :start, :final, :ret, :bh, :days, :trades, :news)
    ');
    $stmt->execute([
        'uid'    => CURRENT_USER_ID,
        'start'  => SAVE_START_CASH,
        'final'  => round($finalValue, 2),
        'ret'    => round($returnPct, 2),
        'bh'     => round($buyHold, 2),
        'days'   => $days,
        'trades' => $trades,
        'news'   => $newsSeen,
    ]);
    $id = (int)$pdo->lastInsertId();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM game_results WHERE user_id = :uid AND final_value > :final');
    $stmt->execute(['uid' => CURRENT_USER_ID, 'final' => round($finalValue, 2)]);
    $rank = (int)$stmt->fetchColumn() + 1;

    $stmt = $pdo->prepare('
        SELECT id, final_value, return_pct, buy_hold_pct, days_played, trades, played_at
        FROM game_results
        WHERE user_id = :uid
        ORDER BY final_value DESC
        LIMIT 5
    ');
    $stmt->execute(['uid' => CURRENT_USER_ID]);

    echo json_encode(['ok' => true, 'id' => $id, 'rank' => $rank, 'high_scores' => $stmt->fetchAll()]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Could not save — is sql/migrations/002_trading_game.sql imported?']);
}
