<?php
// api/get_signed_url.php
// Returns a short-lived signed WebSocket URL for the ElevenLabs Conversational AI agent.
// The real API key lives only here, on the server, loaded from .env - never sent to the browser.

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // tighten this to your domain in production

// --- Minimal .env loader (no Composer needed) ---
function loadEnv($path) {
    if (!file_exists($path)) return;
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), '#') === 0) continue; // skip comments
        if (strpos($line, '=') === false) continue;
        [$key, $value] = explode('=', $line, 2);
        putenv(trim($key) . '=' . trim($value));
    }
}
loadEnv(__DIR__ . '/../.env');

$apiKey  = getenv('ELEVENLABS_API_KEY');
$agentId = getenv('ELEVENLABS_AGENT_ID');

if (!$apiKey || !$agentId) {
    http_response_code(500);
    echo json_encode(['error' => 'Server not configured: missing ELEVENLABS_API_KEY or ELEVENLABS_AGENT_ID']);
    exit;
}

$url = 'https://api.elevenlabs.io/v1/convai/conversation/get_signed_url?agent_id=' . urlencode($agentId);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'xi-api-key: ' . $apiKey,
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($curlErr) {
    http_response_code(502);
    echo json_encode(['error' => 'Request to ElevenLabs failed', 'details' => $curlErr]);
    exit;
}

if ($httpCode !== 200) {
    http_response_code($httpCode);
    echo $response; // pass through ElevenLabs' own error message
    exit;
}

// Response looks like: { "signed_url": "wss://api.elevenlabs.io/..." }
echo $response;
