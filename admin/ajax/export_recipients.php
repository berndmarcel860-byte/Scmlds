<?php
/**
 * Export mailing recipients as CSV.
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

admin_check();

$campaign_id = (int) ($_GET['campaign_id'] ?? 0);
if (!$campaign_id) { http_response_code(400); exit('No campaign'); }

$filter     = in_array($_GET['filter'] ?? '', ['','pending','sent','failed','bounced','unsubscribed']) ? ($_GET['filter'] ?? '') : '';
$campaign   = get_mailing_campaign($campaign_id);
$recipients = get_mailing_recipients($campaign_id, $filter, 100000, 0, '');

$filename = 'recipients_campaign_' . $campaign_id . ($filter ? '_' . $filter : '') . '_' . date('Ymd') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel

$out = fopen('php://output', 'w');
fputcsv($out, ['ID','E-Mail','Name','E-Mail Gültigkeit','Status','SMTP-Account','Versendet am','Geöffnet am','Link geklickt','Klick-Anzahl','Fehlermeldung']);
foreach ($recipients as $r) {
    $smtp_label = '';
    if ($r['smtp_account_id']) {
        $a = get_mailing_smtp_account((int) $r['smtp_account_id']);
        $smtp_label = $a ? ($a['label'] ?: $a['from_email']) : '';
    }
    fputcsv($out, [
        $r['id'],
        $r['email'],
        $r['name'],
        $r['email_validity'] ?? 'valid',
        $r['status'],
        $smtp_label,
        $r['sent_at'] ?? '',
        $r['opened_at'] ?? '',
        $r['clicked_at'] ?? '',
        $r['click_count'] ?? 0,
        $r['error_msg'] ?? '',
    ]);
}
fclose($out);
