<?php
/**
 * StockSense — Trading Game.
 *
 * A Invisible-Hand-style market game: a simulated SOXX (semiconductor ETF)
 * chart ticks forward on its own, anonymized news pops up on the left,
 * and the player goes long or short with $100,000 of play money.
 *
 * The price is a random walk most of the time; each news item pushes it
 * up or down according to its `impact` and category (see
 * assets/js/trading-game.js). All game logic runs in the browser — this
 * page only loads the news pool and the high-score table from MySQL.
 * Finished games are saved through api/save_game.php.
 *
 * Requires sql/migrations/002_trading_game.sql to have been imported.
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/categories.php';

const GAME_START_CASH = 100000;

$pdo = get_db();
$news = [];
$highScores = [];
$setupError = null;

try {
    $news = $pdo->query('
        SELECT id, category, headline, summary, impact, explanation
        FROM game_news
        WHERE is_active = 1
    ')->fetchAll();

    $stmt = $pdo->prepare('
        SELECT final_value, return_pct, buy_hold_pct, days_played, trades, played_at
        FROM game_results
        WHERE user_id = :uid
        ORDER BY final_value DESC
        LIMIT 5
    ');
    $stmt->execute(['uid' => CURRENT_USER_ID]);
    $highScores = $stmt->fetchAll();
} catch (PDOException $e) {
    $setupError = 'The trading game tables are missing. Import sql/migrations/002_trading_game.sql '
        . 'in phpMyAdmin (select the stocksense database → Import, character set utf8mb4), then reload this page.';
}

foreach ($news as &$n) {
    $n['id'] = (int)$n['id'];
    $n['impact'] = (int)$n['impact'];
}
unset($n);

$categoriesForJs = [];
foreach (CATEGORIES as $key => $cat) {
    $categoriesForJs[$key] = ['label' => $cat['label'], 'color' => $cat['color'], 'icon' => $cat['icon']];
}

$gameConfig = [
    'startCash'  => GAME_START_CASH,
    'news'       => $news,
    'categories' => $categoriesForJs,
    'saveUrl'    => '../api/save_game.php',
    'imgBase'    => '../assets/img/',
];

$base_url = '..';
$page_title = 'Trading Game';
$extra_css = ['assets/css/game.css'];
$main_class = 'site-main-wide';
require __DIR__ . '/../includes/header.php';
?>

<?php if ($setupError !== null): ?>
    <section class="game-setup-error">
        <h1>Trading Game</h1>
        <p><?= htmlspecialchars($setupError) ?></p>
    </section>
<?php elseif (count($news) === 0): ?>
    <section class="game-setup-error">
        <h1>Trading Game</h1>
        <p>No active news in the <code>game_news</code> table yet, so there's nothing to trade on.
           Re-import <code>sql/migrations/002_trading_game.sql</code>.</p>
    </section>
<?php else: ?>

<section class="game" id="game">
    <!-- Top bar: ticker, price, clock, controls -->
    <div class="game-topbar">
        <div class="ticker-block">
            <span class="ticker-symbol">SOXX</span>
            <span class="ticker-name">Semiconductor ETF · simulated</span>
        </div>
        <div class="price-block">
            <span class="price-now" id="price-now">—</span>
            <span class="price-change" id="price-change">—</span>
        </div>
        <div class="clock-block">
            <span class="clock-day" id="clock-day">Day 1</span>
            <span class="clock-time" id="clock-time">9:30 AM</span>
            <div class="day-progress"><div class="day-progress-fill" id="day-progress"></div></div>
        </div>
        <div class="controls-block">
            <button class="ctl-btn" id="btn-play" title="Pause / play (Space)" aria-label="Play"></button>
            <div class="speed-group" role="group" aria-label="Game speed">
                <button class="ctl-btn speed-btn active" data-speed="1">1x</button>
                <button class="ctl-btn speed-btn" data-speed="2">2x</button>
                <button class="ctl-btn speed-btn" data-speed="4">4x</button>
            </div>
            <button class="btn btn-small end-btn" id="btn-end">End game</button>
        </div>
    </div>

    <div class="game-body">
        <!-- Left: news feed -->
        <aside class="news-panel">
            <div class="panel-head">
                <h2>Newswire</h2>
                <label class="news-pause-toggle">
                    <input type="checkbox" id="pause-on-news" checked> Pause on news
                </label>
            </div>
            <div class="news-feed" id="news-feed">
                <p class="news-empty" id="news-empty">Markets are open. Headlines will appear here as they break.</p>
            </div>
        </aside>

        <!-- Centre: chart + trading -->
        <div class="chart-panel">
            <div class="chart-head">
                <div class="view-tabs" role="tablist" aria-label="Chart range">
                    <button class="view-tab" data-view="day" role="tab">Daily</button>
                    <button class="view-tab active" data-view="week" role="tab">Weekly</button>
                    <button class="view-tab" data-view="year" role="tab">Yearly</button>
                </div>
                <span class="chart-legend">
                    <span class="legend-dot legend-news"></span> news
                    <span class="legend-tri legend-long">▲</span> long
                    <span class="legend-tri legend-short">▼</span> short
                </span>
            </div>
            <div class="chart-wrap">
                <canvas id="price-chart" aria-label="SOXX price chart"></canvas>
                <div class="chart-tooltip" id="chart-tooltip" hidden></div>
            </div>

            <div class="trade-panel">
                <div class="trade-amount">
                    <label for="trade-amount">Trade size ($)</label>
                    <input type="number" id="trade-amount" min="0" step="1000" value="25000">
                    <div class="amount-presets">
                        <button class="preset-btn" data-pct="10">10%</button>
                        <button class="preset-btn" data-pct="25">25%</button>
                        <button class="preset-btn" data-pct="50">50%</button>
                        <button class="preset-btn" data-pct="100">Max</button>
                    </div>
                    <p class="trade-hint" id="trade-hint">≈ 0 shares</p>
                </div>
                <div class="trade-buttons">
                    <button class="trade-btn trade-long" id="btn-long" title="Shortcut: L">
                        <span class="trade-btn-label">▲ Long</span>
                        <span class="trade-btn-sub">profit if price rises</span>
                    </button>
                    <button class="trade-btn trade-short" id="btn-short" title="Shortcut: S">
                        <span class="trade-btn-label">▼ Short</span>
                        <span class="trade-btn-sub">profit if price falls</span>
                    </button>
                    <button class="trade-btn trade-close" id="btn-close" title="Shortcut: C">
                        <span class="trade-btn-label">Close position</span>
                    </button>
                </div>
                <p class="trade-message" id="trade-message" aria-live="polite"></p>
            </div>
        </div>

        <!-- Right: account -->
        <aside class="account-panel">
            <div class="panel-head"><h2>Your account</h2></div>

            <div class="stat stat-big">
                <span class="stat-label">Net worth</span>
                <span class="stat-value" id="acct-equity">$100,000.00</span>
                <span class="stat-sub" id="acct-return">+0.00% since start</span>
            </div>

            <div class="stat">
                <span class="stat-label">Cash available to trade</span>
                <span class="stat-value" id="acct-cash">$100,000.00</span>
                <div class="meter"><div class="meter-fill" id="acct-cash-meter"></div></div>
            </div>

            <div class="position-card" id="position-card">
                <span class="stat-label">Your position</span>
                <span class="position-side" id="pos-side">No position</span>
                <dl class="position-grid">
                    <dt>Shares</dt><dd id="pos-shares">0</dd>
                    <dt>Avg. entry</dt><dd id="pos-avg">—</dd>
                    <dt>Value</dt><dd id="pos-value">$0.00</dd>
                </dl>
                <div class="pnl" id="pos-pnl-box">
                    <span class="pnl-label">Open profit / loss</span>
                    <span class="pnl-value" id="pos-pnl">$0.00</span>
                </div>
            </div>

            <div class="stat">
                <span class="stat-label">Locked-in profit / loss</span>
                <span class="stat-value stat-mid" id="acct-realized">$0.00</span>
            </div>

            <ol class="trade-log" id="trade-log"></ol>
        </aside>
    </div>

    <!-- Start screen -->
    <div class="game-overlay" id="start-screen">
        <div class="overlay-box">
            <img class="overlay-mascot" src="../assets/img/mascot-base.png" alt="">
            <h1>The Trading Floor</h1>
            <p>You have <strong>$100,000</strong> of play money and one year on the trading floor.
               The chart is a simulated <strong>SOXX</strong>, a basket of semiconductor stocks.
               It wanders randomly, but <strong>news moves it</strong> in the ways you learned in the lessons.</p>
            <ul class="how-to">
                <li><strong>Read the Newswire</strong> on the left. Company names are disguised (e.g. "Company ABC"), so trust your lessons, not your memory.</li>
                <li><strong>Long</strong> if you think the sector will rise, <strong>Short</strong> if you think it will fall. <strong>Close</strong> to lock in your profit or loss.</li>
                <li>Not every headline moves prices the same way. Some hit instantly, others ripple for days.</li>
                <li>End the game whenever you like. At the end you'll see how each headline really moved the market.</li>
            </ul>
            <p class="shortcut-note">Shortcuts: <kbd>Space</kbd> pause · <kbd>L</kbd> long · <kbd>S</kbd> short · <kbd>C</kbd> close</p>
            <button class="btn btn-primary" id="btn-start">Open the market</button>

            <?php if ($highScores): ?>
                <div class="highscores">
                    <h3>Best games</h3>
                    <table>
                        <thead><tr><th>#</th><th>Final value</th><th>Return</th><th>Days</th></tr></thead>
                        <tbody>
                        <?php foreach ($highScores as $i => $hs): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td>$<?= number_format((float)$hs['final_value'], 2) ?></td>
                                <td class="<?= $hs['return_pct'] >= 0 ? 'up' : 'down' ?>"><?= $hs['return_pct'] >= 0 ? '+' : '' ?><?= number_format((float)$hs['return_pct'], 2) ?>%</td>
                                <td><?= (int)$hs['days_played'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- End screen (filled in by JS) -->
    <div class="game-overlay" id="end-screen" hidden>
        <div class="overlay-box overlay-wide" id="end-content"></div>
    </div>
</section>

<script>
window.STOCKSENSE_GAME = <?= json_encode($gameConfig, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="../assets/js/trading-game.js"></script>

<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
