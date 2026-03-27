<?php
/**
 * Front-page Router
 * Reads the active_design setting from the database and serves
 * the appropriate landing page design from themes/ or index2.php.
 *
 * Available designs:
 *   design_original – Original index.php design (dark navy + gold)
 *   index2          – Enhanced index2.php design (the merged professional page)
 *   design1         – Midnight Crypto (black + cyan)
 *   design2         – Legal Shield (navy + silver)
 *   design3         – Green Tech (forest green + neon)
 *   design4         – Corporate Clean (white + blue)
 *   design5         – Urgent Red (dark red + gold)
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$active_design = get_setting('active_design', 'index2');

$map = [
    'design_original' => __DIR__ . '/themes/design_original.php',
    'index2'          => __DIR__ . '/index2.php',
    'design1'         => __DIR__ . '/themes/design1.php',
    'design2'         => __DIR__ . '/themes/design2.php',
    'design3'         => __DIR__ . '/themes/design3.php',
    'design4'         => __DIR__ . '/themes/design4.php',
    'design5'         => __DIR__ . '/themes/design5.php',
];

$file = $map[$active_design] ?? __DIR__ . '/index2.php';

if (is_readable($file)) {
    require $file;
} else {
    // Fallback: serve index2 directly
    require __DIR__ . '/index2.php';
}
