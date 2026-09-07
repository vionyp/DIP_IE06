<?php
$base_url = '..';
$page_title = 'Disclaimer';
require __DIR__ . '/../includes/header.php';
?>

<section class="disclaimer-page">
    <h1>Disclaimer</h1>

    <p><strong>StockSense is an educational tool, not a trading platform or a source of financial advice.</strong>
       Nothing in this app — including quiz questions, lesson content, or price/news data — is a
       recommendation to buy, sell, or hold any security.</p>

    <h2>This is not investment advice</h2>
    <p>Quiz "correct" answers represent the most commonly expected market reaction to a given headline,
       based on historical patterns or illustrative scenarios. Real markets are influenced by many
       factors at once, and past reactions to similar news do not guarantee future outcomes.</p>

    <h2>No real or simulated money changes hands</h2>
    <p>StockSense does not simulate a trading account, portfolio, or order execution. There are no
       positions, no profit-and-loss tracking, and no leverage anywhere in this app.</p>

    <h2>Reading the stock-vs-sector comparison</h2>
    <p>After each quiz question, you'll see how a stock's price move compared to the semiconductor
       sector benchmark (SOXX) over the same period. This is shown purely to help you judge whether
       a move was specific to that company or shared across the whole sector — it is
       <strong>an observation about the past, not a trading signal</strong>, and should never be
       read as a recommendation to buy or sell anything.</p>

    <h2>A note on leverage and risk</h2>
    <p>If you go on to use real trading platforms, understand that <strong>leveraged or margin trading
       can result in losses that exceed your original deposit.</strong> Only trade with capital you can
       afford to lose, and consider seeking independent, licensed financial advice before making any
       real investment decisions.</p>

    <h2>Data sources</h2>
    <p>Price data referenced in this app originates from Yahoo Finance. News content originates from
       Finnhub and Alpha Vantage, and may be delayed, incomplete, or — where explicitly labeled
       "simulated" — fictional and written for teaching purposes only.</p>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
