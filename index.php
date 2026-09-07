<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/categories.php';

$pdo = get_db();

// Record the learn-first / quiz-first choice if the user just clicked one.
if (isset($_GET['flow']) && in_array($_GET['flow'], ['education_first', 'quiz_first'], true)) {
    $stmt = $pdo->prepare('UPDATE users SET preferred_flow = :flow WHERE id = :uid');
    $stmt->execute(['flow' => $_GET['flow'], 'uid' => CURRENT_USER_ID]);
}

$stmt = $pdo->prepare('SELECT preferred_flow FROM users WHERE id = :uid');
$stmt->execute(['uid' => CURRENT_USER_ID]);
$preferred_flow = $stmt->fetchColumn();

$base_url = '.';
$page_title = 'Learn semiconductor market news';
require __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <img src="assets/img/hero-landing.png" alt="SemiSense mascot next to a rising stock chart" class="hero-img">
    <div class="hero-copy">
        <h1>Understand <em>why</em> chip stocks move.</h1>
        <p>SemiSense teaches you to read semiconductor-sector news like an analyst —
           short lessons, then real (and clearly-labeled simulated) headline-to-price-move quizzes.
           No trading, no portfolio, just news literacy.</p>

        <?php if (!$preferred_flow): ?>
        <div class="flow-choice">
            <p class="flow-question">How do you want to start?</p>
            <a class="btn btn-primary" href="pages/education.php?category=geopolitical">Learn first</a>
            <a class="btn btn-secondary" href="pages/quiz.php?category=geopolitical">Jump to quizzes</a>
        </div>
        <?php else: ?>
        <div class="flow-choice">
            <a class="btn btn-primary" href="pages/education.php?category=geopolitical">Continue learning</a>
            <a class="btn btn-secondary" href="pages/dashboard.php">View your dashboard</a>
        </div>
        <?php endif; ?>
    </div>
</section>

<section class="category-grid">
    <h2>Pick a category</h2>
    <div class="cards">
        <?php foreach (CATEGORIES as $key => $cat): ?>
        <div class="category-card" style="--cat-color: <?= htmlspecialchars($cat['color']) ?>">
            <img src="assets/img/<?= htmlspecialchars($cat['icon']) ?>" alt="<?= htmlspecialchars($cat['label']) ?> icon">
            <h3><?= htmlspecialchars($cat['label']) ?></h3>
            <p><?= htmlspecialchars($cat['blurb']) ?></p>
            <div class="card-actions">
                <a class="btn btn-small btn-primary" href="pages/education.php?category=<?= urlencode($key) ?>">Learn</a>
                <a class="btn btn-small btn-secondary" href="pages/quiz.php?category=<?= urlencode($key) ?>">Quiz</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
