<?php
/**
 * SemiSense — fixed category taxonomy.
 * Used everywhere: education trail, quiz filters, dashboard breakdown.
 * Keep this list in sync with the ENUM values in sql/schema.sql.
 */

const CATEGORIES = [
    'geopolitical' => [
        'label' => 'Political / Geopolitical',
        'color' => '#2c4a7c',
        'icon'  => 'icon-geopolitical.png',
        'blurb' => 'Export controls, tariffs, and diplomatic tension between chip-making nations.',
    ],
    'earnings' => [
        'label' => 'Earnings Calls',
        'color' => '#2f8f5b',
        'icon'  => 'icon-earnings.png',
        'blurb' => 'Quarterly results, guidance, and how "beat vs. expectations" moves stocks.',
    ],
    'macro' => [
        'label' => 'Industry-Wide / Macro',
        'color' => '#7a4fbf',
        'icon'  => 'icon-macro.png',
        'blurb' => 'Sector-wide demand cycles that move nearly every chip stock at once.',
    ],
    'corporate' => [
        'label' => 'Corporate Actions',
        'color' => '#d97a2e',
        'icon'  => 'icon-corporate.png',
        'blurb' => 'M&A, restructuring, executive changes, and buybacks.',
    ],
    'supply_chain' => [
        'label' => 'Supply Chain',
        'color' => '#1f9a94',
        'icon'  => 'icon-supply-chain.png',
        'blurb' => 'Fab outages, shortages, and how one supplier\'s problem spreads forward.',
    ],
];

function category_exists(string $key): bool
{
    return array_key_exists($key, CATEGORIES);
}
