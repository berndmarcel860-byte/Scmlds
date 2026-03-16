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
