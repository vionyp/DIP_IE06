<?php
require_once __DIR__ . '/../includes/auth.php';

// POST + token only, so a stray link or image tag can't log someone out.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    log_out_user();
}

header('Location: ../index.php');
exit;
