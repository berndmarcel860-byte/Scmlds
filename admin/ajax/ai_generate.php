<?php
/**
 * AI SEO Generator – AJAX endpoint
 * POST params:
 *   context : Description of the page/content
 *   type    : 'all' | 'description' | 'keywords'
 *   lang    : e.g. 'Deutsch'
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

// Must be logged-in admin
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['error' => 'Nicht autorisiert.']);
    exit;
}

$api_key = get_setting('openai_api_key', '');
if (empty($api_key)) {
    echo json_encode(['error' => 'Kein OpenAI API-Key konfiguriert. Bitte unter SEO → KI-Generierung hinterlegen.']);
    exit;
}

$context = trim($_POST['context'] ?? '');
$type    = trim($_POST['type']    ?? 'all');
$lang    = trim($_POST['lang']    ?? 'Deutsch');

if (empty($context)) {
    echo json_encode(['error' => 'Bitte geben Sie einen Kontext/Beschreibungstext ein.']);
    exit;
}

// Build prompt
if ($type === 'description') {
    $prompt = "Schreibe eine prägnante SEO-Meta-Beschreibung auf $lang für folgende Website: $context\n\nDie Beschreibung soll maximal 155 Zeichen haben, einen Call-to-Action enthalten und für Suchmaschinen optimiert sein. Antworte nur mit dem Text, ohne Anführungszeichen.";
} elseif ($type === 'keywords') {
    $prompt = "Generiere 15 relevante SEO-Keywords auf $lang (kommagetrennt) für folgende Website: $context\n\nMix aus Short-Tail und Long-Tail Keywords. Antworte nur mit den Keywords, kommagetrennt, keine Nummerierung.";
} else {
    $prompt = "Du bist ein SEO-Experte. Für die folgende Website generiere:\n1. Eine Meta-Beschreibung auf $lang (max. 155 Zeichen, mit Call-to-Action)\n2. 15 relevante SEO-Keywords auf $lang (kommagetrennt, Mix aus Short-Tail und Long-Tail)\n\nWebsite: $context\n\nAntworte exakt in diesem Format (ohne weitere Erklärungen):\nDESCRIPTION: [die Meta-Beschreibung]\nKEYWORDS: [die Keywords]";
}

// Call OpenAI API
$payload = json_encode([
    'model'       => 'gpt-4o-mini',
    'messages'    => [
        ['role' => 'system', 'content' => 'Du bist ein professioneller SEO-Experte mit Schwerpunkt auf deutschsprachige Websites.'],
        ['role' => 'user',   'content' => $prompt],
    ],
    'max_tokens'  => 400,
    'temperature' => 0.7,
]);

$result = null;

if (function_exists('curl_init')) {
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_key,
        ],
    ]);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err  = curl_error($ch);
    curl_close($ch);

    if ($curl_err) {
        echo json_encode(['error' => 'cURL-Fehler: ' . $curl_err]);
        exit;
    }
    $result = json_decode($response, true);
} else {
    $ctx = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\nAuthorization: Bearer $api_key\r\n",
            'content' => $payload,
            'timeout' => 30,
        ],
    ]);
    $response = @file_get_contents('https://api.openai.com/v1/chat/completions', false, $ctx);
    if ($response === false) {
        echo json_encode(['error' => 'Netzwerkfehler beim Aufrufen der OpenAI-API.']);
        exit;
    }
    $result = json_decode($response, true);
}

if (isset($result['error'])) {
    echo json_encode(['error' => 'OpenAI API-Fehler: ' . ($result['error']['message'] ?? 'Unbekannter Fehler')]);
    exit;
}

$text = trim($result['choices'][0]['message']['content'] ?? '');

if (empty($text)) {
    echo json_encode(['error' => 'Keine Antwort von der API erhalten.']);
    exit;
}

// Parse response
$output = [];

if ($type === 'description') {
    $output['description'] = $text;
} elseif ($type === 'keywords') {
    $output['keywords'] = $text;
} else {
    // Parse the structured response
    if (preg_match('/DESCRIPTION:\s*(.+?)(?=KEYWORDS:|$)/si', $text, $m)) {
        $output['description'] = trim($m[1]);
    }
    if (preg_match('/KEYWORDS:\s*(.+)/si', $text, $m)) {
        $output['keywords'] = trim($m[1]);
    }
    // Fallback if parsing fails
    if (empty($output)) {
        $lines = array_filter(array_map('trim', explode("\n", $text)));
        $output['description'] = array_shift($lines) ?? '';
        $output['keywords']    = implode(', ', array_slice($lines, 0, 1));
    }
}

echo json_encode($output);
