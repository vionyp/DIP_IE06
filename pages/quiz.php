<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/categories.php';
require_once __DIR__ . '/../includes/streak_helper.php';

const QUESTIONS_PER_ATTEMPT = 3;

$category = $_GET['category'] ?? $_POST['category'] ?? '';
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
$cat = CATEGORIES[$category];

// First visit to this category records "quiz first" (no-op if already set —
// see education.php for the matching "lesson first" write).
$pdo->prepare('
    INSERT INTO user_category_prefs (user_id, category, preferred_flow)
    VALUES (:uid, :cat, "quiz_first")
    ON DUPLICATE KEY UPDATE user_id = user_id
')->execute(['uid' => CURRENT_USER_ID, 'cat' => $category]);

$submitted = false;
$results = [];
$score = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted = true;

    // The question set for this attempt was recorded as a hidden field
    // (comma-separated quiz_item ids) so grading uses exactly the
    // questions the user was actually shown, not a fresh random draw.
    $ids = array_filter(array_map('intval', explode(',', $_POST['quiz_ids'] ?? '')));
    if (empty($ids)) {
        $quiz_items = [];
    } else {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("
            SELECT qi.*, na.headline, na.summary, na.ticker, na.price_change_stock, na.price_change_sector
            FROM quiz_items qi
            LEFT JOIN news_articles na ON na.id = qi.news_article_id
            WHERE qi.id IN ($placeholders)
        ");
        $stmt->execute($ids);
        $rowsById = [];
        foreach ($stmt->fetchAll() as $row) {
            $rowsById[$row['id']] = $row;
        }
        // Preserve the original display order.
        $quiz_items = array_values(array_filter(array_map(fn($id) => $rowsById[$id] ?? null, $ids)));
    }

    $insertAttempt = $pdo->prepare('
        INSERT INTO quiz_attempts (user_id, quiz_item_id, chosen_option, is_correct)
        VALUES (:uid, :qid, :chosen, :correct)
    ');

    foreach ($quiz_items as $item) {
        $field = 'q_' . $item['id'];
        $chosen = $_POST[$field] ?? null;
        $is_correct = ($chosen === $item['correct_option']);
        if ($is_correct) {
            $score++;
        }

        if ($chosen !== null) {
            $insertAttempt->execute([
                'uid'     => CURRENT_USER_ID,
                'qid'     => $item['id'],
                'chosen'  => $chosen,
                'correct' => $is_correct ? 1 : 0,
            ]);
        }

        $results[] = ['item' => $item, 'chosen' => $chosen, 'is_correct' => $is_correct];
    }

    $streak = update_streak($pdo, CURRENT_USER_ID);
} else {
    // Draw a random subset from the category's question pool.
    $stmt = $pdo->prepare('
        SELECT id, question_type, question_text, option_a, option_b, option_c
        FROM quiz_items
        WHERE category = :cat
        ORDER BY RAND()
        LIMIT ' . QUESTIONS_PER_ATTEMPT
    );
    $stmt->execute(['cat' => $category]);
    $quiz_items = $stmt->fetchAll();
}

$base_url = '..';
$page_title = $cat['label'] . ' — Quiz';
require __DIR__ . '/../includes/header.php';
?>

<section class="quiz-page" style="--cat-color: <?= htmlspecialchars($cat['color']) ?>">
    <div class="trail-heading">
        <img src="../assets/img/<?= htmlspecialchars($cat['icon']) ?>" alt="" class="trail-icon">
        <div>
            <h1><?= htmlspecialchars($cat['label']) ?> quiz</h1>
            <p>Read each question, then pick the direction you think it went. Questions are drawn
               at random, so retaking this quiz may show a different set.</p>
        </div>
    </div>

    <?php if (empty($quiz_items)): ?>
        <p>No quiz questions have been added for this category yet.</p>

    <?php elseif (!$submitted): ?>
        <form method="post" action="quiz.php">
            <input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>">
            <input type="hidden" name="quiz_ids" value="<?= htmlspecialchars(implode(',', array_column($quiz_items, 'id'))) ?>">

            <?php foreach ($quiz_items as $i => $item): ?>
            <fieldset class="quiz-question">
                <legend>Question <?= $i + 1 ?> <span class="q-type"><?= $item['question_type'] === 'real_news' ? 'Real news' : 'Concept' ?></span></legend>
                <p class="question-text"><?= htmlspecialchars($item['question_text']) ?></p>

                <label class="quiz-option">
                    <input type="radio" name="q_<?= $item['id'] ?>" value="a" required>
                    <?= htmlspecialchars($item['option_a']) ?>
                </label>
                <label class="quiz-option">
                    <input type="radio" name="q_<?= $item['id'] ?>" value="b">
                    <?= htmlspecialchars($item['option_b']) ?>
                </label>
                <label class="quiz-option">
                    <input type="radio" name="q_<?= $item['id'] ?>" value="c">
                    <?= htmlspecialchars($item['option_c']) ?>
                </label>
            </fieldset>
            <?php endforeach; ?>

            <button type="submit" class="btn btn-primary">Submit answers</button>
        </form>

    <?php else: ?>
        <div class="quiz-score">
            <img src="../assets/img/<?= $score === count($quiz_items) ? 'mascot-correct.png' : 'mascot-incorrect.png' ?>"
                 alt="" class="score-mascot">
            <h2>You scored <?= $score ?> / <?= count($quiz_items) ?></h2>
            <?php if (isset($streak)): ?>
            <p class="streak-note">🔥 Current streak: <?= $streak['current_streak'] ?> day<?= $streak['current_streak'] === 1 ? '' : 's' ?></p>
            <?php endif; ?>
        </div>

        <?php foreach ($results as $r): ?>
            <?php
                $item = $r['item'];
                $options = ['a' => $item['option_a'], 'b' => $item['option_b'], 'c' => $item['option_c']];
                $hasComparison = $item['question_type'] === 'real_news'
                    && $item['price_change_stock'] !== null
                    && $item['price_change_sector'] !== null;
            ?>
            <div class="quiz-result <?= $r['is_correct'] ? 'result-correct' : 'result-incorrect' ?>">
                <p class="question-text"><?= htmlspecialchars($item['question_text']) ?></p>
                <p class="your-answer">
                    Your answer: <strong><?= htmlspecialchars($options[$r['chosen']] ?? '(no answer)') ?></strong>
                    — <?= $r['is_correct'] ? 'Correct!' : 'Not quite.' ?>
                </p>
                <?php if (!$r['is_correct']): ?>
                <p class="correct-answer">Most likely answer: <strong><?= htmlspecialchars($options[$item['correct_option']]) ?></strong></p>
                <?php endif; ?>
                <p class="explanation"><?= htmlspecialchars($item['explanation']) ?></p>

                <?php if ($hasComparison): ?>
                <div class="stock-vs-sector">
                    <p class="svs-label"><?= htmlspecialchars($item['ticker']) ?> vs. sector (SOXX)</p>
                    <div class="svs-bars">
                        <div class="svs-row">
                            <span class="svs-name"><?= htmlspecialchars($item['ticker']) ?></span>
                            <div class="svs-track">
                                <div class="svs-fill <?= $item['price_change_stock'] >= 0 ? 'svs-up' : 'svs-down' ?>"
                                     style="width: <?= min(100, abs($item['price_change_stock']) * 10) ?>%"></div>
                            </div>
                            <span class="svs-value"><?= $item['price_change_stock'] >= 0 ? '+' : '' ?><?= number_format($item['price_change_stock'], 2) ?>%</span>
                        </div>
                        <div class="svs-row">
                            <span class="svs-name">Sector</span>
                            <div class="svs-track">
                                <div class="svs-fill <?= $item['price_change_sector'] >= 0 ? 'svs-up' : 'svs-down' ?>"
                                     style="width: <?= min(100, abs($item['price_change_sector']) * 10) ?>%"></div>
                            </div>
                            <span class="svs-value"><?= $item['price_change_sector'] >= 0 ? '+' : '' ?><?= number_format($item['price_change_sector'], 2) ?>%</span>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <div class="lesson-cta">
            <a class="btn btn-secondary" href="education.php?category=<?= urlencode($category) ?>">Review the lesson</a>
            <a class="btn btn-secondary" href="quiz.php?category=<?= urlencode($category) ?>">Try new questions</a>
            <a class="btn btn-primary" href="dashboard.php">Back to dashboard</a>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
