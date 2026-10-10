<?php
require_once __DIR__ . '/config/db.php';

$pdo = get_db();

$base_url = '.';
$page_title = 'Learn semiconductor market news';
$show_ticker = true;

// The three panels of the overview banner under the hero. 'detail' and 'cta'
// are what a panel reveals when it expands (hover or keyboard focus).
$overview_panels = [
    [
        'title'   => 'Learn',
        'tagline' => 'Short lessons on what moves chip stocks',
        'detail'  => 'Pick a news category and follow its lesson trail, one idea at a time.',
        'cta'     => 'Start learning',
        'href'    => 'pages/dashboard.php',
        'img'     => 'overview-learn.svg',
    ],
    [
        'title'   => 'Quiz',
        'tagline' => 'Test yourself on semiconductor news',
        'detail'  => 'Guess how a stock reacted to a headline, then see the answer, an explanation and how the sector moved.',
        'cta'     => 'Take a quiz',
        'href'    => 'pages/dashboard.php',
        'img'     => 'overview-quiz.svg',
    ],
    [
        'title'   => 'Trading Game',
        'tagline' => 'Trade the news with play money',
        'detail'  => 'Go long or short on a simulated SOXX chart with $100,000 of play money while headlines come in.',
        'cta'     => 'Play the game',
        'href'    => 'pages/game.php',
        'img'     => 'overview-game.svg',
    ],
];

// The "Why StockSense" columns: one icon, a title and a couple of lines each.
$usp_points = [
    [
        'title' => 'Built for EEE/IEM students',
        'text'  => 'The business and market side of the industry you are training to enter, not generic finance content.',
        'img'   => 'usp-students.svg',
        'tint'  => '#dfe7f3',
    ],
    [
        'title' => 'News-first, not chart-first',
        'text'  => 'Questions start from a news event, so you learn what kind of news causes what kind of move.',
        'img'   => 'usp-news.svg',
        'tint'  => '#fbe8d3',
    ],
    [
        'title' => 'Learning that sticks',
        'text'  => 'Daily streaks and a bite-sized lesson trail turn sector news into a low-pressure habit.',
        'img'   => 'usp-habit.svg',
        'tint'  => '#dcf0e4',
    ],
];
require __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <img src="assets/img/hero-landing.png" alt="StockSense capybara mascot next to a rising stock chart" class="hero-img">
    <div class="hero-copy">
        <h1>Understand <em>why</em> chip stocks move.</h1>
        <p>StockSense teaches you to read semiconductor-sector news like an analyst —
           short lessons, then real headline-to-price quizzes that show you the stock's
           actual move next to the sector average. No trading, no portfolio, just news literacy.</p>

        <div class="flow-choice">
            <a class="btn btn-primary btn-doodle doodle-arrows" href="pages/dashboard.php"<?= is_logged_in() ? '' : ' data-auth-open="login"' ?>>Enter the dashboard →</a>
        </div>
    </div>
</section>

<!-- Overview banner: three panels side by side; the hovered / focused one widens
     and shows what it leads to. Text comes from $overview_panels at the top. -->
<section class="overview-banner" aria-label="What you can do on StockSense">
    <?php foreach ($overview_panels as $panel): ?>
    <a class="overview-panel" href="<?= htmlspecialchars($panel['href']) ?>">
        <span class="overview-main">
            <img src="assets/img/<?= htmlspecialchars($panel['img']) ?>" alt="" width="120" height="96">
            <span class="overview-title"><?= htmlspecialchars($panel['title']) ?></span>
            <span class="overview-tagline"><?= htmlspecialchars($panel['tagline']) ?></span>
        </span>
        <span class="overview-more">
            <span class="overview-detail"><?= htmlspecialchars($panel['detail']) ?></span>
            <span class="overview-cta"><?= htmlspecialchars($panel['cta']) ?> →</span>
        </span>
    </a>
    <?php endforeach; ?>
</section>

<section class="about-section">
    <h2>What is StockSense?</h2>
    <p>StockSense is a free, gamified tool for learning how semiconductor-sector news moves stock
       prices — the kind of reasoning an analyst does, not a stock tip. Each category pairs a short
       lesson with quizzes built from real historical headlines, where you guess a stock's move and
       then see it compared side-by-side with the SOXX sector benchmark. Daily streaks keep the habit
       going. It never simulates real trading and holds no positions — see the
       <a href="pages/disclaimer.php">disclaimer</a> for the full picture.</p>
</section>

<section class="usp-section">
    <h2>Why StockSense</h2>
    <div class="usp-grid">
        <?php foreach ($usp_points as $usp): ?>
        <div class="usp-item">
            <span class="usp-icon" style="--usp-tint: <?= htmlspecialchars($usp['tint']) ?>">
                <img src="assets/img/<?= htmlspecialchars($usp['img']) ?>" alt="" width="64" height="64">
            </span>
            <h3><?= htmlspecialchars($usp['title']) ?></h3>
            <p><?= htmlspecialchars($usp['text']) ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Disclaimer popup — shown once per browser via localStorage (see assets/js/app.js) -->
<div id="disclaimer-modal" class="modal-overlay" hidden>
    <div class="modal-box">
        <h2>Before you start</h2>
        <p><strong>StockSense is an educational tool, not financial advice.</strong>
           Quiz answers reflect what happened historically, not a prediction of what will
           happen next. This app never simulates real trading, and leveraged or margin
           trading carries risk of loss beyond your deposit.</p>
        <div class="modal-actions">
            <a class="btn btn-secondary btn-small" href="pages/disclaimer.php">Read the full disclaimer</a>
            <button class="btn btn-primary btn-small" id="disclaimer-ack">Got it</button>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
