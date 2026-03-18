<?php
/**
 * Unsubscribe page – Professional German
 * URL: /unsubscribe.php?token={open_token}
 *
 * Marks the recipient as unsubscribed and shows a confirmation.
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$token    = trim($_GET['token'] ?? '');
$status   = 'pending'; // pending | success | error | already

if ($token) {
    if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
        $status = 'error';
    } else {
        $pdo = db_connect();

        // Check current status
        $stmt = $pdo->prepare('SELECT id, status, email FROM mailing_recipients WHERE open_token = :tok LIMIT 1');
        $stmt->execute([':tok' => $token]);
        $recipient = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$recipient) {
            $status = 'error';
        } elseif ($recipient['status'] === 'unsubscribed') {
            $status = 'already';
        } else {
            $upd = $pdo->prepare('UPDATE mailing_recipients SET status = "unsubscribed" WHERE open_token = :tok');
            $upd->execute([':tok' => $token]);
            $status = 'success';
        }
    }
} else {
    $status = 'error';
}

$site_name   = get_setting('company_name', 'KryptoxPay');
$site_url    = 'https://kryptoxpay.co.uk';
$masked_email = '';
if (!empty($recipient['email']) && strpos($recipient['email'], '@') !== false) {
    [$local, $domain] = explode('@', $recipient['email'], 2);
    $masked_email = substr($local, 0, 2) . str_repeat('*', max(1, strlen($local) - 2)) . '@' . $domain;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Abmelden vom Newsletter – <?= htmlspecialchars($site_name) ?></title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 0;
            background: #f2f4f7;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #374151;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card {
            max-width: 520px;
            width: 94%;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,.09);
            overflow: hidden;
        }
        .card-header {
            background: #0d2744;
            padding: 28px 36px;
            text-align: center;
        }
        .card-header a {
            font-size: 22px;
            font-weight: 700;
            color: #fff;
            text-decoration: none;
            letter-spacing: -.4px;
        }
        .card-header a span { color: #f0a500; }
        .card-header p {
            margin: 6px 0 0;
            font-size: 12px;
            color: #7fa8d4;
            letter-spacing: .8px;
            text-transform: uppercase;
        }
        .card-body {
            padding: 36px;
            text-align: center;
        }
        .icon {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 32px;
        }
        .icon-success { background: #dcfce7; color: #16a34a; }
        .icon-already { background: #fef9c3; color: #ca8a04; }
        .icon-error   { background: #fee2e2; color: #dc2626; }
        h1 {
            font-size: 22px;
            font-weight: 700;
            margin: 0 0 12px;
            color: #0d2744;
        }
        p {
            font-size: 15px;
            line-height: 1.7;
            margin: 0 0 14px;
            color: #4b5563;
        }
        .email-badge {
            display: inline-block;
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 4px 12px;
            font-family: monospace;
            font-size: 14px;
            color: #374151;
            margin: 4px 0 16px;
        }
        .divider { height: 1px; background: #e8edf2; margin: 24px 0; }
        .btn {
            display: inline-block;
            padding: 12px 28px;
            background: #0d2744;
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            transition: background .2s;
        }
        .btn:hover { background: #163755; }
        .card-footer {
            background: #f8fafc;
            border-top: 1px solid #e8edf2;
            padding: 16px 36px;
            text-align: center;
        }
        .card-footer p {
            margin: 0;
            font-size: 12px;
            color: #9ca3af;
        }
        .card-footer a { color: #9ca3af; }
    </style>
</head>
<body>
<div class="card">
    <div class="card-header">
        <a href="<?= htmlspecialchars($site_url) ?>">Kryptox<span>Pay</span></a>
        <p>Newsletter-Verwaltung</p>
    </div>

    <div class="card-body">
        <?php if ($status === 'success'): ?>

        <div class="icon icon-success">&#10003;</div>
        <h1>Erfolgreich abgemeldet</h1>
        <?php if ($masked_email): ?>
        <div class="email-badge"><?= htmlspecialchars($masked_email) ?></div>
        <?php endif; ?>
        <p>
            Ihre E-Mail-Adresse wurde aus unserem Verteiler entfernt.<br>
            Sie erhalten ab sofort keine weiteren Nachrichten von uns.
        </p>
        <p style="font-size:13px;color:#6b7280">
            Es kann bis zu 48 Stunden dauern, bis die Änderung vollständig wirksam ist.
            Falls Sie versehentlich abgemeldet haben, können Sie sich jederzeit erneut
            auf unserer Website anmelden.
        </p>

        <?php elseif ($status === 'already'): ?>

        <div class="icon icon-already">&#9888;</div>
        <h1>Bereits abgemeldet</h1>
        <?php if ($masked_email): ?>
        <div class="email-badge"><?= htmlspecialchars($masked_email) ?></div>
        <?php endif; ?>
        <p>
            Diese E-Mail-Adresse ist bereits aus unserem Verteiler ausgetragen.<br>
            Sie erhalten keine weiteren Nachrichten von uns.
        </p>

        <?php else: ?>

        <div class="icon icon-error">&#33;</div>
        <h1>Link ungültig</h1>
        <p>
            Der verwendete Abmelde-Link ist ungültig oder abgelaufen.<br>
            Bitte prüfen Sie den Link in Ihrer E-Mail oder kontaktieren Sie uns direkt.
        </p>

        <?php endif; ?>

        <div class="divider"></div>
        <a href="<?= htmlspecialchars($site_url) ?>" class="btn">Zur Website</a>
    </div>

    <div class="card-footer">
        <p>
            &copy; <?= date('Y') ?> <?= htmlspecialchars($site_name) ?> &nbsp;&middot;&nbsp;
            <a href="<?= htmlspecialchars($site_url) ?>/datenschutz">Datenschutz</a> &nbsp;&middot;&nbsp;
            <a href="<?= htmlspecialchars($site_url) ?>/impressum">Impressum</a>
        </p>
    </div>
</div>
</body>
</html>
