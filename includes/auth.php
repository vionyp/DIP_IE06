<?php
/**
 * StockSense — login session helpers.
 *
 * Include this before any output (it starts the PHP session). It defines
 * CURRENT_USER_ID: the logged-in user's id, or 0 when nobody is logged in.
 * Pages that read or save per-user data call require_login() right after
 * including it; api/ endpoints call require_login_json() instead.
 *
 * Accounts live in the `users` table. Passwords are stored only as
 * password_hash() output (bcrypt) and checked with password_verify().
 */

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

define('CURRENT_USER_ID', (int)($_SESSION['user_id'] ?? 0));

const USERNAME_MIN_LENGTH = 3;
const USERNAME_MAX_LENGTH = 50;
const PASSWORD_MIN_LENGTH = 8;

function is_logged_in(): bool
{
    return CURRENT_USER_ID > 0;
}

function current_username(): ?string
{
    return is_logged_in() ? ($_SESSION['username'] ?? null) : null;
}

/**
 * Only same-folder page links (e.g. "quiz.php?category=macro") are accepted
 * as a post-login destination, so ?next= can't send someone to another site.
 */
function safe_next(?string $next): string
{
    if ($next !== null && preg_match('/^[a-z_]+\.php(\?[^\r\n]*)?$/', $next)) {
        return $next;
    }
    return 'dashboard.php';
}

/** For files in /pages/: send visitors who aren't logged in to the login page. */
function require_login(): void
{
    if (is_logged_in()) {
        return;
    }
    $here = basename($_SERVER['SCRIPT_NAME']);
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_SERVER['QUERY_STRING'])) {
        $here .= '?' . $_SERVER['QUERY_STRING'];
    }
    header('Location: login.php?next=' . urlencode($here));
    exit;
}

/** For files in /api/: answer with a 401 JSON error instead of redirecting. */
function require_login_json(): void
{
    if (is_logged_in()) {
        return;
    }
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'Not logged in']);
    exit;
}

function log_in_user(int $id, string $username): void
{
    // New session id on login so a pre-login session id can't be reused.
    session_regenerate_id(true);
    $_SESSION['user_id'] = $id;
    $_SESSION['username'] = $username;
}

function log_out_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/** One token per session, sent as a hidden field with the login/register/logout forms. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_check(): bool
{
    $sent = $_POST['csrf_token'] ?? '';
    return is_string($sent) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $sent);
}
