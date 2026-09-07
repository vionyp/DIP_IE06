<?php
/**
 * StockSense — database connection
 *
 * Edit the four constants below to match your local XAMPP MySQL setup.
 * Default XAMPP MySQL has user 'root' with an EMPTY password — change
 * DB_USER/DB_PASS here if you created a dedicated 'stocksense' user
 * (recommended — see sql/schema.sql comments / README).
 */

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'stocksense');
define('DB_USER', 'root');
define('DB_PASS', '');

function get_db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            die('Database connection failed. Check config/db.php and confirm the "stocksense" '
                . 'database has been imported (see sql/schema.sql). Error: ' . htmlspecialchars($e->getMessage()));
        }
    }

    return $pdo;
}

/**
 * v1 has no login/signup flow — every visitor acts as the single
 * seeded "guest" user (id = 1) so streaks and quiz history have
 * somewhere to persist to. Swap this out if you add real accounts.
 */
define('CURRENT_USER_ID', 1);
