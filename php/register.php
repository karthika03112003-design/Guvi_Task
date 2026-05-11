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

$name = trim((string)($input['name'] ?? ''));
$email = strtolower(trim((string)($input['email'] ?? '')));
$password = (string)($input['password'] ?? '');

if ($name === '' || $email === '' || $password === '') {
    respond(400, [
        'ok' => false,
        'error' => 'Name, email and password are required'
    ]);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(400, [
        'ok' => false,
        'error' => 'Invalid email address'
    ]);
}

if (strlen($password) < 8) {
    respond(400, [
        'ok' => false,
        'error' => 'Password must be at least 8 characters'
    ]);
}

require_once __DIR__ . '/db/mysql.php';

try {

    $mysqli = mysqlConnect();

    // CHECK EMAIL EXISTS
    $checkStmt = $mysqli->prepare(
        'SELECT id FROM users WHERE email = ? LIMIT 1'
    );

    if (!$checkStmt) {
        respond(500, [
            'ok' => false,
            'error' => 'Prepare failed'
        ]);
    }

    $checkStmt->bind_param('s', $email);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {

        $checkStmt->close();
        $mysqli->close();

        respond(409, [
            'ok' => false,
            'error' => 'Email already registered'
        ]);
    }

    $checkStmt->close();

    // USER UNIQUE HASH
    $userHash = hash('sha512', $email);

    // PASSWORD HASH
    $passwordHash = hash('sha512', $password);

    // INSERT USER
    $insertStmt = $mysqli->prepare(
        'INSERT INTO users
        (hash, name, email, password_hash, created_at)
        VALUES (?, ?, ?, ?, NOW())'
    );

    if (!$insertStmt) {
        respond(500, [
            'ok' => false,
            'error' => 'Insert prepare failed'
        ]);
    }

    $insertStmt->bind_param(
        'ssss',
        $userHash,
        $name,
        $email,
        $passwordHash
    );

    if (!$insertStmt->execute()) {

        $insertStmt->close();
        $mysqli->close();

        respond(500, [
            'ok' => false,
            'error' => 'Registration failed'
        ]);
    }

    $insertStmt->close();
    $mysqli->close();

    respond(201, [
        'ok' => true,
        'message' => 'Registration successful',
        'hash' => $userHash
    ]);

} catch (Throwable $e) {

    error_log($e->getMessage());

    respond(500, [
        'ok' => false,
        'error' => 'Internal server error'
    ]);
}