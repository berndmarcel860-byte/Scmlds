<?php
/**
 * IndexNow helper – notifies Bing and other participating search engines
 * immediately when new blog posts are published or updated.
 *
 * SETUP (one-time):
 *   1. Generate a unique key:  php -r "echo bin2hex(random_bytes(16));"
 *   2. Store it as the 'indexnow_key' setting in the admin panel settings.
 *   3. Nginx serves GET /indexnow.php as a plain 200 OK that returns only the key –
 *      Bing will verify ownership by fetching  https://yourdomain.com/<key>.txt
 *      which Nginx must rewrite to this script (see nginx.conf snippet below).
 *
 * Nginx snippet (add inside the server {} block after other location blocks):
 *   location ~* ^/[a-f0-9]{16,64}\.txt$ {
 *       rewrite ^ /indexnow.php?verify=1 last;
 *   }
 *
 * USAGE (called automatically from admin/blog_edit.php after publishing a post):
 *   $urls = ['https://yourdomain.com/blog/mein-artikel'];
 *   include __DIR__ . '/indexnow.php';
 *   indexnow_submit($urls);
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$indexnow_key = get_setting('indexnow_key', '');

// Ownership-verification request:  GET /<key>.txt  → return just the key
if (!empty($_GET['verify']) && $indexnow_key !== '') {
    header('Content-Type: text/plain; charset=utf-8');
    echo $indexnow_key;
    exit;
}

// Direct API access disabled (only callable as include / CLI)
if (php_sapi_name() !== 'cli' && empty($GLOBALS['_indexnow_include'])) {
    http_response_code(403);
    exit;
}

/**
 * Submit one or more URLs to IndexNow (Bing endpoint).
 *
 * @param  string[]  $urls   Absolute URLs to submit.
 * @return bool              True on success (HTTP 200/202), false otherwise.
 */
function indexnow_submit(array $urls): bool
{
    $key = get_setting('indexnow_key', '');
    if ($key === '' || empty($urls)) {
        return false;
    }

    $host = parse_url(get_setting('site_url', SITE_URL), PHP_URL_HOST);

    $payload = json_encode([
        'host'    => $host,
        'key'     => $key,
        'keyLocation' => 'https://' . $host . '/' . $key . '.txt',
        'urlList' => array_values($urls),
    ]);

    $context = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\nContent-Length: " . strlen($payload),
            'content' => $payload,
            'timeout' => 5,
            'ignore_errors' => true,
        ],
    ]);

    $endpoint = 'https://api.indexnow.org/indexnow';
    $response = @file_get_contents($endpoint, false, $context);
    $status   = $http_response_header[0] ?? '';

    return strpos($status, '200') !== false || strpos($status, '202') !== false;
}
