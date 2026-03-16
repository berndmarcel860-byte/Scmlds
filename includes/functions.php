<?php
require_once __DIR__ . '/db.php';

function sanitize(string $value): string {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

function get_dashboard_stats(): array {
    $pdo = db_connect();

    $stats = [];

    $stmt = $pdo->query('SELECT COUNT(*) FROM leads');
    $stats['total'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM leads WHERE status = 'Neu'");
    $stats['new'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM leads WHERE status = 'In Bearbeitung'");
    $stats['in_progress'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM leads WHERE status = 'Erfolgreich'");
    $stats['successful'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM leads WHERE status = 'Abgelehnt'");
    $stats['rejected'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->query('SELECT COALESCE(SUM(amount_lost), 0) FROM leads');
    $stats['total_amount'] = (float) $stmt->fetchColumn();

    $stmt = $pdo->query(
        "SELECT DATE(created_at) as day, COUNT(*) as cnt
         FROM leads
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
         GROUP BY DATE(created_at)
         ORDER BY day ASC"
    );
    $stats['chart_data'] = $stmt->fetchAll();

    // Leads by category
    $stmt = $pdo->query(
        'SELECT platform_category, COUNT(*) as cnt FROM leads GROUP BY platform_category ORDER BY cnt DESC'
    );
    $stats['by_category'] = $stmt->fetchAll();

    return $stats;
}

function get_leads(array $filters = [], int $page = 1, int $per_page = 20): array {
    $pdo = db_connect();
    $where = ['1=1'];
    $params = [];

    if (!empty($filters['status'])) {
        $where[] = 'status = :status';
        $params[':status'] = $filters['status'];
    }
    if (!empty($filters['search'])) {
        $where[] = '(first_name LIKE :s OR last_name LIKE :s OR email LIKE :s OR phone LIKE :s)';
        $params[':s'] = '%' . $filters['search'] . '%';
    }
    if (!empty($filters['category'])) {
        $where[] = 'platform_category = :cat';
        $params[':cat'] = $filters['category'];
    }

    $whereSQL = implode(' AND ', $where);
    $offset = ($page - 1) * $per_page;

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE $whereSQL");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $stmt = $pdo->prepare(
        "SELECT * FROM leads WHERE $whereSQL ORDER BY created_at DESC LIMIT :limit OFFSET :offset"
    );
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();

    return ['data' => $rows, 'total' => $total, 'pages' => (int) ceil($total / $per_page)];
}

function get_lead(int $id): ?array {
    $pdo = db_connect();
    $stmt = $pdo->prepare('SELECT * FROM leads WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function update_lead(int $id, array $data): bool {
    $pdo = db_connect();
    $stmt = $pdo->prepare(
        'UPDATE leads SET first_name=:fn, last_name=:ln, email=:em, phone=:ph,
         amount_lost=:al, platform_category=:pc, case_description=:cd,
         status=:st, admin_notes=:an
         WHERE id=:id'
    );
    return $stmt->execute([
        ':fn' => $data['first_name'],
        ':ln' => $data['last_name'],
        ':em' => $data['email'],
        ':ph' => $data['phone'],
        ':al' => $data['amount_lost'],
        ':pc' => $data['platform_category'],
        ':cd' => $data['case_description'],
        ':st' => $data['status'],
        ':an' => $data['admin_notes'],
        ':id' => $id,
    ]);
}

function delete_lead(int $id): bool {
    $pdo = db_connect();
    $stmt = $pdo->prepare('DELETE FROM leads WHERE id = :id');
    return $stmt->execute([':id' => $id]);
}

function format_currency(float $amount): string {
    return number_format($amount, 2, ',', '.') . ' €';
}

function status_badge_class(string $status): string {
    return match ($status) {
        'Neu'           => 'bg-primary',
        'In Bearbeitung'=> 'bg-warning text-dark',
        'Kontaktiert'   => 'bg-info text-dark',
        'Erfolgreich'   => 'bg-success',
        'Abgelehnt'     => 'bg-danger',
        default         => 'bg-secondary',
    };
}

function get_visitor_stats(): array
{
    $pdo   = db_connect();
    $stats = [];

    $stmt = $pdo->query('SELECT COUNT(*) FROM visitor_logs');
    $stats['total_visits'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->query('SELECT COUNT(*) FROM visitor_logs WHERE time_on_site > 30');
    $stats['over_30s'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->query('SELECT COALESCE(AVG(time_on_site), 0) FROM visitor_logs');
    $stats['avg_time'] = (float) $stmt->fetchColumn();

    $stmt = $pdo->query('SELECT COUNT(*) FROM visitor_logs WHERE submitted_lead = 1');
    $stats['total_leads_from_visitors'] = (int) $stmt->fetchColumn();

    // Conversion rate (visitors who submitted a lead / total visitors)
    $stats['conversion_rate'] = $stats['total_visits'] > 0
        ? round(($stats['total_leads_from_visitors'] / $stats['total_visits']) * 100, 1)
        : 0;

    // Daily visits over the last 30 days
    $stmt = $pdo->query(
        "SELECT DATE(created_at) as day, COUNT(*) as cnt
         FROM visitor_logs
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
         GROUP BY DATE(created_at)
         ORDER BY day ASC"
    );
    $stats['chart_data'] = $stmt->fetchAll();

    // Top referrers
    $stmt = $pdo->query(
        "SELECT
            CASE
                WHEN referrer = '' OR referrer IS NULL THEN 'Direkt'
                ELSE referrer
            END AS source,
            COUNT(*) as cnt
         FROM visitor_logs
         GROUP BY source
         ORDER BY cnt DESC
         LIMIT 10"
    );
    $stats['top_referrers'] = $stmt->fetchAll();

    return $stats;
}

function get_visitor_logs(array $filters = [], int $page = 1, int $per_page = 50): array
{
    $pdo    = db_connect();
    $where  = ['1=1'];
    $params = [];

    if (!empty($filters['ip'])) {
        $where[]         = 'ip_address LIKE :ip';
        $params[':ip']   = '%' . $filters['ip'] . '%';
    }
    if (isset($filters['with_lead']) && $filters['with_lead'] !== '') {
        $where[]              = 'submitted_lead = :sl';
        $params[':sl']        = (int) $filters['with_lead'];
    }
    if (!empty($filters['over_30s'])) {
        $where[] = 'time_on_site > 30';
    }

    $whereSQL = implode(' AND ', $where);
    $offset   = ($page - 1) * $per_page;

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM visitor_logs WHERE $whereSQL");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $stmt = $pdo->prepare(
        "SELECT vl.*, l.first_name, l.last_name, l.email
         FROM visitor_logs vl
         LEFT JOIN leads l ON l.id = vl.lead_id
         WHERE $whereSQL
         ORDER BY vl.created_at DESC
         LIMIT :limit OFFSET :offset"
    );
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();

    return ['data' => $rows, 'total' => $total, 'pages' => (int) ceil($total / $per_page)];
}

function format_duration(int $seconds): string
{
    if ($seconds < 60) {
        return $seconds . 's';
    }
    $m = (int) floor($seconds / 60);
    $s = $seconds % 60;
    return $m . 'min ' . $s . 's';
}

// ── Site Settings ────────────────────────────────────────────────────────────

/**
 * Return a single setting value from the `settings` table.
 * Results are cached per request.
 */
function get_setting(string $key, string $default = ''): string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            $pdo  = db_connect();
            $stmt = $pdo->query('SELECT setting_key, setting_value FROM settings');
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $cache[$row['setting_key']] = (string) $row['setting_value'];
            }
        } catch (PDOException $e) {
            error_log('[VerlustRückholung] get_setting() failed: ' . $e->getMessage());
        }
    }
    return $cache[$key] ?? $default;
}

/**
 * Return all settings rows grouped by setting_group.
 */
function get_all_settings(): array
{
    try {
        $pdo  = db_connect();
        $stmt = $pdo->query(
            'SELECT setting_key, setting_value, setting_label, setting_group
             FROM settings ORDER BY setting_group, id'
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('[VerlustRückholung] get_all_settings() failed: ' . $e->getMessage());
        return [];
    }
}

/** Persist one or many settings. $data is [key => value]. */
function save_settings(array $data): bool
{
    try {
        $pdo  = db_connect();
        $stmt = $pdo->prepare(
            'UPDATE settings SET setting_value = :val WHERE setting_key = :key'
        );
        foreach ($data as $key => $value) {
            $stmt->execute([':key' => $key, ':val' => $value]);
        }
        return true;
    } catch (PDOException $e) {
        error_log('[VerlustRückholung] save_settings() failed: ' . $e->getMessage());
        return false;
    }
}

// ── SMTP Settings ─────────────────────────────────────────────────────────────

function get_smtp_settings(): array
{
    try {
        $pdo  = db_connect();
        $stmt = $pdo->query('SELECT * FROM smtp_settings ORDER BY id LIMIT 1');
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        error_log('[VerlustRückholung] get_smtp_settings() failed: ' . $e->getMessage());
        return [];
    }
}

function save_smtp_settings(array $data): bool
{
    try {
        $pdo  = db_connect();
        $stmt = $pdo->query('SELECT COUNT(*) FROM smtp_settings');
        $exists = (int) $stmt->fetchColumn() > 0;

        if ($exists) {
            $stmt = $pdo->prepare(
                'UPDATE smtp_settings SET host=:host, port=:port, username=:user,
                 password=:pass, secure=:sec, debug=:dbg, from_email=:fe, from_name=:fn
                 WHERE id = (SELECT id FROM (SELECT id FROM smtp_settings ORDER BY id LIMIT 1) t)'
            );
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO smtp_settings (host, port, username, password, secure, debug, from_email, from_name)
                 VALUES (:host, :port, :user, :pass, :sec, :dbg, :fe, :fn)'
            );
        }
        $stmt->execute([
            ':host' => $data['host']       ?? '',
            ':port' => (int) ($data['port'] ?? 587),
            ':user' => $data['username']   ?? '',
            ':pass' => $data['password']   ?? '',
            ':sec'  => $data['secure']     ?? 'tls',
            ':dbg'  => (int) ($data['debug'] ?? 0),
            ':fe'   => $data['from_email'] ?? '',
            ':fn'   => $data['from_name']  ?? '',
        ]);
        return true;
    } catch (PDOException $e) {
        error_log('[VerlustRückholung] save_smtp_settings() failed: ' . $e->getMessage());
        return false;
    }
}

// ── Telegram Settings ─────────────────────────────────────────────────────────

function get_telegram_settings(): array
{
    try {
        $pdo  = db_connect();
        $stmt = $pdo->query('SELECT * FROM telegram_settings ORDER BY id LIMIT 1');
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        error_log('[VerlustRückholung] get_telegram_settings() failed: ' . $e->getMessage());
        return [];
    }
}

function save_telegram_settings(array $data): bool
{
    try {
        $pdo  = db_connect();
        $stmt = $pdo->query('SELECT COUNT(*) FROM telegram_settings');
        $exists = (int) $stmt->fetchColumn() > 0;

        if ($exists) {
            $stmt = $pdo->prepare(
                'UPDATE telegram_settings SET bot_token=:tok, chat_id=:cid, active=:act
                 WHERE id = (SELECT id FROM (SELECT id FROM telegram_settings ORDER BY id LIMIT 1) t)'
            );
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO telegram_settings (bot_token, chat_id, active) VALUES (:tok, :cid, :act)'
            );
        }
        $stmt->execute([
            ':tok' => $data['bot_token'] ?? '',
            ':cid' => $data['chat_id']   ?? '',
            ':act' => (int) ($data['active'] ?? 0),
        ]);
        return true;
    } catch (PDOException $e) {
        error_log('[VerlustRückholung] save_telegram_settings() failed: ' . $e->getMessage());
        return false;
    }
}

// ── Telegram Notification ─────────────────────────────────────────────────────

function send_telegram_notification(array $data): bool
{
    $tg = get_telegram_settings();
    if (empty($tg['active']) || empty($tg['bot_token']) || empty($tg['chat_id'])) {
        return false;
    }

    $name     = $data['first_name'] . ' ' . $data['last_name'];
    $amount   = number_format((float) ($data['amount_lost'] ?? 0), 2, ',', '.') . ' €';
    $category = $data['platform_category'] ?? 'k.A.';
    $country  = $data['country']           ?? 'k.A.';
    $email    = $data['email']             ?? '';
    $phone    = $data['phone']             ?? 'k.A.';
    $ip       = $data['ip']                ?? '';

    $text = "🔔 *Neue Falleinreichung*\n\n"
          . "👤 *Name:* " . $name . "\n"
          . "📧 *E-Mail:* " . $email . "\n"
          . "📞 *Telefon:* " . $phone . "\n"
          . "🌍 *Land:* " . $country . "\n"
          . "💰 *Betrag:* " . $amount . "\n"
          . "🎯 *Betrugsart:* " . $category . "\n"
          . "🌐 *IP:* " . $ip . "\n"
          . "📅 *Zeit:* " . date('d.m.Y H:i:s');

    $url = 'https://api.telegram.org/bot' . rawurlencode($tg['bot_token']) . '/sendMessage';
    $payload = 'chat_id=' . rawurlencode($tg['chat_id'])
             . '&text='    . rawurlencode($text)
             . '&parse_mode=Markdown';

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        ]);
        curl_exec($ch);
        $ok = curl_errno($ch) === 0;
        curl_close($ch);
    } else {
        $ok = (bool) @file_get_contents(
            $url . '?' . $payload,
            false,
            stream_context_create(['http' => ['timeout' => 5]])
        );
    }

    if (!$ok) {
        error_log('[VerlustRückholung] Telegram notification failed.');
    }
    return $ok;
}
