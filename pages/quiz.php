<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/categories.php';
require_once __DIR__ . '/../includes/streak_helper.php';

$category = $_GET['category'] ?? $_POST['category'] ?? '';
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
$cat = CATEGORIES[$category];

$stmt = $pdo->prepare('SELECT * FROM quiz_items WHERE category = :cat ORDER BY id ASC');
$stmt->execute(['cat' => $category]);
$quiz_items = $stmt->fetchAll();

$submitted = false;
$results = [];
$score = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted = true;
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

        $results[] = [
            'item'       => $item,
            'chosen'     => $chosen,
            'is_correct' => $is_correct,
        ];
    }

    $streak = update_streak($pdo, CURRENT_USER_ID);
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
            <p>Read each headline, then pick the direction you think the stock most likely moved.</p>
        </div>
    </div>

    <?php if (empty($quiz_items)): ?>
        <p>No quiz questions have been added for this category yet.</p>

    <?php elseif (!$submitted): ?>
        <form method="post" action="quiz.php">
            <input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>">

            <?php foreach ($quiz_items as $i => $item): ?>
            <fieldset class="quiz-question">
                <legend>Question <?= $i + 1 ?></legend>
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
            </div>
        <?php endforeach; ?>

        <div class="lesson-cta">
            <a class="btn btn-secondary" href="education.php?category=<?= urlencode($category) ?>">Review the lesson</a>
            <a class="btn btn-primary" href="../index.php">Choose another category</a>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
