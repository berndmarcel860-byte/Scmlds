<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

admin_check();

$filters = [
    'status'   => $_GET['status']   ?? '',
    'search'   => $_GET['search']   ?? '',
    'category' => $_GET['category'] ?? '',
];

log_activity('export', 'CSV export with filters: ' . json_encode($filters));

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="verlustrueck_leads_' . date('Ymd_His') . '.csv"');

$output = fopen('php://output', 'w');
// BOM for Excel UTF-8 compatibility
fwrite($output, "\xEF\xBB\xBF");

fputcsv($output, [
    'ID', 'Vorname', 'Nachname', 'E-Mail', 'Telefon', 'Land', 'Jahr des Verlustes',
    'Betrag (€)', 'Betrugsart', 'Status', 'Fallbeschreibung',
    'Lead-Quelle', 'UTM Source',
    'IP-Adresse', 'Admin-Notizen',
    'Eingegangen', 'Aktualisiert'
], ';');

// Stream in chunks to avoid memory exhaustion on large datasets
$chunk_size = 500;
$page = 1;
do {
    $result = get_leads($filters, $page, $chunk_size);
    $leads  = $result['data'];

    foreach ($leads as $lead) {
        fputcsv($output, [
            $lead['id'],
            $lead['first_name'],
            $lead['last_name'],
            $lead['email'],
            $lead['phone'] ?? '',
            $lead['country'] ?? '',
            $lead['year_lost'] ?? '',
            number_format((float) $lead['amount_lost'], 2, ',', '.'),
            $lead['platform_category'],
            $lead['status'],
            $lead['case_description'],
            $lead['lead_source'] ?? 'website',
            $lead['utm_source'] ?? '',
            $lead['ip_address'] ?? '',
            $lead['admin_notes'] ?? '',
            date('d.m.Y H:i', strtotime($lead['created_at'])),
            date('d.m.Y H:i', strtotime($lead['updated_at'])),
        ], ';');
    }

    $page++;
} while (count($leads) === $chunk_size);

fclose($output);
exit;
