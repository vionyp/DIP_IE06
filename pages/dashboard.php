<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/categories.php';

$pdo = get_db();

$stmt = $pdo->prepare('SELECT current_streak, longest_streak FROM streaks WHERE user_id = :uid');
$stmt->execute(['uid' => CURRENT_USER_ID]);
$streak = $stmt->fetch() ?: ['current_streak' => 0, 'longest_streak' => 0];

$stmt = $pdo->prepare('
    SELECT qi.category, COUNT(*) AS attempts, SUM(qa.is_correct) AS correct
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

$stmt = $pdo->prepare('SELECT category, preferred_flow FROM user_category_prefs WHERE user_id = :uid');
$stmt->execute(['uid' => CURRENT_USER_ID]);
$prefs = [];
foreach ($stmt->fetchAll() as $row) {
    $prefs[$row['category']] = $row['preferred_flow'];
}

$base_url = '..';
$page_title = 'Dashboard';
require __DIR__ . '/../includes/header.php';
?>

<section class="dashboard">
    <h1>Your dashboard</h1>

    <div class="streak-banner">
        <span class="streak-flame">🔥</span>
        <div>
            <p class="streak-current"><?= (int)$streak['current_streak'] ?> day streak</p>
            <p class="streak-longest">Longest streak: <?= (int)$streak['longest_streak'] ?> day<?= (int)$streak['longest_streak'] === 1 ? '' : 's' ?></p>
        </div>
    </div>

    <h2>Pick a category</h2>
    <div class="cards">
        <?php foreach (CATEGORIES as $key => $cat): ?>
            <?php
                $stat = $byCategory[$key] ?? null;
                $attempts = $stat['attempts'] ?? 0;
                $correct  = $stat['correct'] ?? 0;
                $pct = $attempts > 0 ? round(($correct / $attempts) * 100) : null;
                $chosenFlow = $prefs[$key] ?? null;
            ?>
            <div class="category-card" style="--cat-color: <?= htmlspecialchars($cat['color']) ?>">
                <img src="../assets/img/<?= htmlspecialchars($cat['icon']) ?>" alt="<?= htmlspecialchars($cat['label']) ?> icon">
                <h3><?= htmlspecialchars($cat['label']) ?></h3>
                <p><?= htmlspecialchars($cat['blurb']) ?></p>

                <?php if ($pct !== null): ?>
                    <p class="score">Score: <strong><?= $pct ?>%</strong> <span class="attempts">(<?= $correct ?>/<?= $attempts ?>)</span></p>
                <?php else: ?>
                    <p class="no-data">No quizzes taken yet.</p>
                <?php endif; ?>

                <?php if ($chosenFlow): ?>
                    <div class="card-actions">
                        <a class="btn btn-small btn-primary"
                           href="<?= $chosenFlow === 'education_first' ? 'education.php' : 'quiz.php' ?>?category=<?= urlencode($key) ?>">
                            Continue <?= $chosenFlow === 'education_first' ? '(lesson)' : '(quiz)' ?>
                        </a>
                    </div>
                <?php else: ?>
                    <p class="flow-prompt">Start with the lesson, or jump straight to the quiz?</p>
                    <div class="card-actions">
                        <a class="btn btn-small btn-primary" href="education.php?category=<?= urlencode($key) ?>">Lesson first</a>
                        <a class="btn btn-small btn-secondary" href="quiz.php?category=<?= urlencode($key) ?>">Quiz first</a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
