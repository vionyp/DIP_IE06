<?php
/**
 * Shared page header. Expects an optional $page_title to already be set
 * by the including page. Assumes it is included from a file living
 * directly inside /pages/ or as index.php in the project root — the
 * $base_url variable adjusts asset/link paths accordingly.
 * Optional: $extra_css (array of stylesheet paths relative to $base_url)
 * for page-specific styles, $main_class (extra class on <main>), and
 * $show_ticker = true to show the scrolling ticker strip under the navbar.
 */
if (!isset($base_url)) {
    $base_url = '.';
}
$page_title = $page_title ?? 'StockSense';
require_once __DIR__ . '/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($page_title) ?> · StockSense</title>
<link rel="stylesheet" href="<?= $base_url ?>/assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
<?php foreach (($extra_css ?? []) as $css): ?>
<link rel="stylesheet" href="<?= $base_url ?>/<?= htmlspecialchars($css) ?>">
<?php endforeach; ?>
</head>
<body>
<header class="site-header">
    <div class="site-header-inner">
        <a class="brand" href="<?= $base_url ?>/index.php">
            <span class="brand-mark">🔷</span> StockSense
        </a>
        <nav class="main-nav">
            <a href="<?= $base_url ?>/index.php">Home</a>
            <a href="<?= $base_url ?>/pages/dashboard.php">Dashboard</a>
            <a href="<?= $base_url ?>/pages/game.php">Trading Game</a>
            <a href="<?= $base_url ?>/pages/forum.php">Forum</a>
            <a href="<?= $base_url ?>/pages/disclaimer.php">Disclaimer</a>
            <?php if (is_logged_in()): ?>
            <span class="nav-user"><?= htmlspecialchars((string)current_username()) ?></span>
            <form method="post" action="<?= $base_url ?>/pages/logout.php" class="nav-logout">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                <button type="submit">Log out</button>
            </form>
            <?php else: ?>
            <a href="<?= $base_url ?>/pages/login.php" data-auth-open="login" class="nav-cta">Log in / Register</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<?php if (!empty($show_ticker)) { require __DIR__ . '/ticker.php'; } ?>
<main class="site-main<?= isset($main_class) ? ' ' . htmlspecialchars($main_class) : '' ?>">
