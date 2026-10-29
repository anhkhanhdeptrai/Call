<?php

require_once 'vendor/autoload.php';

use Firebase\JWT\JWT;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$apiKeySid = $_ENV['API_KEY_SID'] ?? getenv('API_KEY_SID');
$apiKeySecret = $_ENV['API_KEY_SECRET'] ?? getenv('API_KEY_SECRET');

if (!$apiKeySid || !$apiKeySecret) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => 'API_KEY_SID hoặc API_KEY_SECRET chưa được cấu hình'
    ]);
    exit;
}

if (!isset($_GET['u']) || empty($_GET['u'])) {
    $jwt = '';
} else {
    $username = $_GET['u'];
    $now = time();

    $header = [
        'cty' => 'stringee-api;v=1'
    ];

    $payload = [
        'jti' => $apiKeySid . '-' . $now,
        'iss' => $apiKeySid,
        'exp' => $now + 3600,
        'userId' => $username
    ];

    $jwt = JWT::encode(
        $payload,
        $apiKeySecret,
        'HS256',
        null,
        $header
    );
}

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

echo json_encode([
    'access_token' => $jwt
]);
