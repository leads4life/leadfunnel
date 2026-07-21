<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);

// The lead form is served from this same site, so no cross-origin (CORS)
// headers are needed. Omitting Access-Control-Allow-Origin keeps other
// websites from POSTing spam to this endpoint from a browser.
header('Content-Type: application/json');

// Password protection
$API_PASSWORD = 'leads4life123'; // Change this to your secure password

// Where leads are stored. By default this lives OUTSIDE the public web root
// (one level up from the site folder) so it can never be downloaded via a URL,
// regardless of Apache config or whether .htaccess is honored. setup.sh creates
// this directory with the right permissions. Override with the LEADS_FILE env
// var if you want a different location.
$LEADS_FILE = getenv('LEADS_FILE') ?: '/var/www/leads_store/leads_data.json';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Verify password
$password = isset($_POST['password']) ? $_POST['password'] : '';
if ($password !== $API_PASSWORD) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Helper to support both direct fields and nested personal_details arrays.
function get_post_value($key, $default = '') {
    if (isset($_POST[$key])) {
        return sanitize($_POST[$key]);
    }
    if (isset($_POST['personal_details'][$key])) {
        return sanitize($_POST['personal_details'][$key]);
    }
    return $default;
}

// Collect form data
$lead = [
    'timestamp' => date('Y-m-d H:i:s'),
    'name' => get_post_value('name'),
    'email' => get_post_value('email'),
    'phone' => get_post_value('phone'),
    'is_buying' => isset($_POST['is_buying']) ? sanitize($_POST['is_buying']) : '',
    'is_selling' => isset($_POST['is_selling']) ? sanitize($_POST['is_selling']) : '',
    'buy_location' => isset($_POST['buy_location']) ? sanitize($_POST['buy_location']) : '',
    'sell_address' => isset($_POST['sell_address']) ? sanitize($_POST['sell_address']) : '',
    'property_type' => isset($_POST['property_type']) ? sanitize($_POST['property_type']) : '',
    'value' => isset($_POST['value']) ? sanitize($_POST['value']) : '',
    'quote' => isset($_POST['quote']) ? sanitize($_POST['quote']) : '',
    'notes' => get_post_value('notes'),
    'page_source' => isset($_POST['page_source']) ? sanitize($_POST['page_source']) : 'funnel',
    'ip_address' => $_SERVER['REMOTE_ADDR']
];

// Read existing leads
$leads = [];
if (file_exists($LEADS_FILE)) {
    $json_data = file_get_contents($LEADS_FILE);
    $leads = json_decode($json_data, true);
    if (!is_array($leads)) {
        $leads = [];
    }
}

// Append new lead
$leads[] = $lead;

// Ensure the storage directory exists (created on first write if setup.sh
// wasn't run). Fails gracefully if the web user lacks permission.
$leads_dir = dirname($LEADS_FILE);
if (!is_dir($leads_dir)) {
    @mkdir($leads_dir, 0770, true);
}

// Write back to file with pretty formatting
$json_output = json_encode($leads, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
if (file_put_contents($LEADS_FILE, $json_output)) {
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Lead saved successfully']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save lead']);
}

function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}
?>
