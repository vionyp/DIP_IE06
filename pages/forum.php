<?php
/**
 * StockSense — forum.
 *
 * One page, four views picked by the query string:
 *   forum.php                    board list + latest topics
 *   forum.php?board=KEY          the topics in one board
 *   forum.php?topic=ID           one topic and its replies
 *   forum.php?new=1[&board=KEY]  the "ask a question" form
 *
 * Anyone can read. Asking, replying and liking need an account; every
 * change is a POST to this page with a hidden "action" field and the CSRF
 * token, followed by a redirect. Boards and moderators are set in
 * config/forum.php; the tables are in sql/migrations/004_forum.sql.
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/forum.php';
require_once __DIR__ . '/../includes/auth.php';

/** "3h", "2d" … or the date once it is more than 30 days old. */
function forum_ago(int $seconds, string $date): string
{
    if ($seconds < 3600) {
        return max(1, (int)floor($seconds / 60)) . 'm';
    }
    if ($seconds < 86400) {
        return (int)floor($seconds / 3600) . 'h';
    }
    if ($seconds < 30 * 86400) {
        return (int)floor($seconds / 86400) . 'd';
    }
    return date('j M Y', strtotime($date));
}

/** Round badge with the user's initial; the colour is fixed per username. */
function forum_avatar(string $user): string
{
    $palette = ['#2c4a7c', '#2f8f5b', '#7a4fbf', '#d97a2e', '#1f9a94', '#b5527a'];
    $color = $palette[crc32($user) % count($palette)];
    return '<span class="forum-avatar" style="background: ' . $color . '" title="' . htmlspecialchars($user) . '">'
        . htmlspecialchars(strtoupper(substr($user, 0, 1))) . '</span>';
}

function forum_board_tag(string $key): string
{
    // A topic whose board was later removed from config/forum.php still shows.
    $board = FORUM_BOARDS[$key] ?? ['label' => $key, 'color' => '#6b6b6b'];
    return '<a class="forum-tag" style="--cat-color: ' . htmlspecialchars($board['color']) . '" href="forum.php?board='
        . urlencode($key) . '">' . htmlspecialchars($board['label']) . '</a>';
}

function forum_is_moderator(): bool
{
    return is_logged_in() && in_array(current_username(), FORUM_MODERATORS, true);
}

/** Line endings normalised and outer blank space removed. */
function forum_clean_text(string $text): string
{
    return trim(str_replace(["\r\n", "\r"], "\n", $text));
}

function forum_redirect(string $to): void
{
    header('Location: ' . $to);
    exit;
}

$pdo = get_db();
$error = null;
$draft = ['board' => '', 'title' => '', 'body' => ''];

// ---------------------------------------------------------------------
// Changes: new topic, reply, like, pin, delete
// ---------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');

    if (!is_logged_in()) {
        forum_redirect('login.php?next=' . urlencode('forum.php'));
    }
    if (!csrf_check()) {
        $error = 'Your session expired. Please try again.';
    } elseif ($action === 'new_topic') {
        $draft = [
            'board' => (string)($_POST['board'] ?? ''),
            'title' => trim(preg_replace('/\s+/', ' ', (string)($_POST['title'] ?? ''))),
            'body'  => forum_clean_text((string)($_POST['body'] ?? '')),
        ];
        if (!isset(FORUM_BOARDS[$draft['board']])) {
            $error = 'Please choose a board for your question.';
        } elseif (mb_strlen($draft['title']) < FORUM_TITLE_MIN_LENGTH || mb_strlen($draft['title']) > FORUM_TITLE_MAX_LENGTH) {
            $error = 'The title must be ' . FORUM_TITLE_MIN_LENGTH . '–' . FORUM_TITLE_MAX_LENGTH . ' characters.';
        } elseif ($draft['body'] === '' || mb_strlen($draft['body']) > FORUM_BODY_MAX_LENGTH) {
            $error = 'Please write your question (up to ' . FORUM_BODY_MAX_LENGTH . ' characters).';
        } else {
            // The topic and its first post are saved together or not at all.
            $pdo->beginTransaction();
            $pdo->prepare('INSERT INTO forum_topics (board, user_id, title) VALUES (:board, :uid, :title)')
                ->execute(['board' => $draft['board'], 'uid' => CURRENT_USER_ID, 'title' => $draft['title']]);
            $newId = (int)$pdo->lastInsertId();
            $pdo->prepare('INSERT INTO forum_posts (topic_id, user_id, body) VALUES (:tid, :uid, :body)')
                ->execute(['tid' => $newId, 'uid' => CURRENT_USER_ID, 'body' => $draft['body']]);
            $pdo->commit();
            forum_redirect('forum.php?topic=' . $newId);
        }
    } elseif ($action === 'reply') {
        $tid = (int)($_POST['topic_id'] ?? 0);
        $draft['body'] = forum_clean_text((string)($_POST['body'] ?? ''));
        $stmt = $pdo->prepare('SELECT id FROM forum_topics WHERE id = :id');
        $stmt->execute(['id' => $tid]);
        if (!$stmt->fetch()) {
            forum_redirect('forum.php');
        }
        if ($draft['body'] === '' || mb_strlen($draft['body']) > FORUM_BODY_MAX_LENGTH) {
            $error = 'Please write a reply (up to ' . FORUM_BODY_MAX_LENGTH . ' characters).';
        } else {
            $pdo->prepare('INSERT INTO forum_posts (topic_id, user_id, body) VALUES (:tid, :uid, :body)')
                ->execute(['tid' => $tid, 'uid' => CURRENT_USER_ID, 'body' => $draft['body']]);
            $newId = (int)$pdo->lastInsertId();
            $pdo->prepare('UPDATE forum_topics SET last_activity_at = NOW() WHERE id = :id')->execute(['id' => $tid]);
            forum_redirect('forum.php?topic=' . $tid . '#post-' . $newId);
        }
    } elseif ($action === 'like') {
        $stmt = $pdo->prepare('SELECT id, topic_id FROM forum_posts WHERE id = :id');
        $stmt->execute(['id' => (int)($_POST['post_id'] ?? 0)]);
        $post = $stmt->fetch();
        if (!$post) {
            forum_redirect('forum.php');
        }
        // Liking twice takes the like back.
        $del = $pdo->prepare('DELETE FROM forum_likes WHERE post_id = :pid AND user_id = :uid');
        $del->execute(['pid' => $post['id'], 'uid' => CURRENT_USER_ID]);
        if ($del->rowCount() === 0) {
            $pdo->prepare('INSERT INTO forum_likes (post_id, user_id) VALUES (:pid, :uid)')
                ->execute(['pid' => $post['id'], 'uid' => CURRENT_USER_ID]);
        }
        forum_redirect('forum.php?topic=' . (int)$post['topic_id'] . '#post-' . (int)$post['id']);
    } elseif ($action === 'pin' || $action === 'delete_topic') {
        $stmt = $pdo->prepare('SELECT id, board, user_id FROM forum_topics WHERE id = :id');
        $stmt->execute(['id' => (int)($_POST['topic_id'] ?? 0)]);
        $target = $stmt->fetch();
        if (!$target) {
            forum_redirect('forum.php');
        }
        if ($action === 'pin' && forum_is_moderator()) {
            $pdo->prepare('UPDATE forum_topics SET is_pinned = 1 - is_pinned WHERE id = :id')->execute(['id' => $target['id']]);
            forum_redirect('forum.php?topic=' . (int)$target['id']);
        }
        if ($action === 'delete_topic' && (forum_is_moderator() || (int)$target['user_id'] === CURRENT_USER_ID)) {
            // Its posts and their likes go with it (ON DELETE CASCADE).
            $pdo->prepare('DELETE FROM forum_topics WHERE id = :id')->execute(['id' => $target['id']]);
            forum_redirect('forum.php?board=' . urlencode($target['board']));
        }
        forum_redirect('forum.php?topic=' . (int)$target['id']);
    } elseif ($action === 'delete_post') {
        $stmt = $pdo->prepare('
            SELECT p.id, p.topic_id, p.user_id,
                   (SELECT MIN(f.id) FROM forum_posts f WHERE f.topic_id = p.topic_id) AS first_id
            FROM forum_posts p WHERE p.id = :id
        ');
        $stmt->execute(['id' => (int)($_POST['post_id'] ?? 0)]);
        $post = $stmt->fetch();
        if (!$post) {
            forum_redirect('forum.php');
        }
        // The first post is the question: that is removed by deleting the topic.
        if ((int)$post['id'] !== (int)$post['first_id']
            && (forum_is_moderator() || (int)$post['user_id'] === CURRENT_USER_ID)) {
            $pdo->prepare('DELETE FROM forum_posts WHERE id = :id')->execute(['id' => $post['id']]);
        }
        forum_redirect('forum.php?topic=' . (int)$post['topic_id']);
    } else {
        forum_redirect('forum.php');
    }
}

// ---------------------------------------------------------------------
// Work out which view was asked for
// ---------------------------------------------------------------------
// "replies" and "last_user" are worked out per topic so every list can show them.
const FORUM_TOPIC_COLUMNS = '
    t.id, t.board, t.user_id, t.title, t.is_pinned, t.views, t.last_activity_at,
    TIMESTAMPDIFF(SECOND, t.last_activity_at, NOW()) AS age_seconds,
    (SELECT COUNT(*) FROM forum_posts p WHERE p.topic_id = t.id) - 1 AS replies,
    (SELECT u.username FROM forum_posts p JOIN users u ON u.id = p.user_id
      WHERE p.topic_id = t.id ORDER BY p.id DESC LIMIT 1) AS last_user,
    (SELECT p.body FROM forum_posts p WHERE p.topic_id = t.id ORDER BY p.id ASC LIMIT 1) AS first_body
';

// A failed reply re-shows its topic, so the id may come from the form.
$topicId = (int)($_GET['topic'] ?? $_POST['topic_id'] ?? 0);
$boardKey = (string)($_GET['board'] ?? '');
$wantsNew = isset($_GET['new']) || (($_POST['action'] ?? '') === 'new_topic');

$topic = null;
if ($topicId > 0) {
    $stmt = $pdo->prepare('SELECT ' . FORUM_TOPIC_COLUMNS . ' FROM forum_topics t WHERE t.id = :id');
    $stmt->execute(['id' => $topicId]);
    $topic = $stmt->fetch() ?: null;
}

if ($wantsNew) {
    if (!is_logged_in()) {
        forum_redirect('login.php?next=' . urlencode('forum.php?new=1' . (isset(FORUM_BOARDS[$boardKey]) ? '&board=' . $boardKey : '')));
    }
    $view = 'new';
    $page_title = 'Ask a question';
    if ($draft['board'] === '' && isset(FORUM_BOARDS[$boardKey])) {
        $draft['board'] = $boardKey;
    }
} elseif ($topic !== null) {
    $view = 'topic';
    $boardKey = $topic['board'];
    $page_title = $topic['title'];

    // Count a view once per visitor session, not on every refresh.
    if (empty($_SESSION['forum_seen'][$topicId])) {
        $_SESSION['forum_seen'][$topicId] = true;
        $pdo->prepare('UPDATE forum_topics SET views = views + 1 WHERE id = :id')->execute(['id' => $topicId]);
        $topic['views']++;
    }

    $stmt = $pdo->prepare('
        SELECT p.id, p.user_id, p.body, p.created_at, u.username,
               (SELECT COUNT(*) FROM forum_likes l WHERE l.post_id = p.id) AS likes,
               (SELECT COUNT(*) FROM forum_likes l WHERE l.post_id = p.id AND l.user_id = :uid) AS liked
        FROM forum_posts p
        JOIN users u ON u.id = p.user_id
        WHERE p.topic_id = :tid
        ORDER BY p.id ASC
    ');
    $stmt->execute(['tid' => $topicId, 'uid' => CURRENT_USER_ID]);
    $posts = $stmt->fetchAll();
} elseif (isset(FORUM_BOARDS[$boardKey])) {
    $view = 'board';
    $page_title = FORUM_BOARDS[$boardKey]['label'] . ' — Forum';

    $stmt = $pdo->prepare('SELECT ' . FORUM_TOPIC_COLUMNS . ' FROM forum_topics t WHERE t.board = :board
                           ORDER BY t.is_pinned DESC, t.last_activity_at DESC, t.id DESC LIMIT 100');
    $stmt->execute(['board' => $boardKey]);
    $boardTopics = $stmt->fetchAll();

    // Who has posted in each topic, in order of their first post.
    $stmt = $pdo->prepare('
        SELECT p.topic_id, u.username
        FROM forum_posts p
        JOIN users u ON u.id = p.user_id
        JOIN forum_topics t ON t.id = p.topic_id
        WHERE t.board = :board
        GROUP BY p.topic_id, u.username
        ORDER BY MIN(p.id) ASC
    ');
    $stmt->execute(['board' => $boardKey]);
    $participants = [];
    foreach ($stmt->fetchAll() as $row) {
        $participants[$row['topic_id']][] = $row['username'];
    }
} else {
    $view = 'home';
    $page_title = 'Forum';
    if ($topicId !== 0 || $boardKey !== '') {
        http_response_code(404);
    }

    $topicCounts = $pdo->query('SELECT board, COUNT(*) FROM forum_topics GROUP BY board')->fetchAll(PDO::FETCH_KEY_PAIR);
    $latest = $pdo->query('SELECT ' . FORUM_TOPIC_COLUMNS . ' FROM forum_topics t
                           ORDER BY t.last_activity_at DESC, t.id DESC LIMIT 6')->fetchAll();
}

$board = FORUM_BOARDS[$boardKey] ?? ['label' => $boardKey, 'color' => '#6b6b6b', 'blurb' => ''];
$askUrl = 'forum.php?new=1' . (isset(FORUM_BOARDS[$boardKey]) ? '&board=' . urlencode($boardKey) : '');

$base_url = '..';
require __DIR__ . '/../includes/header.php';
?>

<section class="forum">
<?php if ($view === 'home'): ?>
    <div class="forum-head">
        <div>
            <h1>Forum</h1>
            <p>Ask about a lesson, a quiz explanation or a headline, grouped by the same topics you study.</p>
        </div>
        <a class="btn btn-primary" href="<?= htmlspecialchars($askUrl) ?>">Ask a question</a>
    </div>

    <div class="forum-home">
        <div>
            <h2 class="forum-col-title">Boards</h2>
            <ul class="forum-boards">
                <?php foreach (FORUM_BOARDS as $key => $b): ?>
                <?php $count = (int)($topicCounts[$key] ?? 0); ?>
                <li class="forum-board" style="--cat-color: <?= htmlspecialchars($b['color']) ?>">
                    <div>
                        <h3><a href="forum.php?board=<?= urlencode($key) ?>"><?= htmlspecialchars($b['label']) ?></a></h3>
                        <p><?= htmlspecialchars($b['blurb']) ?></p>
                    </div>
                    <span class="forum-board-count"><strong><?= $count ?></strong> topic<?= $count === 1 ? '' : 's' ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div>
            <h2 class="forum-col-title">Latest</h2>
            <?php if (empty($latest)): ?>
            <p class="forum-empty">No questions yet. Be the first to ask one.</p>
            <?php else: ?>
            <ul class="forum-latest">
                <?php foreach ($latest as $t): ?>
                <li>
                    <?= forum_avatar($t['last_user']) ?>
                    <div>
                        <a class="forum-latest-title" href="forum.php?topic=<?= (int)$t['id'] ?>"><?= htmlspecialchars($t['title']) ?></a>
                        <?= forum_board_tag($t['board']) ?>
                    </div>
                    <span class="forum-latest-meta"><strong title="Replies"><?= (int)$t['replies'] ?></strong><?= htmlspecialchars(forum_ago((int)$t['age_seconds'], $t['last_activity_at'])) ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
    </div>

<?php elseif ($view === 'board'): ?>
    <p class="forum-crumbs"><a href="forum.php">Forum</a> › <?= htmlspecialchars($board['label']) ?></p>
    <div class="forum-head" style="--cat-color: <?= htmlspecialchars($board['color']) ?>">
        <div>
            <h1 class="forum-board-heading"><?= htmlspecialchars($board['label']) ?></h1>
            <p><?= htmlspecialchars($board['blurb']) ?></p>
        </div>
        <a class="btn btn-primary" href="<?= htmlspecialchars($askUrl) ?>">Ask a question</a>
    </div>

    <?php if (empty($boardTopics)): ?>
        <p class="forum-empty">No questions in this board yet. Be the first to ask one.</p>
    <?php else: ?>
    <table class="forum-topics">
        <thead>
            <tr>
                <th scope="col">Topic</th>
                <th scope="col" class="forum-col-people"><span class="visually-hidden">Participants</span></th>
                <th scope="col" class="forum-col-num">Replies</th>
                <th scope="col" class="forum-col-num forum-col-views">Views</th>
                <th scope="col" class="forum-col-num">Activity</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($boardTopics as $t): ?>
            <tr>
                <td>
                    <a class="forum-topic-title" href="forum.php?topic=<?= (int)$t['id'] ?>"><?php if ($t['is_pinned']): ?><span class="forum-pin" title="Pinned">📌</span> <?php endif; ?><?= htmlspecialchars($t['title']) ?></a>
                    <?php if ($t['is_pinned']): ?>
                    <p class="forum-topic-excerpt"><?= htmlspecialchars(mb_strimwidth((string)$t['first_body'], 0, 240, '…')) ?></p>
                    <?php endif; ?>
                </td>
                <td class="forum-col-people">
                    <span class="forum-avatars">
                        <?php foreach (array_slice($participants[$t['id']] ?? [], 0, 4) as $user): ?><?= forum_avatar($user) ?><?php endforeach; ?>
                    </span>
                </td>
                <td class="forum-col-num"><?= (int)$t['replies'] ?></td>
                <td class="forum-col-num forum-col-views"><?= (int)$t['views'] ?></td>
                <td class="forum-col-num"><?= htmlspecialchars(forum_ago((int)$t['age_seconds'], $t['last_activity_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

<?php elseif ($view === 'new'): ?>
    <p class="forum-crumbs"><a href="forum.php">Forum</a> › Ask a question</p>
    <h1 class="forum-topic-heading">Ask a question</h1>

    <form method="post" action="forum.php" class="forum-form">
        <input type="hidden" name="action" value="new_topic">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
        <?php if ($error !== null): ?>
        <p class="auth-error" role="alert"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <label for="forum-board">Board</label>
        <select id="forum-board" name="board" required>
            <option value="">Choose a board…</option>
            <?php foreach (FORUM_BOARDS as $key => $b): ?>
            <option value="<?= htmlspecialchars($key) ?>"<?= $draft['board'] === $key ? ' selected' : '' ?>><?= htmlspecialchars($b['label']) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="forum-title">Title</label>
        <input type="text" id="forum-title" name="title" value="<?= htmlspecialchars($draft['title']) ?>"
               minlength="<?= FORUM_TITLE_MIN_LENGTH ?>" maxlength="<?= FORUM_TITLE_MAX_LENGTH ?>"
               placeholder="Your question in one line" required>

        <label for="forum-body">Details</label>
        <textarea id="forum-body" name="body" rows="7" maxlength="<?= FORUM_BODY_MAX_LENGTH ?>"
                  placeholder="Which lesson or quiz is it about? What did you expect, and what confused you?" required><?= htmlspecialchars($draft['body']) ?></textarea>
        <p class="auth-hint">Keep it about understanding the news, not about what to buy or sell.</p>

        <div class="forum-form-actions">
            <button type="submit" class="btn btn-primary">Post question</button>
            <a class="btn btn-secondary" href="forum.php<?= isset(FORUM_BOARDS[$boardKey]) ? '?board=' . urlencode($boardKey) : '' ?>">Cancel</a>
        </div>
    </form>

<?php else: ?>
    <?php $canManageTopic = forum_is_moderator() || (int)$topic['user_id'] === CURRENT_USER_ID && is_logged_in(); ?>
    <p class="forum-crumbs"><a href="forum.php">Forum</a> ›
       <a href="forum.php?board=<?= urlencode($boardKey) ?>"><?= htmlspecialchars($board['label']) ?></a></p>
    <h1 class="forum-topic-heading"><?php if ($topic['is_pinned']): ?><span class="forum-pin" title="Pinned">📌</span> <?php endif; ?><?= htmlspecialchars($topic['title']) ?></h1>
    <div class="forum-topic-meta">
        <?= forum_board_tag($boardKey) ?>
        <span><?= (int)$topic['replies'] ?> repl<?= (int)$topic['replies'] === 1 ? 'y' : 'ies' ?></span>
        <span><?= (int)$topic['views'] ?> view<?= (int)$topic['views'] === 1 ? '' : 's' ?></span>
        <?php if (forum_is_moderator()): ?>
        <form method="post" action="forum.php">
            <input type="hidden" name="action" value="pin">
            <input type="hidden" name="topic_id" value="<?= (int)$topic['id'] ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
            <button type="submit" class="forum-link-btn"><?= $topic['is_pinned'] ? 'Unpin' : 'Pin' ?></button>
        </form>
        <?php endif; ?>
        <?php if ($canManageTopic): ?>
        <form method="post" action="forum.php" data-confirm="Delete this whole topic and all its replies?">
            <input type="hidden" name="action" value="delete_topic">
            <input type="hidden" name="topic_id" value="<?= (int)$topic['id'] ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
            <button type="submit" class="forum-link-btn forum-danger">Delete topic</button>
        </form>
        <?php endif; ?>
    </div>

    <ol class="forum-posts">
        <?php foreach ($posts as $i => $post): ?>
        <li class="forum-post" id="post-<?= (int)$post['id'] ?>">
            <?= forum_avatar($post['username']) ?>
            <div class="forum-post-main">
                <p class="forum-post-head">
                    <strong><?= htmlspecialchars($post['username']) ?></strong>
                    <?php if ($i === 0): ?><span class="q-type">Asked</span><?php endif; ?>
                    <time datetime="<?= htmlspecialchars(date('Y-m-d\TH:i', strtotime($post['created_at']))) ?>"><?= htmlspecialchars(date('j M Y', strtotime($post['created_at']))) ?></time>
                </p>
                <p class="forum-post-body"><?= nl2br(htmlspecialchars($post['body'])) ?></p>
                <div class="forum-post-foot">
                    <?php if (is_logged_in()): ?>
                    <form method="post" action="forum.php">
                        <input type="hidden" name="action" value="like">
                        <input type="hidden" name="post_id" value="<?= (int)$post['id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                        <button type="submit" class="forum-like<?= $post['liked'] ? ' is-liked' : '' ?>" aria-pressed="<?= $post['liked'] ? 'true' : 'false' ?>"
                                aria-label="Like this post (<?= (int)$post['likes'] ?> so far)">♥ <?= (int)$post['likes'] ?></button>
                    </form>
                    <?php else: ?>
                    <span class="forum-like" aria-label="<?= (int)$post['likes'] ?> likes">♥ <?= (int)$post['likes'] ?></span>
                    <?php endif; ?>
                    <?php if ($i > 0 && is_logged_in() && (forum_is_moderator() || (int)$post['user_id'] === CURRENT_USER_ID)): ?>
                    <form method="post" action="forum.php" data-confirm="Delete this reply?">
                        <input type="hidden" name="action" value="delete_post">
                        <input type="hidden" name="post_id" value="<?= (int)$post['id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                        <button type="submit" class="forum-link-btn forum-danger">Delete</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </li>
        <?php endforeach; ?>
    </ol>

    <?php if (is_logged_in()): ?>
    <form method="post" action="forum.php" class="forum-form forum-reply" id="reply">
        <input type="hidden" name="action" value="reply">
        <input type="hidden" name="topic_id" value="<?= (int)$topic['id'] ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
        <?php if ($error !== null): ?>
        <p class="auth-error" role="alert"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <label for="forum-reply-box">Your reply</label>
        <textarea id="forum-reply-box" name="body" rows="4" maxlength="<?= FORUM_BODY_MAX_LENGTH ?>" required><?= htmlspecialchars($draft['body']) ?></textarea>
        <button type="submit" class="btn btn-primary btn-small">Post reply</button>
    </form>
    <?php else: ?>
    <p class="forum-reply forum-login-note">
        <a href="login.php?next=<?= urlencode('forum.php?topic=' . (int)$topic['id']) ?>">Log in or register</a> to reply or like a post.
    </p>
    <?php endif; ?>
<?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
