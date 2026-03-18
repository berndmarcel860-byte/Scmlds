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
         country=:co, year_lost=:yl,
         amount_lost=:al, platform_category=:pc, case_description=:cd,
         status=:st, admin_notes=:an,
         lead_source=:ls, utm_source=:us
         WHERE id=:id'
    );
    return $stmt->execute([
        ':fn' => $data['first_name'],
        ':ln' => $data['last_name'],
        ':em' => $data['email'],
        ':ph' => $data['phone'],
        ':co' => $data['country']    ?? null,
        ':yl' => $data['year_lost']  !== '' ? (int) $data['year_lost'] : null,
        ':al' => $data['amount_lost'],
        ':pc' => $data['platform_category'],
        ':cd' => $data['case_description'],
        ':st' => $data['status'],
        ':an' => $data['admin_notes'],
        ':ls' => $data['lead_source'] ?? 'website',
        ':us' => $data['utm_source']  !== '' ? $data['utm_source'] : null,
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

    // UTM source breakdown (visits + leads)
    $stmt = $pdo->query(
        "SELECT
            COALESCE(utm_source, '(keine)') AS utm_source,
            COUNT(*)                         AS visits,
            SUM(submitted_lead)              AS leads
         FROM visitor_logs
         GROUP BY utm_source
         ORDER BY visits DESC
         LIMIT 20"
    );
    $stats['utm_breakdown'] = $stmt->fetchAll();

    // Lead source breakdown from leads table
    $stmt = $pdo->query(
        "SELECT
            COALESCE(lead_source, 'website') AS lead_source,
            COUNT(*)                          AS cnt
         FROM leads
         GROUP BY lead_source
         ORDER BY cnt DESC"
    );
    $stats['lead_source_breakdown'] = $stmt->fetchAll();

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

// ── Blog Posts ────────────────────────────────────────────────────────────────

function get_blog_posts(array $filters = [], int $page = 1, int $per_page = 20): array
{
    $pdo    = db_connect();
    $where  = ['1=1'];
    $params = [];

    if (!empty($filters['status'])) {
        $where[]           = 'status = :status';
        $params[':status'] = $filters['status'];
    }
    if (!empty($filters['search'])) {
        $where[]      = '(title LIKE :s OR excerpt LIKE :s)';
        $params[':s'] = '%' . $filters['search'] . '%';
    }

    $whereSQL = implode(' AND ', $where);
    $offset   = ($page - 1) * $per_page;

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM blog_posts WHERE $whereSQL");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $stmt = $pdo->prepare(
        "SELECT id, title, slug, excerpt, status, featured_image, published_at, created_at
         FROM blog_posts WHERE $whereSQL ORDER BY created_at DESC LIMIT :limit OFFSET :offset"
    );
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return ['data' => $stmt->fetchAll(), 'total' => $total, 'pages' => (int) ceil($total / $per_page)];
}

function get_blog_post(int $id): ?array
{
    $pdo  = db_connect();
    $stmt = $pdo->prepare('SELECT * FROM blog_posts WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function get_blog_post_by_slug(string $slug): ?array
{
    $pdo  = db_connect();
    $stmt = $pdo->prepare("SELECT * FROM blog_posts WHERE slug = :slug AND status = 'published'");
    $stmt->execute([':slug' => $slug]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Get published blog posts for the public index.
 */
function get_published_blog_posts(int $page = 1, int $per_page = 10): array
{
    $pdo    = db_connect();
    $offset = ($page - 1) * $per_page;

    $total = (int) $pdo->query("SELECT COUNT(*) FROM blog_posts WHERE status = 'published'")->fetchColumn();

    $stmt = $pdo->prepare(
        "SELECT id, title, slug, excerpt, featured_image, published_at, created_at
         FROM blog_posts WHERE status = 'published'
         ORDER BY COALESCE(published_at, created_at) DESC LIMIT :limit OFFSET :offset"
    );
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return ['data' => $stmt->fetchAll(), 'total' => $total, 'pages' => (int) ceil($total / $per_page)];
}

function save_blog_post(array $data, ?int $id = null): int|false
{
    $pdo = db_connect();

    $published_at = null;
    if ($data['status'] === 'published' && !empty($data['published_at'])) {
        $published_at = $data['published_at'];
    } elseif ($data['status'] === 'published' && $id === null) {
        $published_at = date('Y-m-d H:i:s');
    }

    if ($id === null) {
        $stmt = $pdo->prepare(
            'INSERT INTO blog_posts
             (title, slug, excerpt, content, meta_title, meta_description, meta_keywords,
              featured_image, status, published_at)
             VALUES (:ti, :sl, :ex, :co, :mt, :md, :mk, :fi, :st, :pa)'
        );
        $ok = $stmt->execute([
            ':ti' => $data['title'],
            ':sl' => $data['slug'],
            ':ex' => $data['excerpt']          ?? null,
            ':co' => $data['content']          ?? null,
            ':mt' => $data['meta_title']       ?? null,
            ':md' => $data['meta_description'] ?? null,
            ':mk' => $data['meta_keywords']    ?? null,
            ':fi' => $data['featured_image']   ?? null,
            ':st' => $data['status'],
            ':pa' => $published_at,
        ]);
        return $ok ? (int) $pdo->lastInsertId() : false;
    }

    $stmt = $pdo->prepare(
        'UPDATE blog_posts SET title=:ti, slug=:sl, excerpt=:ex, content=:co,
         meta_title=:mt, meta_description=:md, meta_keywords=:mk,
         featured_image=:fi, status=:st, published_at=:pa WHERE id=:id'
    );
    $ok = $stmt->execute([
        ':ti' => $data['title'],
        ':sl' => $data['slug'],
        ':ex' => $data['excerpt']          ?? null,
        ':co' => $data['content']          ?? null,
        ':mt' => $data['meta_title']       ?? null,
        ':md' => $data['meta_description'] ?? null,
        ':mk' => $data['meta_keywords']    ?? null,
        ':fi' => $data['featured_image']   ?? null,
        ':st' => $data['status'],
        ':pa' => $published_at,
        ':id' => $id,
    ]);
    return $ok ? $id : false;
}

function delete_blog_post(int $id): bool
{
    $pdo  = db_connect();
    $stmt = $pdo->prepare('DELETE FROM blog_posts WHERE id = :id');
    return $stmt->execute([':id' => $id]);
}

/**
 * Generate a URL-safe slug from a title.
 * Handles German umlauts.
 */
function slugify(string $text): string
{
    $map = ['ä'=>'ae','ö'=>'oe','ü'=>'ue','Ä'=>'ae','Ö'=>'oe','Ü'=>'ue','ß'=>'ss'];
    $text = strtr($text, $map);
    $text = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $text), '-'));
    return $text ?: 'post';
}

/**
 * Ensure the blog_posts table exists (forward-compatible migration).
 */
function ensure_blog_table(): void
{
    $pdo = db_connect();
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS blog_posts (
            id              INT AUTO_INCREMENT PRIMARY KEY,
            title           VARCHAR(255) NOT NULL,
            slug            VARCHAR(255) NOT NULL UNIQUE,
            excerpt         TEXT,
            content         LONGTEXT,
            meta_title      VARCHAR(255),
            meta_description TEXT,
            meta_keywords   TEXT,
            featured_image  VARCHAR(512),
            status          ENUM('draft','published') NOT NULL DEFAULT 'draft',
            published_at    DATETIME DEFAULT NULL,
            created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}

// ============================================================
// Mass-Mailing Module Functions
// ============================================================

/**
 * Get all mailing SMTP accounts.
 */
function get_mailing_smtp_accounts(bool $active_only = false): array
{
    $pdo = db_connect();
    $sql = 'SELECT * FROM mailing_smtp_accounts';
    if ($active_only) $sql .= ' WHERE active = 1';
    $sql .= ' ORDER BY id ASC';
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function get_mailing_smtp_account(int $id): ?array
{
    $pdo  = db_connect();
    $stmt = $pdo->prepare('SELECT * FROM mailing_smtp_accounts WHERE id = :id');
    $stmt->execute([':id' => $id]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function save_mailing_smtp_account(array $d, ?int $id = null): bool
{
    $pdo = db_connect();
    if ($id) {
        $stmt = $pdo->prepare('UPDATE mailing_smtp_accounts SET label=:lb,host=:ho,port=:po,username=:us,password=:pw,secure=:sc,from_email=:fe,from_name=:fn,active=:ac WHERE id=:id');
        return $stmt->execute([':lb'=>$d['label'],':ho'=>$d['host'],':po'=>(int)$d['port'],':us'=>$d['username'],':pw'=>$d['password'],':sc'=>$d['secure'],':fe'=>$d['from_email'],':fn'=>$d['from_name'],':ac'=>(int)$d['active'],':id'=>$id]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO mailing_smtp_accounts (label,host,port,username,password,secure,from_email,from_name,active) VALUES (:lb,:ho,:po,:us,:pw,:sc,:fe,:fn,:ac)');
        return $stmt->execute([':lb'=>$d['label'],':ho'=>$d['host'],':po'=>(int)$d['port'],':us'=>$d['username'],':pw'=>$d['password'],':sc'=>$d['secure'],':fe'=>$d['from_email'],':fn'=>$d['from_name'],':ac'=>(int)$d['active']]);
    }
}

function delete_mailing_smtp_account(int $id): bool
{
    $pdo  = db_connect();
    $stmt = $pdo->prepare('DELETE FROM mailing_smtp_accounts WHERE id = :id');
    return $stmt->execute([':id' => $id]);
}

/**
 * Get a mailing setting value.
 */
function get_mailing_setting(string $key, string $default = ''): string
{
    $pdo  = db_connect();
    $stmt = $pdo->prepare('SELECT setting_value FROM mailing_settings WHERE setting_key = :k');
    $stmt->execute([':k' => $key]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? (string) $row['setting_value'] : $default;
}

function get_all_mailing_settings(): array
{
    $pdo  = db_connect();
    $rows = $pdo->query('SELECT * FROM mailing_settings ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
    $out  = [];
    foreach ($rows as $r) { $out[$r['setting_key']] = $r['setting_value']; }
    return $out;
}

function save_mailing_settings(array $data): bool
{
    $pdo = db_connect();
    $stmt = $pdo->prepare('INSERT INTO mailing_settings (setting_key,setting_value) VALUES (:k,:v) ON DUPLICATE KEY UPDATE setting_value=:v2');
    foreach ($data as $k => $v) {
        $stmt->execute([':k'=>$k,':v'=>$v,':v2'=>$v]);
    }
    return true;
}

/**
 * Templates
 */
function get_mailing_templates(): array
{
    return db_connect()->query('SELECT * FROM mailing_templates ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
}

function get_mailing_template(int $id): ?array
{
    $pdo  = db_connect();
    $stmt = $pdo->prepare('SELECT * FROM mailing_templates WHERE id = :id');
    $stmt->execute([':id' => $id]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function save_mailing_template(array $d, ?int $id = null): int|false
{
    $pdo = db_connect();
    if ($id) {
        $stmt = $pdo->prepare('UPDATE mailing_templates SET name=:n,subject=:s,body_html=:bh,body_text=:bt WHERE id=:id');
        $ok = $stmt->execute([':n'=>$d['name'],':s'=>$d['subject'],':bh'=>$d['body_html'],':bt'=>$d['body_text'],':id'=>$id]);
        return $ok ? $id : false;
    }
    $stmt = $pdo->prepare('INSERT INTO mailing_templates (name,subject,body_html,body_text) VALUES (:n,:s,:bh,:bt)');
    $ok   = $stmt->execute([':n'=>$d['name'],':s'=>$d['subject'],':bh'=>$d['body_html'],':bt'=>$d['body_text']]);
    return $ok ? (int) $pdo->lastInsertId() : false;
}

function delete_mailing_template(int $id): bool
{
    $stmt = db_connect()->prepare('DELETE FROM mailing_templates WHERE id = :id');
    return $stmt->execute([':id' => $id]);
}

/**
 * Campaigns
 */
function get_mailing_campaigns(): array
{
    return db_connect()->query('SELECT c.*,t.name AS template_name FROM mailing_campaigns c LEFT JOIN mailing_templates t ON t.id=c.template_id ORDER BY c.id DESC')->fetchAll(PDO::FETCH_ASSOC);
}

function get_mailing_campaign(int $id): ?array
{
    $pdo  = db_connect();
    $stmt = $pdo->prepare('SELECT c.*,t.name AS template_name,t.subject,t.body_html,t.body_text FROM mailing_campaigns c LEFT JOIN mailing_templates t ON t.id=c.template_id WHERE c.id=:id');
    $stmt->execute([':id' => $id]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function create_mailing_campaign(string $name, int $template_id): int|false
{
    $pdo  = db_connect();
    $stmt = $pdo->prepare('INSERT INTO mailing_campaigns (name,template_id) VALUES (:n,:t)');
    $ok   = $stmt->execute([':n' => $name, ':t' => $template_id]);
    return $ok ? (int) $pdo->lastInsertId() : false;
}

function update_mailing_campaign(int $id, array $d): bool
{
    $pdo  = db_connect();
    $stmt = $pdo->prepare('UPDATE mailing_campaigns SET name=:n,template_id=:t WHERE id=:id');
    return $stmt->execute([':n'=>$d['name'],':t'=>$d['template_id'],':id'=>$id]);
}

function delete_mailing_campaign(int $id): bool
{
    $stmt = db_connect()->prepare('DELETE FROM mailing_campaigns WHERE id=:id');
    return $stmt->execute([':id'=>$id]);
}

/**
 * Recipients
 */
function get_mailing_recipients(int $campaign_id, string $status = '', int $limit = 0, int $offset = 0): array
{
    $pdo = db_connect();
    $sql = 'SELECT * FROM mailing_recipients WHERE campaign_id = :cid';
    $params = [':cid' => $campaign_id];
    if ($status) { $sql .= ' AND status = :st'; $params[':st'] = $status; }
    $sql .= ' ORDER BY id ASC';
    if ($limit) $sql .= " LIMIT $limit OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function count_mailing_recipients(int $campaign_id, string $status = ''): int
{
    $pdo = db_connect();
    $sql = 'SELECT COUNT(*) FROM mailing_recipients WHERE campaign_id = :cid';
    $params = [':cid' => $campaign_id];
    if ($status) { $sql .= ' AND status = :st'; $params[':st'] = $status; }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

/**
 * Import recipients from a parsed CSV array ([ [email, name], ... ]).
 * Returns number of rows inserted.
 */
function import_mailing_recipients(int $campaign_id, array $rows): int
{
    $pdo  = db_connect();
    $stmt = $pdo->prepare('INSERT IGNORE INTO mailing_recipients (campaign_id,email,name,open_token) VALUES (:cid,:em,:nm,:tok)');
    $count = 0;
    foreach ($rows as $row) {
        $email = filter_var(trim($row[0] ?? ''), FILTER_VALIDATE_EMAIL);
        if (!$email) continue;
        $name  = trim($row[1] ?? '');
        $token = bin2hex(random_bytes(16));
        if ($stmt->execute([':cid'=>$campaign_id,':em'=>$email,':nm'=>$name,':tok'=>$token])) {
            $count++;
        }
    }
    // Update campaign total
    $pdo->prepare('UPDATE mailing_campaigns SET total=(SELECT COUNT(*) FROM mailing_recipients WHERE campaign_id=:cid) WHERE id=:cid2')->execute([':cid'=>$campaign_id,':cid2'=>$campaign_id]);
    return $count;
}

/**
 * Start a campaign (set status=running).
 */
function start_mailing_campaign(int $id): bool
{
    $accounts = get_mailing_smtp_accounts(true);
    if (empty($accounts)) return false;
    $first = $accounts[0]['id'];
    $pdo = db_connect();
    $stmt = $pdo->prepare('UPDATE mailing_campaigns SET status="running",started_at=NOW(),current_smtp_account_id=:aid,current_smtp_batch_count=0 WHERE id=:id AND status IN ("draft","paused")');
    return $stmt->execute([':aid'=>$first,':id'=>$id]);
}

function pause_mailing_campaign(int $id): bool
{
    $stmt = db_connect()->prepare('UPDATE mailing_campaigns SET status="paused" WHERE id=:id');
    return $stmt->execute([':id'=>$id]);
}

/**
 * Get campaign statistics summary.
 */
function get_campaign_stats(int $campaign_id): array
{
    $pdo = db_connect();
    $r = $pdo->prepare('SELECT status, COUNT(*) AS cnt FROM mailing_recipients WHERE campaign_id=:cid GROUP BY status');
    $r->execute([':cid'=>$campaign_id]);
    $rows = $r->fetchAll(PDO::FETCH_ASSOC);
    $stats = ['pending'=>0,'sent'=>0,'failed'=>0,'bounced'=>0,'unsubscribed'=>0,'total'=>0,'opens'=>0];
    foreach ($rows as $row) {
        $stats[$row['status']] = (int) $row['cnt'];
        $stats['total'] += (int) $row['cnt'];
    }
    // opens
    $o = $pdo->prepare('SELECT COUNT(*) FROM mailing_recipients WHERE campaign_id=:cid AND opened_at IS NOT NULL');
    $o->execute([':cid'=>$campaign_id]);
    $stats['opens'] = (int) $o->fetchColumn();
    return $stats;
}

/**
 * Record an email open (called by tracking pixel).
 */
function record_mailing_open(string $token): void
{
    $pdo  = db_connect();
    $stmt = $pdo->prepare('UPDATE mailing_recipients SET opened_at=NOW() WHERE open_token=:tok AND opened_at IS NULL');
    $stmt->execute([':tok' => $token]);
}
