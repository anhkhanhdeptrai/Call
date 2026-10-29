<?php

require_once __DIR__ . '/vendor/autoload.php';

use Firebase\JWT\JWT;

// Đọc file .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Lấy API Key và Secret
$apiKeySid = $_ENV['API_KEY_SID'];
$apiKeySecret = $_ENV['API_KEY_SECRET'];

// Kiểm tra cấu hình
if (empty($apiKeySid) || empty($apiKeySecret)) {
    http_response_code(500);
    header('Content-Type: application/json');

    echo json_encode([
        'error' => 'API_KEY_SID hoặc API_KEY_SECRET chưa được cấu hình'
    ]);

    exit;
}

// Kiểm tra username
if (!isset($_GET['u']) || empty($_GET['u'])) {
    header('Content-Type: application/json');

    echo json_encode([
        'access_token' => ''
    ]);

    exit;
}

$username = $_GET['u'];

$now = time();

// Header của Stringee
$header = [
    'cty' => 'stringee-api;v=1'
];

// Payload JWT
$payload = [
    'jti' => $apiKeySid . '-' . $now,
    'iss' => $apiKeySid,
    'exp' => $now + 3600,
    'userId' => $username
];

// Tạo JWT
$jwt = JWT::encode(
    $payload,
    $apiKeySecret,
    'HS256',
    null,
    $header
);

// CORS
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

// Trả token
echo json_encode([
    'access_token' => $jwt
]);
