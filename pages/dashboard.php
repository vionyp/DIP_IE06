<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/categories.php';

$pdo = get_db();

$stmt = $pdo->prepare('SELECT current_streak, longest_streak FROM streaks WHERE user_id = :uid');
$stmt->execute(['uid' => CURRENT_USER_ID]);
$streak = $stmt->fetch() ?: ['current_streak' => 0, 'longest_streak' => 0];

// Most recent COMPLETE attempt per category — a session only counts once all
// QUESTIONS_PER_ATTEMPT questions have been answered, so the score shown is
// always "X / QUESTIONS_PER_ATTEMPT", never a partial or an old differently-
// sized attempt. One row per (category, session_id), newest session first,
// so the first row seen for a category below is that category's latest
// complete attempt.
$stmt = $pdo->prepare('
    SELECT qi.category, qa.session_id,
           COUNT(*) AS total,
           SUM(qa.is_correct) AS correct,
           MAX(qa.attempted_at) AS session_time
    FROM quiz_attempts qa
    JOIN quiz_items qi ON qi.id = qa.quiz_item_id
    WHERE qa.user_id = :uid
    GROUP BY qi.category, qa.session_id
    HAVING total = :qpa
    ORDER BY session_time DESC
');
$stmt->execute(['uid' => CURRENT_USER_ID, 'qpa' => QUESTIONS_PER_ATTEMPT]);
$byCategory = [];
foreach ($stmt->fetchAll() as $row) {
    if (!isset($byCategory[$row['category']])) {
        $byCategory[$row['category']] = $row;
    }
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
            <?php $stat = $byCategory[$key] ?? null; ?>
            <div class="category-card" style="--cat-color: <?= htmlspecialchars($cat['color']) ?>">
                <img src="../assets/img/<?= htmlspecialchars($cat['icon']) ?>" alt="<?= htmlspecialchars($cat['label']) ?> icon">
                <h3><?= htmlspecialchars($cat['label']) ?></h3>
                <p><?= htmlspecialchars($cat['blurb']) ?></p>

                <?php if ($stat !== null): ?>
                    <p class="score">Score: <strong><?= (int)$stat['correct'] ?> / <?= (int)$stat['total'] ?></strong></p>
                <?php else: ?>
                    <p class="no-data">No quizzes taken yet.</p>
                <?php endif; ?>

                <div class="card-actions">
                    <a class="btn btn-small btn-secondary" href="education.php?category=<?= urlencode($key) ?>">Lesson</a>
                    <a class="btn btn-small btn-primary" href="quiz.php?category=<?= urlencode($key) ?>">Quiz</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
