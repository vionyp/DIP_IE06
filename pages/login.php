<?php
/**
 * StockSense — log in and register, on one page.
 *
 * Both forms live in the same card (includes/auth_card.php) and post here;
 * the hidden "action" field says which one was submitted. ?tab=register
 * opens the page on the Register tab.
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

$next = safe_next($_GET['next'] ?? $_POST['next'] ?? null);

if (is_logged_in()) {
    header('Location: ' . $next);
    exit;
}

$mode = ($_POST['action'] ?? $_GET['tab'] ?? '') === 'register' ? 'register' : 'login';
$error = null;
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $pdo = get_db();

    if (!csrf_check()) {
        $error = 'Your session expired. Please try again.';
    } elseif ($mode === 'login') {
        $stmt = $pdo->prepare('SELECT id, username, password_hash FROM users WHERE username = :u');
        $stmt->execute(['u' => $username]);
        $user = $stmt->fetch();

        // The seeded "guest" row has no password_hash, so it can never log in.
        if ($user && $user['password_hash'] !== null && password_verify($password, $user['password_hash'])) {
            log_in_user((int)$user['id'], $user['username']);
            header('Location: ' . $next);
            exit;
        }
        // Same message for an unknown username and a wrong password.
        $error = 'Wrong username or password.';
    } elseif (!preg_match('/^[A-Za-z0-9_]{' . USERNAME_MIN_LENGTH . ',' . USERNAME_MAX_LENGTH . '}$/', $username)) {
        $error = 'Username must be ' . USERNAME_MIN_LENGTH . '–' . USERNAME_MAX_LENGTH
            . ' characters: letters, numbers and underscores only.';
    } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
        $error = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters.';
    } elseif ($password !== (string)($_POST['password_confirm'] ?? '')) {
        $error = 'The two passwords do not match.';
    } else {
        try {
            // Only the hash is stored — never the password itself.
            $pdo->prepare('INSERT INTO users (username, password_hash) VALUES (:u, :h)')
                ->execute(['u' => $username, 'h' => password_hash($password, PASSWORD_DEFAULT)]);
            $id = (int)$pdo->lastInsertId();

            $pdo->prepare('INSERT INTO streaks (user_id) VALUES (:uid)')->execute(['uid' => $id]);

            log_in_user($id, $username);
            header('Location: ' . $next);
            exit;
        } catch (PDOException $e) {
            // 23000 = the UNIQUE key on users.username rejected a duplicate.
            if ($e->getCode() !== '23000') {
                throw $e;
            }
            $error = 'That username is already taken.';
        }
    }
}

$base_url = '..';
$page_title = $mode === 'register' ? 'Register' : 'Log in';
$main_class = 'auth-main-wrap';
require __DIR__ . '/../includes/header.php';

$auth_mode = $mode;
$auth_error = $error;
$auth_username = $username;
$auth_next = $next;
require __DIR__ . '/../includes/auth_card.php';

require __DIR__ . '/../includes/footer.php';
