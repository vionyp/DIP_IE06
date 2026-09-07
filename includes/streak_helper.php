<?php
/**
 * Updates the streak for $user_id, to be called once per completed
 * quiz submission (not once per question). Streak increments at most
 * once per calendar day:
 *   - same day as last_active_date  -> no change
 *   - exactly one day after         -> current_streak + 1
 *   - more than one day after, or no prior activity -> current_streak = 1
 */
function update_streak(PDO $pdo, int $user_id): array
{
    $stmt = $pdo->prepare('SELECT current_streak, longest_streak, last_active_date FROM streaks WHERE user_id = :uid');
    $stmt->execute(['uid' => $user_id]);
    $row = $stmt->fetch();

    $today = new DateTime('today');

    if (!$row) {
        // Safety net in case a streaks row doesn't exist yet.
        $current = 1;
        $longest = 1;
    } else {
        $current = (int)$row['current_streak'];
        $longest = (int)$row['longest_streak'];
        $last    = $row['last_active_date'] ? new DateTime($row['last_active_date']) : null;

        if ($last === null) {
            $current = 1;
        } else {
            $diffDays = (int)$today->diff($last)->format('%a');
            if ($diffDays === 0) {
                // already counted today, leave $current as-is
            } elseif ($diffDays === 1 && $today > $last) {
                $current += 1;
            } else {
                $current = 1;
            }
        }
        $longest = max($longest, $current);
    }

    $upsert = $pdo->prepare('
        INSERT INTO streaks (user_id, current_streak, longest_streak, last_active_date)
        VALUES (:uid, :cur, :longest, :today)
        ON DUPLICATE KEY UPDATE
            current_streak = VALUES(current_streak),
            longest_streak = VALUES(longest_streak),
            last_active_date = VALUES(last_active_date)
    ');
    $upsert->execute([
        'uid'     => $user_id,
        'cur'     => $current,
        'longest' => $longest,
        'today'   => $today->format('Y-m-d'),
    ]);

    return ['current_streak' => $current, 'longest_streak' => $longest];
}
