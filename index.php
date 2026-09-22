<?php
require_once __DIR__ . '/config/db.php';

$pdo = get_db();

$base_url = '.';
$page_title = 'Learn semiconductor market news';
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
            <a class="btn btn-primary" href="pages/dashboard.php">Enter the dashboard →</a>
        </div>
    </div>
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

<section class="about-section">
    <h2>Why StockSense</h2>
    <p>Most students learning about semiconductor stocks fall into one of two gaps. Generic
       investing apps teach broad market basics with no sector focus, so an EEE or IEM student
       gets no context for the industry they're actually heading into. Professional platforms like
       Bloomberg Terminal go the other way: built for institutional traders, assuming knowledge
       most students don't have yet. StockSense sits in the space between these, built specifically
       for students who already care about semiconductors because it's their field, not because
       they're day traders.</p>
    <p>Second, most tools that do exist teach price and trend reading in isolation: candlesticks,
       moving averages, chart patterns. That teaches you to read a chart, not to understand why it
       moved. StockSense flips the emphasis to cause and effect. Every quiz question is anchored to
       a real news event (export controls, an earnings call, a fab outage) and asks the student to
       reason about how that specific category of news tends to move semiconductor stocks. The
       stock-vs-sector comparison then shows whether the reaction was company-specific or
       sector-wide, which is the kind of judgment an analyst actually needs, not just chart
       literacy.</p>
    <p>In short, StockSense's USP rests on three things:</p>
    <ol>
        <li><strong>Built for EEE/IEM students specifically.</strong> It teaches the business and
            market side of the industry these students are training to enter, so it's
            career-relevant rather than generic finance content.</li>
        <li><strong>News-first, not chart-first.</strong> Instead of teaching students to read
            price movement, it teaches them to reason about what kind of news causes what kind of
            movement, which is a transferable analytical skill rather than pattern memorization.</li>
        <li><strong>Design that makes the learning stick.</strong> A Duolingo-style streak and
            progress system paired with a Babypips-style course trail turns a normally dry topic
            (sector news analysis) into a low-pressure, bite-sized habit, so students actually
            finish it instead of bouncing off a wall of financial jargon.</li>
    </ol>
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
