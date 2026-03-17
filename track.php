<?php
/**
 * Visitor tracking endpoint.
 *
 * POST action=visit  – log a new page visit; returns JSON {visit_id: N}
 * POST action=update – update time_on_site for an existing visit
 *
 * Bots (identified by User-Agent) are silently ignored.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// ── Bot detection ────────────────────────────────────────────────────────────
function visitor_is_bot(string $ua): bool
{
    if (empty($ua)) {
        return true;
    }
    $ua_lower = strtolower($ua);
    $patterns = [
        'bot', 'crawl', 'spider', 'slurp', 'search', 'fetch', 'scan',
        'index', 'archive', 'wget', 'curl', 'python', 'scrapy',
        'httpclient', 'libwww', 'java/', 'go-http', 'ruby',
    ];
    foreach ($patterns as $pattern) {
        if (strpos($ua_lower, $pattern) !== false) {
            return true;
        }
    }
    return false;
}

$action = trim($_POST['action'] ?? '');
$ua     = $_SERVER['HTTP_USER_AGENT'] ?? '';

// ── action=visit ─────────────────────────────────────────────────────────────
if ($action === 'visit') {
    if (visitor_is_bot($ua)) {
        echo json_encode(['visit_id' => null, 'bot' => true]);
        exit;
    }

    $ip       = $_SERVER['REMOTE_ADDR'] ?? '';
    $referrer = substr(trim($_POST['referrer'] ?? ''), 0, 512);
    $ua_clean = substr($ua, 0, 512);

    // UTM / click-id parameters sent by the JS beacon
    $utm_source   = substr(trim($_POST['utm_source']   ?? ''), 0, 100) ?: null;
    $utm_medium   = substr(trim($_POST['utm_medium']   ?? ''), 0, 100) ?: null;
    $utm_campaign = substr(trim($_POST['utm_campaign'] ?? ''), 0, 150) ?: null;
    $utm_content  = substr(trim($_POST['utm_content']  ?? ''), 0, 150) ?: null;
    $utm_term     = substr(trim($_POST['utm_term']     ?? ''), 0, 150) ?: null;
    $gclid        = substr(trim($_POST['gclid']        ?? ''), 0, 200) ?: null;
    $landing_page = substr(trim($_POST['landing_page'] ?? ''), 0, 512) ?: null;

    try {
        $pdo  = db_connect();
        $stmt = $pdo->prepare(
            'INSERT INTO visitor_logs
                 (ip_address, referrer, user_agent,
                  utm_source, utm_medium, utm_campaign, utm_content, utm_term, gclid, landing_page)
             VALUES
                 (:ip, :ref, :ua,
                  :us,  :um,  :uc,  :uct, :ut,  :gl,  :lp)'
        );
        $stmt->execute([
            ':ip'  => $ip,
            ':ref' => $referrer,
            ':ua'  => $ua_clean,
            ':us'  => $utm_source,
            ':um'  => $utm_medium,
            ':uc'  => $utm_campaign,
            ':uct' => $utm_content,
            ':ut'  => $utm_term,
            ':gl'  => $gclid,
            ':lp'  => $landing_page,
        ]);
        $visit_id = (int) $pdo->lastInsertId();

        // Store in session so submit_lead.php can link the row to the lead.
        // Note: session_start() is called via config/config.php which is required above.
        $_SESSION['visit_id'] = $visit_id;

        echo json_encode(['visit_id' => $visit_id]);
    } catch (PDOException $e) {
        error_log('[VerlustRückholung] Visitor log insert error: ' . $e->getMessage());
        echo json_encode(['visit_id' => null]);
    }

// ── action=update ────────────────────────────────────────────────────────────
} elseif ($action === 'update') {
    $visit_id    = (int) ($_POST['visit_id']    ?? 0);
    $time_on_site = (int) ($_POST['time_on_site'] ?? 0);

    if ($visit_id <= 0) {
        echo json_encode(['ok' => false]);
        exit;
    }

    // Cap at 6 hours to reject obviously wrong values.
    $time_on_site = max(0, min($time_on_site, 21600));

    try {
        $pdo  = db_connect();
        $stmt = $pdo->prepare(
            'UPDATE visitor_logs SET time_on_site = :t WHERE id = :id'
        );
        $stmt->execute([':t' => $time_on_site, ':id' => $visit_id]);
        echo json_encode(['ok' => true]);
    } catch (PDOException $e) {
        error_log('[VerlustRückholung] Visitor log update error: ' . $e->getMessage());
        echo json_encode(['ok' => false]);
    }

} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
