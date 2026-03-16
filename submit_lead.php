<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';

// CSRF validation
if (
    empty($_POST['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])
) {
    header('Location: index.php?error=' . urlencode('Ungültige Sitzung. Bitte versuchen Sie es erneut.'));
    exit;
}

// Collect & sanitize input
$first_name        = trim($_POST['first_name'] ?? '');
$last_name         = trim($_POST['last_name'] ?? '');
$email             = trim($_POST['email'] ?? '');
$phone             = trim($_POST['phone'] ?? '');
$amount_lost       = trim($_POST['amount_lost'] ?? '');
$platform_category = trim($_POST['platform_category'] ?? 'Andere');
$case_description  = trim($_POST['case_description'] ?? '');
$lead_source       = trim($_POST['lead_source'] ?? 'website');

// Normalize engagement-modal submissions
$is_engagement = ($lead_source === 'engagement_modal');
if ($is_engagement) {
    if (empty($case_description)) {
        $case_description = 'Über 60-Sekunden-Engagement-Modal eingereicht.';
    }
    if (empty($amount_lost) || !is_numeric($amount_lost)) {
        $amount_lost = '0';
    }
    if (empty($platform_category)) {
        $platform_category = 'Andere';
    }
}

// Validate required fields
$errors = [];
if (empty($first_name)) $errors[] = 'Vorname fehlt.';
if (empty($last_name))  $errors[] = 'Nachname fehlt.';
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Ungültige E-Mail-Adresse.';
if (!$is_engagement && (empty($amount_lost) || !is_numeric($amount_lost) || $amount_lost <= 0)) {
    $errors[] = 'Ungültiger Betrag (muss größer als 0 sein).';
}
if (!$is_engagement && empty($case_description)) $errors[] = 'Fallbeschreibung fehlt.';

if (!empty($errors)) {
    header('Location: index.php?error=' . urlencode(implode(' ', $errors)));
    exit;
}

// Allowed categories
$allowed_categories = [
    'Krypto-Betrug',
    'Forex-Betrug',
    'Fake-Broker',
    'Romance-Scam mit Investitionsbetrug',
    'Binäre Optionen',
    'Andere',
];
if (!in_array($platform_category, $allowed_categories, true)) {
    $platform_category = 'Andere';
}

try {
    $pdo = db_connect();
    $stmt = $pdo->prepare(
        'INSERT INTO leads
         (first_name, last_name, email, phone, amount_lost, platform_category, case_description, status, ip_address)
         VALUES (:fn, :ln, :em, :ph, :al, :pc, :cd, :st, :ip)'
    );
    $stmt->execute([
        ':fn' => $first_name,
        ':ln' => $last_name,
        ':em' => $email,
        ':ph' => $phone,
        ':al' => (float) $amount_lost,
        ':pc' => $platform_category,
        ':cd' => $case_description,
        ':st' => 'Neu',
        ':ip' => $_SERVER['REMOTE_ADDR'] ?? '',
    ]);

    // Regenerate CSRF token after successful submission
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    header('Location: index.php?success=1#fallform');
    exit;
} catch (PDOException $e) {
    header('Location: index.php?error=' . urlencode('Datenbankfehler. Bitte versuchen Sie es später erneut.'));
    exit;
}
