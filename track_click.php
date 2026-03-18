<?php
/**
 * Email link click-tracking endpoint.
 * URL: /track_click.php?t={click_token}&url={encoded_target_url}
 *
 * Records the click for the recipient identified by the click token, then
 * performs a transparent redirect to the original destination URL.
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$token      = trim($_GET['t']   ?? '');
$target_url = trim($_GET['url'] ?? '');

// Validate token format (32-char hex)
if ($token && preg_match('/^[a-f0-9]{32}$/', $token)) {
    record_mailing_click($token);
}

// Validate and sanitise the redirect URL.
// Only allow absolute http/https URLs that belong to the configured site domain.
$site_url    = rtrim(get_setting('site_url', ''), '/');
$allowed_host = parse_url($site_url, PHP_URL_HOST);
$redirect_to  = $site_url ?: '/';

if ($target_url && preg_match('/^https?:\/\//i', $target_url)) {
    $target_host = parse_url($target_url, PHP_URL_HOST);
    // Allow redirect only to the configured site's own domain
    if ($allowed_host && $target_host === $allowed_host) {
        $redirect_to = $target_url;
    }
}

header('Location: ' . $redirect_to, true, 302);
