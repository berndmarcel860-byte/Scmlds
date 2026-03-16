<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

admin_check();

$id = (int) ($_GET['id'] ?? 0);
if (!$id) {
    header('Location: leads.php');
    exit;
}

$lead = get_lead($id);
if (!$lead) {
    header('Location: leads.php');
    exit;
}

$success_msg = '';
$error_msg   = '';

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_lead'])) {
    $allowed_statuses = ['Neu', 'In Bearbeitung', 'Kontaktiert', 'Erfolgreich', 'Abgelehnt'];
    $new_status = $_POST['status'] ?? $lead['status'];
    if (!in_array($new_status, $allowed_statuses, true)) {
        $new_status = $lead['status'];
    }

    $data = [
        'first_name'        => trim($_POST['first_name'] ?? ''),
        'last_name'         => trim($_POST['last_name'] ?? ''),
        'email'             => trim($_POST['email'] ?? ''),
        'phone'             => trim($_POST['phone'] ?? ''),
        'amount_lost'       => (float) ($_POST['amount_lost'] ?? 0),
        'platform_category' => trim($_POST['platform_category'] ?? ''),
        'case_description'  => trim($_POST['case_description'] ?? ''),
        'status'            => $new_status,
        'admin_notes'       => trim($_POST['admin_notes'] ?? ''),
    ];

    if (update_lead($id, $data)) {
        log_activity('update_lead', "Lead #$id updated");
        $success_msg = 'Lead erfolgreich aktualisiert.';
        $lead = get_lead($id);
    } else {
        $error_msg = 'Fehler beim Aktualisieren.';
    }
}

$statuses = ['Neu', 'In Bearbeitung', 'Kontaktiert', 'Erfolgreich', 'Abgelehnt'];
$categories = ['Krypto-Betrug', 'Forex-Betrug', 'Fake-Broker', 'Romance-Scam mit Investitionsbetrug', 'Binäre Optionen', 'Andere'];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lead #<?= $id ?> – VerlustRück Admin</title>
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
                <a href="leads.php" class="btn btn-sm btn-light mb-2">
                    <i class="bi bi-arrow-left me-1"></i>Zurück
                </a>
                <h4 class="fw-bold mb-0">
                    Lead #<?= $id ?> –
                    <?= htmlspecialchars($lead['first_name'] . ' ' . $lead['last_name'], ENT_QUOTES, 'UTF-8') ?>
                </h4>
            </div>
            <span class="badge <?= status_badge_class($lead['status']) ?> fs-6 px-3 py-2">
                <?= htmlspecialchars($lead['status'], ENT_QUOTES, 'UTF-8') ?>
            </span>
        </div>

        <?php if ($success_msg): ?>
            <div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success_msg, ENT_QUOTES, 'UTF-8') ?></div>
        <?php elseif ($error_msg): ?>
            <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error_msg, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="row g-4">
                <!-- Left column: Lead info -->
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-0 fw-bold">
                            <i class="bi bi-person me-2 text-primary"></i>Persönliche Daten
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Vorname</label>
                                    <input type="text" name="first_name" class="form-control"
                                           value="<?= htmlspecialchars($lead['first_name'], ENT_QUOTES, 'UTF-8') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Nachname</label>
                                    <input type="text" name="last_name" class="form-control"
                                           value="<?= htmlspecialchars($lead['last_name'], ENT_QUOTES, 'UTF-8') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">E-Mail</label>
                                    <input type="email" name="email" class="form-control"
                                           value="<?= htmlspecialchars($lead['email'], ENT_QUOTES, 'UTF-8') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Telefon</label>
                                    <input type="text" name="phone" class="form-control"
                                           value="<?= htmlspecialchars($lead['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Verlorener Betrag (€)</label>
                                    <input type="number" name="amount_lost" class="form-control" min="0"
                                           value="<?= htmlspecialchars($lead['amount_lost'] ?? '0', ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Betrugsart</label>
                                    <select name="platform_category" class="form-select">
                                        <?php foreach ($categories as $c): ?>
                                            <option value="<?= $c ?>" <?= $lead['platform_category'] === $c ? 'selected' : '' ?>><?= $c ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Fallbeschreibung</label>
                                    <textarea name="case_description" class="form-control" rows="5"><?= htmlspecialchars($lead['case_description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0 fw-bold">
                            <i class="bi bi-journal-text me-2 text-primary"></i>Admin-Notizen
                        </div>
                        <div class="card-body">
                            <textarea name="admin_notes" class="form-control" rows="4"
                                      placeholder="Interne Notizen zum Fall..."><?= htmlspecialchars($lead['admin_notes'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Right column: Status & meta -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-white border-0 fw-bold">
                            <i class="bi bi-tag me-2 text-primary"></i>Status
                        </div>
                        <div class="card-body">
                            <select name="status" class="form-select fw-semibold">
                                <?php foreach ($statuses as $s): ?>
                                    <option value="<?= $s ?>" <?= $lead['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-white border-0 fw-bold">
                            <i class="bi bi-info-circle me-2 text-primary"></i>Metadaten
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between small">
                                    <span class="text-muted">Lead-ID</span>
                                    <span class="fw-bold">#<?= $id ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between small">
                                    <span class="text-muted">Eingegangen</span>
                                    <span><?= date('d.m.Y H:i', strtotime($lead['created_at'])) ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between small">
                                    <span class="text-muted">Aktualisiert</span>
                                    <span><?= date('d.m.Y H:i', strtotime($lead['updated_at'])) ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between small">
                                    <span class="text-muted">IP-Adresse</span>
                                    <span><?= htmlspecialchars($lead['ip_address'] ?? '–', ENT_QUOTES, 'UTF-8') ?></span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" name="update_lead" class="btn btn-primary fw-bold">
                            <i class="bi bi-save me-2"></i>Speichern
                        </button>
                        <a href="mailto:<?= htmlspecialchars($lead['email'], ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-envelope me-2"></i>E-Mail senden
                        </a>
                        <button type="button" class="btn btn-outline-danger"
                                data-bs-toggle="modal" data-bs-target="#deleteModal">
                            <i class="bi bi-trash me-2"></i>Lead löschen
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Delete Modal -->
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
                Möchten Sie diesen Lead wirklich unwiderruflich löschen?
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                <form method="POST" action="leads.php">
                    <input type="hidden" name="lead_id" value="<?= $id ?>">
                    <button type="submit" name="delete_lead" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i>Endgültig löschen
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
