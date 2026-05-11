<?php
declare(strict_types=1);

$envPath = __DIR__ . '/../.env';
if (!file_exists($envPath)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => '.env file not found']);
    exit;
}

$lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#')) {
        continue;
    }
    $parts = explode('=', $line, 2);
    if (count($parts) === 2) {
        $key = trim($parts[0]);
        $value = trim($parts[1]);
        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
    }
}

function env(string $key, string $default = ''): string {
    $val = getenv($key);
    return $val !== false ? $val : $default;
}