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
function get_mailing_recipients(int $campaign_id, string $status = '', int $limit = 0, int $offset = 0, string $validity = 'valid'): array
{
    $pdo = db_connect();
    $sql = 'SELECT * FROM mailing_recipients WHERE campaign_id = :cid';
    $params = [':cid' => $campaign_id];
    if ($status) { $sql .= ' AND status = :st'; $params[':st'] = $status; }
    if ($validity) { $sql .= ' AND email_validity = :ev'; $params[':ev'] = $validity; }
    $sql .= ' ORDER BY id ASC';
    if ($limit) $sql .= " LIMIT $limit OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function count_mailing_recipients(int $campaign_id, string $status = '', string $validity = 'valid'): int
{
    $pdo = db_connect();
    $sql = 'SELECT COUNT(*) FROM mailing_recipients WHERE campaign_id = :cid';
    $params = [':cid' => $campaign_id];
    if ($status) { $sql .= ' AND status = :st'; $params[':st'] = $status; }
    if ($validity) { $sql .= ' AND email_validity = :ev'; $params[':ev'] = $validity; }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

/**
 * Read a CSV file and return a preview: header row (or auto-generated) + first $limit data rows.
 *
 * Used by the column-mapper UI so the admin can see column contents before assigning roles.
 *
 * @param  resource $fh     Open file handle (will be rewound).
 * @param  int      $limit  Max data rows to return (default 5).
 * @return array{
 *   delim: string,
 *   header: string[],
 *   has_header: bool,
 *   preview: array<array<string>>
 * }
 */
function read_csv_preview($fh, int $limit = 5): array
{
    rewind($fh);
    $sample = fread($fh, 4096);
    rewind($fh);
    $commas     = substr_count($sample, ',');
    $semicolons = substr_count($sample, ';');
    $tabs       = substr_count($sample, "\t");
    $delim = ';';
    if ($commas >= $semicolons && $commas >= $tabs)  $delim = ',';
    elseif ($tabs > $semicolons)                     $delim = "\t";

    $raw = [];
    while (($row = fgetcsv($fh, 0, $delim)) !== false) {
        $row = array_map('trim', $row);
        if (array_filter($row)) $raw[] = $row;
        if (count($raw) >= $limit + 2) break; // read a bit extra
    }
    if (empty($raw)) return ['delim'=>$delim,'header'=>[],'has_header'=>false,'preview'=>[]];

    $header_patterns = '/^(e-?mail|name|vorname|nachname|first|last|platform|scam|broker|company|firma)/i';
    $has_header = false;
    foreach ($raw[0] as $cell) {
        if (preg_match($header_patterns, trim($cell))) { $has_header = true; break; }
    }

    $header = $has_header ? $raw[0] : array_map(fn($i) => 'Spalte ' . ($i + 1), array_keys($raw[0]));
    $data   = $has_header ? array_slice($raw, 1, $limit) : array_slice($raw, 0, $limit);

    return ['delim'=>$delim,'header'=>$header,'has_header'=>$has_header,'preview'=>$data];
}

/**
 * Parse a raw CSV file handle into normalised associative recipient rows using a column map.
 *
 * $column_map is an array of field names indexed by CSV column index, e.g.:
 *   [0 => 'email', 1 => 'name', 2 => 'scam_platform']
 *
 * Supported field names: 'email', 'name', 'firstname', 'lastname', 'scam_platform', '' (skip).
 *
 * @param resource $fh         Open file handle (will be rewound).
 * @param array    $column_map CSV-column-index → field-name mapping.
 * @param bool     $has_header Whether the first row is a header (skip it).
 * @return array   Each element: ['email'=>…, 'name'=>…, 'scam_platform'=>…]
 */
function parse_csv_with_column_map($fh, array $column_map, bool $has_header, string $delim = ','): array
{
    rewind($fh);
    $out = [];
    $first = true;
    while (($row = fgetcsv($fh, 0, $delim)) !== false) {
        $row = array_map('trim', $row);
        if ($first && $has_header) { $first = false; continue; }
        $first = false;
        if (!array_filter($row)) continue;

        $rec = ['email'=>'','name'=>'','scam_platform'=>'','_firstname'=>'','_lastname'=>''];
        foreach ($column_map as $col_idx => $field) {
            $val = $row[(int)$col_idx] ?? '';
            switch ($field) {
                case 'email':         $rec['email']         = $val; break;
                case 'name':          $rec['name']          = $val; break;
                case 'firstname':     $rec['_firstname']    = $val; break;
                case 'lastname':      $rec['_lastname']     = $val; break;
                case 'scam_platform': $rec['scam_platform'] = $val; break;
                // '' = skip column
            }
        }
        // Combine firstname + lastname into name if name not set directly
        if ($rec['name'] === '' && ($rec['_firstname'] !== '' || $rec['_lastname'] !== '')) {
            $rec['name'] = trim($rec['_firstname'] . ' ' . $rec['_lastname']);
        }
        unset($rec['_firstname'], $rec['_lastname']);
        $out[] = $rec;
    }
    return $out;
}


/**
 * Handles all common column layouts (legacy auto-detect parser):
 *   – email, name               (2 cols, name combined)
 *   – email, firstname, lastname (3 cols, names separate)
 *   – firstname, lastname, email (3 cols, email last)
 *   – name, email               (2 cols, name first)
 *   – email only                (1 col, no name)
 *
 * Also auto-detects comma vs. semicolon delimiter and optional header row.
 *
 * @param resource $fh  Open file handle (will be rewound).
 * @return array        Array of [email_string, full_name_string] pairs.
 */
function parse_csv_file_to_recipient_rows($fh): array
{
    rewind($fh);

    // ── 1. Detect delimiter ───────────────────────────────────────────────────
    $sample = fread($fh, 4096);
    rewind($fh);
    $commas     = substr_count($sample, ',');
    $semicolons = substr_count($sample, ';');
    $tabs       = substr_count($sample, "\t");
    $delim = ';';
    if ($commas >= $semicolons && $commas >= $tabs)  $delim = ',';
    elseif ($tabs > $semicolons)                     $delim = "\t";

    // ── 2. Read all rows ──────────────────────────────────────────────────────
    $raw = [];
    while (($row = fgetcsv($fh, 0, $delim)) !== false) {
        $row = array_map('trim', $row);
        if (array_filter($row)) $raw[] = $row; // skip blank lines
    }
    if (empty($raw)) return [];

    // ── 3. Detect header row ──────────────────────────────────────────────────
    $header_patterns = '/^(e-?mail|name|vorname|nachname|first|last|firstname|lastname)/i';
    $first = $raw[0];
    $is_header = false;
    foreach ($first as $cell) {
        if (preg_match($header_patterns, trim($cell))) { $is_header = true; break; }
    }
    if ($is_header) array_shift($raw);
    if (empty($raw)) return [];

    // ── 4. Detect column layout from the first data row ───────────────────────
    $sample_row = $raw[0];
    $ncols      = count($sample_row);

    // Find which column contains an email address
    $email_col = -1;
    foreach ($sample_row as $ci => $cell) {
        if (filter_var($cell, FILTER_VALIDATE_EMAIL)) { $email_col = $ci; break; }
    }
    if ($email_col === -1) return []; // no email found at all

    // ── 5. Determine name columns ─────────────────────────────────────────────
    // Remaining non-email columns become the name
    $name_cols = [];
    for ($i = 0; $i < $ncols; $i++) {
        if ($i !== $email_col) $name_cols[] = $i;
    }

    // ── 6. Normalise rows ─────────────────────────────────────────────────────
    $out = [];
    foreach ($raw as $row) {
        $email = filter_var(trim($row[$email_col] ?? ''), FILTER_VALIDATE_EMAIL);
        if (!$email) continue;

        // Build full name from remaining columns
        $parts = [];
        foreach ($name_cols as $nc) {
            $v = trim($row[$nc] ?? '');
            if ($v !== '') $parts[] = $v;
        }
        $full_name = implode(' ', $parts);

        // If the name looks like a combined "Lastname, Firstname" (comma inside), normalise
        if (strpos($full_name, ',') !== false) {
            [$ln, $fn] = explode(',', $full_name, 2);
            $full_name = trim($fn) . ' ' . trim($ln);
        }

        $out[] = [$email, $full_name];
    }
    return $out;
}

/**
 * Validate a single email address for import deliverability.
 *
 * Checks performed (in order):
 *  1. RFC syntax via filter_var(FILTER_VALIDATE_EMAIL)
 *  2. Domain has at least one MX record (or an A/AAAA record as fallback)
 *     – only when $check_mx = true
 *
 * @param  string $email     The raw email string to validate.
 * @param  bool   $check_mx  Whether to perform a DNS MX lookup (default true).
 * @return string  Empty string = valid; non-empty = reason code:
 *                 'invalid_syntax'  – fails RFC syntax check
 *                 'no_mx'           – domain has no MX or A record
 */
function validate_email_for_import(string $email, bool $check_mx = true): string
{
    $email = trim($email);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'invalid_syntax';
    }
    if ($check_mx) {
        $domain = substr($email, strrpos($email, '@') + 1);
        if (!checkdnsrr($domain, 'MX') && !checkdnsrr($domain, 'A') && !checkdnsrr($domain, 'AAAA')) {
            return 'no_mx';
        }
    }
    return '';
}

/**
 * Validate email validity for an array of recipient rows.
 *
 * All rows are returned; each row gets an injected '_validity' key set to
 * 'valid' or 'invalid'.  Invalid rows also get an '_invalid_reason' key.
 *
 * Accepts both associative rows (with 'email' key) and legacy positional rows.
 * Performs per-domain MX caching to avoid redundant DNS lookups when many
 * addresses share the same domain (e.g., gmail.com).
 *
 * @param  array  $rows      Rows from parse_csv_with_column_map() or manual input.
 * @param  bool   $check_mx  Whether to check MX records (default true).
 * @return array{
 *   valid:   array,
 *   invalid: array,
 *   skipped: array<array{email:string, reason:string}>
 * }
 */
function filter_rows_by_email_validity(array $rows, bool $check_mx = true): array
{
    $mx_cache = [];   // domain → bool
    $valid    = [];
    $invalid  = [];
    $skipped  = [];   // kept for backwards-compat / summary display

    foreach ($rows as $row) {
        $email = trim(isset($row['email']) ? ($row['email'] ?? '') : ($row[0] ?? ''));

        // 1. Syntax
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $reason  = 'invalid_syntax';
            $skipped[] = ['email' => $email, 'reason' => $reason];
            $row['_validity']       = 'invalid';
            $row['_invalid_reason'] = $reason;
            $invalid[] = $row;
            continue;
        }

        // 2. MX (cached per domain)
        if ($check_mx) {
            $domain = substr($email, strrpos($email, '@') + 1);
            if (!isset($mx_cache[$domain])) {
                $mx_cache[$domain] = (
                    checkdnsrr($domain, 'MX') ||
                    checkdnsrr($domain, 'A')  ||
                    checkdnsrr($domain, 'AAAA')
                );
            }
            if (!$mx_cache[$domain]) {
                $reason  = 'no_mx';
                $skipped[] = ['email' => $email, 'reason' => $reason];
                $row['_validity']       = 'invalid';
                $row['_invalid_reason'] = $reason;
                $invalid[] = $row;
                continue;
            }
        }

        $row['_validity'] = 'valid';
        $valid[] = $row;
    }

    return ['valid' => $valid, 'invalid' => $invalid, 'skipped' => $skipped];
}

/**
 * Import recipients from a parsed CSV array.
 *
 *   'name'          – optional full name
 *   'scam_platform' – optional platform where the lead lost money
 *   '_validity'     – 'valid' or 'invalid' (set by filter_rows_by_email_validity)
 *
 * ALL rows (valid and invalid) are inserted so the admin can see them all on
 * the Leads page.  Only recipients with email_validity='valid' are picked
 * by the batch sender.
 *
 * Legacy positional arrays [ email, name ] are also accepted for backwards compatibility.
 *
 * Returns number of rows inserted.
 */
function import_mailing_recipients(int $campaign_id, array $rows): int
{
    $pdo  = db_connect();
    $stmt = $pdo->prepare(
        'INSERT IGNORE INTO mailing_recipients (campaign_id,email,name,scam_platform,email_validity,open_token,click_token) ' .
        'VALUES (:cid,:em,:nm,:sp,:ev,:tok,:ctok)'
    );
    $count = 0;
    foreach ($rows as $row) {
        // Support both associative and legacy positional format
        if (isset($row['email'])) {
            $raw_email = trim($row['email'] ?? '');
            $name      = trim($row['name']          ?? '');
            $platform  = trim($row['scam_platform'] ?? '');
            $validity  = $row['_validity'] ?? 'valid';
        } else {
            $raw_email = trim($row[0] ?? '');
            $name      = trim($row[1] ?? '');
            $platform  = trim($row[2] ?? '');
            $validity  = $row['_validity'] ?? 'valid';
        }
        // For invalid rows we keep the raw email string (even if not RFC-valid)
        // For valid rows we additionally run filter_var as a safety net
        if ($validity === 'valid') {
            $email = filter_var($raw_email, FILTER_VALIDATE_EMAIL);
            if (!$email) continue;
        } else {
            $email = $raw_email;
            if ($email === '') continue;
        }
        $token  = bin2hex(random_bytes(16));
        $ctoken = bin2hex(random_bytes(16));
        if ($stmt->execute([':cid'=>$campaign_id,':em'=>$email,':nm'=>$name,':sp'=>$platform,':ev'=>$validity,':tok'=>$token,':ctok'=>$ctoken])) {
            $count++;
        }
    }
    // Update campaign total
    $pdo->prepare('UPDATE mailing_campaigns SET total=(SELECT COUNT(*) FROM mailing_recipients WHERE campaign_id=:cid AND email_validity="valid") WHERE id=:cid2')
        ->execute([':cid'=>$campaign_id,':cid2'=>$campaign_id]);
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
    $r = $pdo->prepare('SELECT status, COUNT(*) AS cnt FROM mailing_recipients WHERE campaign_id=:cid AND email_validity="valid" GROUP BY status');
    $r->execute([':cid'=>$campaign_id]);
    $rows = $r->fetchAll(PDO::FETCH_ASSOC);
    $stats = ['pending'=>0,'sent'=>0,'failed'=>0,'bounced'=>0,'unsubscribed'=>0,'total'=>0,'opens'=>0,'clicks'=>0,'invalid'=>0];
    foreach ($rows as $row) {
        $stats[$row['status']] = (int) $row['cnt'];
        $stats['total'] += (int) $row['cnt'];
    }
    // opens
    $o = $pdo->prepare('SELECT COUNT(*) FROM mailing_recipients WHERE campaign_id=:cid AND email_validity="valid" AND opened_at IS NOT NULL');
    $o->execute([':cid'=>$campaign_id]);
    $stats['opens'] = (int) $o->fetchColumn();
    // clicks
    $c = $pdo->prepare('SELECT COUNT(*) FROM mailing_recipients WHERE campaign_id=:cid AND email_validity="valid" AND clicked_at IS NOT NULL');
    $c->execute([':cid'=>$campaign_id]);
    $stats['clicks'] = (int) $c->fetchColumn();
    // invalid count (for display)
    $inv = $pdo->prepare('SELECT COUNT(*) FROM mailing_recipients WHERE campaign_id=:cid AND email_validity="invalid"');
    $inv->execute([':cid'=>$campaign_id]);
    $stats['invalid'] = (int) $inv->fetchColumn();
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

/**
 * Record a link click (called by track_click.php).
 * Increments click_count and sets clicked_at on first click.
 * Returns the target URL stored in the click token, or NULL if not found.
 */
function record_mailing_click(string $token): ?string
{
    $pdo  = db_connect();
    $stmt = $pdo->prepare('SELECT id FROM mailing_recipients WHERE click_token=:tok LIMIT 1');
    $stmt->execute([':tok' => $token]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) return null;
    $pdo->prepare(
        'UPDATE mailing_recipients
         SET click_count=click_count+1, clicked_at=COALESCE(clicked_at,NOW())
         WHERE click_token=:tok'
    )->execute([':tok' => $token]);
    return 'found';
}

/**
 * Ensure all mailing tables exist and seed default data.
 * Called lazily on first visit to mailing module.
 */
function ensure_mailing_tables(): void
{
    $pdo = db_connect();

    $pdo->exec("CREATE TABLE IF NOT EXISTS mailing_smtp_accounts (
        id           INT AUTO_INCREMENT PRIMARY KEY,
        label        VARCHAR(100)                  NOT NULL DEFAULT '',
        host         VARCHAR(255)                  NOT NULL DEFAULT '',
        port         SMALLINT UNSIGNED             NOT NULL DEFAULT 587,
        username     VARCHAR(255)                  NOT NULL DEFAULT '',
        password     VARCHAR(255)                  NOT NULL DEFAULT '',
        secure       ENUM('tls','ssl','none')       NOT NULL DEFAULT 'tls',
        from_email   VARCHAR(255)                  NOT NULL DEFAULT '',
        from_name    VARCHAR(255)                  NOT NULL DEFAULT '',
        active       TINYINT(1)                    NOT NULL DEFAULT 1,
        emails_sent  INT UNSIGNED                  NOT NULL DEFAULT 0,
        last_used_at DATETIME                               DEFAULT NULL,
        created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS mailing_settings (
        id            INT AUTO_INCREMENT PRIMARY KEY,
        setting_key   VARCHAR(100) NOT NULL UNIQUE,
        setting_value TEXT,
        setting_label VARCHAR(255),
        updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $defaults = [
        ['emails_per_account',        '5',     'E-Mails pro SMTP-Account (dann rotieren)'],
        ['pause_between_emails_ms',   '3000',  'Pause zwischen E-Mails (Millisekunden)'],
        ['pause_between_accounts_ms', '15000', 'Pause zwischen SMTP-Wechsel (Millisekunden)'],
        ['max_daily_per_account',     '200',   'Max. E-Mails pro Account pro Tag'],
        ['unsubscribe_url',           '',      'Globaler Abmelde-Link (leer = auto-generiert)'],
        ['track_opens',               '0',     'Öffnungs-Tracking aktiv (1 = ja)'],
    ];
    $ins = $pdo->prepare('INSERT IGNORE INTO mailing_settings (setting_key,setting_value,setting_label) VALUES (?,?,?)');
    foreach ($defaults as $d) { $ins->execute($d); }

    $pdo->exec("CREATE TABLE IF NOT EXISTS mailing_templates (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        name       VARCHAR(255) NOT NULL,
        subject    VARCHAR(255) NOT NULL DEFAULT '',
        body_html  LONGTEXT,
        body_text  TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS mailing_campaigns (
        id           INT AUTO_INCREMENT PRIMARY KEY,
        name         VARCHAR(255)  NOT NULL,
        template_id  INT           DEFAULT NULL,
        status       ENUM('draft','running','paused','completed','failed') NOT NULL DEFAULT 'draft',
        total        INT UNSIGNED  NOT NULL DEFAULT 0,
        sent         INT UNSIGNED  NOT NULL DEFAULT 0,
        failed       INT UNSIGNED  NOT NULL DEFAULT 0,
        opens        INT UNSIGNED  NOT NULL DEFAULT 0,
        current_smtp_account_id INT DEFAULT NULL,
        current_smtp_batch_count INT UNSIGNED NOT NULL DEFAULT 0,
        created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        started_at   DATETIME DEFAULT NULL,
        finished_at  DATETIME DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS mailing_recipients (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        campaign_id     INT           NOT NULL,
        email           VARCHAR(255)  NOT NULL,
        name            VARCHAR(255)  DEFAULT '',
        scam_platform   VARCHAR(255)  DEFAULT '',
        email_validity  ENUM('valid','invalid') NOT NULL DEFAULT 'valid',
        status          ENUM('pending','sent','failed','bounced','unsubscribed') NOT NULL DEFAULT 'pending',
        smtp_account_id INT           DEFAULT NULL,
        sent_at         DATETIME      DEFAULT NULL,
        error_msg       VARCHAR(512)  DEFAULT NULL,
        open_token      VARCHAR(64)   DEFAULT NULL,
        opened_at       DATETIME      DEFAULT NULL,
        click_token     VARCHAR(64)   DEFAULT NULL,
        clicked_at      DATETIME      DEFAULT NULL,
        click_count     INT UNSIGNED  NOT NULL DEFAULT 0,
        created_at      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Add scam_platform column to existing tables that pre-date this migration
    try {
        $pdo->exec("ALTER TABLE mailing_recipients ADD COLUMN scam_platform VARCHAR(255) DEFAULT '' AFTER name");
    } catch (\PDOException $e) {
        // Column already exists — ignore duplicate column error
    }
    // Add email_validity column (migration for existing installations)
    try {
        $pdo->exec("ALTER TABLE mailing_recipients ADD COLUMN email_validity ENUM('valid','invalid') NOT NULL DEFAULT 'valid' AFTER scam_platform");
    } catch (\PDOException $e) { /* already exists */ }
    // Add click tracking columns (migration for existing installations)
    try {
        $pdo->exec("ALTER TABLE mailing_recipients ADD COLUMN click_token VARCHAR(64) DEFAULT NULL AFTER opened_at");
    } catch (\PDOException $e) { /* already exists */ }
    try {
        $pdo->exec("ALTER TABLE mailing_recipients ADD COLUMN clicked_at DATETIME DEFAULT NULL AFTER click_token");
    } catch (\PDOException $e) { /* already exists */ }
    try {
        $pdo->exec("ALTER TABLE mailing_recipients ADD COLUMN click_count INT UNSIGNED NOT NULL DEFAULT 0 AFTER clicked_at");
    } catch (\PDOException $e) { /* already exists */ }

    // Seed the KryptoxPay professional German template if not already present
    $check = $pdo->query("SELECT COUNT(*) FROM mailing_templates WHERE name = 'KryptoxPay – Professionell (DE)'")->fetchColumn();
    if (!$check) {
        $html = _kryptoxpay_email_template_html();
        $text = _kryptoxpay_email_template_text();
        $pdo->prepare('INSERT INTO mailing_templates (name,subject,body_html,body_text) VALUES (?,?,?,?)')->execute([
            'KryptoxPay – Professionell (DE)',
            'Wichtige Information zu Ihren digitalen Vermögenswerten',
            $html,
            $text,
        ]);
    }
}

/**
 * Returns the full HTML of the KryptoxPay professional German email template.
 * Supports {{#if scam_platform}}…{{else}}…{{/if}} conditional blocks.
 */
function _kryptoxpay_email_template_html(): string
{
    return '<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>{{company_name}}</title>
<style>
  body,table,td,a{-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%}
  body{margin:0;padding:0;background-color:#f2f4f7;font-family:\'Helvetica Neue\',Helvetica,Arial,sans-serif}
  .email-wrapper{width:100%;background:#f2f4f7;padding:30px 0}
  .email-content{max-width:600px;margin:0 auto;background:#ffffff;border-radius:10px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.08)}
  .header{background:#0d2744;padding:32px 40px;text-align:center}
  .header-logo{font-size:24px;font-weight:700;color:#ffffff;letter-spacing:-0.5px;text-decoration:none;display:block}
  .header-logo span{color:#f0a500}
  .header-tagline{margin:6px 0 0;font-size:12px;color:#7fa8d4;letter-spacing:1px;text-transform:uppercase}
  .alert-banner{background:#fff3cd;border-left:4px solid #f0a500;padding:14px 20px;margin:0 0 20px;border-radius:0 6px 6px 0}
  .alert-banner p{margin:0;font-size:14px;color:#7a5c00}
  .body{padding:38px 40px;color:#374151;font-size:15px;line-height:1.8}
  .body h2{margin:0 0 18px;font-size:20px;color:#0d2744;font-weight:700}
  .body p{margin:0 0 16px}
  .body ul{padding-left:20px;margin:0 0 16px}
  .body ul li{margin-bottom:6px}
  .divider{height:1px;background:#e8edf2;margin:24px 0}
  .cta-wrapper{text-align:center;margin:28px 0}
  .cta-btn{display:inline-block;background:#f0a500;color:#ffffff!important;padding:14px 36px;border-radius:6px;font-size:15px;font-weight:700;text-decoration:none;letter-spacing:0.3px}
  .footer{background:#f8fafc;padding:22px 40px;border-top:1px solid #e8edf2}
  .footer p{margin:0 0 6px;font-size:12px;color:#9ca3af;text-align:center;line-height:1.6}
  .footer a{color:#9ca3af;text-decoration:underline}
  @media only screen and (max-width:620px){
    .email-content,.header,.body,.footer{border-radius:0!important}
    .body,.header,.footer{padding:24px 20px!important}
  }
</style>
</head>
<body>
<div class="email-wrapper">
  <div class="email-content">
    <div class="header">
      <a class="header-logo" href="https://kryptoxpay.co.uk">Kryptox<span>Pay</span></a>
      <p class="header-tagline">KI-gestützte Kapitalrückholung &amp; Beratung</p>
    </div>
    <div class="body">
      <h2>Sehr geehrte/r {{name}},</h2>
      {{#if scam_platform}}
      <div class="alert-banner">
        <p>&#9888;&nbsp; Wir haben Informationen erhalten, dass Sie Kapital auf der Plattform <strong>{{scam_platform}}</strong> verloren haben könnten. Unser KI-System hat diese Plattform als bekannte Betrugsstätte identifiziert.</p>
      </div>
      <p>wir wenden uns heute gezielt an Sie, da Anzeichen vorliegen, dass Sie durch <strong>{{scam_platform}}</strong> einen finanziellen Schaden erlitten haben könnten.</p>
      <p>Mit modernster KI-Technologie und langjähriger Erfahrung im Bereich der Kapitalrückholung unterstützen wir Betroffene dabei, verlorene Mittel zurückzuholen.</p>
      {{else}}
      <p>wir wenden uns heute mit einer wichtigen Mitteilung an Sie, die im Zusammenhang mit Ihren digitalen Vermögenswerten stehen könnte.</p>
      <p>Bei <strong>KryptoxPay</strong> begleiten wir Anlegerinnen und Anleger dabei, ihre finanzielle Situation transparent zu bewerten und fundierte Entscheidungen zu treffen.</p>
      {{/if}}
      <p>Unsere Leistungen im Überblick:</p>
      <ul>
        <li>Unverbindliche und kostenlose Erstberatung</li>
        <li>KI-gestützte Analyse Ihrer individuellen Situation</li>
        <li>Transparente Kommunikation ohne versteckte Kosten</li>
        <li>Vertrauliche Bearbeitung Ihres Anliegens</li>
      </ul>
      <div class="divider"></div>
      {{#if scam_platform}}
      <p>Handeln Sie jetzt – je früher wir Ihren Fall prüfen können, desto besser sind die Chancen auf eine Rückholung Ihrer Mittel.</p>
      {{else}}
      <p>Wir laden Sie herzlich ein, sich auf unserer Website zu informieren und unverbindlich Kontakt aufzunehmen.</p>
      {{/if}}
      <div class="cta-wrapper">
        <a href="https://kryptoxpay.co.uk" class="cta-btn">Jetzt unverbindlich informieren</a>
      </div>
      <div class="divider"></div>
      <p style="font-size:14px;color:#374151">
        Mit freundlichen Grüßen,<br>
        <strong style="color:#0d2744">{{sender_name}}</strong><br>
        KryptoxPay<br>
        <a href="https://kryptoxpay.co.uk" style="color:#0d2744">https://kryptoxpay.co.uk</a>
      </p>
    </div>
    <div class="footer">
      <p>Sie erhalten diese E-Mail, da Sie sich für digitale Finanzthemen interessiert haben oder früher Kontakt mit uns aufgenommen haben.</p>
      <p>
        <a href="{{unsubscribe_url}}">Abmelden</a> &nbsp;&middot;&nbsp;
        <a href="https://kryptoxpay.co.uk/datenschutz">Datenschutz</a> &nbsp;&middot;&nbsp;
        <a href="https://kryptoxpay.co.uk/impressum">Impressum</a>
      </p>
      <p>KryptoxPay &nbsp;&middot;&nbsp; <a href="https://kryptoxpay.co.uk">https://kryptoxpay.co.uk</a></p>
      {{open_tracker}}
    </div>
  </div>
</div>
</body>
</html>';
}

/**
 * Plain-text version of the KryptoxPay email template.
 * Supports {{#if scam_platform}}…{{else}}…{{/if}} blocks.
 */
function _kryptoxpay_email_template_text(): string
{
    return 'Sehr geehrte/r {{name}},

{{#if scam_platform}}
Wir wenden uns heute gezielt an Sie, da Anzeichen vorliegen, dass Sie durch
{{scam_platform}} einen finanziellen Schaden erlitten haben könnten.

Mit modernster KI-Technologie unterstützen wir Betroffene dabei,
verlorene Mittel zurückzuholen.
{{else}}
wir wenden uns heute mit einer wichtigen Mitteilung an Sie, die im Zusammenhang
mit Ihren digitalen Vermögenswerten stehen könnte.

Bei KryptoxPay begleiten wir Anlegerinnen und Anleger dabei, ihre finanzielle
Situation transparent zu bewerten und fundierte Entscheidungen zu treffen.
{{/if}}

Unsere Leistungen:
- Unverbindliche und kostenlose Erstberatung
- KI-gestützte Analyse Ihrer individuellen Situation
- Transparente Kommunikation ohne versteckte Kosten
- Vertrauliche Bearbeitung Ihres Anliegens

{{#if scam_platform}}
Handeln Sie jetzt – je früher wir Ihren Fall prüfen können, desto besser sind
die Chancen auf eine Rückholung Ihrer Mittel.
{{else}}
Wir laden Sie herzlich ein, sich auf unserer Website zu informieren.
{{/if}}

Mehr Informationen finden Sie unter:
https://kryptoxpay.co.uk

Mit freundlichen Grüßen,
{{sender_name}}
KryptoxPay
https://kryptoxpay.co.uk

---
Sie erhalten diese E-Mail, da Sie sich für digitale Finanzthemen interessiert haben.
Abmelden: {{unsubscribe_url}}
Datenschutz: https://kryptoxpay.co.uk/datenschutz';
}
