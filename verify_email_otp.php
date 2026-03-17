<?php
/**
 * verify_email_otp.php
 * AJAX endpoint – verify a 6-digit OTP that was sent to an e-mail address.
 *
 * POST fields:
 *   csrf_token  – current session CSRF token
 *   email       – e-mail address the code was sent to
 *   code        – 6-digit code entered by the user
 *
 * On success sets:
 *   $_SESSION['email_verified']  =  $email
 */

require_once __DIR__ . '/config/config.php';

header('Content-Type: application/json; charset=utf-8');

function verify_json(bool $ok, string $msg, array $extra = []): never
{
    echo json_encode(array_merge(['success' => $ok, 'message' => $msg], $extra));
    exit;
}

// ── CSRF ──────────────────────────────────────────────────────────────────────
if (
    empty($_POST['csrf_token']) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    verify_json(false, 'Sitzung abgelaufen. Bitte Seite neu laden.');
}

// ── Input ─────────────────────────────────────────────────────────────────────
$email    = trim($_POST['email']    ?? '');
$submitted = trim($_POST['code']   ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    verify_json(false, 'Ungültige E-Mail-Adresse.');
}

if (!preg_match('/^\d{6}$/', $submitted)) {
    verify_json(false, 'Bitte den 6-stelligen Code eingeben.');
}

// ── Load OTP from session ─────────────────────────────────────────────────────
$otp = $_SESSION['email_otp'] ?? null;

if (!$otp) {
    verify_json(false, 'Es wurde kein Code angefordert. Bitte zuerst einen Code senden.');
}

// ── Email must match ─────────────────────────────────────────────────────────
if (!hash_equals(strtolower($otp['email']), strtolower($email))) {
    verify_json(false, 'Die E-Mail-Adresse stimmt nicht mit der überein, an die der Code gesendet wurde.');
}

// ── Expiry ────────────────────────────────────────────────────────────────────
if (time() > ($otp['expires'] ?? 0)) {
    unset($_SESSION['email_otp']);
    verify_json(false, 'Der Code ist abgelaufen. Bitte fordern Sie einen neuen Code an.');
}

// ── Rate-limit failed attempts (max 5) ───────────────────────────────────────
if (($otp['attempts'] ?? 0) >= 5) {
    unset($_SESSION['email_otp']);
    verify_json(false, 'Zu viele Fehlversuche. Bitte fordern Sie einen neuen Code an.');
}

// Increment attempt counter *before* checking, so a timing attack can't bypass it
$_SESSION['email_otp']['attempts'] = ($otp['attempts'] ?? 0) + 1;

// ── Verify code ───────────────────────────────────────────────────────────────
if (!password_verify($submitted, $otp['code'] ?? '')) {
    $remaining = 5 - $_SESSION['email_otp']['attempts'];
    if ($remaining <= 0) {
        unset($_SESSION['email_otp']);
        verify_json(false, 'Zu viele Fehlversuche. Bitte fordern Sie einen neuen Code an.');
    }
    verify_json(false, 'Der Code ist falsch. Noch ' . $remaining . ' Versuch(e) übrig.');
}

// ── Success ───────────────────────────────────────────────────────────────────
$_SESSION['email_verified'] = strtolower($email);
unset($_SESSION['email_otp']); // consumed

verify_json(true, 'E-Mail-Adresse erfolgreich bestätigt.');
