</main>
<footer class="site-footer">
    <p>StockSense is an educational tool for learning how news affects semiconductor stocks.
       It is <strong>not financial advice</strong> and does not simulate real trading.
       Read the <a href="<?= $base_url ?>/pages/disclaimer.php">full disclaimer</a>.</p>
</footer>
<?php if (!is_logged_in() && !isset($auth_mode)): ?>
<!-- Login / register popup — opened by any [data-auth-open] link (see assets/js/app.js).
     Left out on login.php, which already shows the same card. -->
<div id="auth-modal" class="modal-overlay auth-modal" hidden>
    <div class="auth-modal-box" role="dialog" aria-modal="true" aria-label="Log in or register">
        <button type="button" class="auth-close" data-auth-close aria-label="Close">&times;</button>
        <?php $auth_mode = 'login'; require __DIR__ . '/auth_card.php'; ?>
    </div>
</div>
<?php endif; ?>
<script src="<?= $base_url ?>/assets/js/app.js?v=<?= filemtime(__DIR__ . '/../assets/js/app.js') ?>"></script>
</body>
</html>
