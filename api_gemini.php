<?php
// ===================================================
// Gemini 音声文字起こし＋話者分離 プロキシ
// smatraのproxy.phpと同じ方式（base64 JSON）
// ===================================================
define('GEMINI_API_KEY', 'AIzaSyAu6LZABtKCcdUGDu7j2EK2DnjpzjIlGCw');

set_time_limit(180);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit;
}

$input = file_get_contents('php://input');
if (!$input) {
    http_response_code(400); echo json_encode(['error' => 'No input']); exit;
}

$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . GEMINI_API_KEY;

$maxRetry = 3;
$backoff   = [3, 6, 12];
$lastCode  = 503;
$lastBody  = '';

for ($i = 0; $i < $maxRetry; $i++) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $input,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 150,
    ]);
    $lastBody = curl_exec($ch);
    $lastCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        http_response_code(500);
        echo json_encode(['error' => 'curl: ' . $curlErr]);
        exit;
    }
    if ($lastCode !== 503) break;
    if ($i < $maxRetry - 1) sleep($backoff[$i]);
}

http_response_code($lastCode);
echo $lastBody;
