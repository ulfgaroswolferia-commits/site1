<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=900');

$now = gmdate('Y-m-d\TH:00');
$cacheFile = sys_get_temp_dir() . '/nasa_dialamoon_' . md5($now) . '.json';

if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 900)) {
    readfile($cacheFile);
    exit;
}

$url = "https://svs.gsfc.nasa.gov/api/dialamoon/" . $now;
$ctx = stream_context_create([
    'http' => [
        'timeout' => 4,
        'user_agent' => 'jakubgorski.com-NASA-LRO-Viewer/1.0'
    ],
    'ssl' => [
        'verify_peer' => true,
        'verify_peer_name' => true
    ]
]);

$response = @file_get_contents($url, false, $ctx);
if ($response !== false && strlen($response) > 50) {
    @file_put_contents($cacheFile, $response);
    echo $response;
} else {
    http_response_code(503);
    echo json_encode(['error' => 'NASA SVS unavailable, using local ephemeris']);
}
