<?php
/**
 * Login / register card — one box with a "Log in" and a "Register" tab.
 * Used as the body of pages/login.php, and inside the popup that
 * includes/footer.php adds for logged-out visitors. Both forms post to
 * pages/login.php; the hidden "action" field says which one was submitted.
 *
 * Expects: $base_url, $auth_mode ('login' | 'register').
 * Optional: $auth_error (shown on the $auth_mode tab), $auth_username
 * (refills the field after a failed submit), $auth_next (post-login page).
 *
 * The tabs are plain links (login.php, login.php?tab=register), so they work without JS;
 * assets/js/app.js switches the panels in place when it is available.
 */
$auth_error    = $auth_error ?? null;
$auth_username = $auth_username ?? '';
$auth_next     = $auth_next ?? null;
$auth_url      = $base_url . '/pages/login.php';
$auth_next_qs  = $auth_next !== null ? 'next=' . urlencode($auth_next) : '';
?>
<div class="auth-card" data-auth-card>
    <div class="auth-side">
        <img src="<?= $base_url ?>/assets/img/mascot-base.png" alt="">
        <h2>Welcome to StockSense</h2>
        <p>Your quiz answers, daily streak and trading-game scores are saved to your account.</p>
    </div>

    <div class="auth-main">
        <div class="auth-tabs">
            <a href="<?= $auth_url ?><?= $auth_next_qs !== '' ? '?' . htmlspecialchars($auth_next_qs) : '' ?>" data-auth-tab="login"
               class="auth-tab<?= $auth_mode === 'login' ? ' active' : '' ?>">Log in</a>
            <a href="<?= $auth_url ?>?tab=register<?= $auth_next_qs !== '' ? '&amp;' . htmlspecialchars($auth_next_qs) : '' ?>" data-auth-tab="register"
               class="auth-tab<?= $auth_mode === 'register' ? ' active' : '' ?>">Register</a>
        </div>

        <form method="post" action="<?= $auth_url ?>" class="auth-form"
              data-auth-panel="login"<?= $auth_mode === 'login' ? '' : ' hidden' ?>>
            <input type="hidden" name="action" value="login">
            <?php if ($auth_error !== null && $auth_mode === 'login'): ?>
            <p class="auth-error"><?= htmlspecialchars($auth_error) ?></p>
            <?php endif; ?>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
            <?php if ($auth_next !== null): ?>
            <input type="hidden" name="next" value="<?= htmlspecialchars($auth_next) ?>">
            <?php endif; ?>

            <label for="login-username">Username</label>
            <input type="text" id="login-username" name="username"
                   value="<?= $auth_mode === 'login' ? htmlspecialchars($auth_username) : '' ?>"
                   maxlength="<?= USERNAME_MAX_LENGTH ?>" autocomplete="username" required>

            <label for="login-password">Password</label>
            <div class="auth-password">
                <input type="password" id="login-password" name="password" autocomplete="current-password" required>
                <button type="button" class="auth-toggle" data-auth-toggle="login-password">Show</button>
            </div>

            <button type="submit" class="btn btn-primary">Log in</button>
        </form>

        <form method="post" action="<?= $auth_url ?>" class="auth-form"
              data-auth-panel="register"<?= $auth_mode === 'register' ? '' : ' hidden' ?>>
            <input type="hidden" name="action" value="register">
            <?php if ($auth_error !== null && $auth_mode === 'register'): ?>
            <p class="auth-error"><?= htmlspecialchars($auth_error) ?></p>
            <?php endif; ?>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
            <?php if ($auth_next !== null): ?>
            <input type="hidden" name="next" value="<?= htmlspecialchars($auth_next) ?>">
            <?php endif; ?>

            <label for="register-username">Username</label>
            <input type="text" id="register-username" name="username"
                   value="<?= $auth_mode === 'register' ? htmlspecialchars($auth_username) : '' ?>"
                   minlength="<?= USERNAME_MIN_LENGTH ?>" maxlength="<?= USERNAME_MAX_LENGTH ?>"
                   pattern="[A-Za-z0-9_]+" autocomplete="username" required>
            <p class="auth-hint">Letters, numbers and underscores.</p>

            <label for="register-password">Password</label>
            <div class="auth-password">
                <input type="password" id="register-password" name="password"
                       minlength="<?= PASSWORD_MIN_LENGTH ?>" autocomplete="new-password" required>
                <button type="button" class="auth-toggle" data-auth-toggle="register-password">Show</button>
            </div>
            <p class="auth-hint">At least <?= PASSWORD_MIN_LENGTH ?> characters.</p>

            <label for="register-confirm">Confirm password</label>
            <input type="password" id="register-confirm" name="password_confirm"
                   minlength="<?= PASSWORD_MIN_LENGTH ?>" autocomplete="new-password" required>

            <button type="submit" class="btn btn-primary">Create account</button>
        </form>
    </div>
</div>
