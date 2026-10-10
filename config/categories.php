<?php
/**
 * StockSense — fixed category taxonomy.
 * Used everywhere: education trail, quiz filters, dashboard breakdown.
 * Keep this list in sync with the ENUM values in sql/schema.sql.
 *
 * 'overview' is the text a dashboard card reveals when it expands (hover,
 * keyboard focus or tap): what the user gets by clicking Lesson or Quiz.
 */

// How many questions make up one quiz attempt — shared by quiz.php (draws
// this many, one at a time) and dashboard.php (only a session with exactly
// this many answered rows counts as a complete "X / N" attempt).
const QUESTIONS_PER_ATTEMPT = 5;

const CATEGORIES = [
    'geopolitical' => [
        'label' => 'Political / Geopolitical',
        'color' => '#2c4a7c',
        'icon'  => 'icon-geopolitical.png',
        'blurb' => 'Export controls, tariffs, and diplomatic tension between chip-making nations.',
        'overview' => 'Lesson walks you through how export controls and tariffs hit chip makers. Quiz draws a fresh set of questions on that kind of news and shows the answer and an explanation after each one.',
    ],
    'earnings' => [
        'label' => 'Earnings Calls',
        'color' => '#2f8f5b',
        'icon'  => 'icon-earnings.png',
        'blurb' => 'Quarterly results, guidance, and how "beat vs. expectations" moves stocks.',
        'overview' => 'Lesson shows how to read a quarterly result against expectations. Quiz draws a fresh set of questions on that kind of news and shows the answer and an explanation after each one.',
    ],
    'macro' => [
        'label' => 'Industry-Wide / Macro',
        'color' => '#7a4fbf',
        'icon'  => 'icon-macro.png',
        'blurb' => 'Sector-wide demand cycles that move nearly every chip stock at once.',
        'overview' => 'Lesson covers the demand cycles that lift or sink the whole sector. Quiz draws a fresh set of questions on that kind of news and shows the answer and an explanation after each one.',
    ],
    'corporate' => [
        'label' => 'Corporate Actions',
        'color' => '#d97a2e',
        'icon'  => 'icon-corporate.png',
        'blurb' => 'M&A, restructuring, executive changes, and buybacks.',
        'overview' => 'Lesson explains how deals, restructurings and leadership changes get priced in. Quiz draws a fresh set of questions on that kind of news and shows the answer and an explanation after each one.',
    ],
    'supply_chain' => [
        'label' => 'Supply Chain',
        'color' => '#1f9a94',
        'icon'  => 'icon-supply-chain.png',
        'blurb' => 'Fab outages, shortages, and how one supplier\'s problem spreads forward.',
        'overview' => 'Lesson traces how one supplier\'s outage spreads down the chain. Quiz draws a fresh set of questions on that kind of news and shows the answer and an explanation after each one.',
    ],
];

function category_exists(string $key): bool
{
    return array_key_exists($key, CATEGORIES);
}
