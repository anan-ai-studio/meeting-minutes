<?php
// ===================================================
// Google Cloud Speech-to-Text API プロキシ（非同期版）
// 長時間録音対応：longrunningrecognize を使用
// ===================================================
define('GOOGLE_API_KEY', 'AIzaSyB_8wWxprNVGXKU9FvRsCWtcKF6ygIHcdo');

ini_set('upload_max_filesize', '200M');
ini_set('post_max_size', '200M');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!isset($_FILES['audio']) || $_FILES['audio']['error'] !== UPLOAD_ERR_OK) {
    $uploadError = $_FILES['audio']['error'] ?? 'ファイルなし';
    http_response_code(400);
    echo json_encode(['error' => '音声ファイルエラー: ' . $uploadError]);
    exit;
}

$audioData = base64_encode(file_get_contents($_FILES['audio']['tmp_name']));

// 非同期APIリクエスト（60分の長時間録音対応）
$requestBody = [
    'config' => [
        'languageCode'               => 'ja-JP',
        'model'                      => 'latest_long',
        'enableAutomaticPunctuation' => true,
        'enableSpeakerDiarization'   => true,
        'diarizationSpeakerCount'    => 6,
        // encodingは省略→Google自動検出（MP4/AACに対応）
    ],
    'audio' => ['content' => $audioData],
];

$apiUrl = 'https://speech.googleapis.com/v1p1beta1/speech:longrunningrecognize?key=' . GOOGLE_API_KEY;

$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($requestBody),
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT        => 120,
]);

$response  = curl_exec($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    http_response_code(500);
    echo json_encode(['error' => 'API接続エラー: ' . $curlError]);
    exit;
}

// {"name": "operations/xxxxx"} を返す
http_response_code($httpCode);
echo $response;
