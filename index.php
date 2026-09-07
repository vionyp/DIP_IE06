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
