<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

admin_check();

// Handle bulk actions / quick status change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quick_status'])) {
    $lead_id  = (int) ($_POST['lead_id'] ?? 0);
    $new_status = $_POST['quick_status'] ?? '';
    $allowed_statuses = ['Neu', 'In Bearbeitung', 'Kontaktiert', 'Erfolgreich', 'Abgelehnt'];
    if ($lead_id && in_array($new_status, $allowed_statuses, true)) {
        $pdo = db_connect();
        $stmt = $pdo->prepare('UPDATE leads SET status=:s WHERE id=:id');
        $stmt->execute([':s' => $new_status, ':id' => $lead_id]);
        log_activity('status_change', "Lead #$lead_id → $new_status");
    }
    header('Location: leads.php?' . http_build_query($_GET));
    exit;
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_lead'])) {
    $lead_id = (int) ($_POST['lead_id'] ?? 0);
    if ($lead_id) {
        delete_lead($lead_id);
        log_activity('delete_lead', "Lead #$lead_id deleted");
    }
    header('Location: leads.php?' . http_build_query($_GET));
    exit;
}

$page     = max(1, (int) ($_GET['page'] ?? 1));
$per_page = 20;
$filters  = [
    'status'   => $_GET['status']   ?? '',
    'search'   => $_GET['search']   ?? '',
    'category' => $_GET['category'] ?? '',
];

$result = get_leads($filters, $page, $per_page);
$leads  = $result['data'];
$total  = $result['total'];
$pages  = $result['pages'];

$statuses = ['Neu', 'In Bearbeitung', 'Kontaktiert', 'Erfolgreich', 'Abgelehnt'];
$categories = ['Krypto-Betrug', 'Forex-Betrug', 'Fake-Broker', 'Romance-Scam mit Investitionsbetrug', 'Binäre Optionen', 'Andere'];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leads – Scmlds Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
</head>
<body class="admin-body">

<?php include __DIR__ . '/partials/sidebar.php'; ?>

<div class="admin-main">
    <?php include __DIR__ . '/partials/topbar.php'; ?>

    <div class="admin-content p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0">Leads</h4>
                <p class="text-muted small mb-0"><?= $total ?> Einträge gefunden</p>
            </div>
            <a href="export.php?<?= http_build_query($filters) ?>" class="btn btn-success">
                <i class="bi bi-download me-1"></i>CSV Export
            </a>
        </div>

        <!-- Filters -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Suche</label>
                        <input type="text" name="search" class="form-control form-control-sm"
                               placeholder="Name, E-Mail, Telefon..."
                               value="<?= htmlspecialchars($filters['search'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">Alle Status</option>
                            <?php foreach ($statuses as $s): ?>
                                <option value="<?= $s ?>" <?= $filters['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Betrugsart</label>
                        <select name="category" class="form-select form-select-sm">
                            <option value="">Alle Kategorien</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= $c ?>" <?= $filters['category'] === $c ? 'selected' : '' ?>><?= $c ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm flex-fill">
                            <i class="bi bi-search me-1"></i>Filtern
                        </button>
                        <a href="leads.php" class="btn btn-light btn-sm">
                            <i class="bi bi-x"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Leads Table -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>E-Mail</th>
                                <th>Telefon</th>
                                <th>Betrag</th>
                                <th>Kategorie</th>
                                <th>Status</th>
                                <th>Datum</th>
                                <th>Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($leads as $lead): ?>
                            <tr>
                                <td class="text-muted small"><?= (int)$lead['id'] ?></td>
                                <td class="fw-semibold">
                                    <a href="lead_detail.php?id=<?= (int)$lead['id'] ?>" class="text-decoration-none">
                                        <?= htmlspecialchars($lead['first_name'] . ' ' . $lead['last_name'], ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                </td>
                                <td class="text-muted">
                                    <?= htmlspecialchars($lead['email'], ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td class="text-muted">
                                    <?= htmlspecialchars($lead['phone'] ?: '–', ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td class="fw-semibold text-danger">
                                    <?= format_currency((float) $lead['amount_lost']) ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <?= htmlspecialchars($lead['platform_category'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td>
                                    <!-- Quick status change -->
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="lead_id" value="<?= (int)$lead['id'] ?>">
                                        <select name="quick_status" class="form-select form-select-sm status-select"
                                                onchange="this.form.submit()" style="min-width:130px;">
                                            <?php foreach ($statuses as $s): ?>
                                                <option value="<?= $s ?>"
                                                    <?= $lead['status'] === $s ? 'selected' : '' ?>
                                                    class="<?= status_badge_class($s) ?>">
                                                    <?= $s ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                </td>
                                <td class="text-muted small">
                                    <?= date('d.m.Y', strtotime($lead['created_at'])) ?>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <a href="lead_detail.php?id=<?= (int)$lead['id'] ?>"
                                           class="btn btn-sm btn-outline-primary" title="Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <button class="btn btn-sm btn-outline-danger" title="Löschen"
                                                data-bs-toggle="modal" data-bs-target="#deleteModal"
                                                data-lead-id="<?= (int)$lead['id'] ?>"
                                                data-lead-name="<?= htmlspecialchars($lead['first_name'] . ' ' . $lead['last_name'], ENT_QUOTES, 'UTF-8') ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($leads)): ?>
                            <tr><td colspan="9" class="text-center text-muted py-5">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Keine Leads gefunden.
                            </td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Pagination -->
            <?php if ($pages > 1): ?>
            <div class="card-footer bg-white border-0 d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Seite <?= $page ?> von <?= $pages ?> (<?= $total ?> Einträge)
                </div>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <?php for ($p = 1; $p <= $pages; $p++): ?>
                            <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page' => $p])) ?>">
                                    <?= $p ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold text-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>Lead löschen
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Möchten Sie den Lead <strong id="deleteLeadName"></strong> wirklich unwiderruflich löschen?
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                <form method="POST">
                    <input type="hidden" name="lead_id" id="deleteLeadId">
                    <button type="submit" name="delete_lead" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i>Endgültig löschen
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('deleteModal').addEventListener('show.bs.modal', function(e) {
    const btn = e.relatedTarget;
    document.getElementById('deleteLeadName').textContent = btn.getAttribute('data-lead-name');
    document.getElementById('deleteLeadId').value = btn.getAttribute('data-lead-id');
});
</script>
</body>
</html>
