<?php
// ===================================================
// Google Cloud STT 処理状況ポーリング
// ?op=operations/xxxxx で呼び出す
// ===================================================
define('GOOGLE_API_KEY', 'AIzaSyB_8wWxprNVGXKU9FvRsCWtcKF6ygIHcdo');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$opName = $_GET['op'] ?? '';

// operations/ で始まることを確認（簡易バリデーション）
if (!preg_match('/^operations\/[\w\-]+$/', $opName)) {
    http_response_code(400);
    echo json_encode(['error' => 'operationNameが不正です']);
    exit;
}

$apiUrl = 'https://speech.googleapis.com/v1/' . $opName . '?key=' . GOOGLE_API_KEY;

$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 30,
]);

$response  = curl_exec($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    http_response_code(500);
    echo json_encode(['error' => 'ポーリングエラー: ' . $curlError]);
    exit;
}

http_response_code($httpCode);
echo $response;
