<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

function redisConnect(): Redis
{
    $redisUrl = trim((string) env('REDIS_URL'));

    if ($redisUrl === '') {
        throw new RuntimeException('REDIS_URL missing');
    }

    $parts = parse_url($redisUrl);

    if ($parts === false) {
        throw new RuntimeException('Invalid REDIS_URL');
    }

    $scheme = $parts['scheme'] ?? 'rediss';
    $host = $parts['host'] ?? '';
    $port = (int) ($parts['port'] ?? 6379);
    $password = $parts['pass'] ?? '';

    $redis = new Redis();

    $connected = $redis->connect(
        $host,
        $port,
        5.0,
        null,
        0,
        0,
        $scheme === 'rediss'
            ? [
                'stream' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false
                ]
            ]
            : []
    );

    if (!$connected) {
        throw new RuntimeException('Redis connection failed');
    }

    if ($password !== '') {

        if (!$redis->auth($password)) {
            throw new RuntimeException('Redis auth failed');
        }
    }

    return $redis;
}