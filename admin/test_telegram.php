<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

admin_check();

$ok = send_telegram_notification([
    'first_name'        => 'Test',
    'last_name'         => 'Nachricht',
    'email'             => 'test@example.com',
    'phone'             => '+49 000 000000',
    'country'           => 'Deutschland',
    'amount_lost'       => '9999',
    'platform_category' => 'Krypto-Betrug',
    'case_description'  => 'Das ist eine Test-Nachricht von Ihrer Admin-Oberfläche.',
    'ip'                => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
]);

header('Location: settings.php?tab=telegram&test=' . ($ok ? 'ok' : 'fail'));
exit;
