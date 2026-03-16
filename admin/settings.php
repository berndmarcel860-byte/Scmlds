<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

admin_check();

$message = '';
$msg_type = 'success';

// ── Handle form submissions ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tab = trim($_POST['tab'] ?? 'general');

    if ($tab === 'general') {
        $keys = [
            'company_name', 'site_url', 'admin_email',
            'from_email', 'from_name', 'page_title',
            'modal_delay_seconds', 'send_email_on_submission',
        ];
        $data = [];
        foreach ($keys as $k) {
            $data[$k] = trim($_POST[$k] ?? '');
        }
        // Checkboxes
        $data['send_email_on_submission'] = isset($_POST['send_email_on_submission']) ? '1' : '0';

        if (save_settings($data)) {
            log_activity('settings_updated', 'General settings updated');
            $message = 'Allgemeine Einstellungen wurden gespeichert.';
        } else {
            $msg_type = 'danger';
            $message  = 'Fehler beim Speichern. Bitte versuchen Sie es erneut.';
        }
    } elseif ($tab === 'smtp') {
        $smtp = [
            'host'       => trim($_POST['smtp_host']       ?? ''),
            'port'       => (int) ($_POST['smtp_port']     ?? 587),
            'username'   => trim($_POST['smtp_username']   ?? ''),
            'password'   => trim($_POST['smtp_password']   ?? ''),
            'secure'     => in_array($_POST['smtp_secure'] ?? '', ['tls', 'ssl', 'none']) ? $_POST['smtp_secure'] : 'tls',
            'debug'      => (int) ($_POST['smtp_debug']    ?? 0),
            'from_email' => trim($_POST['smtp_from_email'] ?? ''),
            'from_name'  => trim($_POST['smtp_from_name']  ?? ''),
        ];
        if (save_smtp_settings($smtp)) {
            log_activity('settings_updated', 'SMTP settings updated');
            $message = 'SMTP-Einstellungen wurden gespeichert.';
        } else {
            $msg_type = 'danger';
            $message  = 'Fehler beim Speichern der SMTP-Einstellungen.';
        }
    } elseif ($tab === 'telegram') {
        $tg = [
            'bot_token' => trim($_POST['tg_bot_token'] ?? ''),
            'chat_id'   => trim($_POST['tg_chat_id']   ?? ''),
            'active'    => isset($_POST['tg_active']) ? 1 : 0,
        ];
        if (save_telegram_settings($tg)) {
            log_activity('settings_updated', 'Telegram settings updated');
            $message = 'Telegram-Einstellungen wurden gespeichert.';
        } else {
            $msg_type = 'danger';
            $message  = 'Fehler beim Speichern der Telegram-Einstellungen.';
        }
    }
}

// ── Load current values ───────────────────────────────────────────────────────
$gen  = [];
foreach (get_all_settings() as $row) {
    $gen[$row['setting_key']] = $row['setting_value'];
}
$smtp = get_smtp_settings();
$tg   = get_telegram_settings();

$active_tab = $_POST['tab'] ?? ($_GET['tab'] ?? 'general');
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Einstellungen – VerlustRückholung Admin</title>
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
        <div class="mb-4">
            <h4 class="fw-bold mb-0"><i class="bi bi-gear-fill me-2 text-primary"></i>Einstellungen</h4>
            <p class="text-muted small mb-0">Website-, E-Mail- und Benachrichtigungs-Konfiguration</p>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show" role="alert">
            <i class="bi bi-<?= $msg_type === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Nav tabs -->
        <ul class="nav nav-tabs mb-4" id="settingsTabs">
            <li class="nav-item">
                <a class="nav-link <?= $active_tab === 'general'  ? 'active' : '' ?>"
                   href="?tab=general"><i class="bi bi-sliders me-1"></i>Allgemein</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $active_tab === 'smtp'     ? 'active' : '' ?>"
                   href="?tab=smtp"><i class="bi bi-envelope-at me-1"></i>SMTP / E-Mail</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $active_tab === 'telegram' ? 'active' : '' ?>"
                   href="?tab=telegram"><i class="bi bi-telegram me-1"></i>Telegram</a>
            </li>
        </ul>

        <!-- ===== TAB: GENERAL ===== -->
        <?php if ($active_tab === 'general'): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="tab" value="general">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Firmenname</label>
                            <input type="text" name="company_name" class="form-control"
                                   value="<?= htmlspecialchars($gen['company_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Website-URL</label>
                            <input type="url" name="site_url" class="form-control"
                                   value="<?= htmlspecialchars($gen['site_url'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Admin-E-Mail</label>
                            <input type="email" name="admin_email" class="form-control"
                                   value="<?= htmlspecialchars($gen['admin_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Absender-E-Mail</label>
                            <input type="email" name="from_email" class="form-control"
                                   value="<?= htmlspecialchars($gen['from_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Absender-Name</label>
                            <input type="text" name="from_name" class="form-control"
                                   value="<?= htmlspecialchars($gen['from_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Sekunden bis Modal erscheint</label>
                            <div class="input-group">
                                <input type="number" name="modal_delay_seconds" class="form-control" min="5" max="600"
                                       value="<?= (int) ($gen['modal_delay_seconds'] ?? 60) ?>">
                                <span class="input-group-text">Sekunden</span>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Seiten-Titel (index.php)</label>
                            <input type="text" name="page_title" class="form-control"
                                   value="<?= htmlspecialchars($gen['page_title'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="send_email_on_submission"
                                       id="sendEmailToggle" value="1"
                                       <?= ($gen['send_email_on_submission'] ?? '1') === '1' ? 'checked' : '' ?>>
                                <label class="form-check-label fw-semibold" for="sendEmailToggle">
                                    Bestätigungs-E-Mail nach Formular-Absendung senden
                                </label>
                            </div>
                            <div class="text-muted small mt-1">
                                Wenn deaktiviert, werden keine E-Mails nach einer Falleinreichung gesendet.
                                Telegram-Benachrichtigungen sind davon unabhängig.
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i>Einstellungen speichern
                    </button>
                </form>
            </div>
        </div>

        <!-- ===== TAB: SMTP ===== -->
        <?php elseif ($active_tab === 'smtp'): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="alert alert-info mb-4">
                    <i class="bi bi-info-circle me-2"></i>
                    SMTP-Einstellungen überschreiben die Werte in <code>config/config.php</code>.
                    Lassen Sie Felder leer, um die Standardwerte aus <code>config.php</code> zu verwenden.
                </div>
                <form method="POST">
                    <input type="hidden" name="tab" value="smtp">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold small">SMTP-Server (Host)</label>
                            <input type="text" name="smtp_host" class="form-control"
                                   placeholder="smtp.example.com"
                                   value="<?= htmlspecialchars($smtp['host'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Port</label>
                            <input type="number" name="smtp_port" class="form-control"
                                   value="<?= (int) ($smtp['port'] ?? 587) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Benutzername</label>
                            <input type="text" name="smtp_username" class="form-control" autocomplete="off"
                                   value="<?= htmlspecialchars($smtp['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Passwort</label>
                            <div class="input-group">
                                <input type="password" name="smtp_password" class="form-control"
                                       id="smtpPass" autocomplete="new-password"
                                       placeholder="<?= !empty($smtp['password']) ? '••••••••' : 'Passwort eingeben' ?>">
                                <button class="btn btn-outline-secondary" type="button"
                                        onclick="var f=document.getElementById('smtpPass');f.type=f.type==='password'?'text':'password'">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="form-text">Leer lassen, um das bisherige Passwort beizubehalten.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Verschlüsselung</label>
                            <select name="smtp_secure" class="form-select">
                                <option value="tls"  <?= ($smtp['secure'] ?? 'tls') === 'tls'  ? 'selected' : '' ?>>TLS (empfohlen)</option>
                                <option value="ssl"  <?= ($smtp['secure'] ?? '') === 'ssl'  ? 'selected' : '' ?>>SSL</option>
                                <option value="none" <?= ($smtp['secure'] ?? '') === 'none' ? 'selected' : '' ?>>Keine</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Absender-E-Mail</label>
                            <input type="email" name="smtp_from_email" class="form-control"
                                   value="<?= htmlspecialchars($smtp['from_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Absender-Name</label>
                            <input type="text" name="smtp_from_name" class="form-control"
                                   value="<?= htmlspecialchars($smtp['from_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Debug-Modus</label>
                            <select name="smtp_debug" class="form-select">
                                <option value="0" <?= (int)($smtp['debug'] ?? 0) === 0 ? 'selected' : '' ?>>Aus (0)</option>
                                <option value="1" <?= (int)($smtp['debug'] ?? 0) === 1 ? 'selected' : '' ?>>Minimal (1)</option>
                                <option value="2" <?= (int)($smtp['debug'] ?? 0) === 2 ? 'selected' : '' ?>>Verbose (2)</option>
                            </select>
                        </div>
                    </div>
                    <hr class="my-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i>SMTP-Einstellungen speichern
                    </button>
                </form>
            </div>
        </div>

        <!-- ===== TAB: TELEGRAM ===== -->
        <?php elseif ($active_tab === 'telegram'): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="alert alert-info mb-4">
                    <i class="bi bi-telegram me-2"></i>
                    <strong>So einrichten:</strong>
                    <ol class="mb-0 mt-2 ps-3">
                        <li>Erstellen Sie einen Bot mit <a href="https://t.me/BotFather" target="_blank">@BotFather</a> und kopieren Sie den <strong>Bot-Token</strong>.</li>
                        <li>Senden Sie Ihrem Bot eine Nachricht und rufen Sie dann
                            <code>https://api.telegram.org/bot&lt;TOKEN&gt;/getUpdates</code> auf, um Ihre <strong>Chat-ID</strong> zu erhalten.</li>
                        <li>Tragen Sie beide Werte ein, aktivieren Sie die Benachrichtigungen und speichern Sie.</li>
                    </ol>
                </div>
                <form method="POST">
                    <input type="hidden" name="tab" value="telegram">
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label fw-semibold small">Bot-Token</label>
                            <input type="text" name="tg_bot_token" class="form-control font-monospace"
                                   placeholder="1234567890:AAF..."
                                   value="<?= htmlspecialchars($tg['bot_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-semibold small">Chat-ID</label>
                            <input type="text" name="tg_chat_id" class="form-control font-monospace"
                                   placeholder="-100123456789"
                                   value="<?= htmlspecialchars($tg['chat_id'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="tg_active" id="tgActive"
                                       value="1" <?= !empty($tg['active']) ? 'checked' : '' ?>>
                                <label class="form-check-label fw-semibold" for="tgActive">
                                    Telegram-Benachrichtigungen aktivieren
                                </label>
                            </div>
                        </div>
                    </div>
                    <hr class="my-4">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-save me-1"></i>Telegram-Einstellungen speichern
                    </button>
                    <?php if (!empty($tg['bot_token']) && !empty($tg['chat_id'])): ?>
                    <a href="test_telegram.php" class="btn btn-outline-secondary" target="_blank">
                        <i class="bi bi-send me-1"></i>Test-Nachricht senden
                    </a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        <?php endif; ?>

    </div><!-- /admin-content -->
</div><!-- /admin-main -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
