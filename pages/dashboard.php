<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/categories.php';

$pdo = get_db();

$stmt = $pdo->prepare('SELECT current_streak, longest_streak, last_active_date FROM streaks WHERE user_id = :uid');
$stmt->execute(['uid' => CURRENT_USER_ID]);
$streak = $stmt->fetch() ?: ['current_streak' => 0, 'longest_streak' => 0, 'last_active_date' => null];

$stmt = $pdo->prepare('
    SELECT qi.category,
           COUNT(*) AS attempts,
           SUM(qa.is_correct) AS correct
    FROM quiz_attempts qa
    JOIN quiz_items qi ON qi.id = qa.quiz_item_id
    WHERE qa.user_id = :uid
    GROUP BY qi.category
');
$stmt->execute(['uid' => CURRENT_USER_ID]);
$byCategory = [];
foreach ($stmt->fetchAll() as $row) {
    $byCategory[$row['category']] = $row;
}

$base_url = '..';
$page_title = 'Your dashboard';
require __DIR__ . '/../includes/header.php';
?>

<section class="dashboard">
    <h1>Your progress</h1>

    <div class="streak-banner">
        <span class="streak-flame">🔥</span>
        <div>
            <p class="streak-current"><?= (int)$streak['current_streak'] ?> day streak</p>
            <p class="streak-longest">Longest streak: <?= (int)$streak['longest_streak'] ?> day<?= (int)$streak['longest_streak'] === 1 ? '' : 's' ?></p>
        </div>
    </div>

    <h2>Accuracy by category</h2>
    <div class="dashboard-grid">
        <?php foreach (CATEGORIES as $key => $cat): ?>
            <?php
                $stat = $byCategory[$key] ?? null;
                $attempts = $stat['attempts'] ?? 0;
                $correct  = $stat['correct'] ?? 0;
                $pct = $attempts > 0 ? round(($correct / $attempts) * 100) : null;
            ?>
            <div class="dashboard-card" style="--cat-color: <?= htmlspecialchars($cat['color']) ?>">
                <img src="../assets/img/<?= htmlspecialchars($cat['icon']) ?>" alt="">
                <h3><?= htmlspecialchars($cat['label']) ?></h3>
                <?php if ($pct === null): ?>
                    <p class="no-data">No quizzes taken yet.</p>
                    <a class="btn btn-small btn-primary" href="quiz.php?category=<?= urlencode($key) ?>">Take the quiz</a>
                <?php else: ?>
                    <p class="score"><?= $pct ?>% correct <span class="attempts">(<?= $correct ?>/<?= $attempts ?>)</span></p>
                    <a class="btn btn-small btn-secondary" href="quiz.php?category=<?= urlencode($key) ?>">Practice again</a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
