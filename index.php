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
        'step'    => 'Step 1',
        'title'   => 'Learn',
        'tagline' => 'Short lessons on what moves chip stocks',
        'detail'  => 'Pick a news category and follow its lesson trail, one idea at a time.',
        'cta'     => 'Start learning',
        'href'    => 'pages/dashboard.php',
        'img'     => 'overview-learn.svg',
    ],
    [
        'step'    => 'Step 2',
        'title'   => 'Quiz',
        'tagline' => 'Test yourself on real headlines',
        'detail'  => 'Guess how a stock reacted to a headline, then see the answer, an explanation and how the sector moved.',
        'cta'     => 'Take a quiz',
        'href'    => 'pages/dashboard.php',
        'img'     => 'overview-quiz.svg',
    ],
    [
        'step'    => 'Step 3',
        'title'   => 'Trading Game',
        'tagline' => 'Practise with play money',
        'detail'  => 'Replay a real year of the SOXX chip index with $100,000 of play money. Real headlines break as they happened. Go long or short and see if you beat the market.',
        'cta'     => 'Play the game',
        'href'    => 'pages/game.php',
        'img'     => 'overview-game.svg',
    ],
];

// "How StockSense helps you": what a student can do after using it.
$outcomes = [
    [
        'title' => 'Read a headline like an analyst',
        'text'  => 'Tell whether news hits one company or the whole chip sector, and roughly how hard.',
    ],
    [
        'title' => 'Explain surprising moves',
        'text'  => 'Understand why a stock can fall after "good" news, such as an earnings beat with weak guidance.',
    ],
    [
        'title' => 'Connect the big forces',
        'text'  => 'Link export bans, interest rates, supply-chain shocks and company decisions to chip stock prices.',
    ],
    [
        'title' => 'Practise without risk',
        'text'  => 'React to real headlines from real market history with play money, and learn from every result.',
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
    <img src="assets/img/animations/mascot-hero-3d-v2.gif" alt="StockSense capybara mascot next to a rising stock chart" class="hero-img mascot-animated">
    <div class="hero-copy">
        <h1>Understand <em>why</em> chip stocks move.</h1>
        <p class="hero-lead">StockSense teaches engineering students how news moves the stocks of the
           semiconductor industry they are training to join, in a few minutes a day.</p>
        <ul class="hero-points">
            <li>Short lessons on the five kinds of news that move chip stocks</li>
            <li>Quizzes built from real headlines, with the real price move revealed</li>
            <li>A trading game that replays real market history with play money</li>
        </ul>

        <div class="flow-choice">
            <a class="btn btn-primary btn-doodle doodle-arrows" href="pages/dashboard.php"<?= is_logged_in() ? '' : ' data-auth-open="login"' ?>>Start learning →</a>
            <a class="btn btn-secondary" href="pages/game.php"<?= is_logged_in() ? '' : ' data-auth-open="login"' ?>>Try the trading game</a>
        </div>
    </div>
</section>

<!-- Mission statement -->
<section class="mission" aria-labelledby="mission-heading">
    <p class="mission-eyebrow" id="mission-heading">Our mission</p>
    <p class="mission-statement">To help engineering students understand the business side of semiconductors,
       so that when a headline breaks, they know why chip stocks move and what it means for the industry.</p>
    <p class="mission-sub">Free, bite-sized and built on real headlines. Educational only: no real money and no stock tips.</p>
</section>

<h2 class="section-heading">How StockSense works</h2>
<p class="section-sub">Three steps, from first idea to practice. Hover over or tap a step to see more.</p>

<!-- Overview banner: three panels side by side; the hovered / focused one widens
     and shows what it leads to. Text comes from $overview_panels at the top. -->
<section class="overview-banner" aria-label="What you can do on StockSense">
    <?php foreach ($overview_panels as $panel): ?>
    <a class="overview-panel" href="<?= htmlspecialchars($panel['href']) ?>">
        <span class="overview-main">
            <img src="assets/img/<?= htmlspecialchars($panel['img']) ?>" alt="" width="160" height="128">
            <span class="overview-step"><?= htmlspecialchars($panel['step']) ?></span>
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

<!-- What the student will be able to do — the concrete benefit of using StockSense -->
<section class="outcomes" aria-labelledby="outcomes-heading">
    <h2 class="section-heading" id="outcomes-heading">How StockSense helps you</h2>
    <p class="section-sub">After a few weeks of lessons, quizzes and games, you'll be able to:</p>
    <div class="outcomes-grid">
        <?php foreach ($outcomes as $i => $o): ?>
        <div class="outcome">
            <span class="outcome-num"><?= $i + 1 ?></span>
            <div>
                <h3><?= htmlspecialchars($o['title']) ?></h3>
                <p><?= htmlspecialchars($o['text']) ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="usp-section">
    <h2>Why StockSense</h2>
    <div class="usp-grid">
        <?php foreach ($usp_points as $usp): ?>
        <div class="usp-item">
            <span class="usp-icon" style="--usp-tint: <?= htmlspecialchars($usp['tint']) ?>">
                <img src="assets/img/<?= htmlspecialchars($usp['img']) ?>" alt="" width="92" height="92">
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
           happen next. The trading game uses play money only and never real trades, and
           leveraged or margin trading carries risk of loss beyond your deposit.</p>
        <div class="modal-actions">
            <a class="btn btn-secondary btn-small" href="pages/disclaimer.php">Read the full disclaimer</a>
            <button class="btn btn-primary btn-small" id="disclaimer-ack">Got it</button>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
