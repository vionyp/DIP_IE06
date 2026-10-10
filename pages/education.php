<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/categories.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$category = $_GET['category'] ?? '';
if (!category_exists($category)) {
    http_response_code(404);
    $base_url = '..';
    $page_title = 'Category not found';
    require __DIR__ . '/../includes/header.php';
    echo '<p>Unknown category. <a href="../pages/dashboard.php">Back to dashboard</a>.</p>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}

$pdo = get_db();

// First visit to this category records "lesson first" as the chosen path.
// ON DUPLICATE KEY UPDATE is a no-op here so a later quiz-first visit to a
// different category is unaffected, and this category's choice sticks.
$pdo->prepare('
    INSERT INTO user_category_prefs (user_id, category, preferred_flow)
    VALUES (:uid, :cat, "education_first")
    ON DUPLICATE KEY UPDATE user_id = user_id
')->execute(['uid' => CURRENT_USER_ID, 'cat' => $category]);

$stmt = $pdo->prepare('SELECT * FROM lessons WHERE category = :cat ORDER BY sort_order ASC, id ASC');
$stmt->execute(['cat' => $category]);
$lessons = $stmt->fetchAll();

$total = count($lessons);
// 1-based position within this category's trail, clamped to a valid lesson.
$pos = (int)($_GET['lesson'] ?? 1);
$pos = max(1, min($pos, max($total, 1)));
$lesson = $total > 0 ? $lessons[$pos - 1] : null;
$isLast = $pos >= $total;

$cat = CATEGORIES[$category];
$base_url = '..';
$page_title = $cat['label'] . ' — Lessons';
require __DIR__ . '/../includes/header.php';
?>

<section class="lesson-trail" style="--cat-color: <?= htmlspecialchars($cat['color']) ?>">
    <div class="trail-heading">
        <img src="../assets/img/<?= htmlspecialchars($cat['icon']) ?>" alt="" class="trail-icon">
        <div>
            <h1><?= htmlspecialchars($cat['label']) ?></h1>
            <p><?= htmlspecialchars($cat['blurb']) ?></p>
        </div>
    </div>

    <?php if (empty($lessons)): ?>
        <p>No lessons have been added for this category yet.</p>
    <?php else: ?>
        <p class="lesson-progress">Lesson <?= $pos ?> of <?= $total ?></p>

        <article class="lesson-card">
            <div class="lesson-step">
                <img src="../assets/img/trail-node.png" alt="Lesson <?= $pos ?>">
            </div>
            <div class="lesson-body">
                <h2><?= htmlspecialchars($lesson['title']) ?></h2>
                <div class="lesson-text"><?= $lesson['body_html'] ?></div>
            </div>
        </article>

        <div class="lesson-cta">
            <?php if (!$isLast): ?>
            <a class="btn btn-secondary" href="education.php?category=<?= urlencode($category) ?>&lesson=<?= $pos + 1 ?>">
                Next lesson →
            </a>
            <?php endif; ?>
            <a class="btn btn-primary btn-doodle doodle-sparkle doodle-right" href="quiz.php?category=<?= urlencode($category) ?>">
                Start quiz →
            </a>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
