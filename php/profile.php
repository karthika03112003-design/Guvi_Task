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

$action = trim((string)($input['action'] ?? ''));
$token = trim((string)($input['token'] ?? ''));

if ($token === '') {
    respond(401, [
        'ok' => false,
        'error' => 'Unauthorized'
    ]);
}

require_once __DIR__ . '/db/mysql.php';
require_once __DIR__ . '/db/mongo.php';
require_once __DIR__ . '/db/redis.php';

try {

    $redis = redisConnect();

    $sessionRaw = $redis->get('session:' . $token);

    if (!$sessionRaw) {
        respond(401, [
            'ok' => false,
            'error' => 'Session expired'
        ]);
    }

    $session = json_decode((string)$sessionRaw, true);

    if (!is_array($session)) {
        respond(401, [
            'ok' => false,
            'error' => 'Invalid session'
        ]);
    }

    $userId = (int)($session['user_id'] ?? 0);
    $userHash = trim((string)($session['hash'] ?? ''));

    if ($userId <= 0 || $userHash === '') {
        respond(401, [
            'ok' => false,
            'error' => 'Invalid session data'
        ]);
    }

    // LOGOUT
    if ($action === 'logout') {

        $redis->del('session:' . $token);

        respond(200, [
            'ok' => true,
            'message' => 'Logout successful'
        ]);
    }

    // MYSQL USER
    $mysqli = mysqlConnect();

    $stmt = $mysqli->prepare(
        'SELECT id, hash, name, email
         FROM users
         WHERE id = ? AND hash = ?
         LIMIT 1'
    );

    $stmt->bind_param('is', $userId, $userHash);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    $stmt->close();

    if (!$user) {

        $mysqli->close();

        respond(404, [
            'ok' => false,
            'error' => 'User not found'
        ]);
    }

    $profiles = mongoConnect('user_profiles');

    // UPDATE PROFILE
    if ($action === 'update') {

        $ageRaw = trim((string)($input['age'] ?? ''));
        $dob = trim((string)($input['dob'] ?? ''));
        $contact = trim((string)($input['contact'] ?? ''));
        $address = trim((string)($input['address'] ?? ''));

        $age = null;

        if ($ageRaw !== '') {

            if (!ctype_digit($ageRaw)) {
                respond(400, [
                    'ok' => false,
                    'error' => 'Invalid age'
                ]);
            }

            $age = (int)$ageRaw;

            if ($age < 1 || $age > 120) {
                respond(400, [
                    'ok' => false,
                    'error' => 'Invalid age'
                ]);
            }
        }

        if (
            $dob !== '' &&
            !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)
        ) {
            respond(400, [
                'ok' => false,
                'error' => 'Invalid DOB'
            ]);
        }

        $profiles->updateOne(
            ['user_id' => $userId],
            [
                '$set' => [
                    'user_id' => $userId,
                    'age' => $age,
                    'dob' => $dob !== '' ? $dob : null,
                    'contact' => $contact !== '' ? $contact : null,
                    'address' => $address !== '' ? $address : null,
                    'updated_at' => new MongoDB\BSON\UTCDateTime()
                ]
            ],
            ['upsert' => true]
        );

        $mysqli->close();

        respond(200, [
            'ok' => true,
            'message' => 'Profile updated successfully'
        ]);
    }

    // GET PROFILE
    $profile = $profiles->findOne([
        'user_id' => $userId
    ]);

    $mysqli->close();

    respond(200, [
        'ok' => true,
        'name' => (string)$user['name'],
        'email' => (string)$user['email'],
        'hash' => (string)$user['hash'],
        'profile' => [
            'age' => $profile['age'] ?? null,
            'dob' => $profile['dob'] ?? null,
            'contact' => $profile['contact'] ?? null,
            'address' => $profile['address'] ?? null
        ]
    ]);

} catch (Throwable $e) {

    error_log($e->getMessage());

    respond(500, [
        'ok' => false,
        'error' => 'Internal server error: ' . $e->getMessage()
    ]);
}