<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function respond(int $code, array $payload): void
{
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, [
        'ok' => false,
        'error' => 'Method not allowed'
    ]);
}

$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input)) {
    respond(400, [
        'ok' => false,
        'error' => 'Invalid JSON body'
    ]);
}

$email = strtolower(trim((string)($input['email'] ?? '')));
$password = (string)($input['password'] ?? '');

if ($email === '' || $password === '') {
    respond(400, [
        'ok' => false,
        'error' => 'Email and password are required'
    ]);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(400, [
        'ok' => false,
        'error' => 'Invalid email'
    ]);
}

require_once __DIR__ . '/db/mysql.php';
require_once __DIR__ . '/db/redis.php';

$sessionTtlSeconds = 86400;

try {
    $mysqli = mysqlConnect();

    $stmt = $mysqli->prepare(
        'SELECT id, hash, name, email, password_hash
         FROM users
         WHERE email = ?
         LIMIT 1'
    );

    $stmt->bind_param('s', $email);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    $stmt->close();
    $mysqli->close();

    if (!$user) {
        respond(401, [
            'ok' => false,
            'error' => 'Invalid credentials'
        ]);
    }

    $inputPasswordHash = hash('sha512', $password);

    if (!hash_equals((string)$user['password_hash'], $inputPasswordHash)) {
        respond(401, [
            'ok' => false,
            'error' => 'Invalid credentials'
        ]);
    }

    $token = bin2hex(random_bytes(32));

    $sessionData = [
        'user_id' => (int)$user['id'],
        'hash' => (string)$user['hash'],
        'name' => (string)$user['name'],
        'email' => (string)$user['email'],
        'login_time' => date('Y-m-d H:i:s')
    ];

    $sessionValue = json_encode($sessionData, JSON_UNESCAPED_SLASHES);

    if ($sessionValue === false) {
        respond(500, [
            'ok' => false,
            'error' => 'Session encode failed'
        ]);
    }

    $redis = redisConnect();

    $sessionKey = 'session:' . $token;

    $ok = $redis->setex(
        $sessionKey,
        $sessionTtlSeconds,
        $sessionValue
    );

    if ($ok === false) {
        respond(500, [
            'ok' => false,
            'error' => 'Failed to create session'
        ]);
    }

    respond(200, [
        'ok' => true,
        'message' => 'Login successful',
        'token' => $token,
        'hash' => (string)$user['hash'],
        'name' => (string)$user['name'],
        'email' => (string)$user['email']
    ]);

} catch (Throwable $e) {
    error_log($e->getMessage());

    respond(500, [
        'ok' => false,
        'error' => 'Internal server error: ' . $e->getMessage()
    ]);
}