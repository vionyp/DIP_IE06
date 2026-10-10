<?php
/**
 * StockSense — forum settings (pages/forum.php).
 *
 * Topics, posts and likes live in the forum_topics / forum_posts /
 * forum_likes tables (sql/migrations/004_forum.sql). The boards are a fixed
 * list kept here, the same way the news categories are kept in
 * config/categories.php; forum_topics.board stores the key.
 */

// Boards (topic categories). The five news boards reuse the colours in
// config/categories.php so the forum matches the rest of the site.
// To add a board, add an entry here — no database change is needed.
const FORUM_BOARDS = [
    'beginner' => [
        'label' => 'Beginner Questions',
        'color' => '#e8963f',
        'blurb' => 'New to chip stocks or to StockSense? No question is too basic here.',
    ],
    'geopolitical' => [
        'label' => 'Political / Geopolitical',
        'color' => '#2c4a7c',
        'blurb' => 'Export controls, tariffs and how policy news reaches chip makers.',
    ],
    'earnings' => [
        'label' => 'Earnings Calls',
        'color' => '#2f8f5b',
        'blurb' => 'Beats, misses, guidance and why the reaction is not always obvious.',
    ],
    'macro' => [
        'label' => 'Industry-Wide / Macro',
        'color' => '#7a4fbf',
        'blurb' => 'Demand cycles and the news that moves the whole sector at once.',
    ],
    'corporate' => [
        'label' => 'Corporate Actions',
        'color' => '#d97a2e',
        'blurb' => 'Mergers, restructurings, leadership changes and buybacks.',
    ],
    'supply_chain' => [
        'label' => 'Supply Chain',
        'color' => '#1f9a94',
        'blurb' => 'Fab outages, shortages and how problems travel down the chain.',
    ],
    'game' => [
        'label' => 'Trading Game',
        'color' => '#6b6b6b',
        'blurb' => 'Strategies, high scores and questions about the Trading Game.',
    ],
];

// Usernames that can pin topics and delete anyone's topics or posts.
// Everyone else can only delete what they wrote themselves.
const FORUM_MODERATORS = ['admin'];

const FORUM_TITLE_MIN_LENGTH = 5;
const FORUM_TITLE_MAX_LENGTH = 150;
const FORUM_BODY_MAX_LENGTH = 5000;
