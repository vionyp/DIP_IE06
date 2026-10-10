<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/categories.php';
require_once __DIR__ . '/../includes/streak_helper.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

// A question gets the price visuals only if it is real news with both price
// changes recorded. Shared by the pre-submit chart and the results view.
function quiz_has_comparison(array $item): bool
{
    return $item['question_type'] === 'real_news'
        && $item['price_change_stock'] !== null
        && $item['price_change_sector'] !== null;
}

// Day -5 .. Day 0 filler values are generated in the browser before submit and
// posted back so the results chart continues the same line. Anything that isn't
// exactly 6 numbers is discarded (the page then regenerates its own).
function parse_chart_history(?string $raw): ?array
{
    $parts = explode(',', (string)$raw);
    if (count($parts) !== 6) {
        return null;
    }
    foreach ($parts as $v) {
        if (!is_numeric($v)) {
            return null;
        }
    }
    return array_map('floatval', $parts);
}

// Keeps only well-formed entries out of the "ans"/"chart" carry-forward maps
// that travel between steps as hidden fields / query params, so a tampered
// or stale value can't slip through as if it were a real answer.
function sanitize_answer_map(array $map): array
{
    $out = [];
    foreach ($map as $id => $val) {
        $id = (int)$id;
        if ($id > 0 && in_array($val, ['a', 'b', 'c'], true)) {
            $out[$id] = $val;
        }
    }
    return $out;
}

function sanitize_chart_map(array $map): array
{
    $out = [];
    foreach ($map as $id => $val) {
        $id = (int)$id;
        if ($id > 0 && is_string($val)) {
            $out[$id] = $val;
        }
    }
    return $out;
}

// Real Day -5..0 history for items authored with show_price_trend = 1 (see
// news_price_trend), instead of the client-generated random filler used for
// every other item. Returns null when this item should keep using the random
// filler / carried-forward hidden-field history.
function trend_history_for(array $item, array $trendByArticle): ?array
{
    if (empty($item['show_price_trend']) || empty($item['news_article_id'])) {
        return null;
    }
    return $trendByArticle[$item['news_article_id']] ?? null;
}

// Shared markup for one graded question: correct/incorrect banner, the
// revealed explanation, and (for real-news items) the chart + stock-vs-sector
// comparison. Used both for the single-question feedback step and for every
// row of the final summary.
function render_result_block(array $r): void
{
    $item = $r['item'];
    $options = ['a' => $item['option_a'], 'b' => $item['option_b'], 'c' => $item['option_c']];
    $hasComparison = quiz_has_comparison($item);
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
        <div class="result-visuals">
        <div class="price-chart">
            <p class="svs-label"><?= htmlspecialchars($item['ticker']) ?> — Day -5 to Day +1</p>
            <div class="price-chart-canvas">
                <canvas data-price-chart="<?= $item['id'] ?>"
                        data-day1="<?= htmlspecialchars((string)(float)$item['price_change_stock']) ?>"
                        <?php if ($r['history'] !== null): ?>data-history="<?= htmlspecialchars(implode(',', $r['history'])) ?>"<?php endif; ?>></canvas>
            </div>
        </div>
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
        </div>
        <?php endif; ?>
    </div>
    <?php
}

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

// ---- Fixed question set for this attempt (comma id list, carried as a hidden
// field / query param across every step) — draw one the first time only. ----
$qidsParam = $_GET['qids'] ?? $_POST['qids'] ?? '';
$quiz_ids = array_values(array_filter(array_map('intval', explode(',', $qidsParam))));

if (empty($quiz_ids)) {
    $stmt = $pdo->prepare(
        'SELECT id FROM quiz_items WHERE category = :cat ORDER BY RAND() LIMIT ' . QUESTIONS_PER_ATTEMPT
    );
    $stmt->execute(['cat' => $category]);
    $quiz_ids = array_column($stmt->fetchAll(), 'id');

    if (empty($quiz_ids)) {
        $base_url = '..';
        $page_title = $cat['label'] . ' — Quiz';
        require __DIR__ . '/../includes/header.php';
        ?>
        <section class="quiz-page" style="--cat-color: <?= htmlspecialchars($cat['color']) ?>">
            <div class="trail-heading">
                <img src="../assets/img/<?= htmlspecialchars($cat['icon']) ?>" alt="" class="trail-icon">
                <div>
                    <h1><?= htmlspecialchars($cat['label']) ?> quiz</h1>
                </div>
            </div>
            <p>No quiz questions have been added for this category yet.</p>
        </section>
        <?php
        require __DIR__ . '/../includes/footer.php';
        exit;
    }

    // Fresh attempt: hand it a stable URL (session id + question 1) instead
    // of rendering straight away, so a reload never draws a second random set.
    header('Location: quiz.php?' . http_build_query([
        'category' => $category,
        'qids'     => implode(',', $quiz_ids),
        'sid'      => bin2hex(random_bytes(16)),
        'idx'      => 0,
    ]));
    exit;
}

$session_id = $_GET['sid'] ?? $_POST['sid'] ?? '';

// Look up the full rows for every id in the fixed set (needed for the current
// question always, and for every question once we reach the summary).
$placeholders = implode(',', array_fill(0, count($quiz_ids), '?'));
$stmt = $pdo->prepare("
    SELECT qi.*, na.headline, na.summary, na.ticker, na.price_change_stock, na.price_change_sector
    FROM quiz_items qi
    LEFT JOIN news_articles na ON na.id = qi.news_article_id
    WHERE qi.id IN ($placeholders)
");
$stmt->execute($quiz_ids);
$rowsById = [];
foreach ($stmt->fetchAll() as $row) {
    $rowsById[$row['id']] = $row;
}
// Preserve the original draw order.
$quiz_items = array_values(array_filter(array_map(fn($id) => $rowsById[$id] ?? null, $quiz_ids)));
$total = count($quiz_items);

if ($total === 0) {
    $base_url = '..';
    $page_title = $cat['label'] . ' — Quiz';
    require __DIR__ . '/../includes/header.php';
    echo '<p>No quiz questions have been added for this category yet.</p>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}

// Real historical Day -5..0 trend, for the items authored with it — keyed by
// news_article_id since several quiz_items can share one news article.
$trendByArticle = [];
$trendArticleIds = array_values(array_unique(array_filter(array_map(
    fn($it) => !empty($it['show_price_trend']) ? (int)$it['news_article_id'] : null,
    $quiz_items
))));
if (!empty($trendArticleIds)) {
    $ph = implode(',', array_fill(0, count($trendArticleIds), '?'));
    $stmt = $pdo->prepare("
        SELECT news_article_id, day_offset, pct_change
        FROM news_price_trend
        WHERE news_article_id IN ($ph)
        ORDER BY news_article_id, day_offset
    ");
    $stmt->execute($trendArticleIds);
    foreach ($stmt->fetchAll() as $row) {
        $trendByArticle[(int)$row['news_article_id']][] = (float)$row['pct_change'];
    }
}

$idx = (int)($_GET['idx'] ?? $_POST['idx'] ?? 0);
$idx = max(0, min($idx, $total - 1));

$priorAns   = sanitize_answer_map((array)($_POST['ans'] ?? $_GET['ans'] ?? []));
$priorChart = sanitize_chart_map((array)($_POST['chart'] ?? $_GET['chart'] ?? []));

$mode = 'question';
$graded = null;
$streak = null;
$results = [];
$score = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Grade the question at $idx.
    $item = $quiz_items[$idx];
    $chosen = $_POST['answer'] ?? null;
    $chosen = in_array($chosen, ['a', 'b', 'c'], true) ? $chosen : null;
    $is_correct = ($chosen !== null && $chosen === $item['correct_option']);

    if ($chosen !== null) {
        $pdo->prepare('
            INSERT INTO quiz_attempts (user_id, session_id, quiz_item_id, chosen_option, is_correct)
            VALUES (:uid, :sid, :qid, :chosen, :correct)
        ')->execute([
            'uid'     => CURRENT_USER_ID,
            'sid'     => $session_id,
            'qid'     => $item['id'],
            'chosen'  => $chosen,
            'correct' => $is_correct ? 1 : 0,
        ]);

        // answers holds one row per (user, question): answering a question
        // again updates that row instead of inserting a second one.
        $pdo->prepare('
            INSERT INTO answers (user_id, question_id, answer, is_correct)
            VALUES (:uid, :qid, :chosen, :correct)
            ON DUPLICATE KEY UPDATE
                answer = VALUES(answer),
                is_correct = VALUES(is_correct),
                answered_at = CURRENT_TIMESTAMP
        ')->execute([
            'uid'     => CURRENT_USER_ID,
            'qid'     => $item['id'],
            'chosen'  => $chosen,
            'correct' => $is_correct ? 1 : 0,
        ]);
    }

    $fixedHistory = trend_history_for($item, $trendByArticle);
    if ($fixedHistory !== null) {
        $history = $fixedHistory;
    } else {
        $history = parse_chart_history($_POST['chart_' . $item['id']] ?? null);
        $priorChart[$item['id']] = $history !== null ? implode(',', $history) : '';
    }
    $priorAns[$item['id']] = $chosen;

    $graded = ['item' => $item, 'chosen' => $chosen, 'is_correct' => $is_correct, 'history' => $history];

    if ($idx + 1 >= $total) {
        // Last question just answered — same wrap-up as before update_streak()
        // is called once per completed attempt, not once per question.
        $streak = update_streak($pdo, CURRENT_USER_ID);
        $mode = 'summary';

        foreach ($quiz_items as $qi) {
            $c = $priorAns[$qi['id']] ?? null;
            $ok = ($c !== null && $c === $qi['correct_option']);
            if ($ok) {
                $score++;
            }
            $h = trend_history_for($qi, $trendByArticle);
            if ($h === null) {
                $h = isset($priorChart[$qi['id']]) ? parse_chart_history($priorChart[$qi['id']]) : null;
            }
            $results[] = ['item' => $qi, 'chosen' => $c, 'is_correct' => $ok, 'history' => $h];
        }
    } else {
        $mode = 'feedback';
    }
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

    <?php if ($mode !== 'summary'): ?>
    <p class="quiz-jump-nav"><a href="education.php?category=<?= urlencode($category) ?>">← Jump to the lesson</a></p>
    <?php endif; ?>

    <?php if ($mode === 'question'): ?>
        <?php $item = $quiz_items[$idx]; ?>
        <p class="quiz-progress">Question <?= $idx + 1 ?> of <?= $total ?></p>

        <form method="post" action="quiz.php">
            <input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>">
            <input type="hidden" name="qids" value="<?= htmlspecialchars(implode(',', $quiz_ids)) ?>">
            <input type="hidden" name="sid" value="<?= htmlspecialchars($session_id) ?>">
            <input type="hidden" name="idx" value="<?= $idx ?>">
            <?php foreach ($priorAns as $pid => $pval): ?>
                <input type="hidden" name="ans[<?= $pid ?>]" value="<?= htmlspecialchars($pval) ?>">
            <?php endforeach; ?>
            <?php foreach ($priorChart as $pid => $pval): ?>
                <input type="hidden" name="chart[<?= $pid ?>]" value="<?= htmlspecialchars($pval) ?>">
            <?php endforeach; ?>

            <fieldset class="quiz-question">
                <legend>Question <?= $idx + 1 ?> <span class="q-type"><?= $item['question_type'] === 'real_news' ? 'Real news' : 'Concept' ?></span></legend>
                <p class="question-text"><?= htmlspecialchars($item['question_text']) ?></p>

                <label class="quiz-option">
                    <input type="radio" name="answer" value="a" required>
                    <?= htmlspecialchars($item['option_a']) ?>
                </label>
                <label class="quiz-option">
                    <input type="radio" name="answer" value="b">
                    <?= htmlspecialchars($item['option_b']) ?>
                </label>
                <label class="quiz-option">
                    <input type="radio" name="answer" value="c">
                    <?= htmlspecialchars($item['option_c']) ?>
                </label>

                <?php if (quiz_has_comparison($item)): ?>
                <?php $fixedHistory = trend_history_for($item, $trendByArticle); ?>
                <div class="price-chart">
                    <p class="svs-label"><?= htmlspecialchars($item['ticker']) ?> — the days leading up to the news<?= $fixedHistory === null ? ' (illustrative)' : '' ?></p>
                    <div class="price-chart-canvas">
                        <canvas data-price-chart="<?= $item['id'] ?>"
                                <?php if ($fixedHistory !== null): ?>data-history="<?= htmlspecialchars(implode(',', $fixedHistory)) ?>"<?php endif; ?>></canvas>
                    </div>
                    <?php if ($fixedHistory === null): ?>
                    <input type="hidden" name="chart_<?= $item['id'] ?>" data-chart-history="<?= $item['id'] ?>">
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </fieldset>

            <button type="submit" class="btn btn-primary btn-doodle doodle-chart doodle-right">Submit answer</button>
        </form>

    <?php elseif ($mode === 'feedback'): ?>
        <p class="quiz-progress">Question <?= $idx + 1 ?> of <?= $total ?></p>
        <?php render_result_block($graded); ?>

        <div class="lesson-cta">
            <?php
                $nextUrl = 'quiz.php?' . http_build_query([
                    'category' => $category,
                    'qids'     => implode(',', $quiz_ids),
                    'sid'      => $session_id,
                    'idx'      => $idx + 1,
                    'ans'      => $priorAns,
                    'chart'    => $priorChart,
                ]);
            ?>
            <a class="btn btn-primary" href="<?= htmlspecialchars($nextUrl) ?>">Next question →</a>
        </div>

    <?php else: // summary ?>
        <div class="quiz-score">
            <img src="../assets/img/<?= $score === $total ? 'mascot-correct.png' : 'mascot-incorrect.png' ?>"
                 alt="" class="score-mascot">
            <h2>You scored <?= $score ?> / <?= $total ?></h2>
            <?php if ($streak !== null): ?>
            <p class="streak-note">🔥 Current streak: <?= $streak['current_streak'] ?> day<?= $streak['current_streak'] === 1 ? '' : 's' ?></p>
            <?php endif; ?>
        </div>

        <?php foreach ($results as $r): ?>
            <?php render_result_block($r); ?>
        <?php endforeach; ?>

        <div class="lesson-cta">
            <a class="btn btn-secondary" href="education.php?category=<?= urlencode($category) ?>">Review the lesson</a>
            <a class="btn btn-secondary" href="quiz.php?category=<?= urlencode($category) ?>">Try new questions</a>
            <a class="btn btn-primary" href="dashboard.php">Back to dashboard</a>
        </div>
    <?php endif; ?>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="../assets/js/price-chart.js"></script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
