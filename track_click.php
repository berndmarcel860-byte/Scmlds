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
// Allow any absolute http/https URL so that tracked external links work correctly.
$_fallback_url = rtrim(get_setting('site_url', '/'), '/') ?: '/';
$redirect_to   = $_fallback_url;

if ($target_url && preg_match('/^https?:\/\//i', $target_url)) {
    // Only block javascript: and data: schemes (which can't start with http/https anyway,
    // but the regex above already filters them). All http/https targets are safe to redirect to.
    $redirect_to = $target_url;
}

header('Location: ' . $redirect_to, true, 302);
exit;
