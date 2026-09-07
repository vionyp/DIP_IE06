<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/categories.php';

$category = $_GET['category'] ?? '';
if (!category_exists($category)) {
    http_response_code(404);
    $base_url = '..';
    $page_title = 'Category not found';
    require __DIR__ . '/../includes/header.php';
    echo '<p>Unknown category. <a href="../index.php">Back to home</a>.</p>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}

$pdo = get_db();
$stmt = $pdo->prepare('SELECT * FROM lessons WHERE category = :cat ORDER BY sort_order ASC, id ASC');
$stmt->execute(['cat' => $category]);
$lessons = $stmt->fetchAll();

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
        <?php foreach ($lessons as $i => $lesson): ?>
        <article class="lesson-card">
            <div class="lesson-step">
                <img src="../assets/img/trail-node.png" alt="Lesson <?= $i + 1 ?>">
            </div>
            <div class="lesson-body">
                <h2><?= htmlspecialchars($lesson['title']) ?></h2>
                <div class="lesson-text"><?= $lesson['body_html'] ?></div>
            </div>
        </article>
        <?php endforeach; ?>

        <div class="lesson-cta">
            <a class="btn btn-primary" href="quiz.php?category=<?= urlencode($category) ?>">
                Test what you just learned →
            </a>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
