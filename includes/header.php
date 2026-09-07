<?php
/**
 * Shared page header. Expects an optional $page_title to already be set
 * by the including page. Assumes it is included from a file living
 * directly inside /pages/ or as index.php in the project root — the
 * $base_url variable adjusts asset/link paths accordingly.
 */
if (!isset($base_url)) {
    $base_url = '.';
}
$page_title = $page_title ?? 'StockSense';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($page_title) ?> · StockSense</title>
<link rel="stylesheet" href="<?= $base_url ?>/assets/css/style.css">
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
            <a href="<?= $base_url ?>/pages/disclaimer.php">Disclaimer</a>
        </nav>
    </div>
</header>
<main class="site-main">
